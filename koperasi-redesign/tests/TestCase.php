<?php

namespace Tests;

use App\Models\Anggota;
use App\Models\Berita;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function seedNewsArticles(array $articles): void
    {
        Berita::query()->delete();
        foreach (collect($articles)->map(fn (array $article): array => $article + ['slug' => Str::slug($article['title'])])->unique('slug') as $article) {
            Berita::create(['judul' => $article['title'], 'slug' => $article['slug'], 'ringkasan' => $article['excerpt'], 'isi' => $article['sections'] ?? [], 'foto' => $article['image'] ?? null, 'penulis' => $article['author'] ?? null, 'published_at' => $article['datetime']]);
        }
    }

    protected function memberSession(array $session = []): static
    {
        if (($session['member.authenticated'] ?? true) === false) {
            Auth::logout();

            return $this->withSession($session);
        }
        if (! auth()->check()) {
            $user = User::factory()->create(['name' => 'Anggota Demo', 'must_change_password' => false]);
            Anggota::factory()->create(['user_id' => $user->id]);
            $this->actingAs($user);
        }
        unset($session['member.authenticated']);

        return $this->withSession($session);
    }
}
