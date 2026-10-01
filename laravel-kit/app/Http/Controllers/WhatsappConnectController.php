<?php

namespace App\Http\Controllers;

use App\Models\TenantWhatsappAccount;
use App\Services\MilanWhatsapp;
use App\Services\MilanWhatsappException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

// The tenant's "WhatsApp" settings page: status, Connect, Disconnect.
class WhatsappConnectController extends Controller
{
    public function __construct(private MilanWhatsapp $whatsapp)
    {
    }

    // ★ Change this to however YOUR app finds the current tenant (stancl/tenancy: tenant('id'), etc.).
    private function tenantId(Request $request): string
    {
        return (string) $request->user()->tenant_id;
    }

    private function tenantName(Request $request): string
    {
        return (string) ($request->user()->tenant->name ?? 'Tenant ' . $this->tenantId($request));
    }

    public function show(Request $request): View
    {
        return view('whatsapp.settings', ['account' => TenantWhatsappAccount::forTenant($this->tenantId($request))]);
    }

    // "Connect WhatsApp" button → create the workspace (if new) → send the user to Milan CRM's connect page.
    public function connect(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId($request);

        try {
            $this->whatsapp->ensureWorkspace($tenantId, $this->tenantName($request));
            $url = $this->whatsapp->connectUrl($tenantId, route('whatsapp.connected'));
        } catch (MilanWhatsappException $e) {
            report($e);
            return back()->with('error', 'Could not start the WhatsApp connection: ' . $e->getMessage());
        }

        return redirect()->away($url);
    }

    // Where the user lands after the Facebook / Meta screens (the return_url above).
    // The "account.connected" webhook is the source of truth; this just refreshes the page state right away.
    public function connected(Request $request): RedirectResponse
    {
        if ($request->query('status') === 'failed') {
            return redirect()->route('whatsapp.settings')->with('error', 'WhatsApp was not connected: ' . $request->query('message', 'cancelled.'));
        }

        $tenantId = $this->tenantId($request);

        try {
            $info = $this->whatsapp->workspace($tenantId)['whatsapp'] ?? [];
            TenantWhatsappAccount::forTenant($tenantId)->update([
                'connected'            => (bool) ($info['connected'] ?? false),
                'display_phone_number' => $info['display_phone_number'] ?? null,
                'verified_name'        => $info['verified_name'] ?? null,
                'connected_at'         => !empty($info['connected']) ? now() : null,
            ]);
        } catch (MilanWhatsappException $e) {
            report($e);   // the webhook will still bring the state in
        }

        return redirect()->route('whatsapp.settings')->with('success', 'WhatsApp connected.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId($request);

        try {
            $this->whatsapp->disconnect($tenantId);
        } catch (MilanWhatsappException $e) {
            return back()->with('error', $e->getMessage());
        }

        TenantWhatsappAccount::forTenant($tenantId)->update(['connected' => false, 'display_phone_number' => null, 'verified_name' => null]);

        return back()->with('success', 'WhatsApp disconnected.');
    }
}
