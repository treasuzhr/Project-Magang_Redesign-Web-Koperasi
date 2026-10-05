<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Produk Koperasi')
            ->assertSee('Kategori Produk')
            ->assertSee('Berita &amp; Informasi', false)
            ->assertSee('Layanan Primkop Pullahta Hankam')
            ->assertSee(asset('assets/images/hero/hero-utama.jpg'))
            ->assertSee(asset('assets/images/categories/snack.png'))
            ->assertSee(asset('assets/images/news/berita-1.jpg'))
            ->assertSee('Layanan Anggota')
            ->assertSee('Pengajuan Pinjaman')
            ->assertSee('Telepon: 3215');
    }
}
