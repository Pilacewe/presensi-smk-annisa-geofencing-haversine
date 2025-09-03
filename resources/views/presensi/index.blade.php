@extends('layouts.presensi')
@section('title','Presensi Pegawai')

@section('content')
@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;

  $nowHuman = Carbon::now(config('app.timezone','Asia/Jakarta'))->translatedFormat('l, d F Y');
  $target07 = config('presensi.jam_target_masuk','07:00');

  // Flag state utk tombol index (sekadar UX, validasi tetap di form & controller)
  $sudahMasuk  = (bool) ($todayRecord?->jam_masuk);
  $sudahKeluar = (bool) ($todayRecord?->jam_keluar);
  $bolehKeluar = $sudahMasuk && ! $sudahKeluar; // sudah masuk & belum keluar
@endphp

{{-- ====== HERO ====== --}}
<div class="mb-6 rounded-2xl overflow-hidden ring-1 ring-slate-200 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50">
  <div class="px-5 py-5 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white grid place-items-center text-lg font-bold">
        {{ Str::of(auth()->user()->name)->substr(0,1)->upper() }}
      </div>
      <div>
        <p class="text-[11px] uppercase tracking-wider text-slate-500">Selamat datang</p>
        <p class="text-sm font-medium leading-tight">{{ auth()->user()->name }}</p>
        <p class="text-xs text-slate-500">{{ strtoupper(auth()->user()->role) }}</p>
      </div>
    </div>
    <div class="text-right">
      <p class="text-[11px] text-slate-500">Hari ini</p>
      <p class="text-sm font-semibold tabular-nums">{{ $nowHuman }} · <span id="nowClock">--:--</span> WIB</p>
    </div>
  </div>
</div>

{{-- ====== ALERTS ====== --}}
@if (session('success'))
  <div class="mb-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-emerald-700">
    {{ session('success') }}
  </div>
@endif
@if (session('message'))
  <div class="mb-4 rounded-xl border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-amber-700">
    {{ session('message') }}
  </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ====== KARTU AKSI ====== --}}
  <section class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold">Presensi Hari Ini</h2>
      <div class="text-right">
        <p class="text-xs text-slate-500">Tanggal</p>
        <p class="font-medium">{{ $nowHuman }}</p>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5">

      {{-- === MASUK === --}}
      <div class="rounded-2xl ring-1 ring-slate-200 p-5">
        <div class="flex items-center justify-between">
          <p class="text-sm text-slate-500">Presensi Masuk</p>
          <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
            Target {{ $target07 }}
          </span>
        </div>

        <div class="mt-2 text-4xl font-extrabold tabular-nums" id="clockIn">--:--:--</div>

        {{-- Status hari ini --}}
        @if ($todayRecord?->jam_masuk)
          @if ($todayRecord->status === 'telat')
            @php
              $total = (int)($todayRecord->telat_menit ?? 0);
              $j = intdiv($total,60);
              $m = $total % 60;
              $labelTelat = ($j>0 ? $j.' jam ' : '').($m>0 ? $m.' menit' : ''); // contoh: "1 jam 5 menit" / "20 menit"
              $labelTelat = trim($labelTelat) ?: '0 menit';
            @endphp
            <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
              Telat {{ $labelTelat }} (jam {{ Str::of($todayRecord->jam_masuk)->substr(0,5) }}).
            </div>
          @else
            <div class="mt-3 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
              Sudah masuk pukul {{ Str::of($todayRecord->jam_masuk)->substr(0,5) }} (tepat waktu).
            </div>
          @endif
        @else
          <div class="mt-3 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
            Belum presensi masuk.
          </div>
        @endif

        <div class="mt-5">
          @if($sudahMasuk)
            <button type="button" disabled
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-200 text-slate-500 cursor-not-allowed">
              {{-- icon login --}}
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
              Masuk (sudah)
            </button>
          @else
            <a href="{{ route('presensi.formMasuk') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
              Masuk
            </a>
          @endif
        </div>
      </div>

      {{-- === KELUAR === --}}
      <div class="rounded-2xl ring-1 ring-slate-200 p-5">
        <div class="flex items-center justify-between">
          <p class="text-sm text-slate-500">Presensi Keluar</p>
          <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
            Mulai {{ config('presensi.jam_keluar_start','16:00') }}
          </span>
        </div>

        <div class="mt-2 text-4xl font-extrabold tabular-nums" id="clockOut">--:--:--</div>

        @if ($sudahKeluar)
          <div class="mt-3 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
            Sudah keluar pukul {{ Str::of($todayRecord->jam_keluar)->substr(0,5) }}.
          </div>
        @elseif(!$sudahMasuk)
          <div class="mt-3 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
            Anda belum presensi masuk.
          </div>
        @else
          <div class="mt-3 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
            Belum presensi keluar.
          </div>
        @endif

        <div class="mt-5">
          @if(!$bolehKeluar)
            <button type="button" disabled
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-200 text-slate-500 cursor-not-allowed">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M14 7l5 5-5 5M19 12H9"/></svg>
              Keluar (tidak tersedia)
            </button>
          @else
            <a href="{{ route('presensi.formKeluar') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M14 7l5 5-5 5M19 12H9"/></svg>
              Keluar
            </a>
          @endif
        </div>
      </div>

    </div>
  </section>

  {{-- ====== SIDEPANEL ====== --}}
  <aside class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
    <div class="flex items-center gap-4 mb-4">
      <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white grid place-items-center font-bold">
        {{ Str::of(auth()->user()->name)->substr(0,1)->upper() }}
      </div>
      <div>
        <p class="font-semibold leading-tight">{{ auth()->user()->name }}</p>
        <p class="text-xs text-slate-500">{{ strtoupper(auth()->user()->role) }}</p>
      </div>
    </div>

    {{-- Statistik kecil --}}
    <div class="grid grid-cols-2 gap-3 text-center">
      <div class="rounded-xl bg-slate-50 ring-1 ring-slate-100 p-3">
        <p class="text-xs text-slate-500">Hadir</p>
        <p class="text-xl font-bold">{{ $stat['hadir'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-amber-50 ring-1 ring-amber-100 p-3">
        <p class="text-xs text-amber-700">Telat</p>
        <p class="text-xl font-bold text-amber-700">{{ $stat['telat'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-rose-50 ring-1 ring-rose-100 p-3">
        <p class="text-xs text-rose-700">Sakit</p>
        <p class="text-xl font-bold text-rose-700">{{ $stat['sakit'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-100 p-3">
        <p class="text-xs text-emerald-700">Izin</p>
        <p class="text-xl font-bold text-emerald-700">{{ $stat['izin'] ?? 0 }}</p>
      </div>
    </div>

    <a href="{{ route('presensi.riwayat') }}"
       class="mt-5 inline-flex items-center gap-2 text-sm text-indigo-700 hover:underline">
      Lihat riwayat →
    </a>

    {{-- Info kartu kecil kondisi hari ini --}}
    <div class="mt-5 p-4 rounded-xl bg-slate-50 ring-1 ring-slate-200 text-xs text-slate-600 leading-relaxed">
      • Target masuk pukul <b>{{ $target07 }}</b>. Jika lewat, status dicatat <b>Telat</b> beserta durasi keterlambatan. <br>
      • Presensi keluar dibuka mulai <b>{{ config('presensi.jam_keluar_start','16:00') }}</b>.
    </div>
  </aside>
</div>

{{-- ====== SCRIPT JAM REALTIME ====== --}}
<script>
  const nowClock = document.getElementById('nowClock');
  const clockIn  = document.getElementById('clockIn');
  const clockOut = document.getElementById('clockOut');
  function tick(){
    const d = new Date();
    const pad = n => String(n).padStart(2,'0');
    const hh = pad(d.getHours()), mm = pad(d.getMinutes()), ss = pad(d.getSeconds());
    if (nowClock) nowClock.textContent = `${hh}:${mm}`;
    if (clockIn)  clockIn.textContent  = `${hh}:${mm}:${ss}`;
    if (clockOut) clockOut.textContent = `${hh}:${mm}:${ss}`;
  }
  setInterval(tick, 1000); tick();
</script>
@endsection
