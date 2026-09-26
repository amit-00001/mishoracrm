<?php

namespace Tests\Feature\Tenant;

use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

// Server-side authorization must not depend on hidden navigation links. This audit
// walks every tenant web route that mutates state (POST/PUT/PATCH/DELETE) or serves
// a create/edit form and requires it to be guarded by either:
//   - route middleware (permission:* / role* / tenant.admin), or
//   - an authorize()/Gate/can()/abort_* check inside the controller method.
// A route that is deliberately open to any signed-in tenant user must be listed in
// $allowed with the reason, so adding a new unguarded route fails this test.
class RouteGuardAuditTest extends TestCase
{
    /** route name => why it is intentionally not permission-gated */
    private array $allowed = [
        // Self-service: identity comes from the logged-in user, never the request.
        'tenant.attendances.clock.in'  => 'clocks in the authenticated user\'s own staff profile',
        'tenant.attendances.clock.out' => 'clocks out the authenticated user\'s own staff profile',
        'tenant.screenshots.upload'    => 'controller verifies the attendance belongs to the logged-in user',
        'tenant.settings.password'     => 'own account',
        'tenant.settings.profile'      => 'own account',
        'tenant.settings.theme'        => 'own account',
        'tenant.settings.avatar'       => 'own account',
        'tenant.notifications.clear'   => 'scoped to the logged-in user\'s notifications',
        'tenant.notifications.destroy' => 'scoped to the logged-in user\'s notifications',
        'tenant.notifications.read-all' => 'scoped to the logged-in user\'s notifications',
        'tenant.notifications.read'    => 'scoped to the logged-in user\'s notifications',
        'tenant.notifications.preferences.save' => 'own notification preferences',
        'tenant.tasks.saved_filters.store'   => 'personal saved filter',
        'tenant.tasks.saved_filters.destroy' => 'personal saved filter',
        'tenant.leads.view'            => 'personal saved list view',
        'tenant.leads.check-duplicate' => 'read-only duplicate lookup used by the lead form',
        'tenant.automation.request'    => 'support request to the platform team; writes only a request row for the user',

        // KNOWN GAP — no permission exists in RolesAndPermissionsSeeder for these
        // modules, so there is nothing to enforce yet. Needs a product decision
        // (define products.* / services.* permissions, or restrict to admins).
        'tenant.products.create'  => 'TODO: no products.* permission defined',
        'tenant.products.store'   => 'TODO: no products.* permission defined',
        'tenant.products.edit'    => 'TODO: no products.* permission defined',
        'tenant.products.update'  => 'TODO: no products.* permission defined',
        'tenant.products.destroy' => 'TODO: no products.* permission defined',
        'tenant.services.create'  => 'TODO: no services.* permission defined',
        'tenant.services.store'   => 'TODO: no services.* permission defined',
        'tenant.services.edit'    => 'TODO: no services.* permission defined',
        'tenant.services.update'  => 'TODO: no services.* permission defined',
        'tenant.services.destroy' => 'TODO: no services.* permission defined',
    ];

    private const BODY_GUARD = '/(authorize\(|Gate::|->can\(|->cannot\(|abort_unless\(|abort_if\(|hasRole|user_type)/';

    public function test_every_mutating_or_form_tenant_route_is_guarded_or_explicitly_allowed(): void
    {
        $unguarded = [];
        $checked   = 0;

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'tenant.')) {
                continue;
            }

            $methods  = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
            $mutating = (bool) array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']);
            $form     = in_array('GET', $methods, true) && preg_match('#/(create|edit)(/|$)#', '/' . $route->uri());

            if (! $mutating && ! $form) {
                continue;
            }
            $checked++;

            if ($this->hasMiddlewareGuard($route) || $this->hasBodyGuard($route)) {
                continue;
            }

            if (! array_key_exists($name, $this->allowed)) {
                $unguarded[] = implode('|', $methods) . ' /' . $route->uri() . "  ({$name})";
            }
        }

        $this->assertGreaterThan(100, $checked, 'Route audit found suspiciously few routes — is the filter broken?');
        $this->assertSame(
            [],
            $unguarded,
            "Unguarded tenant routes (add permission middleware or an authorize() call, or allow-list with a reason):\n - "
            . implode("\n - ", $unguarded)
        );
    }

    public function test_the_allow_list_has_no_stale_entries(): void
    {
        $known = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

        foreach (array_keys($this->allowed) as $name) {
            $this->assertContains($name, $known, "Allow-listed route [{$name}] no longer exists — remove it from the list.");
        }
    }

    public function test_previously_open_routes_are_now_guarded(): void
    {
        // The audit's reported case plus the siblings found alongside it.
        foreach ([
            'tenant.leads.create', 'tenant.deals.create', 'tenant.purchase-orders.create', 'tenant.quotations.create',
            'tenant.contacts.create', 'tenant.contacts.store', 'tenant.contacts.destroy',
            'tenant.invoices.create', 'tenant.invoices.destroy', 'tenant.invoices.record_payment',
            'tenant.attendances.create', 'tenant.attendances.destroy', 'tenant.attendances.bulk.store',
            'tenant.custom-fields.store', 'tenant.whatsapp.send.store', 'tenant.email.send.store',
            'tenant.tasks.store', 'tenant.task-templates.store', 'tenant.lead-integrations.save',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route [{$name}] missing");
            $this->assertTrue(
                $this->hasMiddlewareGuard($route) || $this->hasBodyGuard($route),
                "Route [{$name}] is not guarded"
            );
        }
    }

    private function hasMiddlewareGuard($route): bool
    {
        foreach ($route->gatherMiddleware() as $mw) {
            if (! is_string($mw)) {
                continue;
            }
            if (preg_match('/^(permission:|role:|role_or_permission:|tenant\.admin$)|PermissionMiddleware|RoleMiddleware|EnsureTenantAdmin/', $mw)) {
                return true;
            }
        }

        return false;
    }

    private function hasBodyGuard($route): bool
    {
        $action = $route->getActionName();
        if (! str_contains($action, '@')) {
            return false;
        }

        [$class, $method] = explode('@', $action);
        if (! class_exists($class) || ! method_exists($class, $method)) {
            return false;
        }

        $ref   = new ReflectionMethod($class, $method);
        $lines = file($ref->getFileName());
        $body  = implode('', array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));

        return (bool) preg_match(self::BODY_GUARD, $body);
    }
}
