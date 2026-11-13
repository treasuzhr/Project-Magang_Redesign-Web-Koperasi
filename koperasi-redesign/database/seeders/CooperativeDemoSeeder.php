<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Berita;
use App\Models\Dokumen;
use App\Models\Kategori;
use App\Models\PinjamanJenis;
use App\Models\Produk;
use App\Models\ShuPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class CooperativeDemoSeeder extends Seeder
{
    public const ADMIN_NIP = '900000000000000001';

    public const ANGGOTA_NIP = '900000000000000002';

    public const ANGGOTA_KEDUA_NIP = '900000000000000003';

    public const PASSWORD = 'DemoPrimkop!2026';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Data demo tidak boleh ditanam di production.');
        }

        DB::transaction(function () {
            // Repeat safely without resetting passwords or appending duplicate financial transactions.
            if (User::where('nip', self::ADMIN_NIP)->exists()) {
                return;
            }

            $password = Hash::make(self::PASSWORD);
            User::create([
                'nip' => self::ADMIN_NIP, 'name' => 'Admin Fiktif Demo',
                'email' => null, 'password' => $password, 'role' => 'admin',
                'must_change_password' => true,
            ]);
            $members = collect();
            for ($index = 1; $index <= 20; $index++) {
                $suffix = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
                $user = User::create([
                    'nip' => $index === 1 ? self::ANGGOTA_NIP : '900000000000000'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'name' => 'Anggota Fiktif '.$suffix,
                    'email' => null, 'password' => $index <= 2 ? $password : Hash::make(Str::password(32)), 'role' => 'anggota',
                    'must_change_password' => true,
                ]);
                $joined = match ($index) {
                    17 => '2022-07-15', 18 => '2024-04-10', 19 => '2025-07-01',
                    default => '2021-01-01',
                };
                $left = match ($index) {
                    15 => '2023-06-30', 16 => '2025-09-30', 20 => '2024-03-31',
                    default => null,
                };
                $members->push(Anggota::create([
                    'user_id' => $user->id, 'nomor_anggota' => 'DEMO-'.$suffix,
                    'nama' => $user->name, 'tanggal_masuk' => $joined, 'tanggal_keluar' => $left,
                    'status' => match ($index) {
                        15 => 'keluar', 16 => 'pensiun', 20 => 'pindah', default => 'aktif'
                    },
                ]));
            }

            $this->seedCatalogAndNews();
            $this->seedSavings($members);
            $this->seedLoans($members);
            $this->seedOrders($members);
            $this->seedShu($members);
            $this->seedDocuments();
        });
    }

    private function seedCatalogAndNews(): void
    {
        foreach (config('product_categories', []) as $category) {
            Kategori::firstOrCreate(['slug' => $category['slug']], ['nama' => $category['name']]);
        }
        foreach (config('product_catalog.samples', []) as $product) {
            Produk::firstOrCreate(['nama' => $product['name']], [
                'kategori_id' => Kategori::where('slug', $product['category'])->value('id'),
                'harga' => $product['price'], 'aktif' => true, 'foto' => $product['image'] ?? null,
            ]);
        }
        foreach (config('news.articles', []) as $article) {
            Berita::firstOrCreate(['slug' => $article['slug'] ?? Str::slug($article['title'])], [
                'judul' => $article['title'], 'ringkasan' => $article['excerpt'],
                // Store the existing structured content losslessly, including inline images and author metadata.
                'isi' => ['sections' => $article['sections'] ?? [], 'author_role' => $article['author_role'] ?? null],
                'foto' => $article['detail_image'] ?? $article['image'] ?? null,
                'penulis' => $article['author'] ?? null, 'published_at' => $article['datetime'],
            ]);
        }
    }

    private function seedSavings(Collection $members): void
    {
        foreach ($members as $index => $member) {
            $this->saving($member, 'pokok', 'setor', 100000, $member->tanggal_masuk->toDateString(), 'manual');
            for ($year = 2021; $year <= 2025; $year++) {
                for ($month = 1; $month <= 12; $month++) {
                    $date = CarbonImmutable::create($year, $month, 1)->endOfMonth()->startOfDay();
                    if (! $this->isMemberOn($member, $date)) {
                        continue;
                    }
                    $this->saving($member, 'wajib', 'setor', $year <= 2023 ? 50000 : 100000, $date->toDateString(), 'potong_gaji');
                    $this->saving($member, 'sukarela', 'setor', 50000 + ($index % 4) * 25000, $date->toDateString(), 'manual');
                    if ($month === 10) {
                        $this->saving($member, 'sukarela', 'tarik', 25000, $date->toDateString(), 'manual');
                    }
                }
            }
        }
    }

    private function saving(Anggota $member, string $jenis, string $tipe, int $nominal, string $tanggal, string $sumber): void
    {
        $member->simpananTransaksis()->create(compact('jenis', 'tipe', 'nominal', 'tanggal', 'sumber') + [
            'keterangan' => 'Data fiktif untuk pengembangan',
        ]);
    }

    private function seedLoans(Collection $members): void
    {
        // Plafon, tenor, and USP Khusus interest: ganti sesuai konfirmasi pengurus.
        // Owner supplied Toko 2% and Usipa Anggota 1% flat per month.
        $types = [
            ['kode' => 'toko', 'nama' => 'Toko', 'bunga_persen_per_bulan' => '2.0000', 'plafon_maks' => 10000000, 'tenor_maks' => 12, 'butuh_dokumen_akad' => false],
            ['kode' => 'usipa', 'nama' => 'Unit Simpan Pinjam', 'bunga_persen_per_bulan' => '1.0000', 'plafon_maks' => 50000000, 'tenor_maks' => 36, 'butuh_dokumen_akad' => false],
            ['kode' => 'usipa_khusus', 'nama' => 'USP Khusus', 'bunga_persen_per_bulan' => '1.0000', 'plafon_maks' => 100000000, 'tenor_maks' => 60, 'butuh_dokumen_akad' => true],
        ];
        $kinds = collect($types)->mapWithKeys(fn (array $type): array => [$type['kode'] => PinjamanJenis::firstOrCreate(['kode' => $type['kode']], $type)]);
        for ($year = 2021; $year <= 2025; $year++) {
            foreach (['toko', 'usipa', 'usipa_khusus'] as $index => $code) {
                $this->loan($members[$index], $kinds[$code], 'lunas', $year.'-01-15', 'DEMO-PIN-'.$year.'-'.$code);
            }
        }
        foreach (['diajukan', 'disetujui', 'ditolak', 'berjalan'] as $index => $status) {
            $this->loan($members[$index + 3], $kinds[PinjamanJenis::KODE[$index % 3]], $status, '2025-08-15', 'DEMO-PIN-STATUS-'.$status);
        }
        $this->loan($members[1], $kinds['usipa'], 'berjalan', '2025-08-15', 'DEMO-PIN-ANGGOTA-KEDUA');
    }

    private function loan(Anggota $member, PinjamanJenis $kind, string $status, string $date, string $code): void
    {
        $approved = in_array($status, ['disetujui', 'berjalan', 'lunas'], true);
        $loan = $member->pinjamans()->create([
            'pinjaman_jenis_id' => $kind->id, 'kode' => $code, 'jumlah' => 1000000,
            'tenor_bulan' => 10, 'bunga_persen_per_bulan' => $kind->bunga_persen_per_bulan,
            'metode_bayar' => $kind->kode === 'toko' ? 'potong_gaji' : 'tunjangan_kinerja',
            'status' => $status, 'tanggal_pengajuan' => $date,
            'tanggal_disetujui' => $approved ? $date : null,
            'catatan_admin' => $status === 'ditolak' ? 'Contoh pengajuan ditolak (data fiktif).' : null,
            // No fictional file path: the akad document has not been supplied.
            'dokumen_akad_path' => null,
        ]);
        if (! $approved) {
            return;
        }
        // Integer rupiah: distribute any principal remainder across the first installments.
        $principal = intdiv($loan->jumlah, $loan->tenor_bulan);
        $remainder = $loan->jumlah % $loan->tenor_bulan;
        $interest = (int) round($loan->jumlah * (float) $loan->bunga_persen_per_bulan / 100);
        for ($number = 1; $number <= $loan->tenor_bulan; $number++) {
            $due = CarbonImmutable::parse($date)->addMonthsNoOverflow($number);
            $paid = $status === 'lunas' || ($status === 'berjalan' && $number <= 2);
            $pokok = $principal + ($number <= $remainder ? 1 : 0);
            $loan->angsurans()->create([
                'angsuran_ke' => $number, 'jatuh_tempo' => $due->toDateString(),
                'pokok' => $pokok, 'bunga' => $interest, 'total' => $pokok + $interest,
                'status' => $paid ? 'lunas' : ($status === 'berjalan' && $number === 3 ? 'tunggak' : 'belum'),
                'tanggal_bayar' => $paid ? $due->toDateString() : null,
            ]);
        }
    }

    private function seedOrders(Collection $members): void
    {
        $products = Produk::orderBy('id')->get();
        foreach ($members as $index => $member) {
            for ($year = 2021; $year <= 2025; $year++) {
                $date = CarbonImmutable::create($year, 8, 20);
                if (! $this->isMemberOn($member, $date)) {
                    continue;
                }
                foreach (['selesai', 'baru', 'diproses', 'siap', 'batal'] as $state) {
                    $product = $products[$index % $products->count()];
                    $qty = 1 + $index % 3;
                    $order = $member->pesanans()->create([
                        'kode' => 'DEMO-ORD-'.$member->id.'-'.$year.'-'.$state,
                        'status' => $state, 'tanggal' => $date->toDateTimeString(),
                        'total' => $product->harga * $qty, 'metode_bayar' => 'potong_gaji',
                        'catatan' => 'Data pesanan fiktif.',
                    ]);
                    $order->items()->create([
                        'produk_id' => $product->id, 'nama' => $product->nama,
                        'harga' => $product->harga, 'qty' => $qty, 'subtotal' => $order->total,
                    ]);
                }
            }
        }
    }

    private function seedShu(Collection $members): void
    {
        for ($year = 2021; $year <= 2025; $year++) {
            $period = ShuPeriode::create([
                'tahun' => $year, 'status' => $year === 2025 ? 'draft' : 'ditetapkan',
                'ditetapkan_at' => $year === 2025 ? null : ($year + 1).'-03-01 09:00:00',
            ]);
            // All annual pool values below are placeholders: ganti sesuai konfirmasi pengurus.
            $components = [
                ['simpanan_pokok_wajib', 'toko', 'proporsional', 1000000 + ($year - 2021) * 100000, null],
                ['belanja', 'toko', 'proporsional', 500000, null],
                ['simpanan_sukarela', 'usipa', 'proporsional', 600000, null],
                ['jasa_usipa_anggota', 'usipa', 'persentase_tetap', null, '27.5000'],
                ['jasa_usipa_khusus', 'usipa_khusus', 'proporsional', 400000, null],
                ['jasa_umum', 'jasa_umum', 'bagi_rata_prorata', 800000, null],
            ];
            $eligible = $members->filter(fn (Anggota $member): bool => $this->activeMonths($member, $year) > 0);
            foreach ($components as [$code, $unit, $method, $pool, $percentage]) {
                $component = $period->komponens()->create([
                    'kode' => $code, 'unit' => $unit, 'metode' => $method,
                    'pool_nominal' => $pool, 'persentase' => $percentage,
                ]);
                $bases = $eligible->mapWithKeys(fn (Anggota $member): array => [$member->id => $this->shuBase($member, $code, $year)]);
                $denominator = $bases->sum();
                $percentageUnits = (int) round((float) ($component->persentase ?? 0) * 10000);
                $shares = $bases->map(fn (int $base): int => match ($method) {
                    'proporsional' => $denominator === 0 ? 0 : intdiv($pool * $base, $denominator),
                    'persentase_tetap' => intdiv($base * $percentageUnits, 1000000),
                    default => 0,
                });
                if ($method === 'proporsional' && $denominator > 0) {
                    // Largest remainders preserve the exact integer-rupiah pool total.
                    $remaining = $pool - $shares->sum();
                    $ranked = $bases->keys()->sortByDesc(fn (int $id): int => ($pool * $bases[$id]) % $denominator);
                    foreach ($ranked->take($remaining) as $id) {
                        $shares[$id] = $shares[$id] + 1;
                    }
                }
                foreach ($eligible as $member) {
                    $months = $this->activeMonths($member, $year);
                    $share = $method === 'bagi_rata_prorata'
                        ? intdiv($pool * $months, $eligible->count() * 12)
                        : $shares[$member->id];
                    $component->shuAnggotas()->create([
                        'shu_periode_id' => $period->id, 'anggota_id' => $member->id,
                        'kode_komponen' => $code, 'jumlah_dasar' => $bases[$member->id],
                        'bulan_aktif' => $months, 'shu' => $share,
                    ]);
                    if ($code === 'jasa_usipa_khusus' && $period->status === 'ditetapkan' && $share > 0) {
                        $posted = CarbonImmutable::create($year + 1, 3, 1);
                        if ($this->isMemberOn($member, $posted)) {
                            $this->saving($member, 'khusus', 'setor', $share, $posted->toDateString(), 'shu');
                        }
                    }
                }
            }
            // Seed-only calculated demo allocation; not an official RAT net-profit figure.
            $period->update(['shu_bersih' => $period->shuAnggotas()->sum('shu')]);
        }
    }

    private function shuBase(Anggota $member, string $code, int $year): int
    {
        $end = $year.'-12-31';

        return match ($code) {
            'simpanan_pokok_wajib' => $member->saldoSimpanan('pokok', $end) + $member->saldoSimpanan('wajib', $end),
            'simpanan_sukarela' => $member->saldoSimpanan('sukarela', $end),
            'belanja' => (int) $member->pesanans()->where('status', 'selesai')->whereYear('tanggal', $year)->sum('total'),
            'jasa_usipa_anggota', 'jasa_usipa_khusus' => (int) Angsuran::whereHas('pinjaman', fn ($query) => $query
                ->where('anggota_id', $member->id)
                ->whereHas('jenis', fn ($type) => $type->where('kode', $code === 'jasa_usipa_anggota' ? 'usipa' : 'usipa_khusus')))
                ->where('status', 'lunas')->whereYear('tanggal_bayar', $year)->sum('bunga'),
            default => 0,
        };
    }

    private function activeMonths(Anggota $member, int $year): int
    {
        $start = $member->tanggal_masuk->max(CarbonImmutable::create($year, 1, 1));
        $end = ($member->tanggal_keluar ?? CarbonImmutable::create($year, 12, 31))->min(CarbonImmutable::create($year, 12, 31));
        if ($start->gt($end)) {
            return 0;
        }

        // Demo convention: joining/leaving calendar months count as active months.
        return ($end->year - $start->year) * 12 + $end->month - $start->month + 1;
    }

    private function isMemberOn(Anggota $member, CarbonImmutable $date): bool
    {
        return $date->gte($member->tanggal_masuk)
            && ($member->tanggal_keluar === null || $date->lte($member->tanggal_keluar));
    }

    private function seedDocuments(): void
    {
        // No PDF was supplied. Keep files absent and unpublished; do not fabricate file paths.
        foreach ([2021, 2022, 2023, 2024, 2025] as $year) {
            Dokumen::firstOrCreate(['judul' => 'Dokumen Demo Fiktif '.$year, 'tahun' => $year], [
                'file_path' => null, 'published' => false,
            ]);
        }
    }
}
