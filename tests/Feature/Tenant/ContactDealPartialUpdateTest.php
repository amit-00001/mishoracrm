<?php

namespace Tests\Feature\Tenant;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Services\DealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P2 (contact / deal edits):
//  - contact "Linked Lead" selection did not show after saving
//  - deal edit lost the expected close date (date input rendered blank)
//  - deal moved to Won kept the old 10% probability
class ContactDealPartialUpdateTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    // ── Contact → linked lead ───────────────────────────────────────

    public function test_selecting_a_linked_lead_persists_and_is_shown_on_the_contact(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $lead    = Lead::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Linkable Lead']);
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'QA Contact', 'phone' => '9000000004']);

        $this->actingAs($admin)
            ->put(route('tenant.contacts.update', $contact->id), [
                'name' => 'QA Contact', 'phone' => '9000000004', 'lead_id' => $lead->id,
            ])
            ->assertRedirect();

        $this->assertSame($lead->id, $contact->fresh()->lead_id);

        $this->actingAs($admin)->get(route('tenant.contacts.show', $contact->id))
            ->assertOk()
            ->assertSee('Linked Lead')
            ->assertSee('Linkable Lead');
    }

    public function test_the_contact_edit_form_preselects_the_saved_lead(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $lead    = Lead::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Linkable Lead']);
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'QA Contact', 'phone' => '9000000004', 'lead_id' => $lead->id]);

        $html = $this->actingAs($admin)->get(route('tenant.contacts.edit', $contact->id))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="' . $lead->id . '"\s+selected/', $html);
    }

    public function test_editing_a_contact_without_lead_id_keeps_the_existing_link_and_fields(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $lead    = Lead::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::create([
            'tenant_id' => $tenant->id, 'name' => 'QA Contact', 'phone' => '9000000004',
            'lead_id' => $lead->id, 'company' => 'Acme', 'city' => 'Pune',
        ]);

        $this->actingAs($admin)
            ->put(route('tenant.contacts.update', $contact->id), ['name' => 'Renamed', 'phone' => '9000000004'])
            ->assertRedirect();

        $contact->refresh();
        $this->assertSame('Renamed', $contact->name);
        $this->assertSame($lead->id, $contact->lead_id);
        $this->assertSame('Acme', $contact->company);
        $this->assertSame('Pune', $contact->city);
    }

    public function test_a_lead_from_another_tenant_cannot_be_linked(): void
    {
        $tenant  = $this->setUpTenant();
        $other   = \App\Models\Tenant::factory()->create();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $foreign = Lead::factory()->create(['tenant_id' => $other->id]);
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'QA Contact', 'phone' => '9000000004']);

        $this->actingAs($admin)
            ->put(route('tenant.contacts.update', $contact->id), ['name' => 'QA Contact', 'phone' => '9000000004', 'lead_id' => $foreign->id])
            ->assertSessionHasErrors('lead_id');

        $this->assertNull($contact->fresh()->lead_id);
    }

    public function test_a_deleted_lead_cannot_be_linked(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $lead    = Lead::factory()->create(['tenant_id' => $tenant->id]);
        $lead->delete();
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'QA Contact', 'phone' => '9000000004']);

        $this->actingAs($admin)
            ->put(route('tenant.contacts.update', $contact->id), ['name' => 'QA Contact', 'phone' => '9000000004', 'lead_id' => $lead->id])
            ->assertSessionHasErrors('lead_id');

        $this->assertNull($contact->fresh()->lead_id);
    }

    // ── Deal → close date ───────────────────────────────────────────

    public function test_deal_edit_form_renders_the_close_date_in_a_date_input_compatible_format(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $date   = now()->addDays(15)->startOfDay();
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'expected_close_date' => $date]);

        $html = $this->actingAs($admin)->get(route('tenant.deals.edit', $deal->id))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="expected_close_date"[^>]*value="' . $date->format('Y-m-d') . '"/s', $html);
        $this->assertStringNotContainsString('value="' . $date->format('Y-m-d') . ' 00:00:00"', $html);
    }

    public function test_editing_a_deal_keeps_the_expected_close_date_when_the_form_round_trips(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $date   = now()->addDays(15)->startOfDay();
        $deal   = Deal::factory()->create([
            'tenant_id' => $tenant->id, 'stage' => 'proposal', 'probability' => 30, 'expected_close_date' => $date,
        ]);

        // Post back exactly what the rendered form contains.
        $this->actingAs($admin)
            ->put(route('tenant.deals.update', $deal->id), [
                'title' => 'Renamed deal', 'value' => $deal->value, 'stage' => 'proposal',
                'probability' => 30, 'expected_close_date' => $date->format('Y-m-d'),
            ])
            ->assertRedirect();

        $deal->refresh();
        $this->assertSame('Renamed deal', $deal->title);
        $this->assertSame($date->toDateString(), $deal->expected_close_date->toDateString());
    }

    public function test_a_partial_payload_leaves_omitted_deal_fields_untouched(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $date   = now()->addDays(15)->startOfDay();
        $deal   = Deal::factory()->create([
            'tenant_id' => $tenant->id, 'stage' => 'proposal', 'probability' => 45, 'notes' => 'keep me', 'expected_close_date' => $date,
        ]);

        DealService::update($deal, ['title' => 'Only the title changed']);

        $deal->refresh();
        $this->assertSame('Only the title changed', $deal->title);
        $this->assertSame('proposal', $deal->stage);
        $this->assertSame(45, $deal->probability);
        $this->assertSame('keep me', $deal->notes);
        $this->assertSame($date->toDateString(), $deal->expected_close_date->toDateString());
    }

    // ── Deal → Won probability ──────────────────────────────────────

    public function test_moving_a_deal_to_won_via_the_edit_form_sets_probability_to_100(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new', 'probability' => 10]);

        // The form re-posts the stored 10% alongside the new stage.
        $this->actingAs($admin)
            ->put(route('tenant.deals.update', $deal->id), [
                'title' => $deal->title, 'value' => $deal->value, 'stage' => 'won', 'probability' => 10,
            ])
            ->assertRedirect();

        $deal->refresh();
        $this->assertSame('won', $deal->stage);
        $this->assertSame(100, $deal->probability);
        $this->assertNotNull($deal->actual_close_date);
    }

    public function test_moving_a_deal_to_lost_sets_probability_to_zero(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation', 'probability' => 60]);

        $this->actingAs($admin)
            ->put(route('tenant.deals.update', $deal->id), [
                'title' => $deal->title, 'value' => $deal->value, 'stage' => 'lost', 'probability' => 60,
            ])
            ->assertRedirect();

        $this->assertSame(0, $deal->fresh()->probability);
    }

    public function test_changing_stage_with_untouched_probability_adopts_the_new_stage_default(): void
    {
        $tenant = $this->setUpTenant();
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new', 'probability' => 10]);

        DealService::update($deal, ['title' => $deal->title, 'value' => $deal->value, 'stage' => 'negotiation', 'probability' => 10]);

        $this->assertSame(60, $deal->fresh()->probability);
    }

    public function test_an_explicit_probability_on_an_open_stage_is_respected(): void
    {
        $tenant = $this->setUpTenant();
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new', 'probability' => 10]);

        DealService::update($deal, ['title' => $deal->title, 'value' => $deal->value, 'stage' => 'proposal', 'probability' => 55]);

        $deal->refresh();
        $this->assertSame('proposal', $deal->stage);
        $this->assertSame(55, $deal->probability);
    }

    public function test_a_blank_probability_falls_back_to_the_stage_default(): void
    {
        $tenant = $this->setUpTenant();
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'proposal', 'probability' => 30]);

        DealService::update($deal, ['title' => $deal->title, 'value' => $deal->value, 'stage' => 'proposal', 'probability' => null]);

        $this->assertSame(30, $deal->fresh()->probability);
    }

    public function test_deal_form_with_no_stage_change_keeps_a_custom_probability(): void
    {
        $tenant = $this->setUpTenant();
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'proposal', 'probability' => 45]);

        DealService::update($deal, ['title' => 'x', 'value' => $deal->value, 'stage' => 'proposal', 'probability' => 45]);

        $this->assertSame(45, $deal->fresh()->probability);
    }
}
