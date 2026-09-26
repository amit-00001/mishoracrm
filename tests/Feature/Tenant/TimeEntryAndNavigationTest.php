<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit:
//  P2  time-entry start/stop forms navigated the browser to raw JSON ({"ok":true,...}).
//  P3  /time-tracking and /reports returned 404 (the working URLs are /time-entries
//      and /reports/overview).
class TimeEntryAndNavigationTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private const MODULES = ['time_tracking', 'tickets', 'appointments', 'subscriptions', 'service', 'manufacturing', 'loyalty', 'customer_portal'];

    private function tenantWithModules(): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        foreach (self::MODULES as $module) {
            $settings['modules'][$module] = true;
        }
        $tenant->update(['settings' => $settings]);

        return $tenant;
    }

    // ── Start / stop: browser forms redirect, API clients still get JSON ──────

    public function test_starting_a_timer_from_a_form_redirects_back_with_a_flash_message(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');

        $response = $this->actingAs($user)
            ->from(route('tenant.time-entries.index'))
            ->post(route('tenant.time-entries.start'));

        $response->assertRedirect(route('tenant.time-entries.index'))->assertSessionHas('success', 'Timer started.');
        $this->assertFalse(str_contains((string) $response->headers->get('Content-Type'), 'json'));

        $entry = TimeEntry::first();
        $this->assertNotNull($entry);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertNull($entry->ended_at);
    }

    public function test_starting_a_timer_with_a_json_client_still_returns_json(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');

        $this->actingAs($user)
            ->postJson(route('tenant.time-entries.start'))
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'id', 'started_at']);

        $this->assertSame(1, TimeEntry::count());
    }

    public function test_stopping_a_timer_from_a_form_redirects_back_with_the_logged_duration(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');
        $entry  = TimeEntry::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'started_at' => now()->subMinutes(42)]);

        $this->actingAs($user)
            ->from(route('tenant.time-entries.index'))
            ->post(route('tenant.time-entries.stop', $entry->id))
            ->assertRedirect(route('tenant.time-entries.index'))
            ->assertSessionHas('success');

        $entry->refresh();
        $this->assertNotNull($entry->ended_at);
        $this->assertSame(42, $entry->duration_minutes);
    }

    public function test_stopping_a_timer_with_a_json_client_still_returns_json(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');
        $entry  = TimeEntry::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'started_at' => now()->subMinutes(10)]);

        $this->actingAs($user)
            ->postJson(route('tenant.time-entries.stop', $entry->id))
            ->assertOk()
            ->assertJson(['ok' => true, 'duration_minutes' => 10]);
    }

    public function test_stopping_an_already_stopped_timer_shows_an_error_and_keeps_the_original_end_time(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');
        $ended  = now()->subHour();
        $entry  = TimeEntry::create([
            'tenant_id' => $tenant->id, 'user_id' => $user->id,
            'started_at' => $ended->copy()->subMinutes(30), 'ended_at' => $ended, 'duration_minutes' => 30,
        ]);

        $this->actingAs($user)
            ->from(route('tenant.time-entries.index'))
            ->post(route('tenant.time-entries.stop', $entry->id))
            ->assertRedirect(route('tenant.time-entries.index'))
            ->assertSessionHas('error', 'That timer was already stopped.');

        $this->assertSame(30, $entry->fresh()->duration_minutes);
    }

    public function test_starting_a_second_timer_stops_the_first_and_says_so(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');

        $this->actingAs($user)->post(route('tenant.time-entries.start'));
        $this->actingAs($user)->post(route('tenant.time-entries.start'))
            ->assertSessionHas('success', 'Timer started — your previous timer was stopped.');

        $this->assertSame(2, TimeEntry::count());
        $this->assertSame(1, TimeEntry::whereNull('ended_at')->count());
    }

    public function test_a_colleague_cannot_stop_someone_elses_timer_but_an_admin_can(): void
    {
        $tenant = $this->tenantWithModules();
        $owner  = $this->makeUser($tenant, 'staff');
        $other  = $this->makeUser($tenant, 'staff');
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $entry  = TimeEntry::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'started_at' => now()->subMinutes(5)]);

        $this->actingAs($other)->post(route('tenant.time-entries.stop', $entry->id))->assertForbidden();
        $this->assertNull($entry->fresh()->ended_at);

        $this->actingAs($admin)->post(route('tenant.time-entries.stop', $entry->id))->assertRedirect();
        $this->assertNotNull($entry->fresh()->ended_at);
    }

    public function test_the_page_shows_the_flash_message_after_starting_a_timer(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');

        $this->actingAs($user)->from(route('tenant.time-entries.index'))->post(route('tenant.time-entries.start'));

        $this->actingAs($user)->get(route('tenant.time-entries.index'))
            ->assertOk()
            ->assertSee('Timer running');
    }

    // ── Navigation routes ───────────────────────────────────────────

    public function test_time_tracking_url_redirects_to_the_time_entries_page(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'staff');

        $this->actingAs($user)->get('/time-tracking')->assertRedirect('/time-entries');
        $this->actingAs($user)->get('/time-entries')->assertOk();
    }

    public function test_reports_url_redirects_to_the_reports_overview(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeCustomRoleUser($tenant, ['reports.view_all']); // the overview page requires view_all

        $this->actingAs($user)->get('/reports')->assertRedirect('/reports/overview');
        $this->actingAs($user)->get('/reports/overview')->assertOk();
    }

    public function test_the_redirect_aliases_do_not_accept_state_changing_requests(): void
    {
        $tenant = $this->tenantWithModules();
        $user   = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($user)->post('/time-tracking')->assertStatus(405);
        $this->actingAs($user)->delete('/reports')->assertStatus(405);
    }

    public function test_guests_hitting_the_aliases_are_sent_to_login_not_a_404(): void
    {
        $this->tenantWithModules();

        foreach (['/time-tracking', '/reports'] as $uri) {
            $this->assertNotSame(404, $this->get($uri)->getStatusCode(), "{$uri} 404s for guests");
        }
    }

    public function test_sidebar_highlights_time_tracking_and_reports_on_their_own_pages(): void
    {
        $tenant = $this->tenantWithModules();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $entries = $this->actingAs($admin)->get('/time-entries')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#href="[^"]*/time-entries"\s+class="sb-item active"#', $entries);

        $reports = $this->actingAs($admin)->get('/reports/overview')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#href="[^"]*/reports/overview"\s+class="sb-item active"#', $reports);
    }

    /** Every link in the tenant sidebar must resolve — a dead nav link is a 404 waiting to be reported. */
    public function test_every_sidebar_link_resolves_for_a_tenant_admin(): void
    {
        $tenant = $this->tenantWithModules();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $html = $this->actingAs($admin)->get(route('tenant.dashboard'))->assertOk()->getContent();

        preg_match_all('#<a\s+href="([^"]+)"\s+class="sb-item#', $html, $m);
        $links = array_values(array_unique($m[1]));

        $this->assertGreaterThan(15, count($links), 'Sidebar link extraction found too few links');
        $this->assertContains(route('tenant.time-entries.index'), $links);
        $this->assertContains(route('tenant.reports.overview'), $links);

        $broken = [];
        foreach ($links as $href) {
            if (str_starts_with($href, '#') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:')) {
                continue;
            }

            $path   = parse_url($href, PHP_URL_PATH) . (($q = parse_url($href, PHP_URL_QUERY)) ? "?{$q}" : '');
            $status = $this->actingAs($admin)->get($path)->getStatusCode();

            if ($status >= 400) {
                $broken[] = "{$status} {$path}";
            }
        }

        $this->assertSame([], $broken, "Sidebar links that do not resolve:\n - " . implode("\n - ", $broken));
    }
}
