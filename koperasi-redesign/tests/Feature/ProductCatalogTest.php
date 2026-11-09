<?php

namespace Tests\Feature;

use App\ProductSource;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    public function test_public_footers_link_directly_to_the_product_catalog(): void
    {
        foreach (['home', 'about', 'products.index', 'news.index', 'contact', 'cart.index'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('<footer class="site-footer"', false)
                ->assertSee('<a href="'.route('products.index').'">Produk</a>', false)
                ->assertDontSee('Kategori Produk');
        }
    }

    public function test_category_product_details_only_render_when_provided(): void
    {
        $this->get('/')->assertOk()->assertDontSee('class="category-showcase__sample"', false);

        $categories = config('product_categories');
        $categories[0]['products_count'] = 12;
        $categories[0]['products'] = [
            ['name' => 'Produk pengujian', 'price' => 1500, 'url' => '/produk?q=pengujian'],
            ['name' => 'Produk tanpa tautan', 'price' => 2000],
        ];
        config(['product_categories' => $categories]);

        $this->get('/')->assertOk()
            ->assertSee('12 produk')
            ->assertSee('Produk pengujian')
            ->assertSee('Rp 1.500')
            ->assertDontSee('Produk tanpa tautan');
    }

    public function test_homepage_displays_all_nine_categories_in_a_showcase(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSeeInOrder([
                'ATK &amp; Perlengkapan Kantor',
                'Kebersihan Rumah',
                'Perawatan Diri',
                'Kesehatan &amp; Obat-obatan',
                'Sembako',
                'Minuman &amp; Susu',
                'Makanan &amp; Camilan',
                'Pakaian &amp; Perlengkapan Rumah',
                'Rokok',
            ], false)
            ->assertSee('Produk')
            ->assertDontSee('class="nav-search"', false)
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSee('data-category-showcase', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('data-category-segment="8"', false)
            ->assertSee(' / 09', false)
            ->assertSee('data-category-mobile-link', false)
            ->assertSee('category-showcase__preview')
            ->assertDontSee('Arahkan kursor ke kategori untuk melihat pratinjaunya.')
            ->assertDontSee('data-scroll-statement', false)
            ->assertDontSee('Gulir untuk mengenal Primkop')
            ->assertDontSee('Gulir untuk melihat apa saja yang bisa Anda lakukan sebagai anggota.')
            ->assertDontSee('home-about__seal', false)
            ->assertDontSee('data-count-up', false)
            ->assertSee('id="layanan-anggota"', false)
            ->assertSee('Satu situs,', false)
            ->assertSee('Simpanan yang bisa Anda pantau')
            ->assertSee('Pinjaman dengan simulasi di depan')
            ->assertSee('SHU dari tahun ke tahun')
            ->assertSee('Belanja dari meja kerja')
            ->assertSee('id="home-about-title"', false)
            ->assertSee('id="news-title"', false)
            ->assertSee('home-news-feature')
            ->assertSee('home-news-feature__latest')
            ->assertDontSee('menit baca')
            ->assertSee('home-news-list')
            ->assertDontSee('Kembali ke atas')
            ->assertSee('Sudah jadi anggota?')
            ->assertSee('anggota aktif per 31 Desember 2024')
            ->assertDontSee('200+')
            ->assertSee('assets/images/categories/ATK & Perlengkapan Kantor.jpg')
            ->assertSee('assets/images/categories/Kesehatan & Obat-obatan.jpg')
            ->assertSee('assets/images/categories/Pakaian & Perlengkapan Rumah.jpg')
            ->assertDontSee('category-card__image--placeholder')
            ->assertDontSee('Gambar kategori belum tersedia');

        $this->assertSame(4, substr_count($response->getContent(), 'class="service-card service-card--'));
        $response->assertDontSee('class="nav-link" href="'.route('home').'#kontak"', false);
    }

    public function test_catalog_displays_all_fifteen_supplied_products_and_preserves_codes(): void
    {
        $response = $this->get('/produk');
        $response->assertOk()
            ->assertSee('Adem Sari Chingku')
            ->assertSee('Dettol Refil Body Wash Fresh 410 ML')
            ->assertSee('Amplop Coklat A3')
            ->assertSee('Amplop Coklat A4')
            ->assertSee('Amplop Paperline 104(K)')
            ->assertSee('Amplop Paperline 90(B)')
            ->assertSee('Rp 36.000')
            ->assertSee('Rp 750')
            ->assertDontSee('Data produk belum terhubung.')
            ->assertDontSee('Titip Barang')
            ->assertDontSee('Mode data contoh');
        $this->assertSame(15, substr_count($response->getContent(), 'data-product-card '));
        $this->assertSame(15, $response->viewData('products')->total());
        $this->assertSame('ADEM SARI CHINGKU', $response->viewData('products')->first()['name']);
        $this->assertSame('DETTOL REFIL BODY WASH FRESH 410 ML', config('product_catalog.samples.0.name'));
        $this->assertSame(36000, config('product_catalog.samples.0.price'));
        $this->assertNull(config('product_catalog.samples.2.category'));
    }

    public function test_legacy_category_links_and_filter_names_still_work(): void
    {
        $response = $this->get('/produk?q=amplop&category=atk-perlengkapan-kantor&min_price=10000&max_price=50000&sort=price_asc&view=list');
        $response->assertOk()->assertSee('value="amplop"', false)->assertSee('pc-product-grid is-list');
        $this->assertSame(2, $response->viewData('products')->total());
        $this->assertEquals(['min' => '10000', 'max' => '50000', 'kategori' => 'atk-perlengkapan-kantor'], array_intersect_key($response->viewData('filters'), array_flip(['min', 'max', 'kategori'])));
    }

    public function test_search_category_price_and_sort_can_be_combined(): void
    {
        $response = $this->get('/produk?q=agar&kategori=makanan-camilan&min=5.000&max=6000&urut=price_desc&tampilan=list');
        $response->assertOk()->assertSee('value="agar"', false)->assertSee('pc-product-grid is-list');
        $this->assertSame(4, $response->viewData('products')->total());
        $this->assertSame(6000, $response->viewData('products')->first()['price']);
        $this->assertSame('5000', $response->viewData('filters')['min']);
        $this->assertSame(6, $response->viewData('categoryCounts')['atk-perlengkapan-kantor']);
        $this->assertSame(15, $response->viewData('sourceTotal'));
    }

    public function test_catalog_sorts_names_and_integer_prices(): void
    {
        foreach (['name_asc' => 'ADEM SARI CHINGKU', 'name_desc' => 'GARNIER COLOUR RAMBUT', 'price_asc' => 'AMPLOP COKLAT A4', 'price_desc' => 'DETTOL REFIL BODY WASH FRESH 410 ML'] as $sort => $expected) {
            $response = $this->get('/produk?urut='.$sort);
            $response->assertOk();
            $this->assertSame($expected, $response->viewData('products')->first()['name']);
        }
    }

    public function test_empty_search_is_distinct_from_an_unavailable_source(): void
    {
        $this->get('/produk?q=tidak-ada-produk-ini')->assertOk()
            ->assertSee('Produk tidak ditemukan')
            ->assertSee('Atur ulang filter')
            ->assertDontSee('Katalog sedang disiapkan');
        config(['product_catalog.samples' => []]);
        $this->get('/produk')->assertOk()->assertSee('Katalog sedang disiapkan')->assertDontSee('Produk tidak ditemukan');
    }

    public function test_source_failure_does_not_expose_technical_details(): void
    {
        $source = \Mockery::mock(ProductSource::class);
        $source->shouldReceive('all')->once()->andThrow(new \RuntimeException('private source diagnostic'));
        $this->app->instance(ProductSource::class, $source);
        $this->get('/produk')->assertOk()->assertSee('Katalog sedang disiapkan')->assertDontSee('private source diagnostic');
    }

    public function test_invalid_price_range_keeps_inputs_and_shows_a_specific_error(): void
    {
        $this->get('/produk?q=agar&min=50000&max=10000')->assertStatus(422)
            ->assertSee('Harga minimum tidak boleh lebih besar dari harga maksimum.')
            ->assertSee('value="50000"', false)->assertSee('value="10000"', false)->assertSee('value="agar"', false);
        $this->getJson('/produk?min=-1&max=abc')->assertStatus(422)->assertJsonPath('invalid', true);
        $this->get('/produk?kategori[]=sembako&q[]=agar')->assertStatus(422);
    }

    public function test_pagination_keeps_query_filters_and_supports_partial_rendering(): void
    {
        config(['product_catalog.per_page' => 6]);
        $response = $this->get('/produk?min=1000&urut=price_asc&page=2');
        $response->assertOk()->assertSee('pc-pagination')->assertSee('page=3', false);
        $this->assertCount(6, $response->viewData('products'));
        $this->assertSame(14, $response->viewData('products')->total());
        $this->assertStringContainsString('min=1000', $response->viewData('products')->nextPageUrl());
        $this->get('/produk?page=99')->assertNotFound();
        $partial = $this->getJson('/produk?min=1000&urut=price_asc&page=2');
        $partial->assertOk()->assertJsonStructure(['html', 'url', 'invalid'])->assertJsonPath('invalid', false);
        $this->assertSame(6, substr_count($partial->json('html'), 'data-product-card '));
        $this->assertStringNotContainsString('<html', $partial->json('html'));
        $this->assertStringContainsString('page=2', $partial->json('url'));
        $this->assertStringContainsString('urut=price_asc', $response->viewData('products')->previousPageUrl());
        config(['product_catalog.per_page' => 1]);
        $this->get('/produk?min=1000&urut=price_asc&page=7')->assertOk()
            ->assertSee('&hellip;', false)->assertSee('Sebelumnya')->assertSee('Berikutnya');

    }

    public function test_example_label_is_hidden_in_every_environment(): void
    {
        $this->app->instance('env', 'local');
        $this->get('/produk')->assertOk()->assertDontSee('Mode data contoh');
        $this->app->instance('env', 'production');
        $this->get('/produk')->assertOk()->assertDontSee('Mode data contoh');
    }
}
