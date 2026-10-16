<?php

namespace Tests\Feature;

use Tests\TestCase;

class NewsPageTest extends TestCase
{
    public function test_news_page_renders_the_featured_story_and_expected_articles(): void
    {
        $response = $this->get('/berita');

        $response->assertOk()
            ->assertSee('Berita | Primkop Pullahta Hankam')
            ->assertSee('Kegiatan Pengecekan Stok Barang di Primkop')
            ->assertSee('Tim Primkop melakukan pengecekan stok barang dan kelengkapan produk di unit usaha')
            ->assertSee('Layanan Primkop Pullahta Hankam')
            ->assertSee('Keanggotaan, Manfaat, dan Kewajiban')
            ->assertSee('Rumah Kesejahteraan dan Gotong Royong')
            ->assertSee('01 Februari 2026')
            ->assertSee('href="' . route('news.show', 'layanan-primkop-pullahta-hankam') . '"', false)
            ->assertSee('aria-current="page"', false);

        $this->assertSame(3, substr_count($response->getContent(), 'class="news-card"'));
    }

    public function test_news_page_uses_existing_images_and_nav_links(): void
    {
        $featured = config('news.featured');
        $articles = config('news.articles');

        $this->assertFileExists(public_path($featured['image']));

        foreach ($articles as $article) {
            $this->assertFileExists(public_path($article['image']));
        }

        $this->get('/berita')
            ->assertOk()
            ->assertSee('href="' . route('news.index') . '"', false)
            ->assertSee('href="' . route('home') . '#kategori"', false)
            ->assertSee('href="' . route('products.index') . '"', false);
    }

    public function test_home_page_news_link_opens_the_news_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . route('news.index') . '"', false);
    }
}
