<?php

namespace Tests\Feature;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\Pages\CreateShuPeriode;
use App\Filament\Resources\ShuPeriodes\Pages\EditShuPeriode;
use App\Models\ShuPeriode;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminShuParametersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public static function parameters(): array
    {
        return ['tahun' => 2030, 'pool_toko' => 100, 'pool_usipa' => 100, 'pool_usipa_khusus' => 100, 'pool_jasa_umum' => 100, 'toko_simpanan' => 60, 'toko_belanja' => 40, 'usipa_sukarela' => 70, 'usipa_jasa' => 30, 'jasa_tetap' => 27.5, 'dasar_saldo' => 'akhir_tahun'];
    }

    public function test_admin_can_create_and_update_draft_parameters_in_existing_panel(): void
    {
        $this->get('/admin/shu')->assertOk();
        Livewire::test(CreateShuPeriode::class)->fillForm(self::parameters())->call('create')->assertHasNoFormErrors();
        $period = ShuPeriode::sole();
        $this->assertSame('draft', $period->status);
        $this->assertEquals(27.5, $period->parameter['jasa_tetap']);
        Livewire::test(EditShuPeriode::class, ['record' => $period->id])->fillForm(['pool_toko' => 200])->call('save')->assertHasNoFormErrors();
        $this->assertEquals(200, $period->fresh()->parameter['pool_toko']);
    }

    public function test_invalid_parameters_and_duplicate_year_are_rejected(): void
    {
        app(AdminShu::class)->save(self::parameters());
        foreach ([['tahun' => 2030], ['tahun' => 2031, 'toko_belanja' => 20], ['tahun' => 2031, 'pool_toko' => -1], ['tahun' => 2031, 'usipa_jasa' => 101], ['tahun' => 2031, 'pool_toko' => 1.5], ['tahun' => 2031, 'dasar_saldo' => 'invalid']] as $changes) {
            try {
                app(AdminShu::class)->save(array_replace(self::parameters(), $changes));
                $this->fail('Invalid parameter accepted');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_established_period_is_not_editable_and_members_cannot_access(): void
    {
        $period = app(AdminShu::class)->save(self::parameters());
        $period->update(['status' => 'ditetapkan']);
        $this->get('/admin/shu/'.$period->id.'/edit')->assertForbidden();
        try {
            app(AdminShu::class)->save(self::parameters(), $period);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tahun', $e->errors());
        }
        $this->actingAs(User::factory()->create(['role' => 'anggota', 'must_change_password' => false]));
        $this->get('/admin/shu')->assertForbidden();
    }
}
