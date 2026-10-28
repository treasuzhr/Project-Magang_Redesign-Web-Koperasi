<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    public function test_about_page_renders_all_people_in_their_card_groups(): void
    {
        $response = $this->get('/tentang-kami');
        $people = $this->flattenPeople(config('organization'));

        $response->assertOk()
            ->assertSee('Tentang Kami')
            ->assertSee('Struktur Pengurus Koperasi')
            ->assertDontSee('vision-title', false)
            ->assertDontSee('mission-title', false)
            ->assertDontSee('Menjadi koperasi yang mandiri, modern, dan terpercaya')
            ->assertSeeInOrder(['class="about-overview"', 'data-organization', 'id="about-contact-title"'], false)
            ->assertSee('Melapor kepada')
            ->assertSee('Bayu Istanto Gunawan Utomo, S.E.')
            ->assertSee('Akhmad Kosim, S.H.')
            ->assertSee('Marah Alam Tanjung, S.Kom., M.MT., M.Sc.');

        $response->assertSeeInOrder([
            'data-person-id="isam-adi"',
            'data-person-id="bayu-istanto-gunawan-utomo"',
            'data-person-id="akhmad-kosim"',
            'data-person-id="fadil-armansyah"',
            'data-person-id="gandung-purnomoaji"',
            'data-person-id="dwi-utami"',
            'data-person-id="marah-alam-tanjung"',
            'data-person-id="thia-karmila"',
            'data-person-id="anggraeni-tri-windyastuti"',
            'data-person-id="heribertus-sigit-prasetya"',
            'data-person-id="dewi-ratri-teheranda"',
            'data-person-id="lusy-tri-afzami"',
            'data-person-id="hidayatullah-suryaputra"',
        ], false);

        $this->assertCount(13, $people);
        $names = array_column($people, 'name');
        $this->assertCount(count($names), array_unique($names));
        $this->assertSame(13, substr_count($response->getContent(), 'class="org-person"'));
        foreach ($people as $person) {
            $this->assertSame(1, substr_count($response->getContent(), 'data-person-id="'.$person['id'].'"'));
            $response->assertSee($person['name'])->assertSee($person['position']);
        }
    }

    public function test_every_card_preserves_its_parent_and_photo(): void
    {
        $people = $this->flattenPeople(config('organization'));
        $byName = array_column($people, null, 'name');
        $html = $this->get('/tentang-kami')->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);

        foreach ($people as $person) {
            $card = $xpath->query('//*[@data-person-id="'.$person['id'].'"]')->item(0);
            $this->assertNotNull($card);
            $this->assertSame($person['reports_to'] ? $byName[$person['reports_to']]['id'] : '', $card->getAttribute('data-parent-id'));
            $this->assertSame(asset($person['photo']), $xpath->query('.//img', $card)->item(0)->getAttribute('src'));
            if ($person['reports_to']) {
                $this->assertStringContainsString($person['reports_to'], $xpath->query('.//p[contains(@class,"org-person__report")]', $card)->item(0)->textContent);
            }
        }
        foreach (['pembina' => 2, 'pengurus' => 7, 'unit' => 4] as $group => $count) {
            $this->assertSame($count, $xpath->query('//*[@data-people-group="'.$group.'"]//*[@data-person-id]')->length);
        }
    }

    public function test_missing_portrait_uses_initials_without_removing_the_person(): void
    {
        $organization = config('organization');
        $organization['photo'] = 'assets/images/missing-portrait.png';
        config(['organization' => $organization]);
        $this->get('/tentang-kami')->assertOk()
            ->assertSee('Isam Adi, S.Sos., M.M.')
            ->assertSee('class="org-person__initials"', false)
            ->assertDontSee('src="'.asset($organization['photo']).'"', false);
    }

    public function test_short_about_url_redirects_to_the_existing_page(): void
    {
        $this->get('/tentang')->assertRedirect('/tentang-kami');
    }

    public function test_chart_preserves_branch_counts_and_direct_reporting_lines(): void
    {
        $root = config('organization');
        $bayu = $root['children'][0];
        $akhmad = $bayu['children'][0];
        $marah = $bayu['children'][1];

        $this->assertSame('Isam Adi, S.Sos., M.M.', $bayu['reports_to']);
        $this->assertSame('Bayu Istanto Gunawan Utomo, S.E.', $akhmad['reports_to']);
        $this->assertSame('Bayu Istanto Gunawan Utomo, S.E.', $marah['reports_to']);
        $this->assertSame(7, count($akhmad['children']));
        $this->assertSame(2, count($marah['children']));

        foreach ($akhmad['children'] as $person) {
            $this->assertSame('Akhmad Kosim, S.H.', $person['reports_to']);
        }

        foreach ($marah['children'] as $person) {
            $this->assertSame('Marah Alam Tanjung, S.Kom., M.MT., M.Sc.', $person['reports_to']);
        }
    }

    public function test_every_person_has_an_existing_photo_and_the_reference_reporting_relationship(): void
    {
        $people = $this->flattenPeople(config('organization'));

        foreach ($people as $person) {
            $this->assertFileExists(public_path($person['photo']), $person['name']);
        }

        $response = $this->get('/tentang-kami');

        $response->assertSee('Pembina Utama')
            ->assertSee('Pembina Harian')
            ->assertSee('Ketua Koperasi')
            ->assertSee('Pengawas')
            ->assertSee('Sekretaris')
            ->assertSee('Bendahara Urusan Keuangan')
            ->assertSee('Bendahara Urusan Perbendaharaan')
            ->assertSee('Ka Unit Toko')
            ->assertSee('Ka Unit Usaha Anggota')
            ->assertSee('Ka Unit Usaha Khusus')
            ->assertSee('Ka Unit Jasa Umum')
            ->assertSee('Wakil Ketua')
            ->assertSee('Anggota');
    }

    public function test_about_navigation_links_open_the_about_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('about').'"', false);

        $this->get('/tentang-kami')
            ->assertOk()
            ->assertSee('href="'.route('about').'"', false)
            ->assertSee('aria-current="page"', false);
    }

    private function flattenPeople(array $person): array
    {
        return [
            $person,
            ...array_merge([], ...array_map(
                fn (array $child): array => $this->flattenPeople($child),
                $person['children'],
            )),
        ];
    }
}
