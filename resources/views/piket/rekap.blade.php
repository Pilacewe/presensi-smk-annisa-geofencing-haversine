@extends('layouts.piket')
@section('title','Rekap Harian')

@section('content')
  <h1 class="text-xl font-semibold mb-1">Rekap Harian</h1>

  <form class="mb-4 flex items-center gap-2">
    <input type="date" name="tanggal" value="{{ $tanggal }}" class="rounded-lg border-slate-300">
    <button class="px-3 py-2 rounded-lg bg-slate-900 text-white">Terapkan</button>
  </form>

  <div class="grid sm:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Total Guru</p>
      <p class="text-2xl font-bold tabular-nums">{{ $totalGuru }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Hadir</p>
      <p class="text-2xl font-bold text-emerald-600 tabular-nums">{{ $hadir }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Izin</p>
      <p class="text-2xl font-bold text-amber-600 tabular-nums">{{ $izin }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Sakit</p>
      <p class="text-2xl font-bold text-rose-600 tabular-nums">{{ $sakit }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Belum Absen</p>
      <p class="text-2xl font-bold tabular-nums">{{ $belum }}</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500 border-b">
          <th class="px-4 py-3">Guru</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ $r->user?->name }}</td>
            <td class="px-4 py-3">{{ ucfirst($r->status) }}</td>
            <td class="px-4 py-3">{{ $r->jam_masuk ?? '—' }}</td>
            <td class="px-4 py-3">{{ $r->jam_keluar ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada presensi di tanggal ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
