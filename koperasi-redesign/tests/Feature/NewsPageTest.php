<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class NewsPageTest extends TestCase
{
    public function test_news_page_renders_the_featured_story_and_expected_articles(): void
    {
        $response = $this->get('/berita');

        $response->assertOk()
            ->assertSee('Berita | Primkop Pullahta Hankam')
            ->assertDontSee('Kegiatan Pengecekan Stok Barang di Primkop')
            ->assertSee('Tulang punggung Primkop adalah layanan simpan dan pinjam.')
            ->assertSee('Layanan Primkop Pullahta Hankam')
            ->assertSee('Keanggotaan, Manfaat, dan Kewajiban')
            ->assertSee('Rumah Kesejahteraan dan Gotong Royong')
            ->assertSee('01 Februari 2026')
            ->assertSee('href="'.route('news.show', 'layanan-primkop-pullahta-hankam').'"', false)
            ->assertSee('aria-current="page"', false);

        $this->assertSame(2, substr_count($response->getContent(), 'class="news-card"'));
        $response->assertSee('TERBARU')->assertSee('menit baca')->assertSee('news-page__grid--pair', false);
        foreach (config('news.articles') as $article) {
            $slug = $article['slug'] ?? Str::slug($article['title']);
            $this->assertSame(1, substr_count($response->getContent(), 'data-news-slug="'.$slug.'"'));
            $this->assertStringNotContainsString('...', $article['excerpt']);
        }
        $response->assertDontSee('news-pagination', false)->assertDontSee('type="search"', false);
    }

    public function test_latest_article_becomes_hero_and_is_not_repeated_in_the_grid(): void
    {
        $articles = config('news.articles');
        $articles[2]['datetime'] = '2026-03-01';
        config(['news.articles' => [...$articles, $articles[2]]]);
        $html = $this->get('/berita')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="news-feature-title"[^>]*>Rumah Kesejahteraan dan Gotong Royong<\/h1>/', $html);
        $this->assertSame(1, substr_count($html, 'data-news-slug="rumah-kesejahteraan-dan-gotong-royong"'));
        $this->assertSame(2, substr_count($html, 'class="news-card"'));
    }

    public function test_incomplete_summary_uses_the_first_sentence_and_a_word_boundary(): void
    {
        $article = config('news.articles.0');
        $article['excerpt'] = 'Potongan manual...';
        $article['sections'] = [['paragraphs' => [str_repeat('Koperasi anggota ', 15).'. Kalimat berikutnya.']]];
        config(['news.articles' => [$article]]);
        $response = $this->get('/berita')->assertOk();
        $summary = $response->viewData('featured')['summary'];
        $this->assertLessThanOrEqual(160, mb_strlen($summary));
        $this->assertStringEndsWith('…', $summary);
        $this->assertStringNotContainsString('manual', $summary);
        $this->assertStringNotContainsString('berikutnya', $summary);
        $this->assertMatchesRegularExpression('/(?:Koperasi|anggota)…$/u', $summary);
    }

    public function test_more_than_nine_articles_are_paginated_without_repeating_the_hero(): void
    {
        $base = config('news.articles.0');
        $articles = [];
        for ($index = 1; $index <= 12; $index++) {
            $articles[] = array_merge($base, ['slug' => 'article-'.$index, 'datetime' => sprintf('2026-03-%02d', $index)]);
        }
        config(['news.articles' => $articles]);
        $first = $this->get('/berita')->assertOk()->assertSee('news-pagination', false)->getContent();
        $second = $this->get('/berita?page=2')->assertOk()->getContent();
        $this->assertSame(8, substr_count($first, 'class="news-card"'));
        $this->assertSame(3, substr_count($second, 'class="news-card"'));
        $this->assertSame(1, substr_count($first, 'data-news-slug="article-12"'));
        $this->assertStringNotContainsString('data-news-slug="article-11"', $second);
        $this->get('/berita?page=3')->assertNotFound();
    }

    public function test_empty_news_collection_and_invalid_page_are_handled(): void
    {
        config(['news.articles' => []]);
        $this->get('/berita')->assertOk()->assertSee('Belum ada berita.')->assertDontSee('data-news-hero', false);
        $this->from('/berita')->get('/berita?page=0')->assertRedirect('/berita')->assertSessionHasErrors('page');
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
            ->assertSee('href="'.route('news.index').'"', false)
            ->assertSee('href="'.route('home').'#kategori"', false)
            ->assertSee('href="'.route('products.index').'"', false);
    }

    public function test_home_page_news_link_opens_the_news_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('news.index').'"', false);
    }
}
