<?php

return [
    'lat'    => env('PRESENSI_LAT', -6.473993312484922),
    'lng'    => env('PRESENSI_LNG', 106.87307015952167),
    'radius' => env('PRESENSI_RADIUS_M', 150),

    // === Aturan waktu ===
    // Masuk hanya boleh antara jam ini
    'jam_masuk_start' => '07:00',
    'jam_masuk_end'   => '08:00',

    // Mulai boleh keluar dari jam ini
    'jam_keluar_start' => '16:00',

    // kalau true: boleh pulang kapan saja SETELAH jam_keluar_start
    // kalau false: hanya boleh tepat jam_keluar_start (biasanya tidak dipakai)
    'keluar_fleksibel' => true,
];
