<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
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
            ->assertSee('Arahkan kursor ke kategori untuk melihat pratinjaunya.')
            ->assertDontSee('data-scroll-statement', false)
            ->assertDontSee('Gulir untuk mengenal Primkop')
            ->assertDontSee('Gulir untuk melihat apa saja yang bisa Anda lakukan sebagai anggota.')
            ->assertDontSee('home-about__seal', false)
            ->assertSee('data-count-up', false)
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

    public function test_public_catalog_shows_search_and_preserves_supported_filter_inputs(): void
    {
        $response = $this->get('/produk?q=susu&category=minuman-susu&min_price=1000&max_price=50000&sort=price_asc&view=list&page=2');

        $response->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee('value="susu"', false)
            ->assertSee('name="category"', false)
            ->assertSee('value="minuman-susu" selected', false)
            ->assertSee('name="min_price"', false)
            ->assertSee('value="1000"', false)
            ->assertSee('value="50000"', false)
            ->assertSee('value="price_asc" selected', false)
            ->assertSee('Data produk belum terhubung.')
            ->assertSee('Titip Barang')
            ->assertSee('Produk');
    }

    public function test_catalog_search_keeps_the_query(): void
    {
        $this->get('/produk?q=beras')
            ->assertOk()
            ->assertSee('action="'.route('products.index').'"', false)
            ->assertSee('name="q"', false)
            ->assertDontSee('class="nav-search"', false)
            ->assertSee('value="beras"', false);
    }

    public function test_public_catalog_rejects_a_maximum_price_below_the_minimum(): void
    {
        $this->from('/produk')->get('/produk?min_price=50000&max_price=10000')
            ->assertRedirect('/produk')
            ->assertSessionHasErrors('max_price');
    }
}
