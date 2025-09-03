<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Presensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'tanggal',
        'jam_masuk', 'jam_keluar', 'telat_menit',
        'status', 'latitude', 'longitude',
    ];

    protected $casts = [
        'tanggal'    => 'date',
        'jam_masuk'  => 'datetime:H:i:s',
        'jam_keluar' => 'datetime:H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
        return $this->belongsTo(\App\Models\User::class);
    }
}
