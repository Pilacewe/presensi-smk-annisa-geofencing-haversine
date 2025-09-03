@extends('layouts.presensi')
@section('title','Riwayat Presensi')

@section('content')
@php
  use Carbon\Carbon;
  use Illuminate\Support\Str;

  $tz = config('app.timezone','Asia/Jakarta');

  // Formatter tanggal
  $fmtTgl = fn($d) => Carbon::parse($d, $tz)->translatedFormat('l, d F Y');
  $fmtTglShort = fn($d) => Carbon::parse($d, $tz)->translatedFormat('d M Y');

  // Formatter jam: ambil bagian setelah spasi (kalau string mengandung tanggal), lalu potong HH:MM
  $fmtJam = function ($v) {
    if (!$v) return '—';
    $t = trim(Str::of($v)->afterLast(' '));   // "2025-09-03 07:15:00" -> "07:15:00"
    return Str::of($t)->substr(0,5);          // "07:15"
  };

  // Formatter telat
  $fmtTelat = function ($m) {
    if (!$m) return null;
    $h = intdiv($m, 60); $mm = $m % 60;
    return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
  };

  // Badge status
  $badge = function ($st) {
    $st = strtolower($st ?? '-');
    return match($st) {
      'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
      'telat' => 'bg-amber-50  text-amber-700  ring-amber-200',
      'izin'  => 'bg-sky-50    text-sky-700    ring-sky-200',
      'sakit' => 'bg-rose-50   text-rose-700   ring-rose-200',
      default => 'bg-slate-50  text-slate-700  ring-slate-200',
    };
  };
@endphp

<div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
  {{-- Header + Filter --}}
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <h2 class="text-lg font-semibold">Riwayat Presensi</h2>
      <p class="text-xs text-slate-500">Lihat kehadiran & izin Anda pada periode terpilih.</p>
    </div>

    <form method="GET" class="grid grid-cols-2 md:flex md:items-center gap-3">
      <select name="tahun" class="rounded-lg border-slate-300">
        @foreach ($listTahun as $th)
          <option value="{{ $th }}" @selected($th==$tahun)>{{ $th }}</option>
        @endforeach
      </select>
      <select name="bulan" class="rounded-lg border-slate-300">
        @foreach ($listBulan as $i => $nama)
          <option value="{{ $i }}" @selected($i==$bulan)>{{ $nama }}</option>
        @endforeach
      </select>
      <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Terapkan</button>
      <a href="{{ route('presensi.riwayat') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">Reset</a>
    </form>
  </div>

  {{-- Legend --}}
  <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
    <span class="px-2 py-0.5 rounded ring-1 {{ $badge('hadir') }}">Hadir</span>
    <span class="px-2 py-0.5 rounded ring-1 {{ $badge('telat') }}">Telat</span>
    <span class="px-2 py-0.5 rounded ring-1 {{ $badge('izin') }}">Izin</span>
    <span class="px-2 py-0.5 rounded ring-1 {{ $badge('sakit') }}">Sakit</span>
  </div>

  {{-- Timeline cards --}}
  <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($timeline as $row)
      @if(($row->type ?? '') === 'izin')
        {{-- KARTU IZIN (rentang) --}}
        @php
          $isSakit = ($row->status ?? '') === 'sakit';
          $cap     = ucfirst($row->status ?? 'izin'); // izin|sakit
          $acc     = match($row->approval ?? 'pending') {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => 'Pending'
          };
        @endphp
        <div class="rounded-xl border border-slate-200 p-4 hover:shadow-sm transition">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-slate-500 text-xs">Rentang</p>
              <p class="font-medium">
                {{ $fmtTglShort($row->date_start) }}
                @if($row->date_end !== $row->date_start)
                  – {{ $fmtTglShort($row->date_end) }}
                @endif
              </p>
            </div>
            <span class="px-2 py-1 rounded-md text-[11px] ring-1 {{ $isSakit ? $badge('sakit') : $badge('izin') }}">
              {{ strtoupper($cap) }}
            </span>
          </div>

          @if($row->keterangan)
            <p class="mt-3 text-xs text-slate-600 leading-relaxed">{{ $row->keterangan }}</p>
          @endif

          <div class="mt-3 flex items-center justify-between text-xs">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg ring-1
              {{ $row->approval==='approved' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' :
                 ($row->approval==='rejected' ? 'bg-rose-50 text-rose-700 ring-rose-200' :
                 'bg-slate-50 text-slate-700 ring-slate-200') }}">
              Status: {{ $acc }}
            </span>
            <span class="text-slate-500">{{ $fmtTgl($row->date_start) }}</span>
          </div>
        </div>
      @else
        {{-- KARTU PRESENSI HARIAN --}}
        @php
          $st = strtolower($row->status ?? '-');
          $in  = $fmtJam($row->jam_masuk ?? null);
          $out = $fmtJam($row->jam_keluar ?? null);
        @endphp
        <div class="rounded-xl border border-slate-200 p-4 hover:shadow-sm transition">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-slate-500 text-xs">Tanggal</p>
              <p class="font-medium">{{ $fmtTgl($row->date_start ?? $row->tanggal) }}</p>
            </div>
            <span class="px-2 py-1 rounded-md text-[11px] ring-1 {{ $badge($st) }}">
              {{ strtoupper($st) }}
            </span>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-3">
            <div class="p-3 rounded-lg bg-slate-50 ring-1 ring-slate-100">
              <p class="text-slate-500 text-[11px]">Masuk</p>
              <p class="font-semibold tabular-nums">{{ $in }}</p>
              @if($st==='telat' && ($row->telat_menit ?? null))
                <p class="mt-1 text-[11px] text-amber-700">Telat {{ $fmtTelat($row->telat_menit) }}</p>
              @endif
            </div>
            <div class="p-3 rounded-lg bg-slate-50 ring-1 ring-slate-100">
              <p class="text-slate-500 text-[11px]">Keluar</p>
              <p class="font-semibold tabular-nums">{{ $out }}</p>
            </div>
          </div>
        </div>
      @endif
    @empty
      <div class="col-span-full text-center text-slate-500 py-10">
        Belum ada data pada periode ini.
      </div>
    @endforelse
  </div>

  <p class="mt-6 text-[11px] text-slate-500">
    Catatan: Izin/Sakit ditampilkan sebagai satu kartu per rentang tanggal, bukan per hari.
  </p>
</div>
@endsection
