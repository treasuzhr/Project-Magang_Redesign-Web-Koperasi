<?php

namespace Tests\Feature;

use Tests\TestCase;

class MemberPasswordTest extends TestCase
{
    public function test_password_page_renders_with_shared_portal_components_and_requires_member_session(): void
    {
        $this->get('/anggota/ubah-password')->assertRedirect('/login');

        $response = $this->withSession(['member.authenticated' => true])
            ->get('/anggota/ubah-password')
            ->assertOk();

        $response
            ->assertSee('Ganti password akun Anda.')
            ->assertSee('Password baru')
            ->assertSee('Ulangi password baru')
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('Tampilkan password')
            ->assertSee('href="'.route('anggota.password.edit').'"', false)
            ->assertSee('href="'.route('anggota.avatar.edit').'"', false)
            ->assertDontSee('Password lama')
            ->assertDontSee('member-nav__item is-active');
    }

    public function test_password_update_requires_member_session(): void
    {
        $this->post('/anggota/ubah-password', [
            'password' => 'baru1234',
            'password_confirmation' => 'baru1234',
        ])->assertRedirect('/login');
    }

    public function test_empty_and_mismatched_passwords_show_indonesian_errors_without_flashing_passwords(): void
    {
        $session = ['member.authenticated' => true];

        $this->withSession($session)
            ->from('/anggota/ubah-password')
            ->post('/anggota/ubah-password', [
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect('/anggota/ubah-password')
            ->assertSessionHasErrors([
                'password' => 'Password baru wajib diisi.',
                'password_confirmation' => 'Ulangi password baru wajib diisi.',
            ])
            ->assertSessionMissing('_old_input');

        $this->withSession($session)
            ->from('/anggota/ubah-password')
            ->post('/anggota/ubah-password', [
                'password' => 'baru1234',
                'password_confirmation' => 'beda1234',
            ])
            ->assertSessionHasErrors([
                'password_confirmation' => 'Kedua password harus sama. Ketik ulang dengan teliti.',
            ])
            ->assertSessionMissing('_old_input');
    }

    public function test_existing_minimum_password_rule_is_applied(): void
    {
        $this->withSession(['member.authenticated' => true])
            ->from('/anggota/ubah-password')
            ->post('/anggota/ubah-password', [
                'password' => 'abc',
                'password_confirmation' => 'abc',
            ])
            ->assertSessionHasErrors(['password' => 'Password baru minimal 4 karakter.']);
    }

    public function test_valid_password_only_returns_the_requested_success_message_without_saving(): void
    {
        $this->withSession(['member.authenticated' => true])
            ->post('/anggota/ubah-password', [
                'password' => 'baru1234',
                'password_confirmation' => 'baru1234',
            ])
            ->assertRedirect('/anggota/ubah-password')
            ->assertSessionHas('status', 'Password berhasil diubah.')
            ->assertSessionMissing('_old_input');
    }

    public function test_password_update_is_limited_to_six_attempts_per_minute(): void
    {
        $this->withSession(['member.authenticated' => true]);
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->from('/anggota/ubah-password')
                ->post('/anggota/ubah-password', [
                    'password' => '',
                    'password_confirmation' => '',
                ])
                ->assertRedirect('/anggota/ubah-password');
        }

        $this->from('/anggota/ubah-password')
            ->post('/anggota/ubah-password', [
                'password' => 'baru1234',
                'password_confirmation' => 'baru1234',
            ])
            ->assertRedirect('/anggota/ubah-password')
            ->assertSessionHasErrors(['password' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'])
            ->assertSessionMissing('_old_input');
    }
}
