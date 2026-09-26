<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P3: a failed login sometimes showed no error. It failed via back(); with no
// usable Referer that resolves to "/", which 301s to /login, and flashed errors
// survive only one redirect. Failures now redirect straight to /login.
class LoginErrorMessageTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private const FAILED = 'These credentials do not match our records.';

    private function user(): User
    {
        $tenant = $this->setUpTenant();

        return User::factory()->create([
            'tenant_id' => $tenant->id, 'user_type' => 'staff', 'is_active' => true,
            'email' => 'known@example.com', 'password' => bcrypt('correct-password'),
        ]);
    }

    /** POST with no Referer (privacy setting / automation) and follow through to what the browser renders. */
    private function submitWithoutReferer(array $credentials)
    {
        return $this->followingRedirects()->post(route('login.store'), $credentials);
    }

    public function test_wrong_password_redirects_straight_to_login_with_the_message(): void
    {
        $this->user();

        $this->post(route('login.store'), ['email' => 'known@example.com', 'password' => 'nope'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::FAILED]);
        $this->assertGuest();
    }

    public function test_the_message_is_visible_even_without_a_referer_header(): void
    {
        $this->user();

        $this->submitWithoutReferer(['email' => 'known@example.com', 'password' => 'nope'])
            ->assertOk()
            ->assertSee(self::FAILED);
    }

    public function test_the_message_is_visible_even_when_the_referer_is_the_bouncing_root_url(): void
    {
        $this->user();

        // Referer "/" would previously send the user through "/" -> /login and drop the flash.
        $this->followingRedirects()
            ->from('/')
            ->post(route('login.store'), ['email' => 'known@example.com', 'password' => 'nope'])
            ->assertOk()
            ->assertSee(self::FAILED);
    }

    public function test_unknown_email_shows_the_same_message(): void
    {
        $this->user();

        $this->submitWithoutReferer(['email' => 'nobody@example.com', 'password' => 'whatever'])
            ->assertSee(self::FAILED);
    }

    public function test_malformed_email_shows_a_validation_message_on_the_login_page(): void
    {
        $this->submitWithoutReferer(['email' => 'not-an-email', 'password' => 'x'])
            ->assertOk()
            ->assertSee('valid email address');
    }

    public function test_empty_submission_shows_required_messages(): void
    {
        $this->submitWithoutReferer([])
            ->assertOk()
            ->assertSee('The email field is required.')
            ->assertSee('The password field is required.');
    }

    public function test_the_email_is_kept_and_the_password_is_never_sent_back(): void
    {
        $this->user();

        $html = $this->submitWithoutReferer(['email' => 'known@example.com', 'password' => 'super-secret-guess'])->getContent();

        $this->assertStringContainsString('value="known@example.com"', $html);
        $this->assertStringNotContainsString('super-secret-guess', $html);
    }

    public function test_a_deactivated_account_gets_a_visible_message_and_is_logged_out(): void
    {
        $user = $this->user();
        $user->update(['is_active' => false]);

        $this->submitWithoutReferer(['email' => 'known@example.com', 'password' => 'correct-password'])
            ->assertSee('Your account has been deactivated.');
        $this->assertGuest();
    }

    public function test_too_many_attempts_shows_the_throttle_message(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'known@example.com', 'password' => 'nope']);
        }

        $this->submitWithoutReferer(['email' => 'known@example.com', 'password' => 'correct-password'])
            ->assertSee('Too many login attempts.');
        $this->assertGuest();

        RateLimiter::clear(\Illuminate\Support\Str::transliterate('known@example.com|127.0.0.1'));
    }

    public function test_correct_credentials_still_log_in(): void
    {
        $user = $this->user();

        $this->post(route('login.store'), ['email' => 'known@example.com', 'password' => 'correct-password'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }
}
