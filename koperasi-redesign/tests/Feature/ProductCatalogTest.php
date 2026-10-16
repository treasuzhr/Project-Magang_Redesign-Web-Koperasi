<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    public function test_homepage_displays_all_nine_categories_in_a_carousel(): void
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
            ->assertSee('class="nav-search" method="GET" action="' . route('products.index') . '"', false)
            ->assertSee('placeholder="Cari produk..."', false)
            ->assertSee('id="category-track"', false)
            ->assertSee('data-category-direction="previous"', false)
            ->assertSee('data-category-direction="next"', false)
            ->assertSee('assets/images/categories/ATK & Perlengkapan Kantor.jpg')
            ->assertSee('assets/images/categories/Kesehatan & Obat-obatan.jpg')
            ->assertSee('assets/images/categories/Pakaian & Perlengkapan Rumah.jpg')
            ->assertDontSee('category-card__image--placeholder')
            ->assertDontSee('Gambar kategori belum tersedia');

        $response->assertDontSee('class="nav-link" href="' . route('home') . '#kontak"', false);
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

    public function test_navbar_search_keeps_the_query_and_opens_the_public_catalog(): void
    {
        $this->get('/produk?q=beras')
            ->assertOk()
            ->assertSee('action="' . route('products.index') . '"', false)
            ->assertSee('name="q"', false)
            ->assertSee('value="beras"', false);
    }

    public function test_public_catalog_rejects_a_maximum_price_below_the_minimum(): void
    {
        $this->from('/produk')->get('/produk?min_price=50000&max_price=10000')
            ->assertRedirect('/produk')
            ->assertSessionHasErrors('max_price');
    }
}
