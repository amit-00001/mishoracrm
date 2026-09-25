<?php

namespace App\Support;

// Local-dev shortcut for the customer wallet login (config/portal.php): one
// fixed phone signs in with one fixed code. Hard-gated to local/testing so it
// can never act as a backdoor on a real deployment, whatever the env says.
class PortalTestLogin
{
    public static function active(): bool
    {
        return (bool) config('portal.test_login.enabled')
            && app()->environment(['local', 'testing']);
    }

    public static function matches(?string $phone): bool
    {
        return self::active()
            && $phone !== null
            && self::last10($phone) === self::last10((string) config('portal.test_login.phone'));
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
