<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('izins', function (Blueprint $table) {
            $table->string('bukti')->nullable()->after('keterangan');
        });
    }
    public function down(): void {
        Schema::table('izins', function (Blueprint $table) {
            $table->dropColumn('bukti');
        });
    }
};
