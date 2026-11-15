<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shu_periodes', function (Blueprint $table) {
            $table->json('parameter')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shu_periodes', function (Blueprint $table) {
            $table->dropColumn('parameter');
        });
    }
};
