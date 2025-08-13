<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('presensis', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // 1 record per user per hari
        $table->date('tanggal')->index();

        // waktu
        $table->time('jam_masuk')->nullable();
        $table->time('jam_keluar')->nullable();

        // status umum: hadir/izin/sakit/alfa (default hadir saat masuk)
        $table->enum('status', ['hadir', 'izin', 'sakit', 'alfa'])->default('hadir');

        // lokasi terakhir saat aksi
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();

        $table->timestamps();

        // pastikan tidak ada duplikat per user per tanggal
        $table->unique(['user_id', 'tanggal']);
    });
}

public function down(): void
{
    Schema::dropIfExists('presensis');
}

};
