<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P1: "Start & Issue Materials" appeared to hang for 60s+. The form used
// a native confirm() dialog (freezes the tab, unanswerable by browser automation).
// The endpoint itself is fast — these tests pin the HTTP contract (redirect, DB
// updates, error handling) and that the page uses the non-blocking data-confirm modal.
class WorkOrderStartEndpointTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    /** @return array{0: \App\Models\Tenant, 1: \App\Models\User, 2: Product, 3: Product, 4: WorkOrder} */
    private function pendingWorkOrder(float $rodStock = 100, float $qty = 10): array
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $settings = $tenant->settings ?? [];
        $settings['modules']['manufacturing'] = true;
        $tenant->update(['settings' => $settings]);

        $chair = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'QA Chair', 'type' => 'finished_good',
            'rate' => 1500, 'cost_price' => 1500, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $rod = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'QA Rod', 'type' => 'raw_material',
            'rate' => 100, 'cost_price' => 60, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        StockService::receiveBatch($rod, $rodStock, 'ROD-1', null, null, null, 60);
        BillOfMaterialItem::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id, 'material_id' => $rod->id, 'quantity_per_unit' => 5,
        ]);

        $this->actingAs($admin);
        $wo = WorkOrderService::store(['product_id' => $chair->id, 'quantity' => $qty], $tenant->id, $admin->id);

        return [$tenant, $admin, $chair, $rod, $wo];
    }

    public function test_start_redirects_issues_materials_and_advances_the_work_order(): void
    {
        [, $admin, , $rod, $wo] = $this->pendingWorkOrder(100, 10);

        $this->actingAs($admin)
            ->post(route('tenant.work-orders.start', $wo->id))
            ->assertRedirect(route('tenant.work-orders.show', $wo->id))
            ->assertSessionHas('success');

        $wo->refresh();
        $rod->refresh();

        $this->assertSame('in_progress', $wo->status);
        $this->assertNotNull($wo->materials_issued_at);
        $this->assertEqualsWithDelta(3000.0, (float) $wo->material_cost_snapshot, 0.01); // 50 rods * 60
        $this->assertEqualsWithDelta(50.0, (float) $rod->current_stock, 0.01);           // 100 - 50 issued
        $this->assertEqualsWithDelta(0.0, (float) $rod->reserved_stock, 0.01);           // reservation released
    }

    public function test_start_with_insufficient_stock_returns_an_error_and_changes_nothing(): void
    {
        [, $admin, , $rod, $wo] = $this->pendingWorkOrder(rodStock: 100, qty: 10);
        // Stock drops after the work order was created (reservation only earmarks it).
        $rod->update(['current_stock' => 10]);
        \App\Models\ProductBatch::withoutGlobalScopes()->where('product_id', $rod->id)->update(['quantity' => 10]);

        $this->actingAs($admin)
            ->from(route('tenant.work-orders.show', $wo->id))
            ->post(route('tenant.work-orders.start', $wo->id))
            ->assertRedirect(route('tenant.work-orders.show', $wo->id))
            ->assertSessionHas('error');

        $this->assertSame('pending', $wo->fresh()->status);
        $this->assertEqualsWithDelta(10.0, (float) $rod->fresh()->current_stock, 0.01);
    }

    public function test_starting_an_already_started_work_order_is_rejected_without_re_issuing(): void
    {
        [, $admin, , $rod, $wo] = $this->pendingWorkOrder();

        $this->actingAs($admin)->post(route('tenant.work-orders.start', $wo->id));
        $stockAfterFirstStart = (float) $rod->fresh()->current_stock;

        $this->actingAs($admin)
            ->from(route('tenant.work-orders.show', $wo->id))
            ->post(route('tenant.work-orders.start', $wo->id))
            ->assertRedirect(route('tenant.work-orders.show', $wo->id))
            ->assertSessionHas('error', 'Only a pending work order can be started.');

        $this->assertEqualsWithDelta($stockAfterFirstStart, (float) $rod->fresh()->current_stock, 0.01);
    }

    public function test_start_is_forbidden_without_manage_permission(): void
    {
        [$tenant, , , $rod, $wo] = $this->pendingWorkOrder();
        $viewer = $this->makeCustomRoleUser($tenant, ['work_orders.view_all']);

        $this->actingAs($viewer)->post(route('tenant.work-orders.start', $wo->id))->assertForbidden();

        $this->assertSame('pending', $wo->fresh()->status);
        $this->assertEqualsWithDelta(100.0, (float) $rod->fresh()->current_stock, 0.01);
    }

    public function test_show_page_confirms_via_in_page_modal_not_a_blocking_native_dialog(): void
    {
        [, $admin, , , $wo] = $this->pendingWorkOrder();

        $html = $this->actingAs($admin)->get(route('tenant.work-orders.show', $wo->id))->assertOk()->getContent();

        preg_match_all('/<form\b.*?<\/form>/s', $html, $m);
        $forms = implode("\n", $m[0]);

        $this->assertStringNotContainsString('confirm(', $forms);
        $this->assertStringContainsString('data-confirm="Start production?', $forms);
        $this->assertStringContainsString('data-submit-once', $forms);
    }
}
