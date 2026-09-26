<?php

namespace Tests\Feature\Tenant;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P2: editing a lead cleared company, designation, city, state and value
// (the edit form never posts them, and update() wrote every key as null), and
// assignment could silently fall back to "Unassigned".
class LeadPartialUpdateTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function richLead($tenant, array $overrides = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'tenant_id'           => $tenant->id,
            'name'                => 'QA Lead',
            'phone'               => '9000000002',
            'email'               => 'qa.lead@example.com',
            'company'             => 'Acme Pvt Ltd',
            'designation'         => 'Head of Ops',
            'city'                => 'Pune',
            'state'               => 'Maharashtra',
            'lead_value'          => 45000,
            'notes'               => 'Original notes',
            'status'              => 'contacted',
            'priority'            => 'high',
            'source'              => 'website',
            'expected_close_date' => now()->addDays(20)->startOfDay(),
        ], $overrides));
    }

    // Exactly what the edit form posts (see tenant/leads/edit.blade.php).
    private function editFormPayload(Lead $lead, array $overrides = []): array
    {
        return array_merge([
            'name'        => $lead->name,
            'phone'       => $lead->phone,
            'email'       => $lead->email,
            'source'      => $lead->source,
            'assigned_to' => $lead->assigned_to,
            'status'      => $lead->status,
            'priority'    => $lead->priority,
            'notes'       => $lead->notes,
        ], $overrides);
    }

    public function test_editing_a_lead_preserves_fields_the_form_does_not_post(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = $this->richLead($tenant);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), $this->editFormPayload($lead, ['name' => 'Renamed Lead']))
            ->assertRedirect(route('tenant.leads.show', $lead->id));

        $lead->refresh();
        $this->assertSame('Renamed Lead', $lead->name);
        $this->assertSame('Acme Pvt Ltd', $lead->company);
        $this->assertSame('Head of Ops', $lead->designation);
        $this->assertSame('Pune', $lead->city);
        $this->assertSame('Maharashtra', $lead->state);
        $this->assertEqualsWithDelta(45000.0, (float) $lead->lead_value, 0.01);
        $this->assertNotNull($lead->expected_close_date);
        $this->assertSame(now()->addDays(20)->toDateString(), $lead->expected_close_date->toDateString());
    }

    public function test_omitting_status_priority_and_source_keeps_the_stored_values(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = $this->richLead($tenant);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), ['name' => 'Only Name', 'phone' => $lead->phone])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame('Only Name', $lead->name);
        $this->assertSame('contacted', $lead->status);
        $this->assertSame('high', $lead->priority);
        $this->assertSame('website', $lead->source);
        $this->assertSame('qa.lead@example.com', $lead->email);
        $this->assertSame('Original notes', $lead->notes);
    }

    public function test_a_field_that_is_posted_empty_is_cleared_on_purpose(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = $this->richLead($tenant);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), $this->editFormPayload($lead, ['notes' => '', 'email' => '']))
            ->assertRedirect();

        $lead->refresh();
        $this->assertNull($lead->notes);
        $this->assertNull($lead->email);
        $this->assertSame('Acme Pvt Ltd', $lead->company); // untouched
    }

    public function test_fields_that_are_posted_are_updated(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = $this->richLead($tenant);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), $this->editFormPayload($lead, [
                'company' => 'New Co', 'city' => 'Mumbai', 'lead_value' => 99000,
            ]))
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame('New Co', $lead->company);
        $this->assertSame('Mumbai', $lead->city);
        $this->assertEqualsWithDelta(99000.0, (float) $lead->lead_value, 0.01);
        $this->assertSame('Head of Ops', $lead->designation); // still untouched
    }

    public function test_selected_staff_member_is_saved_and_shown_as_assignee(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $operator = User::factory()->create(['tenant_id' => $tenant->id, 'user_type' => 'staff', 'is_active' => true, 'name' => 'Test Operator']);
        $operator->assignRole('staff');
        $lead = $this->richLead($tenant, ['assigned_to' => null]);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), $this->editFormPayload($lead, ['assigned_to' => $operator->id]))
            ->assertRedirect();

        $this->assertSame($operator->id, $lead->fresh()->assigned_to);

        $this->actingAs($admin)->get(route('tenant.leads.show', $lead->id))
            ->assertOk()
            ->assertSee('Test Operator');
    }

    public function test_omitting_assigned_to_keeps_the_current_assignee(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $operator = $this->makeUser($tenant, 'staff');
        $lead     = $this->richLead($tenant, ['assigned_to' => $operator->id]);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), ['name' => 'Renamed', 'phone' => $lead->phone])
            ->assertRedirect();

        $this->assertSame($operator->id, $lead->fresh()->assigned_to);
    }

    public function test_assigning_to_a_user_from_another_tenant_is_rejected_not_silently_unassigned(): void
    {
        $tenant   = $this->setUpTenant();
        $other    = \App\Models\Tenant::factory()->create();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $outsider = $this->makeUser($other, 'staff');
        $operator = $this->makeUser($tenant, 'staff');
        $lead     = $this->richLead($tenant, ['assigned_to' => $operator->id]);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), $this->editFormPayload($lead, ['assigned_to' => $outsider->id]))
            ->assertSessionHasErrors('assigned_to');

        $this->assertSame($operator->id, $lead->fresh()->assigned_to); // unchanged, not wiped
    }

    public function test_an_explicitly_unassigned_selection_clears_the_assignee(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $operator = $this->makeUser($tenant, 'staff');
        $lead     = $this->richLead($tenant, ['assigned_to' => $operator->id]);

        $this->actingAs($admin)
            ->put(route('tenant.leads.update', $lead->id), $this->editFormPayload($lead, ['assigned_to' => '']))
            ->assertRedirect();

        $this->assertNull($lead->fresh()->assigned_to);
    }
}
