<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjamans', fn (Blueprint $table) => $table->date('tanggal_pencairan')->nullable());
        Schema::table('pesanans', fn (Blueprint $table) => $table->timestamp('selesai_pada')->nullable());
        DB::table('pinjamans')->whereIn('status', ['berjalan', 'lunas'])->whereNotNull('tanggal_disetujui')->update(['tanggal_pencairan' => DB::raw('tanggal_disetujui')]);
        DB::table('pesanans')->where('status', 'selesai')->update(['selesai_pada' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('pinjamans', fn (Blueprint $table) => $table->dropColumn('tanggal_pencairan'));
        Schema::table('pesanans', fn (Blueprint $table) => $table->dropColumn('selesai_pada'));
    }
};
