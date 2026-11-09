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

        $response->assertSee(asset('assets/images/news/berita-1.jpg'), false)
            ->assertSee('alt="Layanan Primkop Pullahta Hankam"', false)
            ->assertDontSee('menit baca')
            ->assertSee('class="button button--secondary news-back-link"', false)
            ->assertSee('Kembali ke berita')
            ->assertDontSee('news-neighbors', false)
            ->assertDontSee('Berita sebelumnya')
            ->assertDontSee('Berita berikutnya');

        $this->assertFileExists(public_path('assets/images/news/berita-1.jpg'));
    }

    public function test_homepage_links_the_layanan_article_to_its_detail_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('news.show', 'layanan-primkop-pullahta-hankam').'"', false);
    }

    public function test_unknown_news_article_slug_returns_not_found(): void
    {
        $this->get('/berita/artikel-tidak-ada')->assertNotFound()
            ->assertSee('Berita tidak ditemukan')
            ->assertSee('Kembali ke berita');
    }

    public function test_articles_without_full_content_do_not_receive_invented_authors_or_body(): void
    {
        $articles = config('news.articles');
        unset($articles[1]['sections'], $articles[1]['author'], $articles[1]['author_role']);
        config(['news.articles' => $articles]);
        $article = $articles[1];
        $this->get('/berita/keanggotaan-manfaat-dan-kewajiban')->assertOk()
            ->assertSee($article['excerpt'])
            ->assertSee('Isi berita sedang disiapkan.')
            ->assertDontSee('news-article__author', false)
            ->assertDontSee('Admin Berita')
            ->assertDontSee('article-section-0', false);
    }

    public function test_related_cards_exclude_the_current_article_and_are_limited_to_two(): void
    {
        $html = $this->get('/berita/layanan-primkop-pullahta-hankam')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'class="news-card"'));
        $this->assertStringNotContainsString('data-news-slug="layanan-primkop-pullahta-hankam"', $html);
    }

    public function test_article_sharing_metadata_and_summary_use_the_current_article(): void
    {
        $response = $this->get('/berita/layanan-primkop-pullahta-hankam')->assertOk();
        $article = $response->viewData('article');
        $this->assertStringEndsWith('…', $article['summary']);
        $this->assertLessThanOrEqual(160, mb_strlen($article['summary']));
        $this->assertStringStartsWith('Tulang punggung Primkop', $article['summary']);
        $words = preg_match_all('/[\p{L}\p{N}]+/u', collect($article['sections'])->flatMap(fn ($section) => $section['paragraphs'])->implode(' '));
        $this->assertSame(max(1, (int) ceil($words / 200)), $article['reading_minutes']);
        foreach (['WhatsApp', 'Telegram', 'Facebook', 'X', 'email'] as $channel) {
            $response->assertSee('Bagikan melalui '.$channel);
        }
        $response->assertSee('data-copy-news', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('property="og:description" content="'.$article['summary'].'"', false)
            ->assertSee('property="og:image" content="'.asset($article['image']).'"', false);
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, 'property="og:title"'));
        $this->assertSame(2, substr_count($html, '<section aria-labelledby="article-section-'));
        $this->assertCount(2, $article['sections']);
        $this->assertCount(2, $article['sections'][0]['paragraphs']);
        $this->assertCount(1, $article['sections'][1]['paragraphs']);
    }

    public function test_article_sections_are_escaped_instead_of_rendering_untrusted_html(): void
    {
        $articles = config('news.articles');
        $articles[0]['sections'][0]['paragraphs'][0] = '<script>alert("test")</script>';
        config(['news.articles' => $articles]);
        $this->get('/berita/layanan-primkop-pullahta-hankam')->assertOk()
            ->assertSee('<script>alert("test")</script>')
            ->assertDontSee('<script>alert("test")</script>', false);
    }

    public function test_second_and_third_articles_match_the_supplied_paragraphs_without_headings(): void
    {
        $expected = [
            'keanggotaan-manfaat-dan-kewajiban' => [
                'Hingga 31 Desember 2024, Primkop mencatat 160 anggota aktif semula 157 anggota pada akhir 2023, dengan dinamika masuk-keluar karena mutasi, pensiun, dan faktor lain. Angka ini menegaskan kepercayaan dan relevansi koperasi di tengah keseharian Pusdatin Kemhan. Bagi anggota yang berpartisipasi aktif, koperasi membagikan Sisa Hasil Usaha (SHU) secara proporsional. Selain itu, tersedia dana sosial bagi anggota maupun keluarga yang melahirkan ataupun meninggal dunia; serta bagi anggota yang mengikuti pendidikan lebih dari satu bulan. Skema manfaat ini mempertebal solidaritas internal.',
                'Di sisi kewajiban, anggota membayar simpanan melalui mekanisme autodebet bulanan, melunasi kewajiban finansial jika mengambil pinjaman atau transaksi kredit, serta aktif mengikuti rapat anggota sebagai forum tertinggi pengambilan keputusan. Kewajiban ini bukan beban, melainkan prasyarat tumbuhnya tata kelola yang sehat dan transparan.',
                'Dengan demikian, Primkop Pullahta Hankam hadir sebagai simpul kesejahteraan di Pusdatin Kemhan: menghimpun daya kolektif, menggerakkan layanan yang relevan, serta menebalkan rasa memiliki antar-pegawai. Dengan visi profesional, mandiri, dan terpercaya, koperasi ini tak hanya melayani kebutuhan sehari-hari, tetapi juga memperkuat budaya kebersamaan yang menjadi fondasi ketahanan institusi.',
            ],
            'rumah-kesejahteraan-dan-gotong-royong' => [
                'Di lingkungan Pusdatin Kemhan, koperasi bukan sekadar unit usaha, melainkan rumah bagi semangat kebersamaan dan peningkatan kesejahteraan pegawai. Itulah yang dihidupi oleh Primer Koperasi (Primkop) Pullahta Hankam yaitu menjadi wadah ekonomi yang profesional, mandiri, dan terpercaya, dengan kontribusi nyata bagi ekosistem kerja di Pusdatin Kemhan. Visi ini diturunkan ke dalam misi meningkatkan kesejahteraan anggota, menyediakan layanan keuangan serta kebutuhan pokok, dan menegakkan tata kelola yang akuntabel, transparan, dan modern.',
                'Primkop berdiri sebagai sarana ekonomi yang dikelola oleh insan Pusdatin Kemhan, menggerakkan roda usaha melalui simpan-pinjam, penyediaan barang konsumsi (toko), pujasera, dan beragam layanan jasa lain. Dengan semangat gotong royong dan profesionalisme, koperasi ini mengisi celah kebutuhan harian sembari menjaga keberlanjutan manfaat bagi para anggota. Landasan operasionalnya kuat: berpegang pada UU No. 25 Tahun 1992 tentang Perkoperasian serta keputusan dan penetapan internal yang memperbarui anggaran dasar dan kepengurusan.',
            ],
        ];
        foreach ($expected as $slug => $paragraphs) {
            $response = $this->get('/berita/'.$slug)->assertOk();
            $article = $response->viewData('article');
            $this->assertSame($paragraphs, $article['sections'][0]['paragraphs']);
            $this->assertArrayNotHasKey('heading', $article['sections'][0]);
            foreach ($paragraphs as $paragraph) {
                $response->assertSee('<p>'.e($paragraph).'</p>', false);
            }
            $response->assertSee('Admin Berita')->assertDontSee('Isi berita sedang disiapkan.')
                ->assertDontSee('id="article-section-', false)->assertDontSee('menit baca');
            $this->assertStringEndsWith('…', $article['summary']);
            $this->assertLessThanOrEqual(160, mb_strlen($article['summary']));
            $this->assertStringStartsWith(rtrim($article['summary'], '…'), $paragraphs[0]);
            $this->assertCount(2, $response->viewData('related'));
            $this->assertNotContains($slug, $response->viewData('related')->pluck('slug')->all());
        }
    }

    public function test_membership_inline_photo_is_after_the_third_paragraph_with_safe_attributes(): void
    {
        $response = $this->get('/berita/keanggotaan-manfaat-dan-kewajiban')->assertOk();
        $response->assertSee('data-news-inline-image', false)
            ->assertSee('alt="Anggota memilih barang di rak toko koperasi"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('width="480" height="560"', false);
        $html = $response->getContent();
        $lastParagraph = $response->viewData('article')['sections'][0]['paragraphs'][2];
        $this->assertGreaterThan(strpos($html, e($lastParagraph)), strpos($html, 'data-news-inline-image'));
        $this->assertFileExists(public_path('assets/images/news/keanggotaan-manfaat-kewajiban-2.jpg'));
        $this->get('/berita/rumah-kesejahteraan-dan-gotong-royong')->assertOk()->assertDontSee('data-news-inline-image', false);
    }

    public function test_missing_or_unsafe_inline_images_are_not_rendered(): void
    {
        $articles = config('news.articles');
        foreach (['images/berita/photo-missing.jpg', 'https://example.com/photo.jpg', '../.env', 'javascript:alert(1)', 'assets/images/news/keanggotaan-manfaat-kewajiban-2_files/style.css'] as $source) {
            $articles[1]['sections'][0]['image']['src'] = $source;
            config(['news.articles' => $articles]);
            $this->get('/berita/keanggotaan-manfaat-dan-kewajiban')->assertOk()
                ->assertDontSee('data-news-inline-image', false);
        }
    }

    public function test_inline_image_alt_text_is_escaped_and_extra_attributes_are_ignored(): void
    {
        $articles = config('news.articles');
        $articles[1]['sections'][0]['image']['alt'] = '" onerror="alert(1)';
        $articles[1]['sections'][0]['image']['onerror'] = 'alert(1)';
        config(['news.articles' => $articles]);
        $this->get('/berita/keanggotaan-manfaat-dan-kewajiban')->assertOk()
            ->assertSee('alt="&quot; onerror=&quot;alert(1)"', false)
            ->assertDontSee(' onerror="alert(1)', false);
    }

    public function test_homepage_supporting_news_images_titles_and_links_open_their_details(): void
    {
        $html = $this->get('/')->assertOk()->assertDontSee('menit baca')->getContent();
        foreach (['keanggotaan-manfaat-dan-kewajiban', 'rumah-kesejahteraan-dan-gotong-royong'] as $slug) {
            $this->assertSame(3, substr_count($html, 'href="'.route('news.show', $slug).'"'));
        }
        $this->assertStringNotContainsString(route('news.index').'#news-list', $html);
    }
}
