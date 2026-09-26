<?php

namespace Tests\Feature\Tenant;

use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P2: clock-in submitted an empty hidden staff_id and never marked the
// staff member present. The server also trusted the posted staff_id, letting any
// tenant user clock anyone in/out. Identity now comes from the authenticated user.
class AttendanceClockTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function staffProfile(Tenant $tenant, ?User $user = null): array
    {
        $user ??= $this->makeUser($tenant, 'staff');
        $staff = Staff::create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        return [$user, $staff];
    }

    public function test_clock_in_creates_a_present_attendance_for_the_logged_in_staff_member(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.in'), ['staff_id' => $staff->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance = Attendance::where('staff_id', $staff->id)->first();
        $this->assertNotNull($attendance);
        $this->assertSame(today()->toDateString(), $attendance->date->toDateString());
        $this->assertSame('present', $attendance->status);
        $this->assertNotNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);
        $this->assertSame($tenant->id, $attendance->tenant_id);
    }

    public function test_clock_in_works_with_an_empty_or_missing_staff_id(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        // The audit's hidden field arrived empty.
        $this->actingAs($user)->post(route('tenant.attendances.clock.in'), ['staff_id' => ''])->assertRedirect()->assertSessionHas('success');

        $this->assertNotNull(Attendance::where('staff_id', $staff->id)->first()?->clock_in);
    }

    public function test_clock_page_prefills_the_hidden_staff_id_for_the_logged_in_user(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        $html = $this->actingAs($user)->get(route('tenant.attendances.clock'))->assertOk()->getContent();

        $this->assertStringContainsString('id="clockInStaffId" value="' . $staff->id . '"', $html);
        $this->assertStringContainsString('id="clockOutStaffId" value="' . $staff->id . '"', $html);
    }

    public function test_duplicate_clock_in_is_rejected_and_keeps_the_first_time(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        $this->actingAs($user)->post(route('tenant.attendances.clock.in'));
        $firstClockIn = Attendance::where('staff_id', $staff->id)->first()->clock_in;

        $this->travel(5)->minutes();

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.in'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Staff already clock in hai!');

        $this->assertSame(1, Attendance::where('staff_id', $staff->id)->count());
        $this->assertTrue($firstClockIn->equalTo(Attendance::where('staff_id', $staff->id)->first()->clock_in));
    }

    public function test_clock_in_reuses_an_attendance_row_already_created_for_today(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);
        Attendance::create(['tenant_id' => $tenant->id, 'staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'present']);

        $this->actingAs($user)->post(route('tenant.attendances.clock.in'))->assertSessionHas('success');

        $this->assertSame(1, Attendance::where('staff_id', $staff->id)->count());
        $this->assertNotNull(Attendance::where('staff_id', $staff->id)->first()->clock_in);
    }

    public function test_clock_out_records_the_time_after_clocking_in(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        $this->actingAs($user)->post(route('tenant.attendances.clock.in'));
        $this->travel(90)->minutes();

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.out'), ['staff_id' => $staff->id])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('clocked_out', true);

        $attendance = Attendance::where('staff_id', $staff->id)->first();
        $this->assertNotNull($attendance->clock_out);
        $this->assertSame('01:30', $attendance->worked_hours);
    }

    public function test_clock_out_without_clocking_in_is_rejected(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.out'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Pehle clock in karo!');

        $this->assertSame(0, Attendance::where('staff_id', $staff->id)->count());
    }

    public function test_second_clock_out_is_rejected_and_keeps_the_first_time(): void
    {
        $tenant = $this->setUpTenant();
        [$user, $staff] = $this->staffProfile($tenant);

        $this->actingAs($user)->post(route('tenant.attendances.clock.in'));
        $this->actingAs($user)->post(route('tenant.attendances.clock.out'));
        $firstClockOut = Attendance::where('staff_id', $staff->id)->first()->clock_out;

        $this->travel(10)->minutes();

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.out'))
            ->assertSessionHas('error', 'Already clock out ho chuka hai!');

        $this->assertTrue($firstClockOut->equalTo(Attendance::where('staff_id', $staff->id)->first()->clock_out));
    }

    // ── Unauthorized staff ids ──────────────────────────────────────

    public function test_a_user_cannot_clock_in_a_colleague_by_posting_their_staff_id(): void
    {
        $tenant = $this->setUpTenant();
        [$user]            = $this->staffProfile($tenant);
        [, $colleagueStaff] = $this->staffProfile($tenant);

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.in'), ['staff_id' => $colleagueStaff->id])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_a_user_cannot_clock_out_a_colleague(): void
    {
        $tenant = $this->setUpTenant();
        [$user]                      = $this->staffProfile($tenant);
        [$colleagueUser, $colleague] = $this->staffProfile($tenant);
        $this->actingAs($colleagueUser)->post(route('tenant.attendances.clock.in'));

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.out'), ['staff_id' => $colleague->id])
            ->assertForbidden();

        $this->assertNull(Attendance::where('staff_id', $colleague->id)->first()->clock_out);
    }

    public function test_a_staff_id_from_another_tenant_is_forbidden(): void
    {
        $tenant = $this->setUpTenant();
        $other  = Tenant::factory()->create();
        [$user]            = $this->staffProfile($tenant);
        [, $foreignStaff]  = $this->staffProfile($other);

        $this->actingAs($user)
            ->post(route('tenant.attendances.clock.in'), ['staff_id' => $foreignStaff->id])
            ->assertForbidden();

        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
    }

    public function test_a_nonexistent_staff_id_is_forbidden_not_a_server_error(): void
    {
        $tenant = $this->setUpTenant();
        [$user] = $this->staffProfile($tenant);

        $this->actingAs($user)->post(route('tenant.attendances.clock.in'), ['staff_id' => 999999])->assertForbidden();
    }

    public function test_a_non_numeric_staff_id_fails_validation(): void
    {
        $tenant = $this->setUpTenant();
        [$user] = $this->staffProfile($tenant);

        $this->actingAs($user)->post(route('tenant.attendances.clock.in'), ['staff_id' => 'abc'])->assertSessionHasErrors('staff_id');
    }

    public function test_a_user_without_a_staff_profile_cannot_clock_in(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.attendances.clock.in'))->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->setUpTenant();

        $this->post(route('tenant.attendances.clock.in'))->assertRedirect();
        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
    }

    // ── Manual entry by an admin (AttendanceRequest was an always-403 stub) ──

    public function test_admin_can_add_an_attendance_record_manually(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [, $staff] = $this->staffProfile($tenant);
        $date = today()->subDay()->toDateString();

        $this->actingAs($admin)
            ->post(route('tenant.attendances.store'), [
                'staff_id' => $staff->id, 'date' => $date, 'status' => 'present', 'clock_in' => '09:30', 'clock_out' => '18:00', 'notes' => 'Manual',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance = Attendance::where('staff_id', $staff->id)->first();
        $this->assertNotNull($attendance);
        $this->assertSame($date, $attendance->date->toDateString());
        $this->assertSame('09:30', $attendance->clock_in->format('H:i'));
        $this->assertSame('18:00', $attendance->clock_out->format('H:i'));
        $this->assertSame('08:30', $attendance->worked_hours);
    }

    public function test_manual_entry_without_times_is_allowed(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [, $staff] = $this->staffProfile($tenant);

        $this->actingAs($admin)
            ->post(route('tenant.attendances.store'), ['staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'absent'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('absent', Attendance::where('staff_id', $staff->id)->first()->status);
    }

    public function test_manual_entry_validates_input(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)
            ->post(route('tenant.attendances.store'), ['staff_id' => '', 'date' => '', 'status' => 'napping', 'clock_in' => '25:99'])
            ->assertSessionHasErrors(['staff_id', 'date', 'status', 'clock_in']);

        $this->assertSame(0, Attendance::count());
    }

    public function test_manual_entry_rejects_a_duplicate_staff_and_date_instead_of_erroring(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [, $staff] = $this->staffProfile($tenant);
        $payload = ['staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'present'];

        $this->actingAs($admin)->post(route('tenant.attendances.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('tenant.attendances.store'), $payload)->assertSessionHasErrors('date');

        $this->assertSame(1, Attendance::where('staff_id', $staff->id)->count());
    }

    public function test_manual_entry_rejects_staff_from_another_tenant(): void
    {
        $tenant = $this->setUpTenant();
        $other  = Tenant::factory()->create();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [, $foreign] = $this->staffProfile($other);

        $this->actingAs($admin)
            ->post(route('tenant.attendances.store'), ['staff_id' => $foreign->id, 'date' => today()->toDateString(), 'status' => 'present'])
            ->assertSessionHasErrors('staff_id');

        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
    }

    public function test_admin_can_edit_an_existing_attendance_record(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [, $staff] = $this->staffProfile($tenant);
        $attendance = Attendance::create(['tenant_id' => $tenant->id, 'staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'present']);

        $this->actingAs($admin)
            ->put(route('tenant.attendances.update', $attendance->id), [
                'staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'half_day', 'clock_in' => '10:00',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame('half_day', $attendance->status);
        $this->assertSame('10:00', $attendance->clock_in->format('H:i'));
    }

    public function test_staff_cannot_add_attendance_for_someone_else(): void
    {
        $tenant = $this->setUpTenant();
        [$user]     = $this->staffProfile($tenant);
        [, $victim] = $this->staffProfile($tenant);

        $this->actingAs($user)
            ->post(route('tenant.attendances.store'), ['staff_id' => $victim->id, 'date' => today()->toDateString(), 'status' => 'absent'])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }
}
