<?php

namespace Tests\Feature;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\Pages\PreviewShuPeriode;
use App\MemberOverview;
use App\Models\Anggota;
use App\Models\User;
use App\ShuCalculator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminShuEstablishmentTest extends TestCase
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

    public function test_only_calculated_draft_can_be_established_then_all_mutations_are_locked(): void
    {
        $member = Anggota::factory()->create();
        $period = app(AdminShu::class)->save(AdminShuParametersTest::parameters());
        Livewire::test(PreviewShuPeriode::class, ['record' => $period->id])->assertActionHidden('establish');
        try {
            app(AdminShu::class)->establish($period);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('periode', $e->errors());
        }
        $period = app(ShuCalculator::class)->calculate($period);
        $component = Livewire::test(PreviewShuPeriode::class, ['record' => $period->id])->assertActionVisible('establish')->mountAction('establish')->assertMountedActionModalSee(['Tetapkan SHU tahun 2030', 'Rp 100']);
        $component->callMountedAction()->assertHasNoActionErrors()->assertActionHidden('establish')->assertActionHidden('calculate');
        $period->refresh();
        $this->assertSame('ditetapkan', $period->status);
        $this->assertNotNull($period->ditetapkan_at);
        $this->get('/admin/shu/'.$period->id.'/edit')->assertForbidden();
        foreach ([fn () => app(AdminShu::class)->save(AdminShuParametersTest::parameters(), $period), fn () => app(ShuCalculator::class)->calculate($period), fn () => app(AdminShu::class)->establish($period)] as $operation) {
            try {
                $operation();
                $this->fail();
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertSame(6, $period->shuAnggotas()->count());
    }

    public function test_draft_is_hidden_and_established_totals_match_member_shu_and_dashboard(): void
    {
        $admin = auth()->user();
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $period = app(ShuCalculator::class)->calculate(app(AdminShu::class)->save(AdminShuParametersTest::parameters()));
        $this->actingAs($member->user);
        $this->get('/anggota/shu/2030')->assertNotFound();
        $this->assertNull(app(MemberOverview::class)->latestShu($member));
        $this->assertTrue(app(MemberOverview::class)->shuHistory($member)->isEmpty());
        $this->actingAs($admin);
        app(AdminShu::class)->establish($period);
        $this->actingAs($member->user);
        $response = $this->get('/anggota/shu/2030')->assertOk();
        $this->assertSame(100, $response->viewData('totalRows')[0]['shu']);
        $this->assertSame(100, app(MemberOverview::class)->latestShu($member)['amount']);
        $this->get('/anggota')->assertOk();
        $this->get('/anggota/shu')->assertOk()->assertViewHas('rows', fn (array $rows): bool => $rows[0]['amount'] === 100);
        $this->get('/admin/shu/'.$period->id)->assertForbidden();
    }

    public function test_guest_member_and_changed_password_guards_are_preserved(): void
    {
        $this->post('/logout');
        $this->get('/admin/shu')->assertRedirect('/login');
        $member = User::factory()->create(['role' => 'anggota', 'must_change_password' => false]);
        $this->actingAs($member)->get('/admin/shu')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => true]);
        $this->actingAs($admin)->get('/admin/shu')->assertRedirect('/ganti-password');
    }
}
