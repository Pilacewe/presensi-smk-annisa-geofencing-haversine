<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // status umum: tambah 'telat' agar selaras dengan query yg ada
            $table->enum('status', ['hadir', 'telat', 'izin', 'sakit', 'alfa'])->default('hadir');

            // menit keterlambatan (opsional)
            $table->integer('telat_menit')->nullable();

            // lokasi (pakai lat/lng agar konsisten dengan sebagian kode)
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

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
