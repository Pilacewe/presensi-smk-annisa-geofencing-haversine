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
    Schema::create('izins', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // izin / sakit / dinas luar (opsional)
        $table->enum('jenis', ['izin', 'sakit', 'dinas'])->default('izin');

        // rentang tanggal
        $table->date('tgl_mulai');
        $table->date('tgl_selesai');

        // alasan
        $table->text('keterangan')->nullable();

        // lampiran bukti (opsional)
        $table->string('lampiran_path')->nullable();

        // alur approval
        $table->enum('status', ['pending','approved','rejected'])->default('pending');
        $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('approved_at')->nullable();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('izins');
}

};
