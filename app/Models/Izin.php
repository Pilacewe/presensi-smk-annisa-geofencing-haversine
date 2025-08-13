<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Izin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','jenis','tgl_mulai','tgl_selesai',
        'keterangan','lampiran_path','status','approver_id','approved_at'
    ];

    protected $casts = [
        'tgl_mulai'   => 'date',
        'tgl_selesai' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user()     { return $this->belongsTo(User::class); }
    public function approver() { return $this->belongsTo(User::class,'approver_id'); }
}
