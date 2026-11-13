<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminProductTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['nama', 'harga', 'kategori'], ',', '"', '');
            fclose($stream);
        }, 'templat-produk.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
