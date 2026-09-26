<?php

namespace Tests\Feature\Tenant;

use App\Models\Department;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit — purchase requests:
//  P1  approve "hung": the approve form used a native confirm() dialog that freezes
//      the tab (and cannot be answered by browser automation). The server side
//      completes immediately; the page must use the in-page data-confirm modal.
//  P2  empty submit showed no validation and a re-submitted form could create a
//      duplicate request.
class PurchaseRequestWorkflowTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date'  => now()->toDateString(),
            'items' => [['name' => 'Office Chairs', 'quantity' => 5]],
        ], $overrides);
    }

    // ── P1: approve ─────────────────────────────────────────────────

    public function test_approve_completes_redirects_and_updates_the_database(): void
    {
        $tenant    = $this->setUpTenant();
        $admin     = $this->makeUser($tenant, 'tenant_admin');
        $requester = $this->makeUser($tenant, 'staff');
        $pr = PurchaseRequest::factory()->create([
            'tenant_id' => $tenant->id, 'requested_by' => $requester->id, 'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('tenant.purchase-requests.approve', $pr->id))
            ->assertRedirect(route('tenant.purchase-requests.show', $pr->id))
            ->assertSessionHas('success');

        $pr->refresh();
        $this->assertSame('converted', $pr->status);
        $this->assertSame($admin->id, $pr->approved_by);
        $this->assertNotNull($pr->approved_at);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertNotNull($po);
        $this->assertSame('draft', $po->status);

        $this->assertSame(1, Notification::where('user_id', $requester->id)->where('type', 'purchase_request.approved')->count());
    }

    public function test_approving_twice_does_not_create_a_second_purchase_order(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr = PurchaseRequest::factory()->create([
            'tenant_id' => $tenant->id, 'requested_by' => $admin->id, 'status' => 'pending',
        ]);

        $this->actingAs($admin)->post(route('tenant.purchase-requests.approve', $pr->id));
        $this->actingAs($admin)->post(route('tenant.purchase-requests.approve', $pr->id))
            ->assertRedirect(route('tenant.purchase-requests.show', $pr->id));

        $this->assertSame(1, PurchaseOrder::where('purchase_request_id', $pr->id)->count());
    }

    public function test_show_page_confirms_via_in_page_modal_not_a_blocking_native_dialog(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr = PurchaseRequest::factory()->create([
            'tenant_id' => $tenant->id, 'requested_by' => $admin->id, 'status' => 'pending',
        ]);

        $html = $this->actingAs($admin)->get(route('tenant.purchase-requests.show', $pr->id))
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('confirm(', $this->formsHtml($html));
        $this->assertStringContainsString('data-confirm="Approve this request?', $html);
        $this->assertStringContainsString('data-confirm="Reject this request?', $html);
        $this->assertStringContainsString('data-submit-once', $html);
    }

    // ── P2: validation ──────────────────────────────────────────────

    public function test_empty_submission_is_rejected_and_creates_nothing(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)
            ->post(route('tenant.purchase-requests.store'), [
                'date' => '', 'department_id' => '', 'reason' => '',
                'items' => [['product_id' => '', 'name' => '', 'description' => '', 'quantity' => '', 'reason' => '']],
            ])
            ->assertSessionHasErrors(['date', 'items.0.name', 'items.0.quantity']);

        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_submission_without_items_is_rejected(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)
            ->post(route('tenant.purchase-requests.store'), ['date' => now()->toDateString()])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_zero_quantity_is_rejected(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)
            ->post(route('tenant.purchase-requests.store'), $this->validPayload(['items' => [['name' => 'Bolts', 'quantity' => 0]]]))
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_validation_errors_are_visible_on_the_form(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)
            ->from(route('tenant.purchase-requests.create'))
            ->post(route('tenant.purchase-requests.store'), [
                'date' => now()->toDateString(),
                'items' => [['name' => '', 'quantity' => '']],
            ])
            ->assertRedirect(route('tenant.purchase-requests.create'));

        $this->actingAs($staff)->get(route('tenant.purchase-requests.create'))
            ->assertOk()
            ->assertSee('Item name is required.')
            ->assertSee('Quantity is required.');
    }

    public function test_create_form_ships_client_side_validation_and_double_submit_guard(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $html = $this->actingAs($staff)->get(route('tenant.purchase-requests.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="prForm" data-submit-once', $html);
        $this->assertStringContainsString('function validateForm()', $html);
        $this->assertMatchesRegularExpression('/name="submission_token" value="[0-9a-f-]{36}"/', $html);
    }

    public function test_department_from_another_tenant_is_rejected(): void
    {
        $tenant = $this->setUpTenant();
        $other  = \App\Models\Tenant::factory()->create();
        $staff  = $this->makeUser($tenant, 'staff');
        $foreignDept = Department::withoutGlobalScopes()->create(['tenant_id' => $other->id, 'name' => 'Foreign']);

        $this->actingAs($staff)
            ->post(route('tenant.purchase-requests.store'), $this->validPayload(['department_id' => $foreignDept->id]))
            ->assertSessionHasErrors('department_id');

        $this->assertSame(0, PurchaseRequest::count());
    }

    // ── P2: duplicate submission ────────────────────────────────────

    public function test_resubmitting_the_same_form_returns_the_original_request(): void
    {
        $tenant   = $this->setUpTenant();
        $staff    = $this->makeUser($tenant, 'staff');
        $approver = $this->makeUser($tenant, 'tenant_admin');
        $payload  = $this->validPayload(['submission_token' => 'form-token-123']);

        $first  = $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $payload);
        $second = $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $payload);

        $this->assertSame(1, PurchaseRequest::count());
        $pr = PurchaseRequest::first();
        $first->assertRedirect(route('tenant.purchase-requests.show', $pr->id));
        $second->assertRedirect(route('tenant.purchase-requests.show', $pr->id))->assertSessionHas('success');

        // Approvers hear about it once, not once per click.
        $this->assertSame(1, Notification::where('user_id', $approver->id)->where('type', 'purchase_request.submitted')->count());
    }

    public function test_distinct_forms_create_distinct_requests(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $this->validPayload(['submission_token' => 'a']));
        $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $this->validPayload(['submission_token' => 'b']));

        $this->assertSame(2, PurchaseRequest::count());
    }

    public function test_submissions_without_a_token_still_work(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $this->validPayload());
        $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $this->validPayload());

        $this->assertSame(2, PurchaseRequest::count());
    }

    public function test_database_rejects_a_duplicate_token_within_a_tenant(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        PurchaseRequest::factory()->create(['tenant_id' => $tenant->id, 'requested_by' => $staff->id, 'submission_token' => 'dup']);

        $this->expectException(UniqueConstraintViolationException::class);
        PurchaseRequest::factory()->create(['tenant_id' => $tenant->id, 'requested_by' => $staff->id, 'submission_token' => 'dup']);
    }

    public function test_the_same_token_is_allowed_in_different_tenants(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = \App\Models\Tenant::factory()->create();
        $userA = $this->makeUser($tenantA, 'staff');
        $userB = $this->makeUser($tenantB, 'staff');

        PurchaseRequest::factory()->create(['tenant_id' => $tenantA->id, 'requested_by' => $userA->id, 'submission_token' => 'same']);
        PurchaseRequest::factory()->create(['tenant_id' => $tenantB->id, 'requested_by' => $userB->id, 'submission_token' => 'same']);

        $this->assertSame(2, PurchaseRequest::withoutGlobalScopes()->where('submission_token', 'same')->count());
    }

    public function test_update_cannot_overwrite_the_submission_token(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr = PurchaseRequest::factory()->create([
            'tenant_id' => $tenant->id, 'requested_by' => $admin->id, 'status' => 'pending', 'submission_token' => 'original',
        ]);

        $this->actingAs($admin);
        PurchaseRequestService::update($pr, $this->validPayload(['submission_token' => 'tampered', 'reason' => 'edited']));

        $pr->refresh();
        $this->assertSame('original', $pr->submission_token);
        $this->assertSame('edited', $pr->reason);
    }

    // Everything inside <form ...> tags, so the layout's own scripts don't count.
    private function formsHtml(string $html): string
    {
        preg_match_all('/<form\b.*?<\/form>/s', $html, $m);

        return implode("\n", $m[0]);
    }
}
