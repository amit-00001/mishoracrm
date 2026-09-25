<?php

return [

    // ── Local-dev login shortcut for the customer wallet ─────────────────
    // With this on, ONE fixed phone number signs in with a fixed code, so you
    // don't have to receive a real WhatsApp/email OTP on every test login.
    //
    // It only ever works when APP_ENV is "local" or "testing" (see
    // App\Support\PortalTestLogin) — setting these in production does nothing.
    'test_login' => [
        'enabled' => env('PORTAL_TEST_LOGIN', false),
        'phone'   => env('PORTAL_TEST_PHONE', '9999999999'),
        'otp'     => env('PORTAL_TEST_OTP', '123456'),
    ],

];
