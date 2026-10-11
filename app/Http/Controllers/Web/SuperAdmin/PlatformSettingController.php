<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public function metaApp(): View
    {
        return view('superadmin.platform-settings.meta', [
            'app_id'             => PlatformSetting::get('meta_app_id'),
            'app_secret'         => PlatformSetting::get('meta_app_secret'),
            'ig_app_id'          => PlatformSetting::get('meta_ig_app_id'),
            'ig_app_secret'      => PlatformSetting::get('meta_ig_app_secret'),
            'wa_config_id'       => PlatformSetting::get('meta_wa_embedded_config_id'),
        ]);
    }

    public function saveMetaApp(Request $request): RedirectResponse
    {
        $existing   = PlatformSetting::get('meta_app_secret');
        $existingIg = PlatformSetting::get('meta_ig_app_secret');

        $request->validate([
            'app_id'        => ['required', 'string', 'max:255'],
            'app_secret'    => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
            // Instagram API "with Instagram Login" uses its own App ID / Secret,
            // shown under Meta App → Instagram → API setup with Instagram login.
            // Optional — falls back to the Facebook app credentials when blank.
            'ig_app_id'     => ['nullable', 'string', 'max:255'],
            'ig_app_secret' => ['nullable', 'string', 'max:255'],
            // WhatsApp Embedded Signup configuration, from
            // App → Facebook Login for Business → Configurations.
            'wa_config_id'  => ['nullable', 'string', 'max:255'],
        ]);

        PlatformSetting::set('meta_app_id', $request->app_id);
        if ($request->filled('app_secret')) {
            PlatformSetting::set('meta_app_secret', $request->app_secret);
        }
        PlatformSetting::set('meta_ig_app_id', $request->ig_app_id ?? '');
        if ($request->filled('ig_app_secret')) {
            PlatformSetting::set('meta_ig_app_secret', $request->ig_app_secret);
        }
        PlatformSetting::set('meta_wa_embedded_config_id', $request->wa_config_id ?? '');

        return back()->with('success', 'Meta App credentials saved.');
    }

    // ── WhatsApp Gateway (Milan CRM) ──────────────────────────────
    // A borrowed, already-approved Meta app: while it is ON, tenants connect
    // a number and send / receive through the gateway; while it is OFF the app
    // talks to Meta directly exactly as before. Flip it off once our own Meta
    // app is live.
    public function whatsappGateway(): View
    {
        return view('superadmin.platform-settings.whatsapp-gateway', [
            'switchedOn'  => PlatformSetting::get('wa_gateway_enabled') === '1',
            'enabled'     => WhatsappGatewayClient::enabled(),
            'configured'  => WhatsappGatewayClient::configured(),
            'base_url'    => PlatformSetting::get('wa_gateway_base_url'),
            'has_api_key' => filled(PlatformSetting::get('wa_gateway_api_key')),
            'has_secret'  => filled(PlatformSetting::get('wa_gateway_webhook_secret')),
            'prefix'      => PlatformSetting::get('wa_gateway_workspace_prefix') ?: 'tenant-',
            'webhook_url' => url('/webhook/wa-gateway'),
            'gatewayTenants' => WhatsappSetting::where('connection_mode', 'gateway')->where('is_connected', true)->count(),
            // What the gateway has actually delivered to us — null when the
            // table is missing (migration not run), which makes every event fail.
            'recentEvents' => Schema::hasTable('whatsapp_webhook_events')
                ? DB::table('whatsapp_webhook_events')->latest('id')->limit(10)->get()
                : null,
        ]);
    }

    public function saveWhatsappGateway(Request $request): RedirectResponse
    {
        $hasKey    = filled(PlatformSetting::get('wa_gateway_api_key'));
        $hasSecret = filled(PlatformSetting::get('wa_gateway_webhook_secret'));

        $request->validate([
            'base_url'         => ['required', 'url', 'max:255'],
            'api_key'          => [$hasKey ? 'nullable' : 'required', 'string', 'max:255'],
            'webhook_secret'   => [$hasSecret ? 'nullable' : 'required', 'string', 'max:255'],
            'workspace_prefix' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        PlatformSetting::set('wa_gateway_base_url', WhatsappGatewayClient::normalizeBaseUrl($request->base_url));
        if ($request->filled('api_key')) {
            PlatformSetting::set('wa_gateway_api_key', trim($request->api_key));
        }
        if ($request->filled('webhook_secret')) {
            PlatformSetting::set('wa_gateway_webhook_secret', trim($request->webhook_secret));
        }
        PlatformSetting::set('wa_gateway_workspace_prefix', $request->workspace_prefix ?? '');

        return back()->with('success', 'WhatsApp Gateway settings saved.');
    }

    public function toggleWhatsappGateway(): RedirectResponse
    {
        $on = PlatformSetting::get('wa_gateway_enabled') === '1';

        if (!$on && !WhatsappGatewayClient::configured()) {
            return back()->with('error', 'Save the gateway URL, API key and webhook secret before turning it on.');
        }

        PlatformSetting::set('wa_gateway_enabled', $on ? '0' : '1');

        return back()->with('success', $on
            ? 'WhatsApp Gateway turned OFF — WhatsApp talks to Meta directly again.'
            : 'WhatsApp Gateway turned ON — tenants now connect and message through the gateway.');
    }

    public function testWhatsappGateway(): JsonResponse
    {
        $result = WhatsappGatewayClient::ping();

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['ok'] ? 'Connected — the gateway accepted the API key.' : $result['error'],
        ]);
    }

    // "Why doesn't the chatbot answer?" in two questions put to the gateway:
    // 1) does it hold the customers' messages (Meta → gateway works), and
    // 2) what happened when it tried to deliver events to this CRM.
    // The two answers tell the Meta/gateway side apart from our side.
    // The payload shapes of these two read endpoints aren't fully documented,
    // so the output is defensive and falls back to raw JSON.
    public function diagnoseWhatsappGateway(): JsonResponse
    {
        $lines         = [];
        $bad           = false;
        $gatewayLatest = null;

        $workspaces = WhatsappSetting::where('connection_mode', 'gateway')
            ->whereNotNull('gateway_workspace_id')
            ->limit(5)
            ->pluck('gateway_workspace_id');

        if ($workspaces->isEmpty()) {
            $bad     = true;
            $lines[] = '✕ No tenant is connected through the gateway yet.';
        }

        foreach ($workspaces as $workspace) {
            $res = WhatsappGatewayClient::inboundMessages($workspace);

            if (!$res['ok']) {
                $bad     = true;
                $lines[] = "✕ {$workspace}: could not read its messages from the gateway — {$res['error']}";
                continue;
            }

            $items = $this->listFrom($res['data'], ['data', 'messages', 'items']);

            if ($items === []) {
                $bad     = true;
                $lines[] = "✕ {$workspace}: the gateway holds NO message from any customer. WhatsApp is not delivering customer messages to the gateway itself — the gateway operator must check the Meta app's webhook (callback URL + the \"messages\" field) and that this number was connected through the gateway.";
                continue;
            }

            $latest  = $items[0]['created_at'] ?? $items[0]['sent_at'] ?? null;
            $lines[] = '✓ ' . $workspace . ': the gateway has received ' . count($items) . ' recent customer message(s)' . ($latest ? ", latest {$latest}" : '') . '. So Meta → gateway works; if the chatbot is silent, the problem is gateway → this CRM (next line) or the chatbot settings.';

            try {
                $at = $latest ? Carbon::parse($latest) : null;
            } catch (\Throwable) {
                $at = null;
            }
            if ($at && (!$gatewayLatest || $at->gt($gatewayLatest))) {
                $gatewayLatest = $at;
            }
        }

        $res = WhatsappGatewayClient::webhookDeliveries('failed');

        if (!$res['ok']) {
            $lines[] = "! Could not read the gateway's delivery log — {$res['error']}";
        } else {
            $failed = $this->listFrom($res['data'], ['data', 'deliveries', 'items']);

            if ($failed === []) {
                $lines[] = '✓ The gateway reports no failed webhook deliveries to this CRM.';
            }

            foreach (array_slice($failed, 0, 5) as $d) {
                $bad  = true;
                $code = $d['last_response_code'] ?? $d['response_code'] ?? $d['http_status'] ?? $d['status_code'] ?? null;
                $err  = $d['last_error'] ?? $d['error'] ?? null;

                $meaning = match (true) {
                    (int) $code === 401                       => 'our server rejected the signature — the webhook secret saved here differs from the gateway client\'s secret',
                    (int) $code === 503                       => 'the WhatsApp gateway switch is OFF here',
                    (int) $code >= 500                        => 'our server errored while handling it — see Superadmin → Error Logs',
                    $code === null || (int) $code === 0       => 'no answer from our server within 8 seconds (down, asleep or too slow) or the registered webhook URL is wrong',
                    default                                   => null,
                };

                $parts   = array_filter([
                    $d['event'] ?? null,
                    $code !== null ? "HTTP {$code}" : null,
                    is_scalar($err) ? $err : null,
                    $d['created_at'] ?? null,
                ]);
                $lines[] = '✕ failed delivery: ' . ($parts ? implode(' · ', $parts) : mb_substr(json_encode($d), 0, 200)) . ($meaning ? " → {$meaning}" : '');
            }
        }

        // "No failed deliveries" is ambiguous: it is also what you see when the
        // gateway never tried to send anything. The latest deliveries of ANY
        // status settle it.
        $res = WhatsappGatewayClient::webhookDeliveries(null, 5);

        if ($res['ok']) {
            $recent = $this->listFrom($res['data'], ['data', 'deliveries', 'items']);

            if ($recent === []) {
                $bad     = true;
                $lines[] = '✕ The gateway has never created a webhook delivery for this client — it is not attempting to send events anywhere. The gateway operator should check the client\'s webhook URL and that the client is active.';
            }

            foreach (array_slice($recent, 0, 3) as $d) {
                $code   = $d['last_response_code'] ?? $d['response_code'] ?? $d['http_status'] ?? $d['status_code'] ?? null;
                $status = $d['status'] ?? null;
                $parts  = array_filter([
                    $d['event'] ?? null,
                    $status ? "status {$status}" : null,
                    $code !== null ? "HTTP {$code}" : null,
                    $d['created_at'] ?? null,
                ]);
                $stuck = in_array(strtolower((string) $status), ['pending', 'queued', 'retrying'], true);

                $lines[] = ($stuck ? '! ' : '· ') . 'gateway delivery: ' . implode(' · ', $parts)
                    . ($stuck ? ' → queued but not delivered: the gateway\'s scheduler (gateway:retry-webhooks, every minute) is probably not running' : '');
            }
        }

        // ── This CRM's side: what actually arrived here, and what the chatbot does with it ──
        $registered = WhatsappGatewayClient::registeredWebhookUrl();
        $ours       = url('/webhook/wa-gateway');

        if ($registered === '') {
            $bad     = true;
            $lines[] = '✕ The gateway has NO webhook URL registered, so it sends this CRM nothing (and shows no failed deliveries). Use "Register webhook URL with gateway" on this production page.';
        } elseif ($registered !== null && rtrim($registered, '/') !== rtrim($ours, '/')) {
            $bad     = true;
            $lines[] = "✕ The gateway sends events to {$registered}, not to this CRM ({$ours}). One API key has one webhook URL — use \"Register webhook URL with gateway\" on this production page (and don't share the key with another app).";
        } elseif ($registered !== null) {
            $lines[] = '✓ The gateway is registered to send events to this CRM.';
        }

        $hasEventsTable = Schema::hasTable('whatsapp_webhook_events');

        if (!$hasEventsTable) {
            $bad     = true;
            $lines[] = '✕ The whatsapp_webhook_events table is missing in this CRM\'s database — run php artisan migrate.';
        } else {
            $seen = DB::table('whatsapp_webhook_events')->latest('id')->limit(3)->get();
            $lines[] = $seen->isEmpty()
                ? '· This CRM has recorded no gateway events at all yet.'
                : '· Events this CRM recorded: ' . $seen->map(fn ($e) => ($e->event ?? '?') . ' ' . Carbon::parse($e->created_at)->diffForHumans())->implode(', ') . '.';
        }

        $lastEvent = $hasEventsTable
            ? DB::table('whatsapp_webhook_events')->where('event', 'message.received')->latest('id')->first()
            : null;

        if (!$lastEvent) {
            $bad     = true;
            $lines[] = '✕ This CRM has never received a message.received event from the gateway, although the gateway holds customer messages — so the gateway is not delivering them here (see the webhook URL line above).';
        } else {
            $arrived = Carbon::parse($lastEvent->created_at);

            if ($gatewayLatest && $gatewayLatest->gt($arrived->copy()->addMinutes(2))) {
                $bad     = true;
                $lines[] = "✕ The gateway's newest customer message ({$gatewayLatest->timezone(config('app.timezone'))->format('d M H:i')}) never arrived here — the last message.received this CRM got was {$arrived->diffForHumans()}.";
            } else {
                $lines[] = "✓ This CRM received a message.received event {$arrived->diffForHumans()}.";
            }
        }

        foreach ($workspaces as $workspace) {
            $setting = WhatsappSetting::where('gateway_workspace_id', $workspace)->first();
            if (!$setting) {
                continue;
            }

            $active   = WhatsappChatbotFlow::withoutGlobalScopes()->where('tenant_id', $setting->tenant_id)->where('is_active', true);
            $flows    = (clone $active)->count();
            $default  = (clone $active)->where('is_default', true)->exists();
            $received = WhatsappLog::withoutGlobalScopes()->where('tenant_id', $setting->tenant_id)->where('status', 'received')->latest('id')->first();

            if (!$setting->chatbot_enabled) {
                $bad     = true;
                $lines[] = "✕ {$workspace}: the chatbot is switched OFF — the CRM receives the message but deliberately does not reply. Tenant → WhatsApp → API Settings → tick \"Enable Chatbot\" → Save.";
            } elseif ($flows === 0) {
                $bad     = true;
                $lines[] = "✕ {$workspace}: the chatbot is on but has no ACTIVE flow to answer with.";
            } else {
                $lines[] = "✓ {$workspace}: chatbot is on with {$flows} active flow(s)" . ($default ? ', including a default flow.' : ' but NO default flow — it only replies when the message contains a flow keyword.');
            }

            $lines[] = $received
                ? "✓ {$workspace}: last customer message saved in the CRM " . $received->created_at->diffForHumans() . '.'
                : "! {$workspace}: no customer message has been saved in the CRM's WhatsApp log yet.";
        }

        return response()->json(['success' => !$bad, 'message' => implode("\n", $lines)]);
    }

    // First list found in a gateway "data" payload: the payload itself when it
    // is a list, or the first of $keys that holds one.
    private function listFrom(array $data, array $keys): array
    {
        if (array_is_list($data)) {
            return $data;
        }

        foreach ($keys as $key) {
            if (is_array($data[$key] ?? null) && array_is_list($data[$key])) {
                return $data[$key];
            }
        }

        return [];
    }

    // Points the gateway at our /webhook/wa-gateway URL (PUT /client).
    public function registerWhatsappGatewayWebhook(): JsonResponse
    {
        $result = WhatsappGatewayClient::registerWebhookUrl(url('/webhook/wa-gateway'));

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['ok'] ? 'Webhook URL registered with the gateway.' : $result['error'],
        ]);
    }
}
