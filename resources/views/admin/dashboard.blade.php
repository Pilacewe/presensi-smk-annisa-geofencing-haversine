@extends('layouts.admin')

@section('title','Dashboard Admin')
@section('subtitle','Ikhtisar presensi & navigasi cepat')

@section('actions')
  <a href="{{ route('admin.presensi.index') }}" class="px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
    Kelola Presensi
  </a>
@endsection

@section('content')
  {{-- Ringkasan --}}
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="rounded-2xl ring-1 ring-slate-200 bg-white p-4">
      <p class="text-xs text-slate-500">Total Presensi (bulan ini)</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-slate-900">{{ $totalBulanIni ?? '—' }}</p>
    </div>
    <div class="rounded-2xl ring-1 ring-emerald-200 bg-white p-4">
      <p class="text-xs text-slate-500">Hadir</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-emerald-700">{{ $hadirBulanIni ?? '—' }}</p>
    </div>
    <div class="rounded-2xl ring-1 ring-amber-200 bg-white p-4">
      <p class="text-xs text-slate-500">Izin</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-amber-700">{{ $izinBulanIni ?? '—' }}</p>
    </div>
    <div class="rounded-2xl ring-1 ring-rose-200 bg-white p-4">
      <p class="text-xs text-slate-500">Sakit</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-rose-700">{{ $sakitBulanIni ?? '—' }}</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Aktivitas Terbaru --}}
    <section class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
      <div class="px-5 py-4 flex items-center justify-between">
        <h3 class="font-semibold">Aktivitas Terbaru</h3>
        <a href="{{ route('admin.presensi.index') }}" class="text-sm text-indigo-700 hover:underline">Kelola Presensi</a>
      </div>
      @if(($recent ?? collect())->isEmpty())
        <div class="px-5 pb-6"><p class="text-sm text-slate-500">Belum ada aktivitas.</p></div>
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
            <li class="px-5 py-3 flex items-center justify-between">
              <div class="min-w-0">
                <p class="font-medium truncate">{{ $r->user?->name ?? '—' }}</p>
                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($r->tanggal)->translatedFormat('l, d F Y') }}</p>
              </div>
              <div class="flex items-center gap-3 text-xs">
                <span class="px-2.5 py-1 rounded-full {{ $badge }}">{{ ucfirst($r->status ?? '-') }}</span>
                <div class="text-right text-slate-600 leading-tight">
                  <div>Masuk: <b>{{ $r->jam_masuk ? \Illuminate\Support\Str::of($r->jam_masuk)->substr(0,5) : '—' }}</b></div>
                  <div>Keluar: <b>{{ $r->jam_keluar ? \Illuminate\Support\Str::of($r->jam_keluar)->substr(0,5) : '—' }}</b></div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    {{-- Navigation cepat --}}
    <aside class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-5">
      <h3 class="font-semibold mb-3">Navigasi</h3>
      <div class="space-y-3">
        <a href="{{ route('admin.presensi.index') }}"
           class="flex items-center justify-between w-full px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
          <span>Kelola Presensi</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('izin.index') }}"
           class="flex items-center justify-between w-full px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
          <span>Daftar Izin</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('tu.export.index') }}"
           class="flex items-center justify-between w-full px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
          <span>Laporan / Export</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 18l6-6-6-6"/></svg>
        </a>
      </div>

      <div class="mt-5 p-4 rounded-xl bg-slate-50 ring-1 ring-slate-200 text-xs text-slate-600">
        Tips: gunakan filter di menu <b>Kelola Presensi</b> untuk melihat per role (Guru/TU/Piket/Kepsek) dan rentang tanggal.
      </div>
    </aside>
  </div>
@endsection
