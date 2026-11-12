<?php

namespace Tests\Feature;

use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_cart_loads_only_requested_active_products_from_server(): void
    {
        $product = Produk::create(['nama' => 'Produk contoh', 'harga' => 750, 'aktif' => true]);
        $inactive = Produk::create(['nama' => 'Tidak aktif', 'harga' => 100, 'aktif' => false]);
        $this->get('/keranjang')->assertOk()->assertSee('Keranjang')->assertSee('data-products-url=', false)->assertDontSee('Produk contoh');
        $this->getJson('/keranjang/produk?'.http_build_query(['ids' => [$product->id, $inactive->id, 99999]]))->assertOk()
            ->assertJsonCount(1, 'products')->assertJsonPath('products.0.id', $product->id)->assertJsonPath('products.0.price', 750);
        $product->update(['harga' => 900]);
        $this->getJson('/keranjang/produk?ids[]='.$product->id)->assertOk()->assertJsonPath('products.0.price', 900);
        $this->getJson('/keranjang/produk?ids[]=slug-lama')->assertUnprocessable();
    }

    public function test_empty_cart_shell_has_no_embedded_catalog(): void
    {
        $this->get('/keranjang')->assertOk()->assertSee('Keranjang masih kosong')->assertDontSee('data-cart-item ', false);
    }
}
