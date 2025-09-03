@extends('layouts.piket')
@section('title','Rekap Harian')

@section('content')
  {{-- Header + Filter Tanggal --}}
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Rekap Harian</h1>
      <p class="text-sm text-slate-500">Status presensi semua guru pada tanggal terpilih.</p>
    </div>

    <form class="flex items-center gap-2">
      <input type="date" name="tanggal" value="{{ $tanggal }}" class="h-10 rounded-lg border-slate-300">
      <button class="h-10 px-4 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Terapkan</button>
    </form>
  </div>

  @php
    // helper: menit → "X jam Y menit" / "Y menit"
    $fmtTelat = function ($m) {
      if (!$m) return null;
      $h = intdiv($m,60); $mm = $m % 60;
      return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
    };

    // safe defaults
    $totalGuru = $totalGuru ?? 0;
    $hadir     = $hadir     ?? 0;
    $telat     = $telat     ?? 0;
    $izin      = $izin      ?? 0;
    $sakit     = $sakit     ?? 0;
    $belum     = $belum     ?? 0;

    // pill builder
    $qs   = fn($s)=> request()->fullUrlWithQuery(['status'=>$s?:null]);
    $pill = function($label,$url,$active,$count=null){
      $badge = is_null($count) ? '' : "<span class='ml-2 inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] bg-slate-100 text-slate-700'>$count</span>";
      return $active
        ? "<a href='{$url}' class='px-3 py-2 rounded-xl bg-slate-900 text-white text-sm shadow-sm'>$label{$badge}</a>"
        : "<a href='{$url}' class='px-3 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50'>$label{$badge}</a>";
    };
  @endphp

  {{-- Ringkasan --}}
  <div class="grid md:grid-cols-6 gap-3 mb-5">
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Total Guru</p>
      <p class="mt-1 text-3xl font-bold tabular-nums">{{ $totalGuru }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-emerald-200">
      <p class="text-xs text-slate-500">Hadir</p>
      <p class="mt-1 text-3xl font-bold text-emerald-700 tabular-nums">{{ $hadir }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-amber-200">
      <p class="text-xs text-slate-500">Telat</p>
      <p class="mt-1 text-3xl font-bold text-amber-700 tabular-nums">{{ $telat }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-sky-200">
      <p class="text-xs text-slate-500">Izin</p>
      <p class="mt-1 text-3xl font-bold text-sky-700 tabular-nums">{{ $izin }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-rose-200">
      <p class="text-xs text-slate-500">Sakit</p>
      <p class="mt-1 text-3xl font-bold text-rose-700 tabular-nums">{{ $sakit }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Belum Absen</p>
      <p class="mt-1 text-3xl font-bold text-slate-800 tabular-nums">{{ $belum }}</p>
    </div>
  </div>

  {{-- Filter Pills --}}
  <div class="mb-4 flex flex-wrap gap-2">
    {!! $pill('Semua', $qs(null), $filterSt===null, $totalGuru) !!}
    {!! $pill('Hadir', $qs('hadir'), $filterSt==='hadir', $hadir) !!}
    {!! $pill('Telat', $qs('telat'), $filterSt==='telat', $telat) !!}
    {!! $pill('Izin',  $qs('izin'),  $filterSt==='izin',  $izin) !!}
    {!! $pill('Sakit', $qs('sakit'), $filterSt==='sakit', $sakit) !!}
    {!! $pill('Belum', $qs('belum'), $filterSt==='belum', $belum) !!}
  </div>

  {{-- LIST MOBILE (cards) --}}
  <div class="md:hidden grid gap-3">
    @forelse($rows as $r)
      @php
        $st = strtolower($r->status ?? 'belum');
        $cls = match($st) {
          'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
          'telat' => 'bg-amber-50 text-amber-700 ring-amber-200',
          'izin'  => 'bg-sky-50 text-sky-700 ring-sky-200',
          'sakit' => 'bg-rose-50 text-rose-700 ring-rose-200',
          default => 'bg-slate-50 text-slate-700 ring-slate-200',
        };
        $in  = $r->jam_masuk  ? \Illuminate\Support\Str::of($r->jam_masuk)->substr(0,5)  : '—';
        $out = $r->jam_keluar ? \Illuminate\Support\Str::of($r->jam_keluar)->substr(0,5) : '—';
        $durTelat = ($st==='telat' && ($r->telat_menit ?? null)) ? $fmtTelat($r->telat_menit) : null;
      @endphp
      <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-4">
        <div class="flex items-center justify-between">
          <p class="font-semibold text-slate-900">{{ $r->user->name }}</p>
          <span class="inline-flex items-center px-2 py-0.5 rounded-lg ring-1 text-xs {{ $cls }}">{{ ucfirst($st) }}</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-lg bg-slate-50 ring-1 ring-slate-100 p-2">
            <p class="text-[11px] text-slate-500">Masuk</p>
            <p class="font-medium tabular-nums">{{ $in }}</p>
          </div>
          <div class="rounded-lg bg-slate-50 ring-1 ring-slate-100 p-2">
            <p class="text-[11px] text-slate-500">Keluar</p>
            <p class="font-medium tabular-nums">{{ $out }}</p>
          </div>
        </div>
        @if($durTelat)
          <p class="mt-2 text-[11px] text-amber-700">Telat {{ $durTelat }}</p>
        @endif
      </div>
    @empty
      <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 text-center text-slate-500">
        Tidak ada data untuk filter ini.
      </div>
    @endforelse
  </div>

  {{-- TABEL DESKTOP --}}
  <div class="hidden md:block bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="max-h-[70vh] overflow-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 sticky top-0 z-10 shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)]">
          <tr class="text-left text-slate-600">
            <th class="px-4 py-3">Guru</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Masuk</th>
            <th class="px-4 py-3">Keluar</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($rows as $r)
            @php
              $st = strtolower($r->status ?? 'belum');
              $cls = match($st) {
                'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'telat' => 'bg-amber-50 text-amber-700 ring-amber-200',
                'izin'  => 'bg-sky-50 text-sky-700 ring-sky-200',
                'sakit' => 'bg-rose-50 text-rose-700 ring-rose-200',
                default => 'bg-slate-50 text-slate-700 ring-slate-200',
              };
              $in  = $r->jam_masuk  ? \Illuminate\Support\Str::of($r->jam_masuk)->substr(0,5)  : '—';
              $out = $r->jam_keluar ? \Illuminate\Support\Str::of($r->jam_keluar)->substr(0,5) : '—';
              $durTelat = ($st==='telat' && ($r->telat_menit ?? null)) ? $fmtTelat($r->telat_menit) : null;
            @endphp
            <tr class="even:bg-slate-50/40 hover:bg-slate-50">
              <td class="px-4 py-3 font-medium text-slate-900">{{ $r->user->name }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-lg ring-1 text-xs {{ $cls }}">{{ ucfirst($st) }}</span>
                  @if($durTelat)
                    <span class="text-[11px] text-amber-700">(+{{ $durTelat }})</span>
                  @endif
                </div>
              </td>
              <td class="px-4 py-3 tabular-nums">{{ $in }}</td>
              <td class="px-4 py-3 tabular-nums">{{ $out }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                Tidak ada data untuk filter ini.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
