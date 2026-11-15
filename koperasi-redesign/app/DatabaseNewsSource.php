<?php

namespace App;

use App\Models\Berita;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseNewsSource
{
    public function articles(): Collection
    {
        return Berita::query()->whereNotNull('published_at')->where('published_at', '<=', now())->orderByDesc('published_at')->orderBy('id')->get()->map(function (Berita $record): array {
            $original = collect(config('news.articles', []))->map(fn (array $article): array => $article + ['slug' => Str::slug($article['title'])])->firstWhere('slug', $record->slug) ?? [];
            $image = $record->foto === null ? 'favicon.svg' : (str_starts_with($record->foto, 'assets/') ? $record->foto : 'storage/'.$record->foto);
            $sections = array_is_list($record->isi) ? $record->isi : [];
            $html = isset($record->isi['html']) ? app(AdminNews::class)->sanitize($record->isi['html']) : null;
            $text = $html !== null ? strip_tags($html) : collect($sections)->flatMap(fn (array $section): array => $section['paragraphs'] ?? [])->implode(' ');
            $text = $text ?: $record->ringkasan;
            $excerpt = trim($record->ringkasan);
            if ($sections !== [] || $html !== null || $excerpt === '' || preg_match('/(?:\.{3}|\x{2026})\s*$/u', $excerpt) || ! preg_match('/[.!?]["\x{201D}\x{2019}]?$/u', $excerpt)) {
                $source = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
                preg_match('/^.*?[.!?](?:\s|$)/u', $source, $sentence);
                $summary = rtrim(Str::limit(trim($sentence[0] ?? $source), 159, '', preserveWords: true), ' .'."\u{2026}")."\u{2026}";
            } else {
                $summary = $excerpt;
            }
            $article = ['slug' => $record->slug, 'title' => $record->judul, 'excerpt' => $record->ringkasan, 'summary' => $summary, 'datetime' => $record->published_at->toDateString(), 'date' => $record->published_at->locale('id')->translatedFormat('d F Y'), 'image' => $image, 'detail_image' => ($record->foto === ($original['image'] ?? null) ? ($original['detail_image'] ?? $image) : $image), 'sections' => $sections, 'reading_minutes' => max(1, (int) ceil(preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($text)) / 200))];
            if ($record->penulis !== null) {
                $article['author'] = $record->penulis;
                if (isset($original['author_role'])) {
                    $article['author_role'] = $original['author_role'];
                }
            }
            if ($html !== null) {
                $article['html'] = $html;
                $article['inline_images'] = $record->isi['inline_images'] ?? [];
            }

            return $article;
        });
    }
}
