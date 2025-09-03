<?php
// database/migrations/2025_09_03_000001_add_coords_to_presensis_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            // precision 10, scale 7 cukup untuk koordinat (±1 cm)
            if (!Schema::hasColumn('presensis','latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('telat_menit');
            }
            if (!Schema::hasColumn('presensis','longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            if (Schema::hasColumn('presensis','latitude'))  $table->dropColumn('latitude');
            if (Schema::hasColumn('presensis','longitude')) $table->dropColumn('longitude');
        });
    }
};
