<?php

namespace Tests\Feature\Tenant;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class QuotationInvoiceCreateDiagnosticTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_quotation_create_page_loads(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->get(route('tenant.quotations.create'));

        $response->assertOk();
    }

    public function test_quotation_can_be_stored(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Diag Contact', 'phone' => '9000000001']);

        $response = $this->actingAs($admin)->post(route('tenant.quotations.store'), [
            'contact_id' => $contact->id,
            'date'       => now()->toDateString(),
            'items'      => [
                ['name' => 'Widget', 'quantity' => 2, 'rate' => 100, 'amount' => 200],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, \App\Models\Quotation::count());
    }

    public function test_invoice_create_page_loads(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->get(route('tenant.invoices.create'));

        $response->assertOk();
    }

    public function test_invoice_can_be_stored(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Diag Contact 2', 'phone' => '9000000002']);

        $response = $this->actingAs($admin)->post(route('tenant.invoices.store'), [
            'contact_id' => $contact->id,
            'date'       => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'items'      => [
                ['description' => 'Widget', 'quantity' => 2, 'rate' => 100],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, \App\Models\Invoice::count());
    }
}
