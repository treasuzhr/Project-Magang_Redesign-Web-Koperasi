<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['judul', 'slug', 'ringkasan', 'isi', 'foto', 'penulis', 'published_at'])]
class Berita extends Model
{
    protected $table = 'beritas';

    protected function casts(): array
    {
        return [
            'isi' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
