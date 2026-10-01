<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\WhatsappSetting;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
