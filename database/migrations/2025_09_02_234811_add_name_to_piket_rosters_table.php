<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // php artisan make:migration add_name_to_piket_rosters_table --table=piket_rosters

public function up(): void
{
    Schema::table('piket_rosters', function (Blueprint $table) {
        if (!Schema::hasColumn('piket_rosters', 'name')) {
            $table->string('name', 100)->nullable()->after('user_id');
        }
    });
}
public function down(): void
{
    Schema::table('piket_rosters', function (Blueprint $table) {
        if (Schema::hasColumn('piket_rosters', 'name')) {
            $table->dropColumn('name');
        }
    });
}

};
