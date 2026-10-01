<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Client for the Milan CRM "WhatsApp Gateway" — a REST API that owns an
// already-approved Meta Tech Provider app and relays WhatsApp for us, so
// tenants can connect a number and send / receive before our own Meta app
// finishes review. Strictly opt-in: nothing here runs unless the superadmin
// turns it on (Superadmin → WhatsApp Gateway); while it is off the app talks
// to Meta directly, exactly as before.
//
// One Mishora tenant = one gateway "workspace" = one WhatsApp number.
// Every call returns the same envelope instead of throwing:
//   ['ok' => bool, 'status' => int, 'data' => array, 'error' => ?string,
//    'code' => ?string, 'meta_code' => ?int]
class WhatsappGatewayClient
{
    private const API_PATH = '/api/v1/gateway';

    // ── Platform configuration ────────────────────────────────────

    // On only when the superadmin switched it on AND the URL + API key exist.
    public static function enabled(): bool
    {
        return PlatformSetting::get('wa_gateway_enabled') === '1' && static::hasCredentials();
    }

    private static function hasCredentials(): bool
    {
        return filled(PlatformSetting::get('wa_gateway_base_url'))
            && filled(PlatformSetting::get('wa_gateway_api_key'));
    }

    // Everything needed to run it end to end — sending needs the key, but
    // receiving needs the webhook secret, so the switch refuses to go on
    // until all three are saved.
    public static function configured(): bool
    {
        return filled(PlatformSetting::get('wa_gateway_base_url'))
            && filled(PlatformSetting::get('wa_gateway_api_key'))
            && filled(PlatformSetting::get('wa_gateway_webhook_secret'));
    }

    // Accepts "https://crm.example.com" or the full ".../api/v1/gateway".
    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        return str_ends_with($url, self::API_PATH) ? $url : $url . self::API_PATH;
    }

    public static function workspaceId(int $tenantId): string
    {
        $prefix = PlatformSetting::get('wa_gateway_workspace_prefix');

        return (filled($prefix) ? $prefix : 'tenant-') . $tenantId;
    }

    // Should this tenant see / use the gateway connect flow? Yes while the
    // gateway is on — except a tenant already connected straight to Meta,
    // who keeps that working connection untouched.
    public static function appliesTo(WhatsappSetting $setting): bool
    {
        if (!static::enabled()) {
            return false;
        }

        return !($setting->exists && $setting->is_connected && !$setting->viaGateway());
    }

    // ── Workspaces / connecting a number ──────────────────────────

    // Idempotent on the gateway side; remembers the workspace id locally.
    // Returns the usual envelope plus 'workspace_id'.
    public static function ensureWorkspace(Tenant $tenant): array
    {
        $setting     = WhatsappSetting::firstOrNew(['tenant_id' => $tenant->id]);
        $workspaceId = $setting->gateway_workspace_id ?: static::workspaceId($tenant->id);

        $res = static::call('post', '/workspaces', ['id' => $workspaceId, 'name' => $tenant->name]);
        if (!$res['ok']) {
            return $res;
        }

        if ($setting->gateway_workspace_id !== $workspaceId) {
            $setting->tenant_id            = $tenant->id;
            $setting->gateway_workspace_id = $workspaceId;
            $setting->save();
        }

        return $res + ['workspace_id' => $workspaceId];
    }

    // Starts the hosted "Connect WhatsApp" flow. On success data.url is the
    // page to send the tenant admin to (valid ~30 minutes); the gateway sends
    // them back to $returnUrl?status=connected|failed afterwards.
    public static function createConnectSession(Tenant $tenant, string $returnUrl): array
    {
        $workspace = static::ensureWorkspace($tenant);
        if (!$workspace['ok']) {
            return $workspace;
        }

        return static::call('post', "/workspaces/{$workspace['workspace_id']}/connect-sessions", [
            'return_url' => $returnUrl,
        ]);
    }

    // Pulls the workspace's real state from the gateway and mirrors it onto
    // our row. The account.connected webhook is the source of truth; this is
    // the same update done on demand (page return / "Refresh status" button),
    // so the UI doesn't depend on the webhook having already arrived.
    public static function refresh(WhatsappSetting $setting): array
    {
        if (!$setting->gateway_workspace_id) {
            return static::failure('This tenant has no gateway workspace yet.');
        }

        $res = static::call('get', "/workspaces/{$setting->gateway_workspace_id}");
        if (!$res['ok']) {
            return $res;
        }

        $whatsapp = $res['data']['whatsapp'] ?? [];

        if (!empty($whatsapp['connected'])) {
            static::applyConnection($setting, $whatsapp);

            // The workspace summary may not carry the number details — the
            // account endpoint does, so top up from it.
            if (blank($setting->display_phone_number)) {
                $account = static::account($setting->gateway_workspace_id);
                if ($account['ok']) {
                    static::applyConnection($setting, $account['data']);
                }
            }
        } elseif ($setting->viaGateway()) {
            static::applyDisconnection($setting);
        }

        return $res;
    }

    // Live number health: quality_rating, messaging_limit_tier, problem (and
    // the number's details). The result's data is flattened so callers can
    // read those keys whether the gateway nests them under "account" /
    // "whatsapp" or not.
    public static function account(string $workspaceId): array
    {
        $res = static::call('get', "/workspaces/{$workspaceId}/account");

        if ($res['ok']) {
            foreach (['account', 'whatsapp'] as $wrapper) {
                if (is_array($res['data'][$wrapper] ?? null)) {
                    $res['data'] = $res['data'][$wrapper] + $res['data'];
                }
            }
        }

        return $res;
    }

    public static function disconnect(WhatsappSetting $setting): array
    {
        if (!$setting->gateway_workspace_id) {
            return static::failure('This tenant has no gateway workspace yet.');
        }

        $res = static::call('post', "/workspaces/{$setting->gateway_workspace_id}/disconnect");

        if ($res['ok']) {
            static::applyDisconnection($setting);
        }

        return $res;
    }

    // Marks a row as connected through the gateway, copying whichever number
    // details the gateway supplied.
    public static function applyConnection(WhatsappSetting $setting, array $info): void
    {
        $setting->connection_mode = 'gateway';
        $setting->is_connected    = true;

        foreach (['phone_number_id', 'waba_id', 'display_phone_number', 'verified_name'] as $field) {
            if (filled($info[$field] ?? null)) {
                $setting->{$field} = $info[$field];
            }
        }

        $setting->save();
    }

    public static function applyDisconnection(WhatsappSetting $setting): void
    {
        $setting->connection_mode      = 'direct';
        $setting->is_connected         = false;
        $setting->phone_number_id      = null;
        $setting->waba_id              = null;
        $setting->display_phone_number = null;
        $setting->verified_name        = null;
        $setting->save();
    }

    // ── Sending ───────────────────────────────────────────────────

    // $payload is the full gateway message body: 'to', 'type' (text, buttons,
    // image, document, …) and that type's own field. On success data.wamid is
    // the WhatsApp message id; a Meta rejection (e.g. the 24h rule, meta_code
    // 131047) comes back as ok=false with the reason in 'error'.
    public static function sendMessage(string $workspaceId, array $payload): array
    {
        return static::call('post', "/workspaces/{$workspaceId}/messages", $payload);
    }

    // Uploads a file for later use as media: {id}. data.id is the media id.
    public static function uploadMedia(string $workspaceId, string $filePath, string $mimeType): array
    {
        return static::call(
            'post',
            "/workspaces/{$workspaceId}/media",
            [],
            fn (PendingRequest $http) => $http->attach(
                'file',
                file_get_contents($filePath),
                basename($filePath),
                ['Content-Type' => $mimeType]
            ),
            60
        );
    }

    // ── Meta message templates ────────────────────────────────────
    // Templates live on the tenant's own WhatsApp Business Account and are
    // reviewed by Meta. A template is the only thing that can start a
    // conversation, or reply after the 24-hour window has closed.

    // The workspace's templates, normalised. data is a list of
    // ['name', 'language', 'status' (lower-case), 'category', 'body',
    //  'params' (how many {{n}} the body has), 'sendable'].
    public static function templates(string $workspaceId, array $query = [], int $timeout = 20): array
    {
        $res = static::call('get', "/workspaces/{$workspaceId}/templates", $query, null, $timeout);

        if ($res['ok']) {
            $list = $res['data'];
            if (!array_is_list($list)) {
                $list = $list['data'] ?? $list['templates'] ?? [];
            }

            $res['data'] = array_values(array_map(
                fn ($t) => static::normalizeTemplate($t),
                array_filter($list, 'is_array')
            ));
        }

        return $res;
    }

    // Approved templates the send forms can use — cached briefly (the forms
    // would otherwise call the gateway on every page load) and refreshed the
    // moment a template.status webhook arrives or one is created / deleted.
    public static function approvedTemplates(string $workspaceId): array
    {
        $cached = Cache::get(static::templateCacheKey($workspaceId));
        if (is_array($cached)) {
            return $cached;
        }

        $res = static::templates($workspaceId, ['status' => 'approved', 'limit' => 100], 10);
        if (!$res['ok']) {
            return [];
        }

        $list = array_values(array_filter($res['data'], fn ($t) => $t['status'] === 'approved' && $t['sendable']));
        Cache::put(static::templateCacheKey($workspaceId), $list, 120);

        return $list;
    }

    // $examples = one sample value per {{n}} in $body (Meta requires them).
    public static function createTemplate(string $workspaceId, string $name, string $language, string $category, string $body, array $examples): array
    {
        $component = ['type' => 'BODY', 'text' => $body];
        if ($examples) {
            $component['example'] = ['body_text' => [array_values($examples)]];
        }

        $res = static::call('post', "/workspaces/{$workspaceId}/templates", [
            'name'       => $name,
            'language'   => $language,
            'category'   => $category,
            'components' => [$component],
        ]);

        static::forgetTemplateCache($workspaceId);

        return $res;
    }

    public static function deleteTemplate(string $workspaceId, string $name): array
    {
        $res = static::call('delete', "/workspaces/{$workspaceId}/templates/{$name}");

        static::forgetTemplateCache($workspaceId);

        return $res;
    }

    public static function forgetTemplateCache(string $workspaceId): void
    {
        Cache::forget(static::templateCacheKey($workspaceId));
    }

    // "Hi {{1}}, order {{2}} shipped" + ['Asha', 'ORD-5'] → the text the
    // customer sees. Used for the WhatsApp Logs row of a template send.
    public static function renderTemplateBody(string $body, array $params): string
    {
        return preg_replace_callback('/\{\{(\d+)\}\}/', fn ($m) => $params[(int) $m[1] - 1] ?? '', $body);
    }

    private static function templateCacheKey(string $workspaceId): string
    {
        return "wa_gateway_templates:{$workspaceId}";
    }

    // Meta shape: {name, language, status, category, components:[{type:'BODY',
    // text}, …]}. Only text-only bodies can be filled from the send forms, so
    // a template with a media header or a header variable is marked not sendable.
    private static function normalizeTemplate(array $t): array
    {
        $body = $t['body'] ?? '';
        $sendable = true;

        foreach ($t['components'] ?? [] as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));

            if ($type === 'BODY') {
                $body = (string) ($component['text'] ?? $body);
            } elseif ($type === 'HEADER') {
                $isPlainText = strtoupper((string) ($component['format'] ?? 'TEXT')) === 'TEXT'
                    && !str_contains((string) ($component['text'] ?? ''), '{{');
                $sendable = $sendable && $isPlainText;
            }
        }

        preg_match_all('/\{\{(\d+)\}\}/', $body, $matches);

        return [
            'name'     => (string) ($t['name'] ?? ''),
            'language' => (string) ($t['language'] ?? ''),
            'status'   => strtolower((string) ($t['status'] ?? '')),
            'category' => strtoupper((string) ($t['category'] ?? '')),
            'body'     => $body,
            'params'   => $matches[1] ? (int) max($matches[1]) : 0,
            'sendable' => $sendable,
        ];
    }

    // ── Platform-level (superadmin) ───────────────────────────────

    // Both below work while the switch is still off — the superadmin tests
    // the credentials and registers the webhook URL before turning it on.

    // Cheap authenticated call — proves the base URL and API key work.
    public static function ping(): array
    {
        return static::call('get', '/workspaces', ['limit' => 1], null, 10, true);
    }

    // Tells the gateway where to POST our events (PUT /client).
    public static function registerWebhookUrl(string $url): array
    {
        return static::call('put', '/client', ['webhook_url' => $url], null, 10, true);
    }

    // ── Receiving ─────────────────────────────────────────────────

    // sha256= HMAC of "{timestamp}.{raw body}" with the client's webhook
    // secret, rejecting anything older (or newer) than 5 minutes — replay guard.
    public static function verifySignature(string $rawBody, ?string $timestamp, ?string $signature): bool
    {
        $secret = (string) PlatformSetting::get('wa_gateway_webhook_secret');

        if ($secret === '' || !$timestamp || !$signature || !ctype_digit($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    // ── Internals ─────────────────────────────────────────────────

    private static function call(string $method, string $path, array $data = [], ?\Closure $prepare = null, int $timeout = 20, bool $ignoreSwitch = false): array
    {
        if (!static::hasCredentials()) {
            return static::failure('Save the WhatsApp gateway URL and API key first.');
        }

        if (!$ignoreSwitch && !static::enabled()) {
            return static::failure('The WhatsApp gateway is turned off.');
        }

        try {
            $http = Http::withToken((string) PlatformSetting::get('wa_gateway_api_key'))
                ->acceptJson()
                ->timeout($timeout);

            if ($prepare) {
                $http = $prepare($http);
            }

            $response = $http->{$method}(rtrim((string) PlatformSetting::get('wa_gateway_base_url'), '/') . $path, $data);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp gateway unreachable: ' . $e->getMessage());

            return static::failure('Could not reach the WhatsApp gateway. Please try again.');
        }

        return static::envelope($response);
    }

    // Gateway shape: {"success":true,"data":{…}} or
    // {"success":false,"message":"…","error":{"code","message","meta_code"}}.
    private static function envelope(Response $response): array
    {
        $json = $response->json();
        $json = is_array($json) ? $json : [];

        if ($response->successful() && ($json['success'] ?? true) !== false) {
            return [
                'ok'        => true,
                'status'    => $response->status(),
                'data'      => is_array($json['data'] ?? null) ? $json['data'] : [],
                'error'     => null,
                'code'      => null,
                'meta_code' => null,
            ];
        }

        $message = $json['error']['message'] ?? $json['message'] ?? null;

        return [
            'ok'        => false,
            'status'    => $response->status(),
            'data'      => is_array($json['data'] ?? null) ? $json['data'] : [],
            'error'     => mb_substr($message ?: 'WhatsApp gateway error (HTTP ' . $response->status() . ')', 0, 250),
            'code'      => $json['error']['code'] ?? null,
            'meta_code' => $json['error']['meta_code'] ?? null,
        ];
    }

    private static function failure(string $message): array
    {
        return ['ok' => false, 'status' => 0, 'data' => [], 'error' => $message, 'code' => null, 'meta_code' => null];
    }
}
