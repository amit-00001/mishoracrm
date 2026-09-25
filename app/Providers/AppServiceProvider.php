<?php

namespace App\Providers;

use App\Database\CaseInsensitivePostgresGrammar;
use App\Models\Deal;
use App\Models\Lead;
use App\Observers\DealObserver;
use App\Observers\LeadObserver;
use App\Support\PortalTestLogin;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Make LIKE searches case-insensitive on PostgreSQL (Supabase), like MySQL.
        if (config('database.default') === 'pgsql') {
            DB::connection()->setQueryGrammar(new CaseInsensitivePostgresGrammar);
        }

        // Production sits behind a reverse proxy that terminates SSL and
        // forwards plain HTTP to the app, so $request->getScheme() (and
        // every url()/route() call built from it — including the Meta
        // OAuth/webhook callback URLs shown in Superadmin > Platform
        // Settings) reports "http" even though the browser used https.
        // Force https whenever APP_URL is configured as https, regardless
        // of what scheme the request actually arrived on.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');

            // Also pin the host itself. Meta's OAuth token exchange (WhatsApp
            // and Instagram coexistence connect) rejects the request unless
            // the redirect_uri sent when swapping the code for a token is
            // byte-for-byte identical to the one used in the original
            // authorize redirect. Those are two separate HTTP requests
            // (oauthStart, then oauthCallback); if the proxy ever forwards a
            // different Host header between them, route() would build two
            // different URLs and Meta throws "Error validating verification
            // code. Please make sure your redirect_uri is identical...".
            // Forcing the root URL makes every route()/url() call use the
            // fixed APP_URL host, independent of the incoming request.
            URL::forceRootUrl(rtrim(config('app.url'), '/'));
        }

        // App CSS has no Tailwind utilities, so Laravel's default pagination
        // view (Tailwind SVG chevrons) renders unstyled and huge. Bootstrap's
        // markup (.pagination/.page-item/.page-link) is plain text arrows and
        // is styled to match the app's design system in app.css.
        Paginator::useBootstrapFive();

        // Laravel's own bootstrap-5 view renders every page number with no
        // ellipsis whenever the paginator has fewer than ~14 pages, which
        // looks broken (e.g. 1..12 all as buttons). Use the same markup/CSS
        // classes but always window to current page ± 2 with first/last
        // jump links, matching the pagination style used elsewhere in the app.
        Paginator::defaultView('vendor.pagination.app');

        // The framework's default RedirectIfAuthenticated (used by the
        // 'guest' middleware on /login etc.) redirects already-logged-in
        // users to a route named "dashboard", falling back to "home" (the
        // Laravel welcome page) since this app has no such route. Point it
        // at the real dashboard routes instead.
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $user = Auth::user();

            if (!$user) {
                return route('home');
            }

            if ($user->isSuperAdmin()) {
                return route('superadmin.dashboard');
            }

            return route('tenant.dashboard', ['tenant' => $user->tenant->subdomain]);
        });

        // Customer-portal login limits, per IP. Named limiters (not "throttle:5,10")
        // so the local-dev test number (config/portal.php) can be exempted without
        // touching anyone else's limit.
        foreach (['portal-otp' => 5, 'portal-verify' => 10, 'portal-pin' => 5] as $name => $perTenMinutes) {
            RateLimiter::for($name, fn (Request $request) => PortalTestLogin::matches($request->input('phone'))
                ? Limit::none()
                : Limit::perMinutes(10, $perTenMinutes)->by($request->ip()));
        }

        Lead::observe(LeadObserver::class);
        Deal::observe(DealObserver::class);
    }
}
