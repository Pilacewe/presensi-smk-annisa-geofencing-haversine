@extends('layouts.tu')
@section('title','Dashboard TU')

@section('content')
  {{-- Stat cards --}}
  <div class="grid sm:grid-cols-4 gap-4 mb-6">
    <x-stat icon="users"  label="Total Guru"        value="{{ $totalGuru }}" color="indigo"/>
    <x-stat icon="check"  label="Hadir (hari ini)"  value="{{ $hadir }}"     color="emerald"/>
    <x-stat icon="clock"  label="Izin (hari ini)"   value="{{ $izin }}"      color="amber"/>
    <x-stat icon="heart"  label="Sakit (hari ini)"  value="{{ $sakit }}"     color="rose"/>
  </div>

  {{-- 2 kolom: Log (span 2) + Quick actions (span 1) --}}
  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Log terbaru --}}
    <div class="lg:col-span-2 rounded-xl bg-white shadow-sm p-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-lg">Log Presensi Terbaru</h3>
        <a href="{{ route('tu.presensi.index') }}" class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
      </div>

      <div class="divide-y">
        @forelse($recent as $r)
          <div class="py-3 flex items-center justify-between">
            <div>
              <div class="font-medium text-slate-800">{{ $r->user->name }}</div>
              <div class="mt-0.5 text-xs text-slate-500 flex flex-wrap items-center gap-2">
                {{-- Tanggal --}}
                <span class="inline-flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}
                </span>
                {{-- Jam Masuk --}}
                <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full text-[11px] font-medium">
                  Masuk: {{ $r->jam_masuk ?? '-' }}
                </span>
                {{-- Jam Keluar --}}
                <span class="bg-rose-50 text-rose-700 px-2 py-0.5 rounded-full text-[11px] font-medium">
                  Keluar: {{ $r->jam_keluar ?? '-' }}
                </span>
              </div>
            </div>

            @php
              $badge = match($r->status){
                'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                'izin'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                'sakit' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
              };
            @endphp
            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">
              {{ ucfirst($r->status) }}
            </span>
          </div>
        @empty
          <p class="text-sm text-slate-500 italic">Belum ada log hari ini.</p>
        @endforelse
      </div>
    </div>

    {{-- Quick actions (di kolom kanan, tetap satu kolom) --}}
    <div class="rounded-xl bg-white shadow-sm p-4 space-y-3">
      <a class="block p-4 rounded-xl bg-slate-900 text-white hover:bg-slate-800"
         href="{{ route('tu.presensi.index') }}">
        Lihat Presensi Guru
      </a>
      <a class="block p-4 rounded-xl bg-slate-100 hover:bg-slate-200"
         href="{{ route('tu.riwayat') }}">
        Riwayat Presensi
      </a>
     
      <a class="block p-4 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700"
         href="{{ route('tu.export.index') }}">
        Export PDF/Excel
      </a>
    </div>
  </div>
@endsection
