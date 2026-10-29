<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnggotaController extends Controller
{
    public function dashboard(): View|RedirectResponse
    {
        if (! session()->get('member.authenticated')) {
            return redirect()->route('login')->with('error', 'Silakan masuk ke akun anggota terlebih dahulu.');
        }

        // TODO: sambungkan ke data asli
        $member = [
            'name' => session('member.name', 'Agus Wiji Suhariono'),
            'nip' => '199008022018021001',
            'jabatan' => 'Pranata Komputer Pelaksana Lanjutan / Mahir',
        ];

        // TODO: sambungkan ke data asli
        $summary = [
            'pokok' => 100000,
            'wajib' => 5050000,
            'sukarela' => 1550000,
            'khusus' => 1464614,
        ];

        $totalSimpanan = array_sum($summary);

        $composition = [
            ['name' => 'Pokok', 'amount' => $summary['pokok'], 'percent' => 1.2, 'color' => '#4a9cc4'],
            ['name' => 'Wajib', 'amount' => $summary['wajib'], 'percent' => 61.9, 'color' => '#2fa37a'],
            ['name' => 'Sukarela', 'amount' => $summary['sukarela'], 'percent' => 19.0, 'color' => '#7fad39'],
            ['name' => 'Khusus', 'amount' => $summary['khusus'], 'percent' => 17.9, 'color' => '#f0c23b'],
        ];

        $stats = [
            'simpanan' => [
                ['name' => 'Simpanan Pokok', 'amount' => $summary['pokok']],
                ['name' => 'Simpanan Wajib', 'amount' => $summary['wajib']],
                ['name' => 'Simpanan Sukarela', 'amount' => $summary['sukarela']],
                ['name' => 'Simpanan Khusus', 'amount' => $summary['khusus']],
            ],
            'pinjaman' => [
                ['name' => 'Pinjaman Aktif', 'amount' => 0],
                ['name' => 'Total Pinjaman Cair', 'amount' => 0],
                ['name' => 'Pinjaman Dalam Pengajuan', 'amount' => 0],
            ],
        ];

        return view('anggota.dashboard', compact('member', 'summary', 'composition', 'stats', 'totalSimpanan'));
    }
}
