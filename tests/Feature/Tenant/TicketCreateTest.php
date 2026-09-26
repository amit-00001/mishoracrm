<?php

namespace Tests\Feature\Tenant;

use App\Models\Contact;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P1: GET /tickets/create returned 500. "create" was being captured by
// the /tickets/{id} show route (registered earlier) and fed to a numeric lookup.
class TicketCreateTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function enableTickets($tenant): void
    {
        $settings = $tenant->settings ?? [];
        $settings['modules']['tickets'] = true;
        $tenant->update(['settings' => $settings]);
    }

    public function test_create_ticket_form_renders_for_tenant_admin(): void
    {
        $tenant = $this->setUpTenant();
        $this->enableTickets($tenant);
        $admin = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)
            ->get(route('tenant.tickets.create'))
            ->assertOk()
            ->assertSee('New Ticket');
    }

    public function test_create_ticket_form_renders_for_staff_with_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->enableTickets($tenant);
        $staff = $this->makeUser($tenant, 'staff', ['tickets.view_own', 'tickets.create']);

        $this->actingAs($staff)->get(route('tenant.tickets.create'))->assertOk();
    }

    public function test_create_ticket_form_is_forbidden_without_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->enableTickets($tenant);
        $staff = $this->makeCustomRoleUser($tenant, ['tickets.view_own']);

        $this->actingAs($staff)->get(route('tenant.tickets.create'))->assertForbidden();
    }

    public function test_show_route_does_not_swallow_non_numeric_segments(): void
    {
        $tenant = $this->setUpTenant();
        $this->enableTickets($tenant);
        $admin = $this->makeUser($tenant, 'tenant_admin');

        // A non-numeric segment must never reach the id lookup (which 500s on
        // strict DBs). Only DELETE /tickets/{id} still matches the path, so a
        // GET is rejected as 405 rather than being handed to show().
        $status = $this->actingAs($admin)->get('/tickets/not-a-ticket')->getStatusCode();
        $this->assertContains($status, [404, 405]);
    }

    public function test_ticket_can_be_created_from_the_form(): void
    {
        $tenant = $this->setUpTenant();
        $this->enableTickets($tenant);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Rita Customer', 'phone' => '9876543210']);

        $response = $this->actingAs($admin)->post(route('tenant.tickets.store'), [
            'contact_id' => $contact->id,
            'subject'    => 'Printer on fire',
            'priority'   => 'high',
            'notify_via' => 'none',
        ]);

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('tenant.tickets.show', $ticket->id));
        $this->assertSame($tenant->id, $ticket->tenant_id);
        $this->assertSame('open', $ticket->status);
    }

    public function test_ticket_creation_requires_a_contact_and_subject(): void
    {
        $tenant = $this->setUpTenant();
        $this->enableTickets($tenant);
        $admin = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.tickets.store'), [])
            ->assertSessionHasErrors(['contact_id', 'subject', 'priority']);

        $this->assertSame(0, Ticket::count());
    }
}
