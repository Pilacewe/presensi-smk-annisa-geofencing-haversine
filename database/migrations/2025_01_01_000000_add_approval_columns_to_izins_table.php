<?php
// database/migrations/2025_01_01_000000_add_approval_columns_to_izins_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // JIKA TABEL IZINS BELUM ADA, LEWAT (agar tidak error urutan)
        if (!Schema::hasTable('izins')) return;

        Schema::table('izins', function (Blueprint $table) {
            // Tambah kolom hanya jika belum ada
            if (!Schema::hasColumn('izins', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
            if (!Schema::hasColumn('izins', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('izins', 'reject_reason')) {
                $table->string('reject_reason', 255)->nullable()->after('approved_at');
            }
        });

        // Optional: pastikan enum status mendukung 'approved' dan 'rejected'
        try {
            $col = DB::selectOne("SHOW COLUMNS FROM `izins` LIKE 'status'");
            $type = $col?->Type ?? '';
            if (str_starts_with(strtolower($type), 'enum(')) {
                $needApproved = !str_contains($type, "'approved'");
                $needRejected = !str_contains($type, "'rejected'");
                if ($needApproved || $needRejected) {
                    DB::statement("ALTER TABLE `izins` MODIFY `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika bukan MySQL/ENUM atau sudah sesuai
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('izins')) return;

        Schema::table('izins', function (Blueprint $table) {
            // Lepas FK lalu drop kolom (aman kalau tidak ada)
            try { $table->dropConstrainedForeignId('approved_by'); } catch (\Throwable $e) {}
            try { $table->dropForeign(['approved_by']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('izins', 'approved_by'))   $table->dropColumn('approved_by');
            if (Schema::hasColumn('izins', 'approved_at'))   $table->dropColumn('approved_at');
            if (Schema::hasColumn('izins', 'reject_reason')) $table->dropColumn('reject_reason');
        });
    }
};
