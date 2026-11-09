<?php

// TODO: konfirmasi format dan batas ukuran dengan koperasi
return [
    'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    'formats' => ['JPG', 'PNG', 'WEBP'],
    'accept' => 'image/jpeg,image/png,image/webp',
    'max_kilobytes' => 2048,
    'max_bytes' => 2 * 1024 * 1024,
    'max_size_label' => '2 MB',
];
