@extends('layouts.piket') 

@section('title','Dashboard Piket')
@section('subtitle','Ringkasan kehadiran & aktivitas presensi')

@section('actions')
  <a href="{{ route('presensi.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700">
    Presensi (Absen Pribadi)
  </a>
@endsection

@section('content')
    {{-- Stat cards --}}
  <div class="grid sm:grid-cols-4 gap-4 mb-6">
    <x-stat icon="users"  label="Total Guru"        value="{{ $totalGuru }}" color="indigo"/>
    <x-stat icon="check"  label="Hadir (hari ini)"  value="{{ $hadir }}"     color="emerald"/>
    <x-stat icon="clock"  label="Izin (hari ini)"   value="{{ $izin }}"      color="amber"/>
    <x-stat icon="heart"  label="Sakit (hari ini)"  value="{{ $sakit }}"     color="rose"/>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Log Aktivitas --}}
    <section class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">
          Log Aktivitas Presensi
          <span class="text-xs text-slate-500 align-middle">
            {{-- info kecil: tampilkan keterangan sumber log --}}
            {{-- Komentar ini opsional; kalau Opsi B dipakai, hilangkan "(hari ini)" --}}
            {{-- (Terbaru lintas hari) --}}
          </span>
        </h3>
        <a href="{{ route('piket.riwayat') ?? '#' }}" class="text-sm text-indigo-700 hover:underline">Lihat riwayat</a>
      </div>

      @if($recent->isEmpty())
        <p class="text-sm text-slate-500">Belum ada aktivitas presensi yang terekam.</p>
      @else
        <ul class="divide-y">
          @foreach($recent as $r)
            @php
              $badge = match($r->status){
                'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                'izin'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                'sakit' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200'
              };
            @endphp
            <li class="py-3 flex items-center justify-between">
              <div class="min-w-0">
                <p class="font-medium truncate">{{ $r->user?->name ?? '—' }}</p>
                <p class="text-xs text-slate-500">
                  {{ \Illuminate\Support\Carbon::parse($r->tanggal)->translatedFormat('l, d F Y') }}
                </p>
              </div>
              <div class="flex items-center gap-3 text-xs">
                <span class="px-2.5 py-1 rounded-full {{ $badge }}">{{ ucfirst($r->status) }}</span>
                <div class="text-right text-slate-600">
                  <div>Masuk: <b>{{ $r->jam_masuk ?? '—' }}</b></div>
                  <div>Keluar: <b>{{ $r->jam_keluar ?? '—' }}</b></div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    {{-- Quick Actions (sesuai flowchart) --}}
    <aside class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-5 space-y-3">
      <a href="{{ route('piket.cek') ?? '#' }}" class="block px-4 py-3 rounded-xl bg-slate-900 text-white hover:bg-slate-800">
        Ngecek Guru (Hari Ini)
      </a>
      <a href="{{ route('piket.rekap') ?? '#' }}" class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
        Rekap Harian
      </a>
      <a href="{{ route('piket.riwayat') ?? '#' }}" class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
        Riwayat Presensi
      </a>
    </aside>
  </div>
@endsection
