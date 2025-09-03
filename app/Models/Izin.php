<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Izin extends Model
{
    use HasFactory;

     protected $fillable = [
        'user_id','jenis','tgl_mulai','tgl_selesai','keterangan',
        'status','approved_by','approved_at','reject_reason','bukti',
    ];
    protected $appends = ['bukti_url'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getBuktiUrlAttribute(): ?string
    {
        if (!$this->bukti) return null;
        // asumsi disimpan di disk 'public'
        return Storage::disk('public')->url($this->bukti);
    }

    protected $casts = [
        'tgl_mulai'   => 'date',
        'tgl_selesai' => 'date',
        'approved_at' => 'datetime',
    ];

    // App\Models\Izin.php
    public function approver() { return $this->belongsTo(User::class,'approved_by'); }
}
