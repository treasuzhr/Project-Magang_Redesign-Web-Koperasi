<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('nomor_anggota')->nullable()->unique();
            $table->string('nama');
            $table->string('pangkat_golongan')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('avatar_path')->nullable();
            $table->date('tanggal_masuk')->index();
            $table->date('tanggal_keluar')->nullable()->index();
            $table->string('status')->default('aktif')->index();
            $table->timestamps();
        });

        Schema::create('simpanan_transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggotas')->restrictOnDelete();
            $table->string('jenis')->index();
            $table->string('tipe');
            $table->unsignedBigInteger('nominal');
            $table->date('tanggal')->index();
            $table->string('sumber');
            $table->text('keterangan')->nullable();
            $table->index(['anggota_id', 'jenis', 'tanggal']);
            $table->timestamps();
        });

        Schema::create('pinjaman_jenis', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->decimal('bunga_persen_per_bulan', 7, 4);
            $table->unsignedBigInteger('plafon_maks');
            $table->unsignedSmallInteger('tenor_maks');
            $table->boolean('butuh_dokumen_akad')->default(false);
            $table->timestamps();
        });

        Schema::create('pinjamans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggotas')->restrictOnDelete();
            $table->foreignId('pinjaman_jenis_id')->constrained('pinjaman_jenis')->restrictOnDelete();
            $table->string('kode')->unique();
            $table->unsignedBigInteger('jumlah');
            $table->unsignedSmallInteger('tenor_bulan');
            $table->decimal('bunga_persen_per_bulan', 7, 4);
            $table->string('metode_bayar');
            $table->string('status')->default('diajukan')->index();
            $table->date('tanggal_pengajuan')->index();
            $table->date('tanggal_disetujui')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->string('dokumen_akad_path')->nullable();
            $table->timestamps();
        });

        Schema::create('angsurans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pinjaman_id')->constrained('pinjamans')->restrictOnDelete();
            $table->unsignedSmallInteger('angsuran_ke');
            $table->date('jatuh_tempo')->index();
            $table->unsignedBigInteger('pokok');
            $table->unsignedBigInteger('bunga');
            $table->unsignedBigInteger('total');
            $table->string('status')->default('belum')->index();
            $table->date('tanggal_bayar')->nullable()->index();
            $table->unique(['pinjaman_id', 'angsuran_ke']);
            $table->timestamps();
        });

        Schema::create('shu_periodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('shu_bersih')->nullable();
            $table->timestamp('ditetapkan_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shu_komponens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_periode_id')->constrained('shu_periodes')->restrictOnDelete();
            $table->string('kode');
            $table->string('unit');
            $table->string('metode');
            $table->unsignedBigInteger('pool_nominal')->nullable();
            $table->decimal('persentase', 7, 4)->nullable();
            $table->unique(['shu_periode_id', 'kode']);
            $table->unique(['id', 'shu_periode_id', 'kode'], 'shu_komponen_identity_unique');
            $table->timestamps();
        });

        Schema::create('shu_anggotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_periode_id')->constrained('shu_periodes')->restrictOnDelete();
            $table->foreignId('shu_komponen_id')->index();
            $table->foreignId('anggota_id')->constrained('anggotas')->restrictOnDelete();
            $table->string('kode_komponen');
            $table->unsignedBigInteger('jumlah_dasar')->nullable();
            $table->unsignedTinyInteger('bulan_aktif')->nullable();
            $table->unsignedBigInteger('shu');
            $table->unique(['shu_periode_id', 'anggota_id', 'kode_komponen']);
            $table->foreign(['shu_komponen_id', 'shu_periode_id', 'kode_komponen'], 'shu_anggota_komponen_fk')
                ->references(['id', 'shu_periode_id', 'kode'])->on('shu_komponens')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->nullable()->constrained('kategoris')->nullOnDelete();
            $table->string('nama')->unique();
            $table->unsignedBigInteger('harga');
            $table->boolean('aktif')->default(true)->index();
            $table->string('foto')->nullable();
            $table->timestamps();
        });

        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggotas')->restrictOnDelete();
            $table->string('kode')->unique();
            $table->string('status')->default('baru')->index();
            $table->unsignedBigInteger('total');
            $table->string('metode_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('tanggal')->index();
            $table->index(['anggota_id', 'status', 'tanggal']);
            $table->timestamps();
        });

        Schema::create('pesanan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanans')->restrictOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produks')->nullOnDelete();
            $table->string('nama');
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('beritas', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan');
            $table->json('isi');
            $table->string('foto')->nullable();
            $table->string('penulis')->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->unsignedSmallInteger('tahun')->index();
            $table->string('file_path')->nullable();
            $table->boolean('published')->default(false)->index();
            $table->unique(['judul', 'tahun']);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('dokumens');
        Schema::dropIfExists('beritas');
        Schema::dropIfExists('pesanan_items');
        Schema::dropIfExists('pesanans');
        Schema::dropIfExists('produks');
        Schema::dropIfExists('kategoris');
        Schema::dropIfExists('shu_anggotas');
        Schema::dropIfExists('shu_komponens');
        Schema::dropIfExists('shu_periodes');
        Schema::dropIfExists('angsurans');
        Schema::dropIfExists('pinjamans');
        Schema::dropIfExists('pinjaman_jenis');
        Schema::dropIfExists('simpanan_transaksis');
        Schema::dropIfExists('anggotas');
    }
};
