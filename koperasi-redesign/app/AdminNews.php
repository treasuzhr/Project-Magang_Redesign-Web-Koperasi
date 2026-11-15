<?php

namespace App;

use App\Models\Berita;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AdminNews
{
    public function sanitize(string $html): string
    {
        $config = (new HtmlSanitizerConfig)->withMaxInputLength(800000)->allowRelativeLinks()->allowLinkSchemes(['http', 'https', 'mailto']);
        foreach (['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'br', 'blockquote'] as $tag) {
            $config = $config->allowElement($tag);
        }
        $config = $config->allowElement('a', ['href', 'title']);

        return (new HtmlSanitizer($config))->sanitize($html);
    }

    public function editorContent(Berita $record): string
    {
        if (is_string($record->isi['html'] ?? null)) {
            return $this->sanitize($record->isi['html']);
        }
        $html = '';
        foreach ($record->isi as $section) {
            if (! empty($section['heading'])) {
                $html .= '<h2>'.e($section['heading']).'</h2>';
            }
            foreach ($section['paragraphs'] ?? [] as $paragraph) {
                $html .= '<p>'.e($paragraph).'</p>';
            }
        }

        return $html;
    }

    public function save(array $input, ?Berita $record = null): Berita
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $input['slug'] = Str::slug($input['slug'] ?? $input['judul'] ?? '');
        $data = Validator::make($input, [
            'judul' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', Rule::unique('beritas', 'slug')->ignore($record?->id)],
            'ringkasan' => ['required', 'string', 'max:5000'], 'isi' => ['required', 'string', 'max:200000'],
            'foto' => ['nullable', 'string', 'max:2048'], 'is_published' => ['required', 'boolean'], 'published_at' => ['nullable', 'date', 'required_if:is_published,1'],
        ])->validate();
        $data['isi'] = ['html' => $this->sanitize($data['isi'])];
        if ($record !== null) {
            $images = $record->isi['inline_images'] ?? collect($record->isi)->filter(fn (mixed $section): bool => is_array($section) && isset($section['image']))->pluck('image')->values()->all();
            if ($images !== []) {
                $data['isi']['inline_images'] = $images;
            }
        }
        $data['published_at'] = $data['is_published'] ? ($data['published_at'] ?? now()) : null;
        unset($data['is_published']);
        if ($record === null) {
            return Berita::create($data);
        }
        $record->update($data);

        return $record->refresh();
    }
}
