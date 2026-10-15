<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    public function test_about_page_renders_the_complete_organization_chart_in_reference_order(): void
    {
        $response = $this->get('/tentang-kami');
        $people = $this->flattenPeople(config('organization'));

        $response->assertOk()
            ->assertSee('Tentang Kami')
            ->assertSee('Struktur Pengurus Koperasi')
            ->assertSee('Melapor kepada')
            ->assertSee('Bayu Istanto Gunawan Utomo, S.E.')
            ->assertSee('Akhmad Kosim, S.H.')
            ->assertSee('Marah Alam Tanjung, S.Kom., M.MT., M.Sc.');

        $response->assertSeeInOrder([
            'Isam Adi, S.Sos., M.M.',
            'Bayu Istanto Gunawan Utomo, S.E.',
            'Akhmad Kosim, S.H.',
            'Fadil Armansyah, S.T., M.M.S.I.',
            'Gandung Purnomoaji, A.Md.Ak.',
            'Dwi Utami, S.M.',
            'Heribertus Sigit Prasetya, S.Kom.',
            'Dewi Ratri Teheranda, A.Md.',
            'Lusy Tri Afzami, S.T.',
            'Hidayatullah Suryaputra, S.Kom.',
            'Marah Alam Tanjung, S.Kom., M.MT., M.Sc.',
            'Thia Karmila, S.A.P., M.Si.',
            'Anggraeni Tri Windyastuti, S.Kom.',
        ]);

        $this->assertCount(13, $people);
        $names = array_column($people, 'name');
        $this->assertCount(count($names), array_unique($names));
        $this->assertSame(13, substr_count($response->getContent(), 'class="org-person"'));
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
            ->assertSee('href="' . route('about') . '"', false);

        $this->get('/tentang-kami')
            ->assertOk()
            ->assertSee('href="' . route('about') . '"', false)
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
