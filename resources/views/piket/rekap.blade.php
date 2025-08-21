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

  {{-- Kartu Ringkasan --}}
  <div class="grid sm:grid-cols-5 gap-3 mb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Total Guru</p>
      <p class="mt-1 text-3xl font-bold tabular-nums">{{ $totalGuru }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Hadir</p>
      <p class="mt-1 text-3xl font-bold text-emerald-600 tabular-nums">{{ $hadir }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Izin</p>
      <p class="mt-1 text-3xl font-bold text-amber-600 tabular-nums">{{ $izin }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Sakit</p>
      <p class="mt-1 text-3xl font-bold text-rose-600 tabular-nums">{{ $sakit }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Belum Absen</p>
      <p class="mt-1 text-3xl font-bold text-slate-700 tabular-nums">{{ $belum }}</p>
    </div>
  </div>

  {{-- Quick Tabs Status (client = querystring "status=") --}}
  <div class="mb-3 flex flex-wrap gap-2">
    @php
      $qs = fn($s)=> request()->fullUrlWithQuery(['status'=>$s?:null]);
      $pill = function($label,$url,$active){
        return $active
          ? "<a href='{$url}' class='px-3 py-2 rounded-xl bg-slate-900 text-white text-sm'>{$label}</a>"
          : "<a href='{$url}' class='px-3 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50'>{$label}</a>";
      };
    @endphp
    {!! $pill('Semua', $qs(null), $filterSt===null) !!}
    {!! $pill('Hadir', $qs('hadir'), $filterSt==='hadir') !!}
    {!! $pill('Izin',  $qs('izin'),  $filterSt==='izin') !!}
    {!! $pill('Sakit', $qs('sakit'), $filterSt==='sakit') !!}
    {!! $pill('Belum', $qs('belum'), $filterSt==='belum') !!}
  </div>

  {{-- Tabel --}}
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50">
        <tr class="text-left text-slate-600">
          <th class="px-4 py-3">Guru</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($rows as $r)
          <tr class="hover:bg-slate-50/50">
            <td class="px-4 py-3 font-medium">{{ $r->user->name }}</td>
            <td class="px-4 py-3">
              @php
                $cls = match($r->status) {
                  'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                  'izin'  => 'bg-amber-50 text-amber-700 ring-amber-200',
                  'sakit' => 'bg-rose-50 text-rose-700 ring-rose-200',
                  default => 'bg-slate-50 text-slate-700 ring-slate-200', // belum
                };
              @endphp
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg ring-1 {{ $cls }}">
                {{ ucfirst($r->status) }}
              </span>
            </td>
            <td class="px-4 py-3 tabular-nums">{{ $r->jam_masuk  ? substr($r->jam_masuk,0,5)  : '—' }}</td>
            <td class="px-4 py-3 tabular-nums">{{ $r->jam_keluar ? substr($r->jam_keluar,0,5) : '—' }}</td>
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
@endsection
