<?php

namespace Tests\Feature;

use App\AdminCatalog;
use App\Filament\Resources\Kategoris\Pages\ListKategoris;
use App\Filament\Resources\Pesanans\Pages\ListPesanans;
use App\Filament\Resources\Pesanans\PesananResource;
use App\Filament\Resources\Pinjamans\Pages\ListPinjamans;
use App\Filament\Resources\Pinjamans\PinjamanResource;
use App\Filament\Resources\Produks\Pages\ListProduks;
use App\Models\Kategori;
use App\Models\Pesanan;
use App\Models\Pinjaman;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CooperativeDemoSeeder::class);
        $this->actingAs(User::where('role', 'admin')->first());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_queue_tabs_counts_filters_and_oldest_first(): void
    {
        foreach ([[ListPinjamans::class, Pinjaman::class, 'diajukan', 'tanggal_pengajuan'], [ListPesanans::class, Pesanan::class, 'baru', 'tanggal']] as [$page,$model,$default,$date]) {
            $component = Livewire::test($page);
            $this->assertSame($default, $component->get('activeTab'));
            foreach ($component->instance()->getTabs() as $key => $tab) {
                $query = $model::query();
                if ($key !== 'semua') {
                    $query->where('status', $key);
                }
                $this->assertEquals($query->count(), $tab->getBadge());
                $component->set('activeTab', $key);
                $this->assertEqualsCanonicalizing($query->pluck('id')->all(), $component->instance()->getFilteredTableQuery()->pluck('id')->all());
            }
            $component->set('activeTab', $default);
            $this->assertSame($model::where('status', $default)->orderBy($date)->orderBy('id')->pluck('id')->all(), $component->instance()->getFilteredSortedTableQuery()->pluck('id')->all());
        }
        $this->assertSame((string) Pinjaman::whereStatus('diajukan')->count(), PinjamanResource::getNavigationBadge());
        $this->assertSame((string) Pesanan::whereStatus('baru')->count(), PesananResource::getNavigationBadge());
    }

    public function test_inline_category_and_product_tabs_and_delete_tooltips(): void
    {
        $product = Produk::first();
        $target = Kategori::whereKeyNot($product->kategori_id)->first();
        Livewire::test(ListProduks::class)->set('activeTab', 'semua')->call('updateTableColumnState', 'kategori_id', (string) $product->id, (string) $target->id)->assertNotified('Kategori diperbarui');
        $this->assertSame($target->id, $product->fresh()->kategori_id);
        foreach (['aktif' => true, 'nonaktif' => false, 'semua' => null] as $tab => $active) {
            $c = Livewire::test(ListProduks::class)->set('activeTab', $tab);
            $query = Produk::query();
            if ($active !== null) {
                $query->where('aktif', $active);
            }
            $this->assertEqualsCanonicalizing($query->pluck('id')->all(), $c->instance()->getFilteredTableQuery()->pluck('id')->all());
            $this->assertEquals($query->count(), $c->instance()->getTabs()[$tab]->getBadge());
        }
        $c = Livewire::test(ListProduks::class)->set('activeTab', 'semua');
        $action = $c->instance()->getTable()->getAction('delete')->record($product);
        $this->assertTrue($action->isDisabled());
        $this->assertSame('Sudah dipakai di pesanan, nonaktifkan saja', $action->getTooltip());
        $c = Livewire::test(ListKategoris::class);
        $category = $target->fresh()->loadCount('produks');
        $action = $c->instance()->getTable()->getAction('delete')->record($category);
        $this->assertTrue($action->isDisabled());
        $this->assertSame('Masih punya '.$category->produks_count.' produk', $action->getTooltip());
        foreach ([null, 999999] as $invalid) {
            try {
                app(AdminCatalog::class)->moveProducts([$product->id], $invalid);
                $this->fail();
            } catch (ValidationException) {
                $this->assertSame($target->id, $product->fresh()->kategori_id);
            }
        }
    }

    public function test_bulk_filtered_selection_and_merge_move_every_product(): void
    {
        $source = Kategori::create(['nama' => 'Sumber uji', 'slug' => 'sumber-uji']);
        $target = Kategori::first();
        for ($i = 0; $i < 30; $i++) {
            Produk::create(['nama' => 'Uji '.$i, 'kategori_id' => $source->id, 'harga' => 1000, 'aktif' => true]);
        }
        $c = Livewire::test(ListProduks::class)->filterTable('kategori_id', (string) $source->id);
        $ids = $c->instance()->getAllSelectableTableRecordKeys();
        $this->assertCount(30, $ids);
        $c->callTableBulkAction('moveCategory', $ids, ['kategori_id' => $target->id])->assertHasNoTableBulkActionErrors();
        $this->assertSame(0, $source->produks()->count());
        $this->assertSame(30, Produk::whereIn('id', $ids)->where('kategori_id', $target->id)->count());
        $one = Produk::create(['nama' => 'Merge inactive', 'kategori_id' => $source->id, 'harga' => 5, 'aktif' => false]);
        Livewire::test(ListKategoris::class)->callTableAction('mergeCategory', $source, ['kategori_id' => $source->id])->assertHasTableActionErrors(['kategori_id']);
        $this->assertModelExists($source);
        Livewire::test(ListKategoris::class)->callTableAction('mergeCategory', $source, ['kategori_id' => $target->id])->assertHasNoTableActionErrors();
        $this->assertModelMissing($source);
        $this->assertSame($target->id, $one->fresh()->kategori_id);
    }
}
