<?php

namespace App\Http\Controllers;

use App\MemberOverview;
use App\Models\Pinjaman;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLoanDocumentController extends Controller
{
    public function __invoke(Pinjaman $pinjaman, MemberOverview $overview): StreamedResponse
    {
        $path = $overview->privateDocumentPath($pinjaman->dokumen_akad_path);
        abort_if($path === null, 404);

        return Storage::disk('local')->download($path, basename($path));
    }
}
