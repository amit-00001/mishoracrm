<?php

namespace App\Support;

use App\Models\PlatformSetting;

// Test login for the customer wallet: one fixed phone signs in with one fixed
// code (config/portal.php), so testers don't need a real OTP. It is ON only while
// a superadmin has switched it on (Superadmin → Portal Customers) — off by default,
// and switching it off removes the test data and stops the shortcut immediately.
class PortalTestLogin
{
    public const SETTING = 'portal_test_login';

    public static function active(): bool
    {
        return PlatformSetting::get(self::SETTING) === '1';
    }

    public static function matches(?string $phone): bool
    {
        return $phone !== null
            && self::last10($phone) === self::phone()
            && self::active();
    }

    public static function phone(): string
    {
        return self::last10((string) config('portal.test_login.phone'));
    }

    public static function code(): string
    {
        return (string) config('portal.test_login.otp');
    }

    private static function last10(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }
}
