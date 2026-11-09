<?php

namespace Tests\Feature;

use Tests\TestCase;

class MemberLoansTest extends TestCase
{
    public function test_loan_pages_render_with_shared_sidebar_and_empty_data_states(): void
    {
        $session = ['member.authenticated' => true];

        $this->withSession($session)
            ->get('/anggota/pinjaman')
            ->assertOk()
            ->assertSee('Halaman')
            ->assertSee('Ajukan Pinjaman')
            ->assertSee('Riwayat Pinjaman')
            ->assertSee('Anda belum memiliki pinjaman aktif.')
            ->assertSee('aria-current="page"', false);

        $this->withSession($session)
            ->get('/anggota/pinjaman/ajukan')
            ->assertOk()
            ->assertSee('-- Pilih Jenis Pinjaman --')
            ->assertSee('<option value="" disabled selected>-- Pilih Jenis Pinjaman --</option>', false)
            ->assertSee('<option value="" disabled selected>-- Pilih Metode Pembayaran --</option>', false)
            ->assertSee('value="toko"', false)
            ->assertSee('>Toko</option>', false)
            ->assertSee('value="usp"', false)
            ->assertSee('>Unit Simpan Pinjam</option>', false)
            ->assertSee('value="usp-khusus"', false)
            ->assertSee('>Unit Simpan Pinjam Khusus</option>', false)
            ->assertSee('-- Pilih Metode Pembayaran --')
            ->assertSee('value="tunjangan-kinerja"', false)
            ->assertSee('>Tunjangan Kinerja</option>', false)
            ->assertSee('value="potong-gaji"', false)
            ->assertSee('>Potong Gaji</option>', false)
            ->assertSee('Simulasi bersifat estimasi, hasil final mengikuti persetujuan koperasi.')
            ->assertSee('data-monthly-interest-rates="{&quot;toko&quot;:null,&quot;usp&quot;:null,&quot;usp-khusus&quot;:null}"', false);

        $this->withSession($session)
            ->get('/anggota/pinjaman/riwayat')
            ->assertOk()
            ->assertSee('Belum ada riwayat pinjaman.')
            ->assertSee('Menampilkan 0-0 dari 0 data')
            ->assertSee('member-table--stack-mobile', false);
    }

    public function test_loan_pages_require_a_member_session(): void
    {
        foreach (['/anggota/pinjaman', '/anggota/pinjaman/ajukan', '/anggota/pinjaman/riwayat'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_application_validation_preserves_input_and_renders_server_side_estimate(): void
    {
        $response = $this->withSession(['member.authenticated' => true])
            ->from('/anggota/pinjaman/ajukan')
            ->followingRedirects()
            ->post('/anggota/pinjaman/ajukan', [
                'loan_type' => 'toko',
                'loan_total' => '10.000.000',
                'loan_term' => '12',
            ]);

        $response
            ->assertOk()
            ->assertSee('value="10.000.000"', false)
            ->assertSee('Rp 833.333')
            ->assertSee('Pilih metode pembayaran dulu.');
    }

    public function test_application_normalizes_amount_and_reports_each_custom_validation_message(): void
    {
        $response = $this->withSession(['member.authenticated' => true])
            ->from('/anggota/pinjaman/ajukan')
            ->post('/anggota/pinjaman/ajukan', [
                'loan_type' => '',
                'loan_total' => '0',
                'loan_term' => '0',
                'payment_method' => '',
            ]);

        $response
            ->assertRedirect('/anggota/pinjaman/ajukan')
            ->assertSessionHasErrors([
                'loan_type' => 'Pilih jenis pinjaman dulu.',
                'loan_total' => 'Total pinjaman wajib diisi dengan angka, contoh 10000000.',
                'loan_term' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
                'payment_method' => 'Pilih metode pembayaran dulu.',
            ]);
    }

    public function test_application_rejects_unknown_loan_type_and_preserves_a_valid_selection(): void
    {
        $this->withSession(['member.authenticated' => true])
            ->from('/anggota/pinjaman/ajukan')
            ->post('/anggota/pinjaman/ajukan', [
                'loan_type' => 'unknown-type',
                'loan_total' => '10000000',
                'loan_term' => '12',
                'payment_method' => 'tunjangan-kinerja',
            ])
            ->assertRedirect('/anggota/pinjaman/ajukan')
            ->assertSessionHasErrors(['loan_type' => 'Pilih jenis pinjaman dulu.']);

        $response = $this->withSession(['member.authenticated' => true])
            ->from('/anggota/pinjaman/ajukan')
            ->followingRedirects()
            ->post('/anggota/pinjaman/ajukan', [
                'loan_type' => 'usp-khusus',
                'loan_total' => '10000000',
                'loan_term' => '12',
                'payment_method' => '',
            ]);

        $response
            ->assertOk()
            ->assertSee('<option value="usp-khusus" selected>Unit Simpan Pinjam Khusus</option>', false)
            ->assertSee('Pilih metode pembayaran dulu.');
    }

    public function test_valid_application_returns_success_notice_without_persisting_dummy_data(): void
    {
        $this->withSession(['member.authenticated' => true])
            ->post('/anggota/pinjaman/ajukan', [
                'loan_type' => 'toko',
                'loan_total' => '10.000.000',
                'loan_term' => '12',
                'payment_method' => 'potong-gaji',
            ])
            ->assertRedirect('/anggota/pinjaman/ajukan')
            ->assertSessionHas('status', 'Pengajuan pinjaman Anda sudah dikirim.');
    }
}
