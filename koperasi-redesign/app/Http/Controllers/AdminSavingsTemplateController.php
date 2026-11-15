<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSavingsTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['nip', 'jenis', 'jumlah', 'tanggal', 'keterangan'], ',', '"', '');
            fclose($stream);
        }, 'templat-setoran.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
