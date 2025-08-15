@extends('layouts.piket')
@section('title','Dashboard Piket')

@section('content')
  <h1 class="text-xl font-semibold mb-4">Dashboard Piket</h1>

  {{-- Stat cards --}}
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Total Guru</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums">{{ $totalGuru }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Hadir (hari ini)</p>
      <p class="mt-1 text-3xl font-extrabold text-emerald-600 tabular-nums">{{ $hadir }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Izin (hari ini)</p>
      <p class="mt-1 text-3xl font-extrabold text-amber-600 tabular-nums">{{ $izin }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200">
      <p class="text-xs text-slate-500">Sakit (hari ini)</p>
      <p class="mt-1 text-3xl font-extrabold text-rose-600 tabular-nums">{{ $sakit }}</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Log terbaru --}}
    <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Log Presensi Terbaru (hari ini)</h2>
        <a href="{{ route('piket.riwayat') }}" class="text-sm text-sky-700 hover:underline">Lihat semua</a>
      </div>

      @if($recent->isEmpty())
        <p class="text-sm text-slate-500">Belum ada log hari ini.</p>
      @else
        <ul class="divide-y">
          @foreach($recent as $r)
            <li class="py-3 flex items-center justify-between">
              <div>
                <p class="font-medium">{{ $r->user?->name }}</p>
                <p class="text-xs text-slate-500">
                  Status: <span class="font-medium">{{ ucfirst($r->status) }}</span>
                </p>
              </div>
              <div class="text-right text-xs text-slate-500">
                @if($r->jam_masuk)  <div>Masuk: <span class="font-semibold">{{ $r->jam_masuk }}</span></div> @endif
                @if($r->jam_keluar) <div>Keluar: <span class="font-semibold">{{ $r->jam_keluar }}</span></div> @endif
              </div>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    {{-- Quick Actions --}}
    <aside class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-3">
      <a href="{{ route('piket.cek') }}" class="block px-4 py-3 rounded-xl bg-slate-900 text-white hover:bg-slate-800">Ngecek Guru (Hari Ini)</a>
      <a href="{{ route('piket.rekap') }}" class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">Rekap Harian</a>
      <a href="{{ route('piket.riwayat') }}" class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">Riwayat Presensi</a>
      <a href="{{ route('presensi.index') }}" class="block px-4 py-3 rounded-xl bg-sky-600 text-white hover:bg-sky-700">Presensi (Absen Pribadi)</a>
    </aside>
  </div>
@endsection
