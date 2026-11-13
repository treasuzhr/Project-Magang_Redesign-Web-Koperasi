<?php

namespace Tests\Feature;

use App\AdminCatalog;
use App\AdminLoans;
use App\AdminOrders;
use App\Filament\Resources\Kategoris\KategoriResource;
use App\Filament\Resources\Kategoris\Pages\CreateKategori;
use App\Filament\Resources\Kategoris\Pages\EditKategori;
use App\Filament\Resources\Kategoris\Pages\ListKategoris;
use App\Filament\Resources\Produks\Pages\CreateProduk;
use App\Filament\Resources\Produks\Pages\EditProduk;
use App\Filament\Resources\Produks\Pages\ListProduks;
use App\Filament\Resources\Produks\ProdukResource;
use App\Models\Anggota;
use App\Models\Kategori;
use App\Models\PinjamanJenis;
use App\Models\Produk;
use App\Models\User;
use App\ProductCsvImporter;
use Database\Seeders\CooperativeDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
    }

    private function category(): Kategori
    {
        return app(AdminCatalog::class)->saveCategory(['nama' => 'Kategori Fiktif']);
    }

    private function csv(string $content): array
    {
        $file = UploadedFile::fake()->createWithContent('produk.csv', $content);

        return app(ProductCsvImporter::class)->import($file->getRealPath());
    }

    private function input(Kategori $category, string $name = 'Produk Fiktif'): array
    {
        return ['nama' => $name, 'kategori_id' => $category->id, 'harga' => 1000, 'aktif' => true];
    }

    public function test_create_edit_product_zero_price_photo_and_no_delete(): void
    {
        Storage::fake('public');
        $this->admin();
        $category = $this->category();
        Livewire::test(CreateProduk::class)->fillForm($this->input($category) + ['foto' => UploadedFile::fake()->image('produk.jpg')])->call('create')->assertHasNoFormErrors();
        $product = Produk::where('nama', 'Produk Fiktif')->firstOrFail();
        Storage::disk('public')->assertExists($product->foto);
        Livewire::test(EditProduk::class, ['record' => (string) $product->id])->fillForm(['harga' => 0, 'aktif' => false])->call('save')->assertHasNoFormErrors();
        $this->assertSame(0, $product->fresh()->harga);
        $this->assertFalse($product->fresh()->aktif);
        $this->assertFalse(ProdukResource::canDelete($product));
        $this->assertFalse(ProdukResource::canDeleteAny());
        Livewire::test(ListProduks::class)->assertTableActionDisabled('delete');
        $this->assertDatabaseHas('produks', ['id' => $product->id]);
    }

    public function test_negative_fractional_and_nonnumeric_prices_are_rejected(): void
    {
        $this->admin();
        $category = $this->category();
        foreach ([-1, '1.5', 'bukan angka'] as $price) {
            Livewire::test(CreateProduk::class)->fillForm(array_replace($this->input($category), ['harga' => $price]))->call('create')->assertHasFormErrors(['harga']);
        }
        $this->assertSame(0, Produk::count());
    }

    public function test_photo_rejects_nonimages_and_files_over_two_megabytes(): void
    {
        Storage::fake('public');
        $this->admin();
        $category = $this->category();
        $oversize = UploadedFile::fake()->image('besar.jpg');
        file_put_contents($oversize->getRealPath(), str_repeat(' ', 2049 * 1024), FILE_APPEND);
        foreach ([UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'), $oversize] as $photo) {
            Livewire::test(CreateProduk::class)->fillForm($this->input($category) + ['foto' => $photo])->call('create')->assertHasFormErrors(['foto']);
        }
        $this->assertSame(0, Produk::count());
    }

    public function test_category_slug_crud_uniqueness_and_guard_against_deleting_used_categories(): void
    {
        $this->admin();
        Livewire::test(CreateKategori::class)->fillForm(['nama' => 'Kategori Kantor'])->call('create')->assertHasNoFormErrors();
        $category = Kategori::where('slug', 'kategori-kantor')->firstOrFail();
        Livewire::test(EditKategori::class, ['record' => (string) $category->id])->fillForm(['nama' => 'Kategori Harian'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('kategori-harian', $category->fresh()->slug);
        Livewire::test(CreateKategori::class)->fillForm(['nama' => 'Kategori Harian'])->call('create')->assertHasFormErrors(['nama']);
        Livewire::test(CreateKategori::class)->fillForm(['nama' => '!!!'])->call('create')->assertHasFormErrors(['nama']);
        try {
            app(AdminCatalog::class)->saveCategory(['nama' => 'Kategori Harian']);
            $this->fail('Slug duplikat harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors());
        }
        app(AdminCatalog::class)->saveProduct($this->input($category));
        $this->assertFalse(KategoriResource::canDelete($category->fresh()));
        Livewire::test(ListKategoris::class)->assertTableActionDisabled('delete', $category);
        try {
            app(AdminCatalog::class)->deleteCategory($category);
            $this->fail('Kategori berproduk tidak boleh dihapus.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('kategoris', ['id' => $category->id]);
        }
        $empty = app(AdminCatalog::class)->saveCategory(['nama' => 'Kategori Kosong']);
        Livewire::test(ListKategoris::class)->callTableAction('delete', $empty)->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing('kategoris', ['id' => $empty->id]);
    }

    public function test_csv_valid_rows_bad_prices_unknown_categories_and_existing_products_are_reported(): void
    {
        $this->admin();
        $category = $this->category();
        $existing = app(AdminCatalog::class)->saveProduct(array_replace($this->input($category, 'Produk Lama'), ['aktif' => false, 'foto' => 'produk/lama.jpg']));
        $result = $this->csv("\xEF\xBB\xBFnama,harga,kategori\nProduk Lama,2500,Kategori Fiktif\nProduk Baru,0,Kategori Fiktif\nPecahan,1.5,Kategori Fiktif\nTak dikenal,100,Kategori Tidak Ada\nProduk Lama,3000,Kategori Fiktif\n");
        $this->assertSame([1, 2, 2], [$result['created'], $result['updated'], $result['skipped']]);
        $this->assertSame([4, 5], array_column($result['errors'], 'row'));
        $this->assertStringContainsString('bilangan bulat', $result['errors'][0]['reason']);
        $this->assertSame('Kategori tidak dikenal.', $result['errors'][1]['reason']);
        $this->assertSame(3000, $existing->fresh()->harga);
        $this->assertFalse($existing->fresh()->aktif);
        $this->assertSame('produk/lama.jpg', $existing->fresh()->foto);
        $this->assertDatabaseHas('produks', ['nama' => 'Produk Baru', 'harga' => 0, 'aktif' => true]);
        $this->assertSame(2, Produk::count());
        try {
            $this->csv("nama,jumlah\nX,2\n");
            $this->fail('Header tidak valid harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('csv', $exception->errors());
        }
    }

    public function test_seven_hundred_csv_rows_use_bounded_batch_queries(): void
    {
        $this->admin();
        $this->category();
        $content = "nama,harga,kategori\n";
        for ($index = 0; $index < 700; $index++) {
            $content .= 'Produk '.$index.','.($index + 1000).",Kategori Fiktif\n";
        }
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            if (str_contains($query->sql, 'produks') || str_contains($query->sql, 'kategoris')) {
                $queries++;
            }
        });
        $result = $this->csv($content);
        $this->assertSame([700, 0, 0], [$result['created'], $result['updated'], $result['skipped']]);
        $this->assertLessThanOrEqual(25, $queries);
        $this->assertSame(700, Produk::count());
        $updated = $this->csv("nama,harga,kategori\nPRODUK 0,999,Kategori Fiktif\n");
        $this->assertSame(1, $updated['updated']);
        $this->assertSame(700, Produk::count());
        $this->assertDatabaseHas('produks', ['nama' => 'Produk 0', 'harga' => 999]);
    }

    public function test_csv_action_displays_counts_reasons_and_provides_a_downloadable_template(): void
    {
        Storage::fake('local');
        $this->admin();
        $this->category();
        $file = UploadedFile::fake()->createWithContent('produk.csv', "nama,harga,kategori\nBarang Import,3500,Kategori Fiktif\nTidak Valid,-1,Kategori Fiktif\n");
        $component = Livewire::test(ListProduks::class)->callAction('importProducts', ['csv' => $file])->assertHasNoActionErrors()->assertNotified();
        $result = $component->get('importResult');
        $this->assertSame([1, 0, 1], [$result['created'], $result['updated'], $result['skipped']]);
        $this->assertSame(3, $result['errors'][0]['row']);
        $component->mountAction('importResult')->assertActionDataSet(['report' => "Dibuat: 1\nDiperbarui: 0\nDilewati: 1\nBaris 3: Harga tidak boleh negatif."]);
        $this->assertSame([], Storage::disk('local')->allFiles('admin-imports'));
        $this->get(route('admin.produk.template'))->assertDownload('templat-produk.csv')->assertStreamedContent("\xEF\xBB\xBFnama,harga,kategori\n");
        $invalid = UploadedFile::fake()->createWithContent('salah.csv', "nama,jumlah\nBarang,1\n");
        Livewire::test(ListProduks::class)->callAction('importProducts', ['csv' => $invalid])->assertHasActionErrors(['csv']);
    }

    public function test_toggle_and_filters_work_and_inactive_products_disappear_from_the_public_catalog(): void
    {
        $this->admin();
        $category = $this->category();
        $product = app(AdminCatalog::class)->saveProduct($this->input($category, 'Produk Eksklusif'));
        $this->assertSame(1, $this->get('/produk?q=Eksklusif')->assertOk()->viewData('products')->total());
        Livewire::test(ListProduks::class)->call('updateTableColumnState', 'aktif', (string) $product->id, false)->assertHasNoErrors();
        $this->assertFalse($product->fresh()->aktif);
        $this->assertSame(0, $this->get('/produk?q=Eksklusif')->assertOk()->viewData('products')->total());
        Livewire::test(ListProduks::class)->set('activeTab', 'semua')->filterTable('aktif', false)->assertCanSeeTableRecords([$product]);
        Livewire::test(ListProduks::class)->set('activeTab', 'semua')->filterTable('kategori_id', (string) $category->id)->searchTable('Eksklusif')->assertCanSeeTableRecords([$product]);
    }

    public function test_members_and_guests_cannot_access_catalog_administration_or_import(): void
    {
        $this->get('/admin/produk')->assertRedirect('/login');
        $this->get('/admin/kategori')->assertRedirect('/login');
        $this->admin();
        $category = $this->category();
        $product = app(AdminCatalog::class)->saveProduct($this->input($category));
        $member = User::factory()->create(['role' => 'anggota', 'must_change_password' => false]);
        Anggota::factory()->create(['user_id' => $member->id]);
        $this->actingAs($member);
        foreach (['/admin/produk', '/admin/produk/create', '/admin/produk/'.$product->id.'/edit', '/admin/kategori', '/admin/kategori/create', '/admin/kategori/'.$category->id.'/edit', route('admin.produk.template')] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->expectException(HttpException::class);
        $this->csv("nama,harga,kategori\nX,1,Kategori Fiktif\n");
    }

    public function test_demo_admin_workflow_is_visible_to_both_demo_members(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $admin = User::where('nip', CooperativeDemoSeeder::ADMIN_NIP)->firstOrFail();
        $first = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail();
        $second = User::where('nip', CooperativeDemoSeeder::ANGGOTA_KEDUA_NIP)->firstOrFail();
        foreach ([$admin, $first, $second] as $user) {
            $user->update(['must_change_password' => false]);
        }
        $this->withSession(['captcha_answer' => 'ABC123'])->post('/login', ['member_id' => $admin->nip, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'ABC123'])->assertRedirect('/admin');
        $kind = PinjamanJenis::where('kode', 'usipa')->firstOrFail();
        $loan = $first->anggota->pinjamans()->create(['pinjaman_jenis_id' => $kind->id, 'kode' => 'VERIFIKASI-DEMO', 'jumlah' => 12000000, 'tenor_bulan' => 12, 'bunga_persen_per_bulan' => 1, 'metode_bayar' => 'potong_gaji', 'status' => 'diajukan', 'tanggal_pengajuan' => today()]);
        $loans = app(AdminLoans::class);
        $loans->approve($loan);
        $loans->disburse($loan);
        $loans->markPaid($loan, [$loan->angsurans()->firstOrFail()->id], today()->toDateString());
        $order = $first->anggota->pesanans()->where('status', 'baru')->firstOrFail();
        foreach (['diproses', 'siap', 'selesai'] as $target) {
            app(AdminOrders::class)->transition($order, $target);
        }
        $category = $this->category();
        app(AdminCatalog::class)->saveProduct($this->input($category, 'Produk Verifikasi Demo'));
        $this->assertSame(1, $this->csv("nama,harga,kategori\nProduk CSV Demo,6000,Kategori Fiktif\n")['created']);
        $dashboard = $this->actingAs($first)->get('/anggota')->assertOk();
        $this->assertSame(11000000, $dashboard->viewData('stats')['pinjaman'][0]['amount']);
        $this->get('/anggota/pinjaman/'.$loan->id)->assertOk()->assertSee('Rp 11.000.000');
        $this->get('/anggota/pesanan/'.$order->id)->assertOk()->assertSee('Selesai');
        $dashboard = $this->actingAs($second)->get('/anggota')->assertOk();
        $this->assertSame(800000, $dashboard->viewData('stats')['pinjaman'][0]['amount']);
        $this->get('/anggota/pinjaman/'.$second->anggota->pinjamans()->where('status', 'berjalan')->firstOrFail()->id)->assertOk();
        $this->get('/anggota/pinjaman/'.$loan->id)->assertNotFound();
        $this->get('/anggota/pesanan/'.$order->id)->assertNotFound();
    }
}
