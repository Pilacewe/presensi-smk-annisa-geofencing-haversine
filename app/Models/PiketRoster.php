<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiketRoster extends Model
{
    protected $table = 'piket_rosters';

    protected $fillable = [
        'tanggal',     // date (YYYY-MM-DD)
        'user_id',     // pegawai yang jaga
        'catatan',     // opsional
        'assigned_by', // admin/piket yang mengeset
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
