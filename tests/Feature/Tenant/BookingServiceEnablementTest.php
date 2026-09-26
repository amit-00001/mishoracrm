<?php

namespace Tests\Feature\Tenant;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P3: public booking was enabled but the public page silently showed nothing
// until each service was separately ticked "bookable". Say so, in both places.
class BookingServiceEnablementTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function tenantWithBooking(bool $enabled = true): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        $settings['modules']['appointments'] = true;
        $settings['booking'] = ['enabled' => $enabled] + $tenant->bookingSettings();
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function service(Tenant $tenant, bool $bookable, string $name = 'Haircut'): Service
    {
        return Service::create([
            'tenant_id' => $tenant->id, 'name' => $name, 'rate' => 500, 'is_active' => true, 'is_bookable' => $bookable,
        ]);
    }

    // ── Admin settings page ─────────────────────────────────────────

    public function test_settings_page_warns_when_booking_is_on_but_no_service_is_bookable(): void
    {
        $tenant = $this->tenantWithBooking(true);
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->service($tenant, false);

        $this->actingAs($admin)->get(route('tenant.appointments.settings'))
            ->assertOk()
            ->assertSee('id="noBookableWarning"', false)
            ->assertSee('no service is enabled for booking yet', false)
            ->assertSee('A service must be checked here to be bookable');
    }

    public function test_settings_page_shows_no_warning_once_a_service_is_bookable(): void
    {
        $tenant = $this->tenantWithBooking(true);
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->service($tenant, true);

        $this->actingAs($admin)->get(route('tenant.appointments.settings'))
            ->assertOk()
            ->assertDontSee('id="noBookableWarning"', false);
    }

    public function test_settings_page_shows_no_warning_while_booking_is_off(): void
    {
        $tenant = $this->tenantWithBooking(false);
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->service($tenant, false);

        $this->actingAs($admin)->get(route('tenant.appointments.settings'))
            ->assertOk()
            ->assertDontSee('id="noBookableWarning"', false);
    }

    public function test_saving_with_booking_on_and_no_bookable_service_flashes_a_warning(): void
    {
        $tenant = $this->tenantWithBooking(false);
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->service($tenant, false);

        $payload = [
            'enabled' => 1, 'slot_duration_minutes' => 30, 'capacity_per_slot' => 1, 'advance_booking_days' => 14,
            'hours'   => ['mon' => ['open' => '09:00', 'close' => '18:00']],
        ];

        $this->actingAs($admin)->post(route('tenant.appointments.settings.update'), $payload)
            ->assertSessionHas('success', 'Booking settings updated.')
            ->assertSessionHas('error');
    }

    public function test_saving_with_a_bookable_service_selected_has_no_warning(): void
    {
        $tenant  = $this->tenantWithBooking(false);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $service = $this->service($tenant, false);

        $this->actingAs($admin)->post(route('tenant.appointments.settings.update'), [
            'enabled' => 1, 'slot_duration_minutes' => 30, 'capacity_per_slot' => 1, 'advance_booking_days' => 14,
            'hours'   => ['mon' => ['open' => '09:00', 'close' => '18:00']],
            'bookable_service_ids' => [$service->id],
        ])
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $this->assertTrue($service->fresh()->is_bookable);
    }

    // ── Public page ─────────────────────────────────────────────────

    public function test_public_page_explains_when_no_service_is_open_for_booking(): void
    {
        $tenant = $this->tenantWithBooking(true);
        $this->service($tenant, false);

        $this->get(route('public.booking.show', $tenant->ensureBookingToken()))
            ->assertOk()
            ->assertSee('No services are open for online booking yet.')
            ->assertSee('contact ' . $tenant->name);
    }

    public function test_public_page_lists_a_service_once_it_is_bookable(): void
    {
        $tenant = $this->tenantWithBooking(true);
        $this->service($tenant, true, 'Deep Tissue Massage');
        $this->service($tenant, false, 'Hidden Service');

        $this->get(route('public.booking.show', $tenant->ensureBookingToken()))
            ->assertOk()
            ->assertSee('Deep Tissue Massage')
            ->assertDontSee('Hidden Service')
            ->assertDontSee('No services are open for online booking yet.');
    }
}
