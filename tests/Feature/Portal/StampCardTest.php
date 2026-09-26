<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use App\Services\LoyaltyService;
use App\Services\WhatsappChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class StampCardTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function shop(array $rules = [], bool $loyalty = true, bool $portal = true): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = $loyalty;
        $settings['modules']['customer_portal'] = $portal;
        $settings['loyalty'] = array_replace(Tenant::LOYALTY_DEFAULTS, ['mode' => 'stamps', 'stamps_required' => 5, 'stamp_reward' => 'Free coffee'], $rules);
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function contact(Tenant $tenant, string $phone = '9876543210'): Contact
    {
        return Contact::create(['tenant_id' => $tenant->id, 'name' => 'Ravi', 'phone' => $phone]);
    }

    private function invoice(Tenant $tenant, Contact $contact, float $total, string $status = 'sent'): Invoice
    {
        return Invoice::create([
            'tenant_id'   => $tenant->id,
            'contact_id'  => $contact->id,
            'number'      => Invoice::generateNumber($tenant->id) . '-' . uniqid(),
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(7)->toDateString(),
            'items'       => [['description' => 'Item', 'quantity' => 1, 'rate' => $total]],
            'subtotal'    => $total,
            'total'       => $total,
            'paid_amount' => $status === 'paid' ? $total : 0,
            'status'      => $status,
        ]);
    }

    private function service(): LoyaltyService
    {
        return app(LoyaltyService::class);
    }

    // Stamps are capped at one a day — age the ledger so the next one is "tomorrow".
    private function nextDay(): void
    {
        LoyaltyTransaction::withoutGlobalScopes()->update(['created_at' => now()->subDay()]);
    }

    // ── Gating / mode ──────────────────────────────────────────

    public function test_points_only_shops_never_earn_stamps(): void
    {
        $tenant  = $this->shop(['mode' => 'points']);
        $contact = $this->contact($tenant);

        $this->assertNull($this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 500, 'paid')));
        $this->assertFalse($this->service()->awardStamp($contact->fresh())['ok']);
        $this->assertSame(0, $contact->fresh()->stamp_count);
    }

    public function test_stamps_need_the_loyalty_module(): void
    {
        $tenant  = $this->shop([], false, true);
        $contact = $this->contact($tenant);

        $this->assertNull($this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 500, 'paid')));
    }

    public function test_stamps_accrue_even_when_the_portal_is_off(): void
    {
        $tenant  = $this->shop([], true, false);
        $contact = $this->contact($tenant);

        $this->assertNotNull($this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 500, 'paid')));
        $this->assertSame(1, $contact->fresh()->stamp_count);
    }

    // ── Join QR is not a stamp QR ──────────────────────────────
    // The shop's printed QR only JOINS (wa.me → JOIN). Stamps come from the
    // customer's own wallet QR, scanned by staff — never from the join path.

    public function test_joining_through_the_welcome_qr_never_gives_a_stamp(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $tenant = $this->shop(['welcome_bonus_points' => 100, 'stamps_required' => 1]);
        $wa     = WhatsappSetting::create(['tenant_id' => $tenant->id, 'phone_number_id' => '1', 'access_token' => 't', 'is_connected' => true]);

        $this->assertTrue((new WhatsappChatbotService($wa))->handleLoyaltyWelcome('919812345678', 'JOIN', 'Ravi Sharma'));

        $contact = Contact::where('tenant_id', $tenant->id)->where('phone', '919812345678')->first();
        $this->assertNotNull($contact);

        // A repeat JOIN from the same (now existing) member stamps nothing either.
        $this->assertTrue((new WhatsappChatbotService($wa))->handleLoyaltyWelcome('919812345678', 'JOIN', 'Ravi Sharma'));

        $contact = $contact->fresh();
        $this->assertSame(0, (int) $contact->stamp_count);
        $this->assertSame(0, (int) $contact->stamp_rewards_earned);
        $this->assertSame(0, LoyaltyTransaction::withoutGlobalScopes()
            ->where('contact_id', $contact->id)
            ->whereIn('type', [LoyaltyTransaction::TYPE_STAMP, LoyaltyTransaction::TYPE_STAMP_REWARD])
            ->count());
    }

    // ── Earning from invoices ──────────────────────────────────

    public function test_paying_an_invoice_in_full_earns_one_stamp(): void
    {
        $tenant  = $this->shop();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 300);

        $this->actingAs($admin)->post(route('tenant.invoices.record_payment', $invoice->id), [
            'payments' => [['amount' => 300, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ])->assertRedirect();

        $fresh = $contact->fresh();
        $this->assertSame(1, $fresh->stamp_count);
        $this->assertSame(1, $fresh->stamps_lifetime);
        $this->assertNotNull($fresh->stamp_updated_at);
        $this->assertDatabaseHas('loyalty_transactions', [
            'contact_id' => $contact->id, 'type' => 'stamp', 'points' => 1, 'balance_after' => 1,
            'source_type' => Invoice::class, 'source_id' => $invoice->id,
        ]);
    }

    public function test_a_stamp_does_not_touch_the_points_balance(): void
    {
        $tenant  = $this->shop(['mode' => 'both']);
        $contact = $this->contact($tenant);

        $this->service()->awardStamp($contact->fresh());

        $fresh = $contact->fresh();
        $this->assertSame(0, $fresh->loyalty_points);
        $this->assertSame(0, $fresh->loyalty_lifetime_points);
        $this->assertSame(1, $fresh->stamp_count);
    }

    public function test_the_same_invoice_never_earns_twice(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 300, 'paid');

        $this->assertNotNull($this->service()->awardStampForInvoice($invoice));
        $this->nextDay();
        $this->assertNull($this->service()->awardStampForInvoice($invoice->fresh()));

        $this->assertSame(1, $contact->fresh()->stamp_count);
    }

    public function test_per_visit_stamps_are_capped_at_one_a_day(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);

        $this->assertNotNull($this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 300, 'paid')));
        $this->assertNull($this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 300, 'paid')));

        $this->assertSame(1, $contact->fresh()->stamp_count);
    }

    public function test_per_amount_stamps_scale_with_the_bill_and_skip_the_daily_cap(): void
    {
        $tenant  = $this->shop(['stamp_per' => 'amount', 'stamp_amount' => 100, 'stamps_required' => 10]);
        $contact = $this->contact($tenant);

        $this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 450, 'paid'));
        $this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 250, 'paid'));

        $this->assertSame(6, $contact->fresh()->stamp_count); // 4 + 2
    }

    public function test_per_amount_bills_below_one_unit_earn_nothing(): void
    {
        $tenant  = $this->shop(['stamp_per' => 'amount', 'stamp_amount' => 100]);
        $contact = $this->contact($tenant);

        $this->assertNull($this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 60, 'paid')));
    }

    public function test_a_fully_redeemed_free_visit_earns_no_stamp(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 100, 'paid');
        $invoice->forceFill(['loyalty_discount' => 100])->save();

        $this->assertNull($this->service()->awardStampForInvoice($invoice->fresh()));
    }

    // ── Cards filling up ───────────────────────────────────────

    public function test_filling_a_card_resets_it_and_unlocks_a_reward(): void
    {
        $tenant  = $this->shop(['stamps_required' => 3]);
        $contact = $this->contact($tenant);

        foreach ([1, 2] as $i) {
            $this->assertTrue($this->service()->awardStamp($contact->fresh())['ok']);
            $this->nextDay();
        }
        $this->assertSame(2, $contact->fresh()->stamp_count);

        $third = $this->service()->awardStamp($contact->fresh());

        $this->assertTrue($third['ok']);
        $this->assertSame(1, $third['unlocked']);
        $this->assertStringContainsString('Card complete', $third['message']);

        $fresh = $contact->fresh();
        $this->assertSame(0, $fresh->stamp_count);
        $this->assertSame(1, $fresh->stamp_rewards_earned);
        $this->assertSame(3, $fresh->stamps_lifetime);
        $this->assertDatabaseHas('loyalty_transactions', ['contact_id' => $contact->id, 'type' => 'stamp_reward', 'points' => 1]);
    }

    public function test_one_big_bill_can_complete_more_than_one_card(): void
    {
        $tenant  = $this->shop(['stamp_per' => 'amount', 'stamp_amount' => 100, 'stamps_required' => 3]);
        $contact = $this->contact($tenant);

        $this->service()->awardStampForInvoice($this->invoice($tenant, $contact, 700, 'paid'));

        $fresh = $contact->fresh();
        $this->assertSame(1, $fresh->stamp_count);          // 7 stamps = 2 cards + 1
        $this->assertSame(2, $fresh->stamp_rewards_earned);
    }

    public function test_a_second_manual_stamp_the_same_day_is_refused(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);

        $this->assertTrue($this->service()->awardStamp($contact->fresh())['ok']);
        $second = $this->service()->awardStamp($contact->fresh());

        $this->assertFalse($second['ok']);
        $this->assertStringContainsString('already got a stamp today', $second['message']);
        $this->assertSame(1, $contact->fresh()->stamp_count);
    }

    // ── Claiming a reward ──────────────────────────────────────

    public function test_claiming_a_reward_uses_one_and_notes_the_invoice(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);
        $contact->forceFill(['stamp_rewards_earned' => 2])->save();
        $invoice = $this->invoice($tenant, $contact, 0);

        $result = $this->service()->redeemStampReward($contact->fresh(), $invoice, null);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $contact->fresh()->stamp_rewards_earned);
        $this->assertStringContainsString('Free coffee', $invoice->fresh()->notes);
        $this->assertSame('Free coffee', $invoice->fresh()->loyalty_reward);
        $this->assertDatabaseHas('loyalty_transactions', ['contact_id' => $contact->id, 'type' => 'stamp_reward', 'points' => -1]);
    }

    public function test_claiming_with_no_reward_unlocked_fails_cleanly(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);

        $result = $this->service()->redeemStampReward($contact->fresh());

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $contact->fresh()->stamp_rewards_earned);
    }

    // ── Reversal ───────────────────────────────────────────────

    public function test_an_invoice_that_stops_being_paid_takes_its_stamp_back_once(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 300, 'paid');

        $this->service()->awardStampForInvoice($invoice);
        $this->assertSame(1, $contact->fresh()->stamp_count);

        $this->service()->reverseStampForInvoice($invoice->fresh());
        $this->service()->reverseStampForInvoice($invoice->fresh());

        $fresh = $contact->fresh();
        $this->assertSame(0, $fresh->stamp_count);
        $this->assertSame(0, $fresh->stamps_lifetime);
        $this->assertSame(1, LoyaltyTransaction::withoutGlobalScopes()->where('description', 'like', 'Stamp reversed%')->count());
    }

    public function test_unpaying_via_the_invoice_status_endpoint_reverses_the_stamp(): void
    {
        $tenant  = $this->shop();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 300);

        $this->actingAs($admin)->post(route('tenant.invoices.record_payment', $invoice->id), [
            'payments' => [['amount' => 300, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ]);
        $this->assertSame(1, $contact->fresh()->stamp_count);

        $this->actingAs($admin)->post(route('tenant.invoices.update_status', $invoice->id), ['status' => 'sent'])->assertRedirect();

        $this->assertSame(0, $contact->fresh()->stamp_count);
    }

    // ── Idle expiry ────────────────────────────────────────────

    public function test_expiry_resets_only_idle_part_filled_cards(): void
    {
        $tenant = $this->shop(['stamp_expiry_days' => 30]);
        $idle   = $this->contact($tenant, '9000000001');
        $recent = $this->contact($tenant, '9000000002');
        $empty  = $this->contact($tenant, '9000000003');

        $idle->forceFill(['stamp_count' => 2, 'stamp_updated_at' => now()->subDays(40)])->save();
        $recent->forceFill(['stamp_count' => 2, 'stamp_updated_at' => now()->subDays(10)])->save();
        $empty->forceFill(['stamp_count' => 0, 'stamp_updated_at' => now()->subDays(90)])->save();

        $this->assertSame(1, $this->service()->expireDueStamps());

        $this->assertSame(0, $idle->fresh()->stamp_count);
        $this->assertSame(2, $recent->fresh()->stamp_count);
        $this->assertDatabaseHas('loyalty_transactions', ['contact_id' => $idle->id, 'type' => 'stamp', 'points' => -2, 'balance_after' => 0]);
    }

    public function test_expiry_never_touches_shops_with_it_switched_off(): void
    {
        $tenant  = $this->shop(['stamp_expiry_days' => 0]);
        $contact = $this->contact($tenant);
        $contact->forceFill(['stamp_count' => 2, 'stamp_updated_at' => now()->subDays(400)])->save();

        $this->assertSame(0, $this->service()->expireDueStamps());
        $this->assertSame(2, $contact->fresh()->stamp_count);
    }

    public function test_expiry_never_removes_an_unclaimed_reward(): void
    {
        $tenant  = $this->shop(['stamp_expiry_days' => 30]);
        $contact = $this->contact($tenant);
        $contact->forceFill(['stamp_count' => 1, 'stamp_rewards_earned' => 1, 'stamp_updated_at' => now()->subDays(60)])->save();

        $this->service()->expireDueStamps();

        $this->assertSame(1, $contact->fresh()->stamp_rewards_earned);
    }

    public function test_expire_command_runs(): void
    {
        $tenant  = $this->shop(['stamp_expiry_days' => 30]);
        $contact = $this->contact($tenant);
        $contact->forceFill(['stamp_count' => 3, 'stamp_updated_at' => now()->subDays(45)])->save();

        $this->artisan('loyalty:expire-stamps')->assertSuccessful();

        $this->assertSame(0, $contact->fresh()->stamp_count);
    }

    // ── Customer message ───────────────────────────────────────

    public function test_customer_is_messaged_when_their_card_fills_if_opted_in(): void
    {
        Http::fake();
        $tenant = $this->shop(['stamps_required' => 1, 'notify_customers' => true]);
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'phone_number_id' => '1', 'access_token' => 't', 'is_connected' => true]);
        $contact = $this->contact($tenant);

        $this->service()->awardStamp($contact->fresh());

        Http::assertSent(fn ($request) => str_contains($request['text']['body'] ?? '', 'card is full'));
    }

    public function test_customer_is_not_messaged_when_opted_out(): void
    {
        Http::fake();
        $tenant = $this->shop(['stamps_required' => 1, 'notify_customers' => false]);
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'phone_number_id' => '1', 'access_token' => 't', 'is_connected' => true]);
        $contact = $this->contact($tenant);

        $this->service()->awardStamp($contact->fresh());

        Http::assertNothingSent();
    }

    // ── Settings ───────────────────────────────────────────────

    private function baseRules(array $extra = []): array
    {
        return array_merge([
            'points_per_amount' => 1, 'amount_per_point_block' => 100, 'redeem_points_block' => 100,
            'redeem_value' => 10, 'min_discount' => 0, 'max_discount_percent' => 20,
            'expiry_months' => 12, 'max_points_per_day' => 500,
            'tiers' => ['bronze' => 0, 'silver' => 2000, 'gold' => 10000],
        ], $extra);
    }

    public function test_stamp_settings_save_and_show_on_the_form_when_the_portal_is_on(): void
    {
        $tenant = $this->shop(['mode' => 'points', 'stamp_reward' => '']);
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), $this->baseRules([
            'mode' => 'both', 'stamps_required' => 8, 'stamp_reward' => '  Free 1kg rice ', 'stamp_per' => 'amount',
            'stamp_amount' => 250, 'stamp_expiry_days' => 90,
        ]))->assertRedirect();

        $s = $tenant->fresh()->loyaltySettings();
        $this->assertSame('both', $s['mode']);
        $this->assertEquals(8, $s['stamps_required']);
        $this->assertSame('Free 1kg rice', $s['stamp_reward']);
        $this->assertSame('amount', $s['stamp_per']);
        $this->assertEquals(250, $s['stamp_amount']);
        $this->assertEquals(90, $s['stamp_expiry_days']);

        $this->actingAs($admin->fresh())->get(route('tenant.loyalty.settings'))
            ->assertOk()->assertSee('Stamp card')->assertSee('Free 1kg rice');
    }

    public function test_saving_rules_without_the_stamp_fields_keeps_the_existing_stamp_setup(): void
    {
        // A portal-off shop's form has no stamp inputs — saving must not wipe them.
        $tenant = $this->shop(['mode' => 'stamps', 'stamps_required' => 9, 'stamp_reward' => 'Free tea'], true, false);
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), $this->baseRules())->assertRedirect();

        $s = $tenant->fresh()->loyaltySettings();
        $this->assertSame('stamps', $s['mode']);
        $this->assertEquals(9, $s['stamps_required']);
        $this->assertSame('Free tea', $s['stamp_reward']);
    }

    public function test_stamp_settings_are_validated(): void
    {
        $tenant = $this->shop();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), $this->baseRules(['mode' => 'bogus']))->assertSessionHasErrors('mode');
        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), $this->baseRules(['stamps_required' => 0]))->assertSessionHasErrors('stamps_required');
        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), $this->baseRules(['stamps_required' => 99]))->assertSessionHasErrors('stamps_required');
    }
}
