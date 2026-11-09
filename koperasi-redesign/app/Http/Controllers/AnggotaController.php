<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnggotaController extends Controller
{
    private const SAVINGS_TYPES = ['pokok', 'wajib', 'sukarela', 'khusus', 'setor', 'tarik'];

    public function avatarEdit(): View|RedirectResponse
    {
        $member = [
            'name' => auth()->user()->name,
            'nip' => '199008022018021001',
            'jabatan' => 'Pranata Komputer Pelaksana Lanjutan / Mahir',
            'avatar_url' => null,
        ];
        $avatarConfig = config('member-avatar');

        return view('anggota.ubah-avatar', compact('member', 'avatarConfig'));
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $limiterKey = 'member-avatar-upload:'.sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($limiterKey, 10)) {
            return back()->withErrors([
                'avatar' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.',
            ]);
        }
        RateLimiter::hit($limiterKey, 60);

        $avatarConfig = config('member-avatar');
        $validator = Validator::make($request->only('avatar'), [
            'avatar' => ['required', 'file', 'mimes:'.implode(',', $avatarConfig['mimes']), 'image', 'max:'.$avatarConfig['max_kilobytes']],
        ], [
            'avatar.required' => 'Pilih foto dulu sebelum mengunggah.',
            'avatar.image' => 'File tidak bisa dibaca sebagai foto. Coba pilih file lain.',
            'avatar.mimes' => 'Format file tidak didukung. Pilih foto JPG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran foto terlalu besar. Pilih foto di bawah '.$avatarConfig['max_size_label'].'.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        RateLimiter::clear($limiterKey);

        // TODO: simpan avatar ke data asli
        return redirect()->route('anggota.avatar.edit')->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        // TODO: hapus avatar dari data asli
        return redirect()->route('anggota.avatar.edit')->with('status', 'Belum ada foto profil untuk dihapus.');
    }

    public function dashboard(): View|RedirectResponse
    {
        // TODO: sambungkan ke data asli
        $member = [
            'name' => auth()->user()->name,
            'nip' => '199008022018021001',
            'jabatan' => 'Pranata Komputer Pelaksana Lanjutan / Mahir',
            'avatar_url' => null,
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

    public function savingsRedirect(): RedirectResponse
    {
        return redirect()->route('anggota.simpanan.tab', ['jenis' => 'pokok']);
    }

    public function savingsIndex(string $jenis): View|RedirectResponse
    {
        abort_unless(in_array($jenis, self::SAVINGS_TYPES, true), 404);

        $typeLabels = [
            'pokok' => 'Pokok',
            'wajib' => 'Wajib',
            'sukarela' => 'Sukarela',
            'khusus' => 'Khusus',
            'setor' => 'Setor',
            'tarik' => 'Tarik',
        ];
        $subtitles = [
            'pokok' => 'Riwayat simpanan pokok Anda.',
            'wajib' => 'Simpanan wajib per tahun.',
            'sukarela' => 'Simpanan sukarela per tahun.',
            'khusus' => 'Simpanan khusus per tahun.',
            'setor' => 'Riwayat setoran simpanan sukarela.',
            'tarik' => 'Riwayat penarikan simpanan sukarela.',
        ];
        $columns = [
            'pokok' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'month', 'label' => 'Bulan'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
            ],
            'wajib' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'action', 'label' => 'Aksi', 'align' => 'center'],
            ],
            'sukarela' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'action', 'label' => 'Aksi', 'align' => 'center'],
            ],
            'khusus' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'nip', 'label' => 'NIP/NRP'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
            ],
            'setor' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'action', 'label' => 'Aksi', 'align' => 'center'],
            ],
            'tarik' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'month', 'label' => 'Bulan'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
            ],
        ];

        // TODO: sambungkan ke data asli.
        $savings = [
            'pokok' => [
                ['number' => 1, 'year' => 2025, 'month' => 'Desember', 'amount' => 100000],
            ],
            'wajib' => [
                ['number' => 1, 'year' => 2025, 'amount' => 5050000],
            ],
            'sukarela' => [
                ['number' => 1, 'year' => 2025, 'amount' => 2100000],
                ['number' => 2, 'year' => 2024, 'amount' => 1500000],
                ['number' => 3, 'year' => 2023, 'amount' => 600000],
                ['number' => 4, 'year' => 2022, 'amount' => 600000],
                ['number' => 5, 'year' => 2021, 'amount' => 600000],
                ['number' => 6, 'year' => 2020, 'amount' => 950000],
            ],
            'khusus' => [
                ['number' => 1, 'nip' => '199008022018021001', 'year' => 2024, 'amount' => 196168],
                ['number' => 2, 'nip' => '199008022018021001', 'year' => 2023, 'amount' => 170786],
                ['number' => 3, 'nip' => '199008022018021001', 'year' => 2022, 'amount' => 180613],
                ['number' => 4, 'nip' => '199008022018021001', 'year' => 2021, 'amount' => 251887],
                ['number' => 5, 'nip' => '199008022018021001', 'year' => 2020, 'amount' => 333184],
                ['number' => 6, 'nip' => '199008022018021001', 'year' => 2019, 'amount' => 331976],
            ],
            'setor' => [],
            'tarik' => [
                ['number' => 1, 'year' => 2025, 'month' => 'Juni', 'amount' => 1300000],
                ['number' => 2, 'year' => 2024, 'month' => 'September', 'amount' => 1000000],
                ['number' => 3, 'year' => 2024, 'month' => 'Maret', 'amount' => 2500000],
            ],
        ];

        $rows = $savings[$jenis];
        if (in_array($jenis, ['wajib', 'sukarela'], true)) {
            foreach ($rows as &$row) {
                $row['action_url'] = route('anggota.simpanan.detail', ['jenis' => $jenis, 'tahun' => $row['year']]);
                $row['action_aria_label'] = "Lihat detail tahun {$row['year']}";
            }
            unset($row);
        }

        $tabs = collect(self::SAVINGS_TYPES)->map(fn (string $type): array => [
            'label' => $typeLabels[$type],
            'url' => route('anggota.simpanan.tab', ['jenis' => $type]),
            'active' => $type === $jenis,
        ])->all();

        $member = $this->memberProfile();
        $headingPrefix = in_array($jenis, ['setor', 'tarik'], true) ? ucfirst($jenis) : 'Simpanan';
        $headingAccent = in_array($jenis, ['setor', 'tarik'], true) ? 'sukarela' : $typeLabels[$jenis];

        return view('anggota.simpanan.index', [
            'member' => $member,
            'jenis' => $jenis,
            'jenisLabel' => $typeLabels[$jenis],
            'headingPrefix' => $headingPrefix,
            'headingAccent' => $headingAccent,
            'subtitle' => $subtitles[$jenis],
            'columns' => $columns[$jenis],
            'rows' => $rows,
            'tabs' => $tabs,
        ]);
    }

    public function savingsDetail(string $jenis, int $tahun): View|RedirectResponse
    {
        abort_unless(in_array($jenis, ['wajib', 'sukarela'], true), 404);

        // TODO: sambungkan ke data asli.
        $savings = [
            'wajib' => [
                2025 => [
                    ['number' => 1, 'year' => 2025, 'month' => 'Desember', 'amount' => 5050000],
                ],
            ],
            'sukarela' => [
                2025 => [
                    ['number' => 1, 'year' => 2025, 'month' => 'Januari', 'amount' => 150000],
                    ['number' => 2, 'year' => 2025, 'month' => 'Februari', 'amount' => 150000],
                    ['number' => 3, 'year' => 2025, 'month' => 'Maret', 'amount' => 150000],
                    ['number' => 4, 'year' => 2025, 'month' => 'April', 'amount' => 150000],
                    ['number' => 5, 'year' => 2025, 'month' => 'Mei', 'amount' => 150000],
                    ['number' => 6, 'year' => 2025, 'month' => 'Juni', 'amount' => 150000],
                    ['number' => 7, 'year' => 2025, 'month' => 'Juli', 'amount' => 200000],
                    ['number' => 8, 'year' => 2025, 'month' => 'Agustus', 'amount' => 200000],
                    ['number' => 9, 'year' => 2025, 'month' => 'September', 'amount' => 200000],
                    ['number' => 10, 'year' => 2025, 'month' => 'Oktober', 'amount' => 200000],
                    ['number' => 11, 'year' => 2025, 'month' => 'November', 'amount' => 200000],
                    ['number' => 12, 'year' => 2025, 'month' => 'Desember', 'amount' => 200000],
                ],
            ],
        ];

        $indexRows = $jenis === 'wajib'
            ? [['year' => 2025]]
            : [
                ['year' => 2025],
                ['year' => 2024],
                ['year' => 2023],
                ['year' => 2022],
                ['year' => 2021],
                ['year' => 2020],
            ];
        abort_unless(collect($indexRows)->contains('year', $tahun), 404);

        $jenisLabel = ucfirst($jenis);
        $member = $this->memberProfile();
        $rows = $savings[$jenis][$tahun] ?? [];
        $emptyText = $rows === [] ? 'Detail belum tersedia.' : 'Belum ada data.';
        $tabs = collect(self::SAVINGS_TYPES)->map(fn (string $type): array => [
            'label' => ucfirst($type),
            'url' => route('anggota.simpanan.tab', ['jenis' => $type]),
            'active' => $type === $jenis,
        ])->all();

        return view('anggota.simpanan.detail', [
            'member' => $member,
            'jenis' => $jenis,
            'jenisLabel' => $jenisLabel,
            'tahun' => $tahun,
            'rows' => $rows,
            'emptyText' => $emptyText,
            'tabs' => $tabs,
            'columns' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'month', 'label' => 'Bulan'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
            ],
        ]);
    }

    public function shuIndex(): View|RedirectResponse
    {
        // TODO: sambungkan ke data asli.
        $shuData = [
            ['year' => 2025, 'amount' => 676717],
            ['year' => 2024, 'amount' => 619000],
            ['year' => 2023, 'amount' => 422762],
            ['year' => 2022, 'amount' => 244835],
            ['year' => 2021, 'amount' => 407235],
        ];

        $member = $this->memberProfile();
        $rows = array_map(fn (array $item, int $index): array => [
            'number' => $index + 1,
            'nip' => $member['nip'],
            'name' => 'Agus Wiji Suhariono, S.Kom.',
            'amount' => $item['amount'],
            'year' => $item['year'],
            'action_url' => route('anggota.shu.detail', ['tahun' => $item['year']]),
            'action_aria_label' => 'Lihat detail SHU tahun '.$item['year'],
        ], $shuData, array_keys($shuData));
        $chartData = array_reverse($shuData);

        return view('anggota.shu.index', [
            'member' => $member,
            'rows' => $rows,
            'chartData' => $chartData,
            'latestShu' => $shuData[0],
            'columns' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'nip', 'label' => 'NRP'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'amount', 'label' => 'Jumlah SHU', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'year', 'label' => 'Tahun', 'align' => 'center'],
                ['key' => 'action', 'label' => 'Aksi', 'align' => 'center'],
            ],
        ]);
    }

    public function shuDetail(int $tahun): View|RedirectResponse
    {
        // TODO: sambungkan ke data asli
        $shuData = [
            2025 => [
                ['number' => 1, 'type' => 'Simpanan Pokok', 'amount' => 100000, 'shu' => '-'],
                ['number' => 2, 'type' => 'Simpanan Wajib', 'amount' => 5050000, 'shu' => 209308],
                ['number' => 3, 'type' => 'Simpanan Sukarela', 'amount' => 1550000, 'shu' => 27081],
                ['number' => 4, 'type' => 'Jumlah Belanja', 'amount' => 1958000, 'shu' => 91410],
                // TODO: konfirmasi kepanjangan istilah ini dengan koperasi
                ['number' => 5, 'type' => 'Jasa Sipa Angg', 'amount' => 400000, 'shu' => 110000],
                // TODO: konfirmasi kepanjangan istilah ini dengan koperasi
                ['number' => 6, 'type' => 'Jasa Sipa Sus', 'amount' => 0, 'shu' => 0],
                ['number' => 7, 'type' => 'Jasa Umum', 'amount' => null, 'shu' => 238917],
            ],
        ];
        $shuYears = [2025, 2024, 2023, 2022, 2021];
        abort_unless(in_array($tahun, $shuYears, true), 404);

        return view('anggota.shu.detail', [
            'member' => $this->memberProfile(),
            'tahun' => $tahun,
            'rows' => $shuData[$tahun] ?? [],
            'totalRows' => $tahun === 2025
                ? [[
                    'label' => 'SHU Seluruhnya',
                    'label_span' => 2,
                    'class' => 'shu-table-total',
                    'amount' => '',
                    'shu' => 676717,
                ]]
                : [],
            'columns' => [
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'type', 'label' => 'Jenis'],
                ['key' => 'amount', 'label' => 'Jumlah', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'shu', 'label' => 'Jml. SHU', 'align' => 'right', 'format' => 'currency'],
            ],
        ]);
    }

    public function loanIndex(): View|RedirectResponse
    {
        // TODO: sambungkan ke data pinjaman asli.
        $activeLoans = [];

        return view('anggota.pinjaman.index', [
            'member' => $this->memberProfile(),
            'activeLoans' => $activeLoans,
        ]);
    }

    public function loanApplication(): View|RedirectResponse
    {
        $options = $this->loanFormOptions();
        // TODO: isi suku bunga dan rumus dari ketentuan koperasi.
        $monthlyInterestRates = [
            'toko' => null,
            'usp' => null,
            'usp-khusus' => null,
        ];
        $totalInputDigits = preg_replace('/\D+/', '', (string) old('loan_total', '')) ?? '';
        $principalEstimate = $this->estimatePrincipal(
            $totalInputDigits,
            old('loan_term')
        );

        return view('anggota.pinjaman.ajukan', [
            'member' => $this->memberProfile(),
            'loanTypes' => $options['loanTypes'],
            'paymentMethods' => $options['paymentMethods'],
            'principalEstimate' => $principalEstimate === null ? '-' : 'Rp '.$this->formatDigits($principalEstimate),
            'interestEstimate' => '-',
            'monthlyTotalEstimate' => '-',
            'monthlyInterestRates' => $monthlyInterestRates,
            'formattedLoanTotal' => $totalInputDigits === '' ? '' : 'Rp '.$this->formatDigits($totalInputDigits),
        ]);
    }

    public function submitLoanApplication(Request $request): RedirectResponse
    {
        $originalInput = $request->all();
        $normalizedTotal = preg_replace('/\D+/', '', (string) $request->input('loan_total', '')) ?? '';
        $request->merge(['loan_total' => $normalizedTotal]);
        $options = $this->loanFormOptions();
        $validator = Validator::make($request->all(), [
            'loan_type' => ['required', Rule::in(array_column($options['loanTypes'], 'value'))],
            'loan_total' => [
                'required',
                'regex:/^[0-9]+$/',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (ltrim((string) $value, '0') === '') {
                        $fail('Total pinjaman wajib diisi dengan angka, contoh 10000000.');
                    }
                },
            ],
            'loan_term' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(array_column($options['paymentMethods'], 'value'))],
        ], [
            'loan_type.required' => 'Pilih jenis pinjaman dulu.',
            'loan_type.in' => 'Pilih jenis pinjaman dulu.',
            'loan_total.required' => 'Total pinjaman wajib diisi dengan angka, contoh 10000000.',
            'loan_total.regex' => 'Total pinjaman wajib diisi dengan angka, contoh 10000000.',
            'loan_term.required' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
            'loan_term.integer' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
            'loan_term.min' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
            'payment_method.required' => 'Pilih metode pembayaran dulu.',
            'payment_method.in' => 'Pilih metode pembayaran dulu.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($originalInput);
        }

        // TODO: simpan pengajuan ke database dan kirim ke koperasi.
        return redirect()->route('anggota.pinjaman.ajukan')->with('status', 'Pengajuan pinjaman Anda sudah dikirim.');
    }

    public function loanHistory(): View|RedirectResponse
    {
        // TODO: sambungkan ke data riwayat pinjaman asli.
        $rows = [];
        // TODO: arahkan detail ke halaman detail pinjaman saat kontennya tersedia.
        foreach ($rows as &$row) {
            $row['action_url'] = route('anggota.soon');
            $row['action_aria_label'] = "Lihat detail pengajuan {$row['application_number']}";
        }
        unset($row);

        return view('anggota.pinjaman.riwayat', [
            'member' => $this->memberProfile(),
            'rows' => $rows,
            'columns' => [
                ['key' => 'action', 'label' => 'Detail', 'align' => 'center', 'action_label' => 'Detail'],
                ['key' => 'number', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'application_number', 'label' => 'No. pengajuan'],
                ['key' => 'loan_type', 'label' => 'Jenis pinjaman'],
                ['key' => 'total', 'label' => 'Total', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'term', 'label' => 'Tenor'],
                ['key' => 'remaining', 'label' => 'Sisa', 'align' => 'right', 'format' => 'currency'],
                // TODO: petakan status asli ke warna badge.
                ['key' => 'status', 'label' => 'Status pinjaman', 'format' => 'badge'],
                ['key' => 'date', 'label' => 'Tanggal', 'format' => 'date'],
            ],
        ]);
    }

    public function orderIndex(): View|RedirectResponse
    {
        $rows = array_values($this->orders());
        foreach ($rows as &$row) {
            $row['action_url'] = route('anggota.pesanan.detail', ['id' => $row['id']]);
            $row['action_aria_label'] = "Lihat detail order tanggal {$row['date']}";
        }
        unset($row);

        return view('anggota.pesanan.index', [
            'member' => $this->memberProfile(),
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'date', 'label' => 'Tanggal order'],
                ['key' => 'item_count', 'label' => 'Jumlah barang', 'align' => 'center'],
                ['key' => 'total', 'label' => 'Total harga', 'align' => 'right', 'format' => 'currency'],
                ['key' => 'action', 'label' => 'Aksi', 'align' => 'center', 'action_label' => 'Lihat detail'],
            ],
        ]);
    }

    public function orderDetail(int $id): View|RedirectResponse
    {
        $orders = $this->orders();
        abort_unless(isset($orders[$id]), 404);

        return view('anggota.pesanan.detail', [
            'member' => $this->memberProfile(),
            'order' => $orders[$id],
        ]);
    }

    public function documentIndex(): View|RedirectResponse
    {
        $rows = array_values($this->documents());
        foreach ($rows as &$row) {
            $row['action_url'] = route('anggota.dokumen.download', ['id' => $row['id']]);
            $row['action_aria_label'] = "Unduh PDF {$row['title']}";

            if ($this->documentFileExists($row)) {
                $row['action_target'] = '_blank';
                $row['action_rel'] = 'noopener';
            }
        }
        unset($row);

        return view('anggota.dokumen.index', [
            'member' => $this->memberProfile(),
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'No.', 'align' => 'center'],
                ['key' => 'title', 'label' => 'Judul'],
                ['key' => 'action', 'label' => 'Dokumen', 'align' => 'center', 'action_label' => 'Unduh PDF', 'action_icon' => 'pdf', 'action_class' => 'member-table-action--document'],
                ['key' => 'year', 'label' => 'Tahun', 'align' => 'center'],
            ],
        ]);
    }

    public function downloadDocument(int $id): RedirectResponse|StreamedResponse
    {
        $documents = $this->documents();
        abort_unless(isset($documents[$id]), 404);
        $document = $documents[$id];

        if ($this->documentFileExists($document)) {
            return Storage::disk('public')->download(
                $document['file_path'],
                $document['file_name'] ?? basename($document['file_path']),
            );
        }

        // TODO: sambungkan ke file PDF asli
        return redirect()
            ->route('anggota.dokumen.index')
            ->with('status', 'Berkas belum tersedia.');
    }

    private function loanFormOptions(): array
    {
        // TODO: sambungkan ke data asli
        return [
            'loanTypes' => [
                ['value' => 'toko', 'label' => 'Toko'],
                ['value' => 'usp', 'label' => 'Unit Simpan Pinjam'],
                ['value' => 'usp-khusus', 'label' => 'Unit Simpan Pinjam Khusus'],
            ],
            // TODO: sambungkan ke data asli.
            'paymentMethods' => [
                ['value' => 'tunjangan-kinerja', 'label' => 'Tunjangan Kinerja'],
                ['value' => 'potong-gaji', 'label' => 'Potong Gaji'],
            ],
        ];
    }

    private function orders(): array
    {
        // TODO: sambungkan ke data asli
        return [
            1 => [
                'id' => 1,
                'date' => '01 Februari 2026',
                'item_count' => 1,
                'total' => 1000,
                'items' => [
                    ['name' => 'AMPLOP COKLAT A3', 'price' => 1000, 'quantity' => 1, 'total' => 1000],
                ],
            ],
            2 => [
                'id' => 2,
                'date' => '01 Februari 2026',
                'item_count' => 3,
                'total' => 21000,
                'items' => [],
            ],
            3 => [
                'id' => 3,
                'date' => '15 November 2023',
                'item_count' => 1,
                'total' => 15000,
                'items' => [],
            ],
            4 => [
                'id' => 4,
                'date' => '19 Maret 2023',
                'item_count' => 21,
                'total' => 447000,
                'items' => [],
            ],
        ];
    }

    private function documents(): array
    {
        // TODO: sambungkan ke data asli
        return [
            1 => ['id' => 1, 'title' => 'BUKU RAT 2025 PRIMKOP PULLAHTA HANKAM', 'year' => 2026, 'file_path' => null],
            2 => ['id' => 2, 'title' => 'BUKU RAT 2024 PRIMKOP PULLAHTA HANKAM', 'year' => 2025, 'file_path' => null],
            3 => ['id' => 3, 'title' => 'BUKU RAT 2023 PRIMKOP', 'year' => 2023, 'file_path' => null],
            4 => ['id' => 4, 'title' => 'BUKU RAT 2023 PRIMKOP Revisi 1', 'year' => 2023, 'file_path' => null],
            5 => ['id' => 5, 'title' => 'REVISI RENCANA KERJA, ANGGARAN PENDAPATAN DAN BELANJA TAHUN 2022', 'year' => 2022, 'file_path' => null],
            6 => ['id' => 6, 'title' => 'GRADE_PRIMKOP_PULLAHTA_HANKAM', 'year' => 2022, 'file_path' => null],
            7 => ['id' => 7, 'title' => 'LAPORAN PERTANGGUNGJAWABAN PENGURUS KOPERASI PRIMKOP PULLAHTA HANKAM TUTUP BUKU TAHUN 2022', 'year' => 2022, 'file_path' => null],
            8 => ['id' => 8, 'title' => 'LAPORAN PERTANGGUNGJAWABAN PENGURUS KOPERASI PRIMKOP PULLAHTA HANKAM TUTUP BUKU TAHUN 2021', 'year' => 2021, 'file_path' => null],
            9 => ['id' => 9, 'title' => 'BUKU RAT 2021 PRIMKOP REVISI 1', 'year' => 2021, 'file_path' => null],
            10 => ['id' => 10, 'title' => 'BUKU RAT 2020 PRIMKOP', 'year' => 2020, 'file_path' => null],
            // TODO: tambahkan dokumen ke-11 dan ke-12 dari data asli
        ];
    }

    private function documentFileExists(array $document): bool
    {
        return isset($document['file_path'])
            && is_string($document['file_path'])
            && $document['file_path'] !== ''
            && Storage::disk('public')->exists($document['file_path']);
    }

    private function estimatePrincipal(string $total, mixed $term): ?string
    {
        $normalizedTotal = ltrim($total, '0');
        $divisor = filter_var($term, FILTER_VALIDATE_INT);

        if ($normalizedTotal === '' || $divisor === false || $divisor < 1) {
            return null;
        }

        $remainder = 0;
        $quotient = '';
        foreach (str_split($normalizedTotal) as $digit) {
            $current = ($remainder * 10) + (int) $digit;
            $quotient .= (string) intdiv($current, $divisor);
            $remainder = $current % $divisor;
        }

        $quotient = ltrim($quotient, '0') ?: '0';
        if ($remainder >= intdiv($divisor, 2) + ($divisor % 2)) {
            $quotient = $this->incrementDigits($quotient);
        }

        return $quotient;
    }

    private function incrementDigits(string $digits): string
    {
        for ($index = strlen($digits) - 1; $index >= 0; $index--) {
            if ($digits[$index] !== '9') {
                $digits[$index] = (string) ((int) $digits[$index] + 1);

                return $digits;
            }

            $digits[$index] = '0';
        }

        return '1'.$digits;
    }

    private function formatDigits(string $digits): string
    {
        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits) ?? $digits;
    }

    private function memberProfile(): array
    {
        // TODO: sambungkan ke data asli.
        return [
            'name' => auth()->user()->name,
            'nip' => '199008022018021001',
            'jabatan' => 'Pranata Komputer Pelaksana Lanjutan / Mahir',
            'avatar_url' => null,
        ];
    }
}
