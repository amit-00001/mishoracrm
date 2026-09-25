<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\Sql;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tenant::with(['subscription.plan'])
            ->withCount(['users as user_count' => fn($q) => $q->withoutGlobalScopes()]);

        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('subdomain', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        // Fetch tenant admin last_login_at for each tenant
        $tenantIds  = $tenants->pluck('id')->toArray();
        $lastLogins = User::withoutGlobalScopes()
            ->whereIn('tenant_id', $tenantIds)
            ->where('user_type', 'tenant_admin')
            ->select('tenant_id', 'last_login_at')
            ->get()
            ->keyBy('tenant_id');

        $stats = [
            'total'    => Tenant::count(),
            'active'   => Tenant::where('status', 'active')->count(),
            'inactive' => Tenant::where('status', 'inactive')->count(),
            'suspended'=> Tenant::where('status', 'suspended')->count(),
        ];

        return view('superadmin.tenants.index', compact('tenants', 'lastLogins', 'stats'));
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['subscription.plan', 'subscription.coupon']);

        // Full subscription / payment history
        $paymentHistory = Subscription::with(['plan', 'coupon'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        // Users of this tenant
        $users = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->orderByRaw(Sql::fieldOrder('user_type', ['tenant_admin', 'staff']))
            ->orderByDesc('last_login_at')
            ->get();

        $userCount = $users->count();
        // Effective seat limit — the superadmin override if one is set,
        // otherwise the plan's features['users'] (see Tenant::userSeatLimit()).
        $seatLimit = $tenant->userSeatLimit();

        $allPlans = Plan::orderBy('sort_order')->get();

        return view('superadmin.tenants.show', compact(
            'tenant',
            'paymentHistory',
            'users',
            'userCount',
            'seatLimit',
            'allPlans'
        ));
    }

    public function toggleStatus(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:active,inactive,suspended']]);

        $old = $tenant->status;
        $tenant->update(['status' => $request->status]);

        return back()->with('success', "Tenant status changed from {$old} to {$request->status}.");
    }

    // ── Manual subscription control ───────────────────────────────
    // Lets support change a tenant's plan, fix/extend their term, comp a
    // plan, or resolve a stuck payment without touching the DB directly.
    public function updateSubscription(Tenant $tenant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id'       => ['required', 'exists:plans,id'],
            'status'        => ['required', Rule::in(['trial', 'active', 'cancelled', 'expired'])],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'ends_at'       => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'note'          => ['nullable', 'string', 'max:255'],
        ]);

        $subscription = $tenant->subscription;

        $payload = [
            'plan_id'                  => $data['plan_id'],
            'status'                   => $data['status'],
            'billing_cycle'            => $data['billing_cycle'],
            'ends_at'                  => $data['ends_at'] ?: null,
            'trial_ends_at'            => $data['trial_ends_at'] ?: null,
            // Let the "expiring soon" reminder fire again for the new term.
            'renewal_reminder_sent_at' => null,
        ];

        if ($data['status'] === 'cancelled' && !$subscription?->cancelled_at) {
            $payload['cancelled_at'] = now();
        }
        if ($data['status'] !== 'cancelled') {
            $payload['cancelled_at'] = null;
        }

        if ($subscription) {
            $subscription->update($payload);
        } else {
            $payload['tenant_id']  = $tenant->id;
            $payload['started_at'] = now();
            $subscription = Subscription::create($payload);
        }

        $planName = Plan::find($data['plan_id'])?->name ?? 'Unknown';

        AuditLog::record([
            'tenant_id'   => $tenant->id,
            'action'      => 'subscription_updated',
            'model_type'  => Subscription::class,
            'model_id'    => $subscription->id,
            'model_label' => $tenant->name,
            'description' => "Superadmin set {$tenant->name} to {$planName} / {$data['status']}"
                . ($data['note'] ? " — {$data['note']}" : ''),
        ]);

        return back()->with('success', "Subscription updated — {$planName} ({$data['status']}).");
    }

    // ── Module access — Manufacturing (Work Orders + Product Batches) ──
    // Sets an explicit force-on/force-off override for this tenant,
    // regardless of what their Plan includes (see Tenant::hasModuleEnabled()).
    public function toggleManufacturing(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = $tenant->settings ?? [];
        $settings['modules']['manufacturing'] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        AuditLog::record([
            'tenant_id'   => $tenant->id,
            'action'      => 'module_' . $state,
            'model_type'  => Tenant::class,
            'model_id'    => $tenant->id,
            'model_label' => $tenant->name,
            'description' => "Superadmin {$state} the Manufacturing module for {$tenant->name}",
        ]);

        return back()->with('success', "Manufacturing module {$state} for {$tenant->name}.");
    }

    // Removes the manual override, so access reverts to whatever the
    // tenant's current Plan dictates.
    public function clearManufacturingOverride(Tenant $tenant): RedirectResponse
    {
        $settings = $tenant->settings ?? [];
        unset($settings['modules']['manufacturing']);
        $tenant->update(['settings' => $settings]);

        AuditLog::record([
            'tenant_id'   => $tenant->id,
            'action'      => 'module_reset',
            'model_type'  => Tenant::class,
            'model_id'    => $tenant->id,
            'model_label' => $tenant->name,
            'description' => "Superadmin cleared the Manufacturing override for {$tenant->name} — now follows plan",
        ]);

        return back()->with('success', "Manufacturing access for {$tenant->name} now follows their plan.");
    }

    // ── Module access — Service (Service Catalog for service-based tenants) ──
    public function toggleService(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = $tenant->settings ?? [];
        $settings['modules']['service'] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        return back()->with('success', "Service module {$state} for {$tenant->name}.");
    }

    public function clearServiceOverride(Tenant $tenant): RedirectResponse
    {
        $settings = $tenant->settings ?? [];
        unset($settings['modules']['service']);
        $tenant->update(['settings' => $settings]);

        return back()->with('success', "Service access for {$tenant->name} now follows their plan.");
    }

    // ── Module access — generic (Subscriptions, Appointments, Time
    // Tracking, and any future module) so each new sub-feature doesn't
    // need its own copy-pasted toggle/clear method pair like
    // manufacturing/service above did. Same tri-state override behavior.
    // The toggleable set is the premium module registry in config/modules.php
    // (manufacturing/service also have dedicated methods above but are still
    // valid here — both paths write the same settings['modules'][x]). ──
    private function moduleLabel(string $module): string
    {
        return config("modules.{$module}.label", ucfirst(str_replace('_', ' ', $module)));
    }

    public function toggleModule(Tenant $tenant, string $module, Request $request): RedirectResponse
    {
        abort_unless(array_key_exists($module, config('modules')), 404);
        $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = $tenant->settings ?? [];
        $settings['modules'][$module] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        return back()->with('success', $this->moduleLabel($module) . " {$state} for {$tenant->name}.");
    }

    public function clearModuleOverride(Tenant $tenant, string $module): RedirectResponse
    {
        abort_unless(array_key_exists($module, config('modules')), 404);

        $settings = $tenant->settings ?? [];
        unset($settings['modules'][$module]);
        $tenant->update(['settings' => $settings]);

        return back()->with('success', $this->moduleLabel($module) . " access for {$tenant->name} now follows their plan.");
    }

    // ── User seat limit — per-tenant override of the plan's features['users'] ──
    // Same tri-state idea as the module overrides above: superadmin can pin a
    // tenant to a fixed number of seats (or unlimited), regardless of what
    // their plan sells. Stored at settings['limits']['users'] — see
    // Tenant::userSeatLimit(). Existing users are never removed; the limit
    // only gates adding new ones (enforced in StaffController::store()).
    public function updateSeatLimit(Tenant $tenant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode'  => ['required', Rule::in(['fixed', 'unlimited'])],
            'seats' => ['nullable', 'integer', 'min:1', 'max:100000', 'required_if:mode,fixed'],
        ]);

        $value = $data['mode'] === 'unlimited' ? -1 : (int) $data['seats'];

        $settings = $tenant->settings ?? [];
        $settings['limits']['users'] = $value;
        $tenant->update(['settings' => $settings]);

        $label = $value === -1 ? 'unlimited seats' : "{$value} seat(s)";

        AuditLog::record([
            'tenant_id'   => $tenant->id,
            'action'      => 'seat_limit_set',
            'model_type'  => Tenant::class,
            'model_id'    => $tenant->id,
            'model_label' => $tenant->name,
            'description' => "Superadmin set {$tenant->name}'s user seat limit to {$label}",
        ]);

        return back()->with('success', "User seat limit for {$tenant->name} set to {$label}.");
    }

    public function clearSeatLimit(Tenant $tenant): RedirectResponse
    {
        $settings = $tenant->settings ?? [];
        unset($settings['limits']['users']);
        if (empty($settings['limits'])) {
            unset($settings['limits']);
        }
        $tenant->update(['settings' => $settings]);

        AuditLog::record([
            'tenant_id'   => $tenant->id,
            'action'      => 'seat_limit_reset',
            'model_type'  => Tenant::class,
            'model_id'    => $tenant->id,
            'model_label' => $tenant->name,
            'description' => "Superadmin cleared {$tenant->name}'s user seat override — now follows their plan",
        ]);

        return back()->with('success', "User seat limit for {$tenant->name} now follows their plan.");
    }

    // ── Subscription tax invoices ────────────────────────────────

    private function tenantSubscription(Tenant $tenant, Subscription $subscription): Subscription
    {
        abort_unless($subscription->tenant_id === $tenant->id, 404);
        abort_unless($subscription->isInvoiceable(), 404, 'No invoice is available for this subscription.');

        return $subscription;
    }

    public function invoiceDownload(Tenant $tenant, Subscription $subscription, SubscriptionInvoiceService $invoices): Response
    {
        $subscription = $this->tenantSubscription($tenant, $subscription);
        $invoices->issue($subscription);

        return $invoices->pdf($subscription)->download($invoices->filename($subscription));
    }

    public function invoiceResend(Tenant $tenant, Subscription $subscription, SubscriptionInvoiceService $invoices): RedirectResponse
    {
        $subscription = $this->tenantSubscription($tenant, $subscription);

        $result = $invoices->issueAndDeliver($subscription);

        $parts = [];
        $parts[] = ($result['email'] ?? false) ? 'email sent' : 'email failed';
        if ($result['whatsapp'] !== null) {
            $parts[] = $result['whatsapp'] ? 'WhatsApp sent' : 'WhatsApp failed';
        }

        AuditLog::record([
            'tenant_id'   => $tenant->id,
            'action'      => 'subscription_invoice_resent',
            'model_type'  => Subscription::class,
            'model_id'    => $subscription->id,
            'model_label' => $tenant->name,
            'description' => "Superadmin re-sent invoice {$subscription->invoice_number} to {$tenant->name} — " . implode(', ', $parts),
        ]);

        return back()->with('success', "Invoice {$subscription->invoice_number} — " . implode(', ', $parts) . '.');
    }
}
