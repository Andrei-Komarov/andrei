<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_an_email_verification_link_and_requires_verification(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'New Member',
            'email' => 'new-member@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'new-member@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get(route('listings.index'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Подтвердите email')
            ->assertSee('new-member@example.com');
    }

    public function test_unverified_user_can_resend_and_complete_signed_email_verification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('listings.index'))
            ->assertSessionHas('status', 'email-verified');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('listings.index'))->assertOk();
    }

    public function test_invalid_or_expired_email_verification_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_password_reset_request_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'member@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Если аккаунт с таким адресом существует, мы отправили ссылку для сброса пароля.');
        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Если аккаунт с таким адресом существует, мы отправили ссылку для сброса пароля.');
    }

    public function test_password_can_be_reset_with_a_valid_token_once(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Создание нового пароля')
            ->assertSee('value="'.$token.'"', false);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Пароль обновлён. Теперь войдите с новым паролем.');

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_password_reset_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $originalPassword = $user->password;

        $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
            ->post(route('password.store'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_unverified_admin_can_still_access_the_separate_admin_panel(): void
    {
        $admin = User::factory()->admin()->unverified()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.index'))
            ->assertOk();
    }
}
