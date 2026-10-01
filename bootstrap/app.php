<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Render ke proxy ke peeche HTTPS detect karne ke liye — warna asset/vite URLs http:// bante hain aur mixed-content block hota hai
        $middleware->trustProxies(at: '*');

        // Webhook routes CSRF se exempt — each has its own signature/token verification
        $middleware->validateCsrfTokens(except: [
            'webhook/razorpay',
            'webhook/instagram',
            'webhook/whatsapp',
            'webhook/wa-gateway', // WhatsApp Gateway — HMAC-signed, not a session
            'webhook/leads/*',  // Meta Lead Ads, JustDial, TradeIndia, Sulekha
            'instagram/deauthorize',    // Meta-required callback — signed_request, not a session
            'instagram/data-deletion',  // Meta-required callback — signed_request, not a session
        ]);

        $middleware->alias([
            // Custom
            'tenant'       => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.admin' => \App\Http\Middleware\EnsureTenantAdmin::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'api.key'      => \App\Http\Middleware\AuthenticateWithApiKey::class,
            'module'       => \App\Http\Middleware\EnsureModuleEnabled::class,
            'customer.auth' => \App\Http\Middleware\AuthenticateCustomer::class,

            // Spatie — yeh teeno register karne zaroori hain
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Auto-capture all exceptions into error_logs for superadmin monitoring
        $exceptions->report(function (\Throwable $e) {
            \App\Models\ErrorLog::capture($e, request());
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $status);
            }
        });

    })
    ->create();