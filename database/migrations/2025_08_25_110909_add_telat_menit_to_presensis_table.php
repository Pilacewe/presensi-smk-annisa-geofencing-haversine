<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('presensis')) return;

        Schema::table('presensis', function (Blueprint $table) {
            if (!Schema::hasColumn('presensis', 'telat_menit')) {
                $table->unsignedSmallInteger('telat_menit')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('presensis')) return;

        Schema::table('presensis', function (Blueprint $table) {
            if (Schema::hasColumn('presensis', 'telat_menit')) {
                $table->dropColumn('telat_menit');
            }
        });
    }
};
