<?php

namespace Tests\Feature\SuperAdmin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P4: a stray "%" appeared beside the GST field on the Plans page. The
// .input-suffix rule that positions it lived only in the plan form's style partial,
// so the index page rendered the "%" unpositioned.
class PlansPageStylingTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_plans_index_ships_the_styles_that_position_the_gst_percent_suffix(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $html = $this->actingAs($admin)->get(route('superadmin.plans.index'))->assertOk()->getContent();

        // The suffix markup is on the page...
        $this->assertStringContainsString('class="input-suffix"', $html);
        // ...and so are the rules that absolutely-position it inside its wrapper.
        $this->assertMatchesRegularExpression('/\.input-prefix-wrap\s*\{[^}]*position:\s*relative/', $html);
        $this->assertMatchesRegularExpression('/\.input-suffix\s*\{[^}]*position:\s*absolute/', $html);
    }

    public function test_gst_percentage_can_still_be_saved(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)->post(route('superadmin.plans.update-gst'), ['gst_percentage' => 18])->assertRedirect();
    }
}
