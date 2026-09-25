<?php

return [

    // ── Test login for the customer wallet ───────────────────────────────
    // ONE fixed phone signs in with ONE fixed code, so nobody has to receive a
    // real WhatsApp/email OTP while testing. These are just the values; whether
    // the shortcut is live is a switch a superadmin flips in Superadmin → Portal
    // Customers (App\Support\PortalTestLogin). It is off until then.
    'test_login' => [
        'phone' => env('PORTAL_TEST_PHONE', '9999999999'),
        'otp'   => env('PORTAL_TEST_OTP', '123456'),
    ],

];
