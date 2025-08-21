<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('izins', function (Blueprint $table) {
            $table->date('tgl_mulai')->nullable()->change();
            // atau tambahkan kolom 'tanggal' yang baru jika mau
            // $table->date('tanggal')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izins', function (Blueprint $table) {
            $table->date('tgl_mulai')->nullable(false)->change();
            // $table->dropColumn('tanggal');
        });
    }
};