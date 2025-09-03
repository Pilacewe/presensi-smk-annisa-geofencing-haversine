<?php
// database/seeders/SettingsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder {
    public function run(): void {
        $defaults = [
            'presensi.jam_target_masuk' => '07:00',
            'presensi.jam_masuk_start'  => '05:00',
            'presensi.jam_keluar_start' => '16:00',
            'presensi.radius'           => '150',
            'presensi.office_lat'       => '-6.200000',
            'presensi.office_lng'       => '106.816666',
        ];
        foreach ($defaults as $k => $v) {
            Setting::firstOrCreate(['key'=>$k], ['value'=>$v]);
        }
    }
}
