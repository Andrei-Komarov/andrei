<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_regular_account_and_authenticates_the_user(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jane Example',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_configured_admin_is_created_only_through_the_console_command(): void
    {
        config(['services.admin.email' => 'admin@example.com']);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Application Admin',
            'email' => 'ADMIN@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'ADMIN@example.com']);

        $this->artisan('app:promote-admin')
            ->expectsQuestion('Имя администратора', 'Application Admin')
            ->expectsQuestion('Пароль администратора (не менее 12 символов)', 'strong-admin-password')
            ->expectsQuestion('Повторите пароль', 'strong-admin-password')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($user->isAdmin());

        $existingUser = User::factory()->create([
            'email' => 'member@example.com',
            'is_admin' => false,
        ]);
        $this->post(route('login.store'), [
            'email' => $existingUser->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($existingUser->fresh()->isAdmin());
    }

    public function test_login_logout_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_registration_validates_required_details_and_password_confirmation(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_site_login_and_registration_pages_do_not_show_site_navigation(): void
    {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Вход')
            ->assertDontSee('aria-label="eBay Finder — главная"', false)
            ->assertDontSee('aria-label="Главное меню"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Регистрация')
            ->assertDontSee('aria-label="eBay Finder — главная"', false)
            ->assertDontSee('aria-label="Главное меню"', false);
    }

    public function test_admin_promotion_requires_a_configured_email(): void
    {
        config(['services.admin.email' => null]);

        $this->artisan('app:promote-admin')
            ->expectsOutputToContain('Укажите корректный ADMIN_EMAIL')
            ->assertExitCode(1);
    }

    public function test_guests_are_redirected_to_login_for_application_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('listings.index'))->assertRedirect(route('login'));
        $this->get(route('profiles.index'))->assertRedirect(route('login'));
        $this->get(route('admin.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.settings'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.payments'))->assertRedirect(route('admin.login'));
    }

    public function test_payments_page_displays_the_provided_prices_and_top_up_instructions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withoutVite()->actingAs($admin, 'admin')
            ->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('Сколько стоит')
            ->assertSee('30 бесплатным поднятиям')
            ->assertSee('1 сом равняется 3 дополнительным поднятиям (up)')
            ->assertSee('10 сом')
            ->assertSee('20 сом')
            ->assertSee('50 сом')
            ->assertSee('Варианты пополнения баланса')
            ->assertSee('О!Деньги')
            ->assertSee('Баланс номера О!')
            ->assertSee('После пополнения необходимо создать запрос на платеж')
            ->assertSee('Меню администратора')
            ->assertSee('Пользователи и роли')
            ->assertSee('Настройки');
    }

    public function test_regular_users_can_manage_their_own_profiles_but_cannot_access_admin_tools(): void
    {
        $user = User::factory()->create();
        $profile = SearchProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('listings.index'))
            ->assertOk()
            ->assertDontSee('Админ-панель')
            ->assertSee(route('profiles.index'));

        $this->get(route('profiles.index'))->assertOk()->assertSee($profile->name);
        $this->get(route('profiles.history'))->assertOk();
        $this->get(route('admin.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.settings'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.payments'))->assertRedirect(route('admin.login'));
        $this->patch(route('admin.users.role', $user), ['is_admin' => true])->assertRedirect(route('admin.login'));
        $this->assertDatabaseHas('search_profiles', ['id' => $profile->id]);
    }

    public function test_users_cannot_view_or_modify_another_users_search_profiles(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $profile = SearchProfile::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->get(route('profiles.edit', $profile))
            ->assertNotFound();

        $this->patch(route('profiles.update', $profile), [
            'name' => 'Stolen profile',
            'query' => 'laptop',
        ])->assertNotFound();

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 0,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 0,
        ])->assertNotFound();

        $this->delete(route('profiles.destroy', $profile))->assertNotFound();
        $this->assertDatabaseHas('search_profiles', ['id' => $profile->id, 'user_id' => $owner->id]);
    }

    public function test_admin_can_view_admin_panel_and_change_another_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['name' => 'Regular Member']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Админ-панель')
            ->assertSee('Пользователи и роли')
            ->assertSee('Настройки')
            ->assertSee(route('admin.payments'))
            ->assertDontSee('Обзор и пользователи')
            ->assertSee('Regular Member')
            ->assertSee('data-table-key="admin-users"', false)
            ->assertSee('data-column-key="email"', false)
            ->assertDontSee('aria-label="eBay Finder — главная"', false)
            ->assertDontSee('Главное меню')
            ->assertDontSee('Список профилей')
            ->assertDontSee('История поиска')
            ->assertSee(route('listings.index'))
            ->assertSee('aria-label="Перейти на сайт"', false)
            ->assertSee('Редактировать')
            ->assertSee('Удалить')
            ->assertSee('id="site-confirm-dialog"', false)
            ->assertSee('data-confirm-dialog-form', false)
            ->assertDontSee('onsubmit=', false)
            ->assertSee('fixed left-1/2 top-1/2', false);

        $this->patch(route('admin.users.role', $member), ['is_admin' => 1])
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('success');
        $this->assertTrue($member->fresh()->isAdmin());
    }

    public function test_admin_user_table_sorts_by_email_across_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $firstMember = User::factory()->create(['email' => 'sort-a@example.com']);
        $lastMember = User::factory()->create(['email' => 'sort-z@example.com']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.index', ['sort' => 'email_desc']))
            ->assertOk()
            ->assertSee(route('admin.index', ['sort' => 'email_asc']));
        $emails = $response->viewData('users')->getCollection()->pluck('email')->all();

        $this->assertLessThan(array_search($firstMember->email, $emails), array_search($lastMember->email, $emails));
    }

    public function test_admin_can_update_a_user_without_changing_password_and_can_reset_the_password(): void
    {
        Notification::fake();
        config(['session.driver' => 'database']);
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
        DB::table('sessions')->insert([
            'id' => 'member-active-session',
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'feature-test',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.edit', $member))
            ->assertOk()
            ->assertSee('Редактирование пользователя')
            ->assertSee('value="Old Name"', false)
            ->assertSee('value="old@example.com"', false);

        $this->patch(route('admin.users.update', $member), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])->assertRedirect(route('admin.index'))
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('New Name', $member->name);
        $this->assertSame('new@example.com', $member->email);
        $this->assertTrue(Hash::check('password', $member->password));
        $this->assertNull($member->email_verified_at);
        Notification::assertSentTo($member, VerifyEmail::class);

        $this->patch(route('admin.users.update', $member), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'password' => 'updated-password',
            'password_confirmation' => 'updated-password',
        ])->assertRedirect(route('admin.index'));

        $this->assertTrue(Hash::check('updated-password', $member->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'member-active-session']);
    }

    public function test_admin_user_update_validates_duplicate_email_and_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $anotherMember = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.users.edit', $member))
            ->patch(route('admin.users.update', $member), [
                'name' => 'Updated name',
                'email' => $anotherMember->email,
            ])
            ->assertRedirect(route('admin.users.edit', $member))
            ->assertSessionHasErrors('email');

        $this->patch(route('admin.users.update', $member), [
            'name' => 'Updated name',
            'email' => $member->email,
            'password' => 'updated-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');
    }

    public function test_admin_can_delete_a_user_and_cascade_profiles_while_preserving_activity_history(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $profile = SearchProfile::factory()->create(['user_id' => $member->id]);
        $listing = LaptopListing::factory()->create(['search_profile_id' => $profile->id]);
        $activity = SearchProfileActivity::query()->create([
            'search_profile_id' => $profile->id,
            'user_id' => $member->id,
            'profile_owner_id' => $member->id,
            'profile_name' => $profile->name,
            'action' => 'created',
            'description' => 'Profile created.',
        ]);
        $sessionId = 'deleted-member-session';
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'feature-test',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.users.destroy', $member))
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        $this->assertDatabaseMissing('search_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('laptop_listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        $this->assertDatabaseHas('search_profile_activities', [
            'id' => $activity->id,
            'search_profile_id' => null,
            'user_id' => null,
            'profile_owner_id' => null,
            'profile_name' => $profile->name,
        ]);
    }

    public function test_admin_cannot_delete_self_or_configured_admin_but_can_delete_another_admin(): void
    {
        config(['services.admin.email' => 'primary@example.com']);
        $primaryAdmin = User::factory()->admin()->create(['email' => 'primary@example.com']);
        $anotherAdmin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($primaryAdmin, 'admin')
            ->from(route('admin.index'))
            ->delete(route('admin.users.destroy', $primaryAdmin))
            ->assertSessionHasErrors('user');

        $this->delete(route('admin.users.destroy', $member))
            ->assertRedirect(route('admin.index'));

        $this->delete(route('admin.users.destroy', $anotherAdmin))
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('users', ['id' => $primaryAdmin->id]);
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        $this->assertDatabaseMissing('users', ['id' => $anotherAdmin->id]);
    }

    public function test_admin_cannot_demote_themselves_or_configured_primary_admin(): void
    {
        config(['services.admin.email' => 'primary@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'primary@example.com']);
        $anotherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.users.role', $admin), ['is_admin' => 0])
            ->assertSessionHasErrors('is_admin');
        $this->assertTrue($admin->fresh()->isAdmin());

        $this->patch(route('admin.users.role', $anotherAdmin), ['is_admin' => 0])
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('success');
        $this->assertFalse($anotherAdmin->fresh()->isAdmin());
    }

    public function test_admin_has_a_separate_login_and_regular_users_cannot_use_it(): void
    {
        $member = User::factory()->create(['email' => 'member@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->withoutVite()
            ->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Вход администратора')
            ->assertDontSee('Главное меню')
            ->assertDontSee('Поиск товаров на eBay');

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => $member->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_admin_login_does_not_share_or_destroy_the_regular_site_session(): void
    {
        $member = User::factory()->create(['email' => 'member@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->actingAs($member)
            ->post(route('admin.login.store'), [
                'email' => $admin->email,
                'password' => 'password',
            ])->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($member, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertAuthenticatedAs($member, 'web');
        $this->assertGuest('admin');
    }

    public function test_site_login_does_not_authenticate_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertGuest('admin');
        $this->get(route('admin.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.settings'))->assertRedirect(route('admin.login'));
    }
}
