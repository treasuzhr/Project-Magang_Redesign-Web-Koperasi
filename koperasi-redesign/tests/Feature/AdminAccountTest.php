<?php

namespace Tests\Feature;

use App\Filament\Pages\AkunSaya;
use App\Models\Anggota;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false, 'password' => Hash::make('PasswordAwal!2026')]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_wrong_old_password_short_password_and_confirmation_are_rejected(): void
    {
        foreach ([['current_password' => 'salah', 'password' => 'PasswordBaru!2026', 'password_confirmation' => 'PasswordBaru!2026', 'error' => 'current_password'], ['current_password' => 'PasswordAwal!2026', 'password' => 'short', 'password_confirmation' => 'short', 'error' => 'password'], ['current_password' => 'PasswordAwal!2026', 'password' => 'PasswordBaru!2026', 'password_confirmation' => 'berbeda', 'error' => 'password']] as $data) {
            $field = $data['error'];
            unset($data['error']);
            Livewire::test(AkunSaya::class)->fillForm($data)->call('save')->assertHasFormErrors([$field]);
            $this->assertTrue(Hash::check('PasswordAwal!2026', $this->admin->fresh()->password));
        }
    }

    public function test_password_is_hashed_form_cleared_session_maintained_and_not_logged(): void
    {
        Log::spy();
        $new = 'PasswordBaru!2026';
        Livewire::test(AkunSaya::class)->fillForm(['current_password' => 'PasswordAwal!2026', 'password' => $new, 'password_confirmation' => $new])->call('save')->assertHasNoFormErrors()->assertNotified('Password berhasil diubah.')->assertDontSee($new);
        $this->assertTrue(Hash::check($new, $this->admin->fresh()->password));
        $this->assertNotSame($new, $this->admin->fresh()->password);
        $this->get('/admin')->assertOk();
        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'log'] as $method) {
            Log::shouldNotHaveReceived($method);
        }
    }

    public function test_only_admin_can_open_account_page(): void
    {
        $this->get('/admin/akun-saya')->assertOk()->assertDontSee('Email');
        auth()->logout();
        $this->get('/admin/akun-saya')->assertRedirect('/login');
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $this->actingAs($member->user)->get('/admin/akun-saya')->assertForbidden();
        $this->assertFalse(AkunSaya::canAccess());
    }
}
