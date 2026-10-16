<?php

namespace Tests\Feature;

use Tests\TestCase;

class NewsArticlePageTest extends TestCase
{
    public function test_layanan_article_displays_the_supplied_content_and_metadata(): void
    {
        $response = $this->get(route('news.show', 'layanan-primkop-pullahta-hankam'));

        $response->assertOk()
            ->assertSee('Layanan Primkop Pullahta Hankam')
            ->assertSee('01 Februari 2026')
            ->assertSee('Admin')
            ->assertSee('Admin Berita')
            ->assertSee('Layanan Inti yang Membumi')
            ->assertSee('Tulang punggung Primkop adalah layanan simpan dan pinjam. Di sisi simpanan, terdapat Simpanan Pokok (setor sekali saat menjadi anggota), Simpanan Wajib, serta Simpanan Sukarela (fleksibel dan dapat diambil kapan diperlukan).')
            ->assertSee('Bagi anggota yang ingin memperkuat permodalan koperasi, tersedia Simpanan Pemodal. Sementara sebagian SHU dari unit tertentu disimpan sebagai Simpanan SHU USIPA Khusus hingga anggota pensiun atau pindah dari Pusdatin Kemhan. Keseluruhan ragam ini dirancang agar anggota bisa menabung dengan aman sekaligus menumbuhkan koperasi.')
            ->assertSee('Unit Usaha yang Dekat dengan Kebutuhan')
            ->assertSee('Di luar simpan-pinjam, Primkop mengelola Def.MART: toko yang menyediakan kebutuhan kantor dan kebutuhan harian di lingkungan Pusdatin Kemhan. Anggota Pusdatin Kemhan dapat menikmati kudapan dan minuman, bercengkerama di ruang yang disediakan (termasuk gazebo), menjadi pelanggan kredit (pembayaran via gaji), hingga mengakses pinjaman kecil sesuai ketentuan. Kehadirannya membuat aktivitas sehari-hari lebih efisien sekaligus menghidupkan interaksi sosial yang sehat di tempat kerja. Di sisi lain, Pujasera menyediakan aneka makanan dan minuman mulai dari nasi lauk-pauk, mie, soto dan bakso, kopi/teh, jus hingga es campur yang berada dekat dengan area kerja, sehingga menunjang produktivitas dan kebersamaan saat jeda.')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Bagikan melalui WhatsApp')
            ->assertSee('Bagikan melalui email');

        $response->assertSee(asset('assets/images/news/berita-1.png'), false)
            ->assertSee('alt="Layanan simpan pinjam dan unit usaha Primkop Pullahta Hankam"', false);

        $this->assertFileExists(public_path('assets/images/news/berita-1.png'));
    }

    public function test_homepage_links_the_layanan_article_to_its_detail_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . route('news.show', 'layanan-primkop-pullahta-hankam') . '"', false);
    }

    public function test_unknown_news_article_slug_returns_not_found(): void
    {
        $this->get('/berita/artikel-tidak-ada')->assertNotFound();
    }
}
