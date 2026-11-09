<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['judul', 'tahun', 'file_path', 'published'])]
class Dokumen extends Model
{
    protected $table = 'dokumens';

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'published' => 'boolean',
        ];
    }
}
