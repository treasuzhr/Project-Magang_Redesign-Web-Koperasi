<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MemberAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_page_renders_profile_and_upload_interface_and_requires_member_session(): void
    {
        $this->get('/anggota/ubah-avatar')->assertRedirect('/login');

        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/ubah-avatar')
            ->assertOk()
            ->assertSee('Ganti foto profil akun Anda.')
            ->assertSee('Seret foto ke sini atau pilih file')
            ->assertSee('JPG, PNG, atau WEBP. Maksimal 2 MB.')
            ->assertSee('Belum ada foto profil')
            ->assertSee('href="'.route('anggota.avatar.edit').'"', false)
            ->assertDontSee('href="'.route('anggota.soon').'"', false)
            ->assertDontSee('member-nav__item is-active');
    }

    public function test_avatar_upload_rejects_missing_unsupported_and_oversized_files(): void
    {
        $session = ['member.authenticated' => true];

        $this->memberSession($session)
            ->from('/anggota/ubah-avatar')
            ->post('/anggota/ubah-avatar')
            ->assertSessionHasErrors(['avatar' => 'Pilih foto dulu sebelum mengunggah.']);

        $this->memberSession($session)
            ->from('/anggota/ubah-avatar')
            ->post('/anggota/ubah-avatar', [
                'avatar' => UploadedFile::fake()->create('dokumen.pdf', 5, 'application/pdf'),
            ])
            ->assertSessionHasErrors(['avatar' => 'Format file tidak didukung. Pilih foto JPG, PNG, atau WEBP.']);

        $this->memberSession($session)
            ->from('/anggota/ubah-avatar')
            ->post('/anggota/ubah-avatar', [
                'avatar' => UploadedFile::fake()->image('besar.png')->size(2049),
            ])
            ->assertSessionHasErrors(['avatar' => 'Ukuran foto terlalu besar. Pilih foto di bawah 2 MB.']);
    }

    public function test_valid_avatar_upload_returns_dummy_success_without_persisting_a_file(): void
    {
        $this->memberSession(['member.authenticated' => true])
            ->post('/anggota/ubah-avatar', [
                'avatar' => UploadedFile::fake()->image('foto.png'),
            ])
            ->assertRedirect('/anggota/ubah-avatar')
            ->assertSessionHas('status', 'Foto profil berhasil diperbarui.');
    }

    public function test_avatar_delete_requires_member_session_and_reports_when_no_photo_exists(): void
    {
        $this->delete('/anggota/ubah-avatar')->assertRedirect('/login');

        $this->memberSession(['member.authenticated' => true])
            ->delete('/anggota/ubah-avatar')
            ->assertRedirect('/anggota/ubah-avatar')
            ->assertSessionHas('status', 'Belum ada foto profil untuk dihapus.');
    }
}
