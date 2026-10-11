<?php

namespace Tests\Feature\Tenant;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// The chatbot builder upgrade: smarter matching, list / button / link replies,
// media, welcome flow, business hours, and the builder helpers.
class WhatsappChatbotUpgradeTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private const WA = '919876543210';

    private function gatewayTenant(array $extra = [], mixed $response = null): array
    {
        PlatformSetting::set('wa_gateway_enabled', '1');
        PlatformSetting::set('wa_gateway_base_url', 'https://gw.test/api/v1/gateway');
        PlatformSetting::set('wa_gateway_api_key', 'gw_testkey');
        PlatformSetting::set('wa_gateway_webhook_secret', 'whsec_test');
        Http::fake(['gw.test/*' => $response ?? Http::response(['success' => true, 'data' => ['wamid' => 'wamid.OUT']], 201)]);

        $tenant  = $this->setUpTenant();
        $setting = WhatsappSetting::create(array_merge([
            'tenant_id' => $tenant->id, 'connection_mode' => 'gateway', 'gateway_workspace_id' => 'tenant-' . $tenant->id,
            'phone_number_id' => 'PN1', 'waba_id' => 'WB1', 'is_connected' => true, 'chatbot_enabled' => true,
        ], $extra));

        return [$tenant, $setting];
    }

    private function flow(Tenant $tenant, array $attrs): WhatsappChatbotFlow
    {
        return WhatsappChatbotFlow::create(array_merge([
            'tenant_id' => $tenant->id, 'name' => 'Flow', 'trigger_keywords' => ['hello'], 'keyword_match' => 'contains',
            'response_message' => 'Hi there!', 'is_active' => true,
        ], $attrs));
    }

    private function say(WhatsappSetting $setting, string $text, ?string $buttonId = null): bool
    {
        return (new WhatsappChatbotService($setting))->handleIncomingMessage(self::WA, $text, 'Asha', $buttonId);
    }

    // The last JSON body sent to the gateway.
    private function lastSent(): array
    {
        $recorded = Http::recorded();

        return $recorded->last()[0]->data();
    }

    // ── Matching ─────────────────────────────────────────────────

    public function test_short_keywords_match_whole_words_only(): void
    {
        $flow = new WhatsappChatbotFlow(['trigger_keywords' => ['hi'], 'keyword_match' => 'contains']);

        $this->assertGreaterThan(0, $flow->score('Hi!'));
        $this->assertGreaterThan(0, $flow->score('hi there'));
        $this->assertSame(0, $flow->score('which one is this'));
    }

    public function test_typos_and_other_languages_still_match(): void
    {
        $price = new WhatsappChatbotFlow(['trigger_keywords' => ['price', 'kitna', 'कीमत'], 'keyword_match' => 'contains']);

        $this->assertGreaterThan(0, $price->score('pricee please'));
        $this->assertGreaterThan(0, $price->score('prise?'));
        $this->assertGreaterThan(0, $price->score('ye kitna hai'));
        $this->assertGreaterThan(0, $price->score('इसकी कीमत क्या है'));
        $this->assertSame(0, $price->score('where are you'));
    }

    public function test_exact_mode_needs_the_whole_message(): void
    {
        $flow = new WhatsappChatbotFlow(['trigger_keywords' => ['menu'], 'keyword_match' => 'exact']);

        $this->assertGreaterThan(0, $flow->score(' MENU! '));
        $this->assertSame(0, $flow->score('show me the menu'));
    }

    public function test_the_most_specific_flow_wins_over_the_first_one(): void
    {
        [$tenant] = $this->gatewayTenant();
        $general  = $this->flow($tenant, ['name' => 'Price', 'trigger_keywords' => ['price'], 'sort_order' => 1]);
        $refund   = $this->flow($tenant, ['name' => 'Refund price', 'trigger_keywords' => ['refund price'], 'sort_order' => 2]);

        $best = WhatsappChatbotFlow::best([$general, $refund], 'what is the refund price?');

        $this->assertSame('Refund price', $best['flow']->name);
    }

    // ── Reply styles ─────────────────────────────────────────────

    public function test_a_list_flow_sends_a_whatsapp_list_menu(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, [
            'reply_type' => 'list', 'header_text' => 'Acme', 'footer_text' => 'Tap one', 'list_button_text' => 'View options',
            'quick_replies' => [['title' => 'Pricing', 'description' => 'Plans and rates', 'next_flow_id' => null], ['title' => 'Address']],
        ]);

        $this->say($setting, 'hello');

        $sent = $this->lastSent();
        $this->assertSame('interactive', $sent['type']);
        $this->assertSame('list', $sent['interactive']['type']);
        $this->assertSame('View options', $sent['interactive']['action']['button']);
        $this->assertSame('Acme', $sent['interactive']['header']['text']);
        $this->assertSame('Tap one', $sent['interactive']['footer']['text']);
        $rows = $sent['interactive']['action']['sections'][0]['rows'];
        $this->assertSame(['Pricing', 'Address'], array_column($rows, 'title'));
        $this->assertSame('Plans and rates', $rows[0]['description']);
    }

    public function test_reply_buttons_carry_a_header_and_footer(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, [
            'reply_type' => 'buttons', 'header_text' => 'Acme', 'footer_text' => 'Reply any time',
            'quick_replies' => [['title' => 'Yes'], ['title' => 'No']],
        ]);

        $this->say($setting, 'hello');

        $sent = $this->lastSent();
        $this->assertSame('buttons', $sent['type']);
        $this->assertSame('Acme', $sent['buttons']['header']);
        $this->assertSame('Reply any time', $sent['buttons']['footer']);
        $this->assertCount(2, $sent['buttons']['items']);
    }

    public function test_a_website_button_is_sent_as_a_cta_url(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, [
            'reply_type' => 'link', 'quick_replies' => [['title' => 'Book now', 'url' => 'https://example.com/book']],
        ]);

        $this->say($setting, 'hello');

        $interactive = $this->lastSent()['interactive'];
        $this->assertSame('cta_url', $interactive['type']);
        $this->assertSame('https://example.com/book', $interactive['action']['parameters']['url']);
        $this->assertSame('Book now', $interactive['action']['parameters']['display_text']);
    }

    public function test_an_attached_image_is_sent_with_the_text_as_its_caption(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, ['media_type' => 'image', 'media_url' => 'https://example.com/menu.jpg', 'response_message' => 'Our menu']);

        $this->say($setting, 'hello');

        $sent = $this->lastSent();
        $this->assertSame('image', $sent['type']);
        $this->assertSame('https://example.com/menu.jpg', $sent['media']['link']);
        $this->assertSame('Our menu', $sent['media']['caption']);
        Http::assertSentCount(1);
    }

    public function test_media_goes_first_when_the_flow_also_has_buttons(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, [
            'media_type' => 'document', 'media_url' => 'https://example.com/brochure.pdf',
            'reply_type' => 'buttons', 'quick_replies' => [['title' => 'Call me']],
        ]);

        $this->say($setting, 'hello');

        $types = collect(Http::recorded())->map(fn ($pair) => $pair[0]->data()['type'])->all();
        $this->assertSame(['document', 'buttons'], $types);
        $this->assertSame('brochure.pdf', Http::recorded()->first()[0]->data()['media']['filename']);
    }

    public function test_when_whatsapp_refuses_the_list_the_options_arrive_as_plain_text(): void
    {
        [$tenant, $setting] = $this->gatewayTenant([], Http::sequence()
            ->push(['success' => false, 'message' => 'bad', 'error' => ['message' => 'Invalid interactive']], 422)
            ->push(['success' => true, 'data' => ['wamid' => 'w']], 201));
        $this->flow($tenant, ['reply_type' => 'list', 'quick_replies' => [['title' => 'Pricing'], ['title' => 'Address']], 'response_message' => 'Pick one']);

        $this->assertTrue($this->say($setting, 'hello'));

        $sent = $this->lastSent();
        $this->assertSame('text', $sent['type']);
        $this->assertStringContainsString("1. Pricing\n2. Address", $sent['text']);
    }

    public function test_old_flows_with_buttons_but_no_reply_type_still_send_buttons(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, ['reply_type' => null, 'quick_replies' => [['title' => 'Yes']]]);

        $this->say($setting, 'hello');

        $this->assertSame('buttons', $this->lastSent()['type']);
    }

    // ── Welcome + business hours ─────────────────────────────────

    public function test_a_new_customer_gets_the_welcome_flow_and_then_the_default(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, ['name' => 'Welcome', 'trigger_keywords' => [], 'is_welcome' => true, 'response_message' => 'Welcome aboard!']);
        $this->flow($tenant, ['name' => 'Default', 'trigger_keywords' => [], 'is_default' => true, 'response_message' => 'Sorry, say that again?']);

        $this->say($setting, 'blah blah');
        $this->assertSame('Welcome aboard!', $this->lastSent()['text']);

        $this->say($setting, 'blah blah');
        $this->assertSame('Sorry, say that again?', $this->lastSent()['text']);
    }

    public function test_a_keyword_beats_the_welcome_flow_for_a_new_customer(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $this->flow($tenant, ['name' => 'Welcome', 'trigger_keywords' => [], 'is_welcome' => true, 'response_message' => 'Welcome aboard!']);
        $this->flow($tenant, ['name' => 'Price', 'trigger_keywords' => ['price'], 'response_message' => 'It is 999']);

        $this->say($setting, 'price?');

        $this->assertSame('It is 999', $this->lastSent()['text']);
    }

    public function test_outside_business_hours_unmatched_messages_get_the_after_hours_notice_once(): void
    {
        [$tenant, $setting] = $this->gatewayTenant([
            'hours_enabled' => true, 'hours_start' => '09:00', 'hours_end' => '18:00', 'hours_days' => [1, 2, 3, 4, 5],
            'after_hours_message' => 'We are closed, back at 9.',
        ]);
        $this->flow($tenant, ['name' => 'Default', 'trigger_keywords' => [], 'is_default' => true, 'response_message' => 'Default answer']);
        $this->flow($tenant, ['name' => 'Price', 'trigger_keywords' => ['price'], 'response_message' => 'It is 999']);

        Carbon::setTestNow(Carbon::parse('2026-10-11 22:30:00')); // a Sunday night

        try {
            $this->say($setting, 'random words');
            $this->assertSame('We are closed, back at 9.', $this->lastSent()['text']);

            // The same customer is not told twice, and a keyword still gets answered.
            $this->assertFalse($this->say($setting, 'more random words'));
            $this->say($setting, 'price');
            $this->assertSame('It is 999', $this->lastSent()['text']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_business_hours_logic(): void
    {
        $setting = new WhatsappSetting(['hours_enabled' => true, 'hours_start' => '09:00', 'hours_end' => '18:00', 'hours_days' => [1, 2, 3, 4, 5]]);

        $this->assertTrue($setting->isOpenNow(Carbon::parse('2026-10-12 10:00')));   // Monday morning
        $this->assertFalse($setting->isOpenNow(Carbon::parse('2026-10-12 19:00')));  // Monday evening
        $this->assertFalse($setting->isOpenNow(Carbon::parse('2026-10-11 10:00')));  // Sunday

        $night = new WhatsappSetting(['hours_enabled' => true, 'hours_start' => '22:00', 'hours_end' => '06:00', 'hours_days' => [1, 2, 3, 4, 5]]);
        $this->assertTrue($night->isOpenNow(Carbon::parse('2026-10-13 02:00')));     // Tuesday 2 AM belongs to Monday's shift
        $this->assertFalse($night->isOpenNow(Carbon::parse('2026-10-13 12:00')));

        $this->assertTrue((new WhatsappSetting(['hours_enabled' => false]))->isOpenNow(Carbon::parse('2026-10-11 03:00')));
    }

    // ── The builder ──────────────────────────────────────────────

    public function test_admin_saves_a_list_flow_with_all_the_new_fields(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.store'), [
            'name' => 'Menu', 'trigger_keywords' => 'menu, hi', 'keyword_match' => 'contains', 'response_message' => 'Choose',
            'reply_type' => 'list', 'header_text' => 'Acme', 'footer_text' => 'Tap', 'list_button_text' => 'Options',
            'media_url' => 'https://example.com/banner.png', 'is_welcome' => '1',
            'quick_replies' => [
                ['title' => 'Pricing', 'description' => 'Plans'], ['title' => ''], ['title' => 'Address'],
            ],
        ])->assertSessionHasNoErrors();

        $flow = WhatsappChatbotFlow::where('tenant_id', $tenant->id)->first();
        $this->assertSame('list', $flow->reply_type);
        $this->assertSame('image', $flow->media_type);        // worked out from the .png link
        $this->assertTrue($flow->is_welcome);
        $this->assertSame(['menu', 'hi'], $flow->trigger_keywords);
        $this->assertSame(['Pricing', 'Address'], array_column($flow->quick_replies, 'title'));
        $this->assertSame('Plans', $flow->quick_replies[0]['description']);
    }

    public function test_a_default_or_welcome_flow_needs_no_keywords_but_others_do(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');
        $base  = ['name' => 'X', 'keyword_match' => 'contains', 'response_message' => 'Hello'];

        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.store'), $base)->assertSessionHasErrors('trigger_keywords');
        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.store'), $base + ['is_welcome' => '1'])->assertSessionHasNoErrors();
    }

    public function test_media_must_be_an_https_link(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.store'), [
            'name' => 'X', 'trigger_keywords' => 'x', 'keyword_match' => 'contains', 'response_message' => 'Hello',
            'media_url' => 'http://insecure.example/a.jpg',
        ])->assertSessionHasErrors('media_url');
    }

    public function test_only_one_welcome_flow_per_tenant(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');
        $old   = $this->flow($tenant, ['name' => 'Old welcome', 'trigger_keywords' => [], 'is_welcome' => true]);

        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.store'), [
            'name' => 'New welcome', 'keyword_match' => 'contains', 'response_message' => 'Hi', 'is_welcome' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($old->fresh()->is_welcome);
        $this->assertSame(1, WhatsappChatbotFlow::where('tenant_id', $tenant->id)->where('is_welcome', true)->count());
    }

    public function test_duplicating_a_flow_makes_a_switched_off_copy(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');
        $flow  = $this->flow($tenant, ['name' => 'Price', 'trigger_keywords' => ['price'], 'is_default' => true]);

        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.duplicate', $flow->id))->assertSessionHas('success');

        $copy = WhatsappChatbotFlow::where('tenant_id', $tenant->id)->where('id', '!=', $flow->id)->first();
        $this->assertSame('Price (copy)', $copy->name);
        $this->assertFalse($copy->is_active);
        $this->assertFalse($copy->is_default);
        $this->assertSame(['price'], $copy->trigger_keywords);
    }

    public function test_the_test_box_names_the_flow_that_would_answer(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');
        $this->flow($tenant, ['name' => 'Price', 'trigger_keywords' => ['price']]);
        $this->flow($tenant, ['name' => 'Fallback', 'trigger_keywords' => [], 'is_default' => true]);

        $this->actingAs($admin)->postJson(route('tenant.whatsapp.chatbot.test'), ['text' => 'pricee kya hai'])
            ->assertOk()->assertJson(['flow' => 'Price']);

        $this->actingAs($admin)->postJson(route('tenant.whatsapp.chatbot.test'), ['text' => 'zzz'])
            ->assertOk()->assertJson(['flow' => 'Fallback']);
    }

    public function test_admin_saves_business_hours(): void
    {
        [$tenant, $setting] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.whatsapp.chatbot.hours'), [
            'hours_enabled' => '1', 'hours_start' => '10:00', 'hours_end' => '20:00', 'hours_days' => ['1', '2', '6'],
            'after_hours_message' => 'Closed now',
        ])->assertSessionHasNoErrors();

        $setting = $setting->fresh();
        $this->assertTrue($setting->hours_enabled);
        $this->assertSame('10:00', $setting->hours_start);
        $this->assertSame([1, 2, 6], $setting->hours_days);
        $this->assertSame('Closed now', $setting->after_hours_message);
    }

    public function test_the_builder_page_renders_with_the_new_controls(): void
    {
        [$tenant] = $this->gatewayTenant();
        $admin = $this->makeUser($tenant, 'tenant_admin');
        $this->flow($tenant, ['name' => 'Menu', 'reply_type' => 'list', 'quick_replies' => [['title' => 'Pricing']], 'media_url' => 'https://example.com/a.jpg', 'media_type' => 'image']);

        $this->actingAs($admin)->get(route('tenant.whatsapp.chatbot'))
            ->assertOk()
            ->assertSee('List menu (up to 10 options)')
            ->assertSee('Business hours')
            ->assertSee('Welcome flow (first message)')
            ->assertSee('list menu · 1 options')
            ->assertSee('Duplicate');
    }
}
