<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\User;

class PiketCekController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // List semua guru + presensi hari ini (bisa null = belum absen)
        $items = User::where('role','guru')
            ->with(['presensis' => function($q) use ($today){
                $q->whereDate('tanggal',$today);
            }])
            ->orderBy('name')
            ->paginate(15);

        return view('piket.cek', compact('items','today'));
    }
}
