<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Geofence
    |--------------------------------------------------------------------------
    */
    'lat'    => env('PRESENSI_LAT', -6.473993312484922),
    'lng'    => env('PRESENSI_LNG', 106.87307015952167),
    'radius' => env('PRESENSI_RADIUS_M', 150), // meter

    /*
    |--------------------------------------------------------------------------
    | Aturan Waktu Presensi
    |--------------------------------------------------------------------------
    |
    | jam_target_masuk
    |   - Patokan “tepat waktu”. Jika absen setelah ini => status "telat"
    |     dan telat_menit dihitung dari jam ini.
    |
    | jam_masuk_end (opsional)
    |   - Batas TERAKHIR boleh absen masuk.
    |   - Jika ingin tanpa batas (boleh absen kapan pun, tetap dihitung telat),
    |     set ke null.
    |
    | jam_keluar_start
    |   - Mulai boleh presensi keluar.
    |
    | keluar_fleksibel
    |   - true  => boleh keluar KAPAN SAJA setelah jam_keluar_start.
    |   - false => hanya boleh tepat pada jam_keluar_start (jarang dipakai).
    |
    */

    // Target jam masuk (patokan telat)
    'jam_target_masuk' => env('PRESENSI_TARGET_MASUK', '07:00'),

    // Batas akhir absen masuk (null = tanpa batas; tetap akan dihitung telat)
    // Kalau mau dibatasi sampai 08:00, isi '08:00'. Kalau mau tanpa batas, biarkan null.
    'jam_masuk_end' => env('PRESENSI_MASUK_END') !== null
        ? env('PRESENSI_MASUK_END') // contoh di .env: PRESENSI_MASUK_END=08:00
        : null,

    // Jam mulai boleh keluar
    'jam_keluar_start' => env('PRESENSI_KELUAR_START', '16:00'),

    // Keluar fleksibel
    'keluar_fleksibel' => filter_var(env('PRESENSI_KELUAR_FLEKSIBEL', true), FILTER_VALIDATE_BOOLEAN),
];
