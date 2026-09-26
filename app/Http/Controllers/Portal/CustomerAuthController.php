<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\WhatsappSetting;
use App\Services\CustomerLinkService;
use App\Services\WhatsappChatbotService;
use App\Support\PortalTestLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

// Passwordless login for the global customer wallet (docs/customer-portal-loyalty.txt §3).
// Never trusts an ambient tenant — a Customer spans tenants, so every Contact
// query here is explicit withoutGlobalScopes().
class CustomerAuthController extends Controller
{
    private const PIN_MAX_ATTEMPTS = 5;
    private const PIN_LOCK_MINUTES = 30;
    private const SENT_NOTICE      = "If you're a member of a participating shop, a 6-digit code is on its way.";
    private const BAD_CODE         = 'That code is incorrect or has expired. Please request a new one.';

    private function guard()
    {
        return Auth::guard('customer');
    }

    private function normalize(string $input): string
    {
        $digits = preg_replace('/\D/', '', $input);

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    public function showLogin(): View|RedirectResponse
    {
        if ($this->guard()->check()) {
            return redirect('/wallet');
        }

        return view('portal.auth.login');
    }

    // Step 1 — request a code. The response is identical whether or not the
    // phone belongs to any shop, and the rate limits below apply to every
    // phone (matched or not) so they can't be used to probe for members.
    public function requestOtp(Request $request): View|RedirectResponse
    {
        $data  = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $phone = $this->normalize($data['phone']);

        if (strlen($phone) < 10) {
            return back()->withInput()->withErrors(['phone' => 'Enter a valid 10-digit phone number.']);
        }

        // Local-dev shortcut: skips the rate limits and any real delivery.
        if (PortalTestLogin::matches($phone)) {
            return $this->issueTestLoginCode($request, $phone);
        }

        $hourKey = "portal_otp_hour:{$phone}";
        if (RateLimiter::tooManyAttempts($hourKey, CustomerOtp::HOURLY_CAP)) {
            return back()->withInput()->with('error', 'Too many codes requested for this number. Please try again in an hour.');
        }
        if (!Cache::add("portal_otp_resend:{$phone}", true, CustomerOtp::RESEND_WINDOW)) {
            return back()->withInput()->with('error', 'A code was just sent. Please wait a minute before trying again.');
        }
        RateLimiter::hit($hourKey, 3600);

        $contacts = Contact::withoutGlobalScopes()
            ->where('phone_normalized', $phone)
            ->orderBy('id')
            ->limit(25)
            ->get();

        if ($contacts->isEmpty()) {
            // No member anywhere: create nothing, send nothing, and take
            // roughly as long as the matched path so timing doesn't leak.
            usleep(random_int(150000, 400000));
        } else {
            $customer = Customer::withTrashed()->firstOrCreate(['phone' => $phone]);
            if ($customer->trashed()) {
                $customer->restore();
            }

            if (!$customer->isBlocked()) {
                app(CustomerLinkService::class)->linkByPhone($customer);

                if ($delivery = $this->pickDelivery($customer, $contacts)) {
                    [, $code] = CustomerOtp::issue($customer, $phone, $delivery['channel'], $request->ip());
                    $this->deliver($delivery, $code);
                }
            }
        }

        return view('portal.auth.verify', ['phone' => $phone, 'notice' => self::SENT_NOTICE, 'error' => null]);
    }

    // Step 2 — check the code against the phone's latest unused code.
    public function verify(Request $request): View|RedirectResponse
    {
        $data  = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code'  => ['required', 'string', 'max:6'],
        ]);
        $phone = $this->normalize($data['phone']);

        $otp      = CustomerOtp::where('phone', $phone)->whereNull('verified_at')->latest('id')->first();
        $customer = $otp?->customer_id ? Customer::find($otp->customer_id) : null;

        if (!$otp || !$customer || $customer->isBlocked() || !$otp->attempt(trim($data['code']))) {
            return view('portal.auth.verify', ['phone' => $phone, 'notice' => null, 'error' => self::BAD_CODE]);
        }

        return $this->loginAndRedirect($request, $customer);
    }

    // Fast re-login for customers who set a PIN. Every failure gets the same
    // message so it can't be used to discover which phones have accounts.
    public function loginWithPin(Request $request): RedirectResponse
    {
        $data  = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'pin'   => ['required', 'string', 'max:6'],
        ]);
        $phone    = $this->normalize($data['phone']);
        $customer = strlen($phone) >= 10 ? Customer::where('phone', $phone)->first() : null;

        $fail = fn () => back()->withInput()->with('error', 'Invalid phone or PIN. You can also sign in with a one-time code.');

        if (!$customer || !$customer->pin_hash || $customer->isBlocked() || $customer->isPinLocked()) {
            return $fail();
        }

        if (!Hash::check($data['pin'], $customer->pin_hash)) {
            $attempts = $customer->pin_attempts + 1;

            if ($attempts >= self::PIN_MAX_ATTEMPTS) {
                $customer->forceFill(['pin_attempts' => 0, 'pin_locked_until' => now()->addMinutes(self::PIN_LOCK_MINUTES)])->save();
            } else {
                $customer->forceFill(['pin_attempts' => $attempts])->save();
            }

            return $fail();
        }

        return $this->loginAndRedirect($request, $customer);
    }

    public function showSetPin(): View
    {
        return view('portal.auth.set-pin');
    }

    public function setPin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
        ]);

        $this->guard()->user()->forceFill([
            'pin_hash'         => Hash::make($data['pin']),
            'pin_attempts'     => 0,
            'pin_locked_until' => null,
        ])->save();

        return redirect('/wallet')->with('success', 'PIN saved. You can use it for faster sign-in next time.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->guard()->logout();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    // ── Helpers ────────────────────────────────────────────────────

    // config/portal.php test login: make sure the Customer exists (no Contact
    // needed), link any contacts on that number as a real login would, and store
    // the fixed code as an ordinary OTP so /login/verify checks it the usual way.
    private function issueTestLoginCode(Request $request, string $phone): View
    {
        $customer = Customer::withTrashed()->firstOrCreate(['phone' => $phone]);
        if ($customer->trashed()) {
            $customer->restore();
        }

        if (!$customer->isBlocked()) {
            app(CustomerLinkService::class)->linkByPhone($customer);

            CustomerOtp::create([
                'customer_id' => $customer->id,
                'phone'       => $phone,
                'channel'     => 'test',
                'code_hash'   => Hash::make(PortalTestLogin::code()),
                'attempts'    => 0,
                'ip'          => $request->ip(),
                'expires_at'  => now()->addMinutes(CustomerOtp::TTL_MINUTES),
            ]);
        }

        return view('portal.auth.verify', [
            'phone'   => $phone,
            'notice'  => self::SENT_NOTICE,
            'error'   => null,
            'devHint' => 'Test login is on — enter ' . PortalTestLogin::code() . '.',
        ]);
    }

    private function loginAndRedirect(Request $request, Customer $customer): RedirectResponse
    {
        $this->guard()->login($customer, true);
        $request->session()->regenerate();

        $customer->forceFill([
            'last_login_at'    => now(),
            'pin_attempts'     => 0,
            'pin_locked_until' => null,
        ])->save();

        return $customer->pin_hash
            ? redirect('/wallet')
            : redirect()->route('portal.pin.setup');
    }

    // WhatsApp only, sent through any matched shop that has it connected. Null =
    // no way to reach them (the visitor sees the same generic response
    // regardless). Email OTP is deliberately off for now.
    private function pickDelivery(Customer $customer, Collection $contacts): ?array
    {
        foreach ($contacts as $contact) {
            $wa = WhatsappSetting::forTenant($contact->tenant_id);
            if ($wa->exists && $wa->is_connected) {
                return [
                    'channel'   => 'whatsapp',
                    'tenant_id' => $contact->tenant_id,
                    'target'    => preg_replace('/\D/', '', (string) $contact->phone),
                ];
            }
        }

        return null;
    }

    private function deliver(array $delivery, string $code): void
    {
        $app  = config('app.name');
        $text = "Your {$app} wallet login code is {$code}. It expires in " . CustomerOtp::TTL_MINUTES . ' minutes.';

        try {
            WhatsappChatbotService::forTenant($delivery['tenant_id'])->sendMessage($delivery['target'], $text);
        } catch (\Throwable $e) {
            // Delivery failures are silent — the visitor just won't get a code.
        }
    }
}
