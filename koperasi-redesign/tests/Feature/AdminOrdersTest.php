<?php

namespace Tests\Feature;

use App\AdminOrders;
use App\Filament\Resources\Pesanans\Pages\ListPesanans;
use App\Filament\Resources\Pesanans\Pages\ViewPesanan;
use App\Filament\Resources\Pesanans\PesananResource;
use App\Models\Anggota;
use App\Models\Pesanan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->actingAs($user);

        return $user;
    }

    private function order(string $status = 'baru'): Pesanan
    {
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $order = $member->pesanans()->create(['kode' => fake()->unique()->numerify('ADM-ORD-########'), 'status' => $status, 'total' => 14000, 'tanggal' => '2025-01-01 10:00:00']);
        $order->items()->create(['nama' => 'Barang fiktif', 'harga' => 7000, 'qty' => 2, 'subtotal' => 14000]);

        return $order;
    }

    public function test_admin_actions_follow_workflow_and_member_order_status_changes(): void
    {
        $admin = $this->admin();
        $order = $this->order();
        $before = $order->items()->get()->toArray();
        Livewire::test(ListPesanans::class)->searchTable($order->kode)->assertCanSeeTableRecords([$order])->callTableAction('diproses', $order)->assertHasNoTableActionErrors();
        $this->assertSame('diproses', $order->fresh()->status);
        Livewire::test(ViewPesanan::class, ['record' => (string) $order->id])->callAction('siap')->assertHasNoActionErrors();
        $this->actingAs($order->anggota->user)->get('/anggota/pesanan/'.$order->id)->assertOk()->assertSee('Siap diambil');
        $this->actingAs($admin);
        Livewire::test(ViewPesanan::class, ['record' => (string) $order->id])->callAction('selesai')->assertHasNoActionErrors();
        $this->assertSame('selesai', $order->fresh()->status);
        $this->assertSame('2025-01-01 10:00:00', $order->fresh()->tanggal->format('Y-m-d H:i:s'));
        $this->assertSame(14000, $order->fresh()->total);
        $this->assertSame($before, $order->items()->get()->toArray());
        $this->actingAs($order->anggota->user)->get('/anggota/pesanan/'.$order->id)->assertOk()->assertSee('Selesai');
        $response = $this->get('/anggota/pesanan?status=selesai')->assertOk()->assertSee('Selesai');
        $this->assertContains($order->id, array_column($response->viewData('rows'), 'id'));
    }

    public function test_each_allowed_cancellation_is_final_and_skipped_or_repeated_transitions_fail(): void
    {
        $this->admin();
        $service = app(AdminOrders::class);
        foreach (['baru', 'diproses', 'siap'] as $status) {
            $order = $this->order($status);
            $service->transition($order, 'batal');
            $this->assertSame('batal', $order->status);
            foreach (Pesanan::STATUSES as $target) {
                try {
                    $service->transition($order, $target);
                    $this->fail('Pesanan batal tidak boleh dilanjutkan.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('status', $exception->errors());
                }
            }
        }
        foreach (Pesanan::STATUSES as $status) {
            foreach (Pesanan::STATUSES as $target) {
                if (in_array($target, AdminOrders::TRANSITIONS[$status], true)) {
                    continue;
                }
                $order = $this->order($status);
                try {
                    $service->transition($order, $target);
                    $this->fail('Transisi tidak berurutan harus ditolak.');
                } catch (ValidationException) {
                    $this->assertSame($status, $order->fresh()->status);
                }
            }
        }
    }

    public function test_admin_lists_filter_and_show_read_only_item_snapshots(): void
    {
        $this->admin();
        $one = $this->order();
        $two = $this->order('selesai');
        Livewire::test(ListPesanans::class)->filterTable('status', 'baru')->assertCanSeeTableRecords([$one])->assertCanNotSeeTableRecords([$two]);
        Livewire::test(ListPesanans::class)->searchTable($one->anggota->nama)->assertCanSeeTableRecords([$one]);
        $this->get('/admin/pesanan/'.$one->id)->assertOk()->assertSee('Barang fiktif');
        $this->assertFalse(PesananResource::canEdit($one));
        $this->assertFalse(PesananResource::canCreate());
    }

    public function test_members_and_guests_cannot_access_order_administration(): void
    {
        $order = $this->order();
        $this->get('/admin/pesanan')->assertRedirect('/login');
        $this->actingAs($order->anggota->user);
        $this->get('/admin/pesanan')->assertForbidden();
        $this->get('/admin/pesanan/'.$order->id)->assertForbidden();
        $this->expectException(HttpException::class);
        app(AdminOrders::class)->transition($order, 'diproses');
    }
}
