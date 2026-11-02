<?php

namespace Tests\Feature;

use App\ProductSource;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    public function test_cart_renders_canonical_catalog_and_shared_navigation(): void
    {
        $response = $this->get('/keranjang');
        $response->assertOk()->assertSeeText('Keranjang belanja')
            ->assertSee('Adem Sari Chingku')->assertSee('data-price="7000"', false)
            ->assertSee('Pemesanan belum tersedia.')
            ->assertSee('href="'.route('cart.index').'" aria-label="Keranjang"', false);
        $this->assertCount(15, $response->viewData('products'));
        $this->assertTrue($response->viewData('sourceAvailable'));
        $this->get('/produk')->assertOk()->assertSee('href="'.route('cart.index').'" aria-label="Buka keranjang"', false);
    }

    public function test_empty_catalog_still_renders_an_empty_cart(): void
    {
        config(['product_catalog.samples' => []]);
        $this->get('/keranjang')->assertOk()->assertSee('Keranjang masih kosong')
            ->assertDontSee('data-cart-item ', false);
    }

    public function test_source_failure_keeps_saved_cart_and_shows_a_retry(): void
    {
        $source = \Mockery::mock(ProductSource::class);
        $source->shouldReceive('all')->once()->andThrow(new \RuntimeException('private diagnostic'));
        $this->app->instance(ProductSource::class, $source);
        $this->get('/keranjang')->assertOk()->assertSee('Keranjang belum dapat dimuat')
            ->assertSee('data-source-available="false"', false)->assertDontSee('private diagnostic');
    }
}
