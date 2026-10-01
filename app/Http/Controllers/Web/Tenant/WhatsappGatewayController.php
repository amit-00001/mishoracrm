<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSetting;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\RedirectResponse;

// The tenant's side of connecting a WhatsApp number through the WhatsApp
// Gateway (only reachable while the superadmin has the gateway switched on —
// see WhatsappChatbotController@settings for the screen these buttons live on).
class WhatsappGatewayController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // "Connect WhatsApp" — sends the tenant admin to the gateway's hosted page
    // where they sign in with Facebook and pick / create their number. The
    // gateway sends them back to the settings page afterwards.
    public function connect(): RedirectResponse
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!WhatsappGatewayClient::appliesTo($settings)) {
            return back()->with('error', 'WhatsApp is already connected for this account.');
        }

        $result = WhatsappGatewayClient::createConnectSession(
            auth()->user()->tenant,
            route('tenant.whatsapp.api-settings')
        );

        if (!$result['ok'] || empty($result['data']['url'])) {
            return back()->with('error', $result['error'] ?? 'Could not start the WhatsApp connection. Please try again.');
        }

        return redirect()->away($result['data']['url']);
    }

    // "Refresh status" — pulls the number's real state from the gateway, for
    // when the account.connected webhook hasn't arrived (yet).
    public function sync(): RedirectResponse
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!$settings->gateway_workspace_id) {
            return back()->with('error', 'WhatsApp has not been connected through the gateway yet.');
        }

        $result = WhatsappGatewayClient::refresh($settings);

        if (!$result['ok']) {
            return back()->with('error', $result['error']);
        }

        return $settings->is_connected
            ? back()->with('success', 'WhatsApp is connected.')
            : back()->with('error', 'WhatsApp is not connected yet — finish the connection on the Meta screen, then refresh again.');
    }

    public function disconnect(): RedirectResponse
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!$settings->exists || !$settings->viaGateway()) {
            return back()->with('error', 'This account is not connected through the gateway.');
        }

        $result = WhatsappGatewayClient::disconnect($settings);

        if (!$result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'WhatsApp disconnected.');
    }
}
