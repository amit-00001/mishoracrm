<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\PlatformSetting;
use App\Services\PortalTestData;
use App\Support\PortalTestLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Platform-level list of customer-portal accounts (docs/customer-portal-loyalty.txt §7).
// Customers are global identities, so this lives ONLY in the superadmin area — a
// tenant must never be able to enumerate them. Contacts are always read with
// explicit withoutGlobalScopes(); only counts/status leave this controller.
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $scope  = $request->query('scope') === 'all' ? 'all' : 'real';
        $status = in_array($request->query('status'), ['active', 'blocked'], true) ? $request->query('status') : null;
        $search = trim((string) $request->query('q', ''));

        $linkedShops = Contact::withoutGlobalScopes()
            ->selectRaw('count(distinct tenant_id)')
            ->whereColumn('contacts.customer_id', 'customers.id')
            ->where('phone_verified', true);

        $customers = Customer::query()
            ->addSelect(['customers.*', 'linked_shops' => $linkedShops])
            // Default view hides junk: never logged in AND linked to nothing.
            ->when($scope === 'real', fn ($q) => $q->where(function ($w) {
                $w->whereNotNull('last_login_at')
                  ->orWhereHas('contacts', fn ($c) => $c->withoutGlobalScopes());
            }))
            ->when($status === 'blocked', fn ($q) => $q->whereNotNull('blocked_at'))
            ->when($status === 'active', fn ($q) => $q->whereNull('blocked_at'))
            ->when($search !== '', function ($q) use ($search) {
                // Phones are stored as the last 10 digits, so drop any +91 / 0 prefix.
                $digits = preg_replace('/\D/', '', $search);
                $digits = strlen($digits) > 10 ? substr($digits, -10) : $digits;
                $q->where(function ($w) use ($search, $digits) {
                    $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                    if ($digits !== '') {
                        $w->orWhere('phone', 'like', "%{$digits}%");
                    }
                });
            })
            ->orderByRaw('last_login_at is null')
            ->orderByDesc('last_login_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.customers.index', [
            'customers' => $customers,
            'metrics'   => $this->metrics(),
            'testLogin' => [
                'on'    => PortalTestLogin::active(),
                'phone' => PortalTestLogin::phone(),
                'otp'   => PortalTestLogin::code(),
            ],
            'scope'     => $scope,
            'status'    => $status,
            'search'    => $search,
        ]);
    }

    public function block(Customer $customer): RedirectResponse
    {
        // A fresh remember token also kills any remember-me cookie right away;
        // the portal middleware then refuses the account on its next request.
        $customer->forceFill(['blocked_at' => now(), 'remember_token' => Str::random(60)])->save();

        return back()->with('success', "Customer ending {$this->tail($customer)} blocked.");
    }

    public function unblock(Customer $customer): RedirectResponse
    {
        $customer->forceFill(['blocked_at' => null])->save();

        return back()->with('success', "Customer ending {$this->tail($customer)} unblocked.");
    }

    // ── Test login switch ────────────────────────────────────────
    // ON: the wallet accepts the test phone with the fixed code, and the test
    // customer is added to the first 5 tenants. OFF: the shortcut stops working
    // at once and the test customer / test contacts are removed.
    public function enableTestLogin(): RedirectResponse
    {
        PlatformSetting::set(PortalTestLogin::SETTING, '1');
        $tenants = PortalTestData::seed();

        return back()->with('success', 'Test login is ON — ' . PortalTestLogin::phone() . ' / OTP ' . PortalTestLogin::code()
            . " added to {$tenants} " . Str::plural('tenant', $tenants) . '.');
    }

    public function disableTestLogin(): RedirectResponse
    {
        PlatformSetting::set(PortalTestLogin::SETTING, '0');
        PortalTestData::purge();

        return back()->with('success', 'Test login is OFF — the test customer and its test contacts were removed.');
    }

    private function tail(Customer $customer): string
    {
        return substr($customer->phone, -4);
    }

    private function metrics(): array
    {
        $verifiedLinks = fn () => Contact::withoutGlobalScopes()
            ->whereNotNull('customer_id')
            ->where('phone_verified', true);

        // Distinct (customer, shop) pairs → average shops per customer that has any.
        $pairs = DB::query()->fromSub(
            $verifiedLinks()->select('customer_id', 'tenant_id')->distinct(),
            'pairs'
        )->count();

        $withShops = (clone $verifiedLinks())->distinct()->count('customer_id');

        return [
            'total'      => Customer::count(),
            'with_shops' => $withShops,
            'active_30d' => Customer::where('last_login_at', '>=', now()->subDays(30))->count(),
            'blocked'    => Customer::whereNotNull('blocked_at')->count(),
            'avg_shops'  => $withShops > 0 ? round($pairs / $withShops, 1) : 0,
        ];
    }
}
