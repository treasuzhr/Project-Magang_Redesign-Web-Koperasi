<?php

namespace App;

use Illuminate\Support\Str;

class ProductDisplayName
{
    public static function format(string $name): string
    {
        return preg_replace_callback('/\S+/u', static function (array $match): string {
            $word = $match[0];
            $units = ['ML', 'L', 'MG', 'G', 'KG', 'MM', 'CM', 'M', 'PCS', 'K', 'SAT'];

            return preg_match('/[0-9]/', $word) || in_array($word, $units, true)
                ? $word
                : Str::ucfirst(Str::lower($word));
        }, $name);
    }
}
