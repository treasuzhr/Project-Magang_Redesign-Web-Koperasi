<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = [], string $status = 'aktif'): User
    {
        $user = User::factory()->create(array_merge(['must_change_password' => false], $attributes));
        Anggota::factory()->create(['user_id' => $user->id, 'status' => $status]);

        return $user;
    }

    private function login(User $user, string $password = 'password'): TestResponse
    {
        return $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', [
            'member_id' => $user->nip, 'password' => $password, 'captcha' => 'ab2cde',
        ]);
    }

    public function test_active_member_logs_in_using_nip_and_regenerates_the_session(): void
    {
        $user = $this->member(['nip' => 'NRP/AB-123']);
        $this->withSession(['captcha_answer' => 'AB2CDE', 'member.authenticated' => true]);
        $sessionId = session()->getId();
        $this->post('/login', ['member_id' => $user->nip, 'password' => 'password', 'captcha' => 'AB2CDE'])
            ->assertRedirect('/anggota')->assertSessionMissing('captcha_answer')->assertSessionMissing('member');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->get('/anggota')->assertOk();
    }

    public function test_unknown_nip_and_wrong_password_have_the_same_generic_error(): void
    {
        $user = $this->member();
        foreach ([[$user->nip, 'incorrect'], ['DOES-NOT-EXIST', 'password']] as [$nip, $password]) {
            $this->withSession(['captcha_answer' => 'AB2CDE'])->from('/login')->post('/login', [
                'member_id' => $nip, 'password' => $password, 'captcha' => 'AB2CDE',
            ])->assertRedirect('/login')->assertSessionHasErrors(['member_id' => 'NIP atau password tidak sesuai'])
                ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.captcha');
            $this->assertGuest();
        }
    }

    public function test_inactive_members_and_accounts_without_a_member_profile_cannot_login(): void
    {
        foreach (['keluar', 'pensiun', 'pindah'] as $status) {
            $this->login($this->member([], $status))->assertSessionHasErrors(['member_id' => 'NIP atau password tidak sesuai']);
            $this->assertGuest();
        }
        $user = User::factory()->create(['must_change_password' => false]);
        $this->login($user)->assertSessionHasErrors(['member_id' => 'NIP atau password tidak sesuai']);
        $this->assertGuest();
    }

    public function test_admin_logs_in_without_member_profile_and_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->login($admin)->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Dashboard Admin')->assertSee('Keluar');
        $this->get('/login')->assertRedirect('/admin');
    }

    public function test_member_routes_require_authentication_even_with_forged_legacy_session(): void
    {
        $this->withSession(['member.authenticated' => true])->get('/anggota')->assertRedirect('/login');
        $this->get('/anggota/pinjaman')->assertRedirect('/login');
        $this->post('/anggota/pinjaman/ajukan')->assertRedirect('/login');
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'anggota')) {
                $this->assertContains('auth', $route->gatherMiddleware(), $route->uri());
                $this->assertContains('role:anggota', $route->gatherMiddleware(), $route->uri());
            }
        }
    }

    public function test_roles_cannot_open_each_others_pages_and_403_uses_shared_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->actingAs($admin);
        foreach (['/anggota', '/anggota/simpanan/pokok', '/anggota/shu', '/anggota/pesanan', '/anggota/ubah-password'] as $path) {
            $this->get($path)->assertForbidden()->assertSee('Akses ditolak')->assertSee('site-header')->assertSee('site-footer');
        }
        $this->actingAs($this->member())->get('/admin')->assertForbidden();
    }

    public function test_mandatory_password_change_blocks_other_pages_for_both_roles(): void
    {
        foreach (['admin', 'anggota'] as $role) {
            $user = $role === 'admin' ? User::factory()->create(['role' => 'admin']) : $this->member(['must_change_password' => true]);
            $this->actingAs($user);
            foreach (['/', '/produk', '/berita', $role === 'admin' ? '/admin' : '/anggota'] as $path) {
                $this->get($path)->assertRedirect('/ganti-password');
            }
            $this->get('/ganti-password')->assertOk()->assertSee('Anda wajib mengganti password')->assertSee('Password baru');
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_password_must_be_at_least_eight_characters_confirmed_and_different(): void
    {
        $user = $this->member(['must_change_password' => true]);
        $this->actingAs($user);
        foreach ([['short', 'short'], ['password', 'password'], ['NewPassword123!', 'Different123!']] as [$password, $confirmation]) {
            $this->from('/ganti-password')->post('/ganti-password', [
                'password' => $password, 'password_confirmation' => $confirmation,
            ])->assertRedirect('/ganti-password')->assertSessionHasErrors()
                ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
            $this->assertTrue($user->fresh()->must_change_password);
            $this->assertTrue(Hash::check('password', $user->fresh()->password));
        }
    }

    public function test_changing_password_updates_hash_and_flag_then_redirects_by_role(): void
    {
        foreach (['admin', 'anggota'] as $role) {
            $user = $role === 'admin' ? User::factory()->create(['role' => 'admin']) : $this->member(['must_change_password' => true]);
            $this->actingAs($user)->post('/ganti-password', [
                'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
            ])->assertRedirect($role === 'admin' ? '/admin' : '/anggota');
            $this->assertFalse($user->fresh()->must_change_password);
            $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
            $this->get($role === 'admin' ? '/admin' : '/anggota')->assertOk();
        }
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $user = $this->member();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login($user, 'wrong-password')->assertSessionHasErrors('member_id');
        }
        $this->login($user)->assertSessionHasErrors(['member_id' => 'Terlalu banyak percobaan masuk. Tunggu satu menit lalu coba lagi.']);
        $this->assertGuest();
    }

    public function test_invalid_input_has_indonesian_errors_and_does_not_flash_passwords(): void
    {
        $this->from('/login')->post('/login', ['member_id' => [], 'password' => [], 'captcha' => []])
            ->assertRedirect('/login')->assertSessionHasErrors([
                'member_id' => 'NIP wajib diisi.', 'password' => 'Password wajib diisi.', 'captcha' => 'Kode verifikasi wajib diisi.',
            ])->assertSessionMissing('_old_input.password');
    }

    public function test_captcha_cannot_be_reused_after_an_attempt(): void
    {
        $user = $this->member();
        $this->login($user, 'wrong-password')->assertSessionHasErrors('member_id');
        $this->post('/login', ['member_id' => $user->nip, 'password' => 'password', 'captcha' => 'AB2CDE'])
            ->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_and_requires_post(): void
    {
        $user = $this->member();
        $this->actingAs($user)->withSession(['private_test' => 'value'])->post('/logout')
            ->assertRedirect('/')->assertSessionMissing('private_test');
        $this->assertGuest();
        $this->get('/logout')->assertStatus(405);
        $this->get('/anggota')->assertRedirect('/login');
    }

    public function test_guest_navbar_is_unchanged_and_authenticated_navbar_has_user_menu(): void
    {
        $this->get('/')->assertOk()->assertSee('>Masuk</a>', false)->assertDontSee('class="nav-user"', false);
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->actingAs($admin)->get('/')->assertOk()->assertSee($admin->name)
            ->assertSee('href="'.route('filament.admin.pages.dashboard').'"', false)->assertSee('action="'.route('logout').'"', false);
    }

    public function test_deactivated_member_is_logged_out_on_the_next_request(): void
    {
        $user = $this->member();
        $this->actingAs($user);
        $user->anggota->update(['status' => 'pensiun']);
        $this->get('/anggota')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_there_is_no_registration_or_self_service_password_reset(): void
    {
        foreach (['/register', '/forgot-password', '/reset-password', '/reset-password/example-token'] as $path) {
            $this->get($path)->assertNotFound();
            $this->post($path)->assertNotFound();
        }
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString('register', $route->uri());
            $this->assertStringNotContainsString('reset-password', $route->uri());
        }
    }

    public function test_seeded_demo_accounts_login_and_are_required_to_change_password(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        foreach ([CooperativeDemoSeeder::ADMIN_NIP, CooperativeDemoSeeder::ANGGOTA_NIP, CooperativeDemoSeeder::ANGGOTA_KEDUA_NIP] as $nip) {
            $user = User::where('nip', $nip)->firstOrFail();
            $this->login($user, CooperativeDemoSeeder::PASSWORD)->assertRedirect('/ganti-password');
            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/');
        }
    }
}
