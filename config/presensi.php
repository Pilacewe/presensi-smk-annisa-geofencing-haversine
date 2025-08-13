<?php

return [
    'lat'    => env('PRESENSI_LAT', -6.473993312484922),
    'lng'    => env('PRESENSI_LNG', 106.87307015952167),
    'radius' => env('PRESENSI_RADIUS_M', 150),

    // Window waktu
    'jam_masuk_start'   => env('PRESENSI_MASUK_START', '07:00'),
    'jam_masuk_end'     => env('PRESENSI_MASUK_END',   '08:00'),

    'jam_keluar_start'  => env('PRESENSI_KELUAR_START','15:00'),
    'jam_keluar_end'    => env('PRESENSI_KELUAR_END',  '15:40'),
];
