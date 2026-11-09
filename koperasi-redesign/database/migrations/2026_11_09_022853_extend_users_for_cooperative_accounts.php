<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->unique();
            $table->string('email')->nullable()->change();
            $table->string('role')->default('anggota')->index();
            $table->boolean('must_change_password')->default(true);
        });

        // Preserve existing accounts without inventing a real NIP for them.
        DB::table('users')->whereNull('nip')->orderBy('id')->eachById(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['nip' => 'LEGACY-'.$user->id]);
        });
        Schema::table('users', fn (Blueprint $table) => $table->string('nip')->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropIndex(['role']);
            $table->dropColumn(['nip', 'role', 'must_change_password']);
        });
        // Email stays nullable so rollback never fabricates addresses or loses accounts.
    }
};
