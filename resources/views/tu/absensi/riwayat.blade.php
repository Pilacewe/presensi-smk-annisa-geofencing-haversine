@extends('layouts.tu')

@section('title','Presensi TU')
@section('subtitle','Absen pribadi TU + Riwayat & Izin dalam satu halaman')

@section('content')
{{-- Alert aturan jam --}}
@if (session('success'))
  <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-3">
    {{ session('success') }}
  </div>
@endif
@if (session('message'))
  <div class="mb-4 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 px-4 py-3">
    {{ session('message') }}
  </div>
@endif

@php
  // target jam telat (mengikuti pegawai)
  $targetMasuk = config('presensi.jam_target_masuk','07:00');

  // helper kecil untuk format menit -> "X jam Y menit" / "Y menit"
  $fmtTelat = function ($m) {
      if (!$m) return null;
      $h = intdiv((int)$m, 60);
      $mm = ((int)$m) % 60;
      return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
  };

  // ambil HH:MM dari field waktu
  $hhmm = fn($v) => $v ? \Illuminate\Support\Str::of($v)->substr(0,5) : '—';
@endphp

<div class="mb-4 rounded-lg bg-emerald-50/60 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
  Presensi masuk hanya <b>{{ $mulaiMasuk }}–{{ $akhirMasuk }}</b>. Presensi keluar <b>mulai {{ $mulaiKeluar }}</b>.
  <span class="inline-block ml-2 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px]">
    Target tepat waktu {{ $targetMasuk }}
  </span>
</div>

{{-- Tabs --}}
<div class="flex flex-wrap gap-2 mb-6">
  @php
    $pill = function($label,$href,$active){
      return $active
        ? "<a href=\"$href\" class=\"px-4 py-2 rounded-xl bg-slate-900 text-white text-sm\">$label</a>"
        : "<a href=\"$href\" class=\"px-4 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50\">$label</a>";
    };
  @endphp
  {!! $pill('Presensi', route('tu.absensi.index',['tab'=>'absen']), $tab==='absen') !!}
  {!! $pill('Riwayat Saya', route('tu.absensi.index',['tab'=>'riwayat']), $tab==='riwayat') !!}
  {!! $pill('Izin Saya', route('tu.absensi.index',['tab'=>'izin']), $tab==='izin') !!}
</div>
@php
  // target jam telat (mengikuti pegawai)
  $targetMasuk = config('presensi.jam_target_masuk','07:00');

  // helper kecil untuk format menit -> "X jam Y menit" / "Y menit"
  $fmtTelat = function ($m) {
      if (!$m) return null;
      $h = intdiv((int)$m, 60);
      $mm = ((int)$m) % 60;
      return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
  };

  // ambil HH:MM dari field waktu
  $hhmm = fn($v) => $v ? \Illuminate\Support\Str::of($v)->substr(0,5) : '—';
@endphp
@php
  $years    = $years    ?? range(now()->year, now()->year - 4);
  $bulanMap = $bulanMap ?? [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
  $tahun    = $tahun    ?? now()->year;
  $bulan    = $bulan    ?? now()->month;
  $status   = $status   ?? null;

  $fmtTime = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('H:i') : '—';
  $fmtTelat = function ($m) { if(!$m) return null; $h=intdiv($m,60); $mm=$m%60; return $h?($mm?"$h jam $mm menit":"$h jam"):"$mm menit"; };
  $badge = fn($st) => [
    'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    'telat' => 'bg-amber-50  text-amber-700  ring-1 ring-amber-200',
    'izin'  => 'bg-sky-50    text-sky-700    ring-1 ring-sky-200',
    'sakit' => 'bg-rose-50   text-rose-700   ring-1 ring-rose-200',
  ][$st] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
@endphp

@section('actions')
  <form method="GET" action="{{ route('tu.absensi.index') }}" class="flex flex-wrap items-center gap-2">
    <input type="hidden" name="tab" value="riwayat">
    <select name="tahun" class="rounded-lg border-slate-300 text-sm">
      @foreach ($years as $y) <option value="{{ $y }}" @selected($tahun==$y)>{{ $y }}</option> @endforeach
    </select>
    <select name="bulan" class="rounded-lg border-slate-300 text-sm">
      @foreach ($bulanMap as $i=>$label) <option value="{{ $i }}" @selected($bulan==$i)>{{ $label }}</option> @endforeach
    </select>
    <select name="status" class="rounded-lg border-slate-300 text-sm">
      <option value=""      @selected($status===null)>Semua status</option>
      <option value="hadir" @selected($status==='hadir')>Hadir</option>
      <option value="telat" @selected($status==='telat')>Telat</option>
      <option value="izin"  @selected($status==='izin')>Izin</option>
      <option value="sakit" @selected($status==='sakit')>Sakit</option>
    </select>
    <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm">Terapkan</button>
    <a href="{{ route('tu.absensi.index',['tab'=>'riwayat']) }}" class="px-3 py-2 rounded-lg bg-slate-100 text-sm">Reset</a>
  </form>
@endsection

@section('content')
  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
    {{-- Legend --}}
    <div class="mb-3 flex flex-wrap items-center gap-2 text-[11px]">
      <span class="px-2 py-0.5 rounded ring-1 {{ $badge('hadir') }}">Hadir</span>
      <span class="px-2 py-0.5 rounded ring-1 {{ $badge('telat') }}">Telat</span>
      <span class="px-2 py-0.5 rounded ring-1 {{ $badge('izin') }}">Izin</span>
      <span class="px-2 py-0.5 rounded ring-1 {{ $badge('sakit') }}">Sakit</span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-slate-600 bg-slate-50">
          <tr class="border-b">
            <th class="py-2 px-4">Tanggal / Rentang</th>
            <th class="py-2 px-4 text-center">Masuk</th>
            <th class="py-2 px-4 text-center">Keluar</th>
            <th class="py-2 px-4 text-center">Status</th>
            <th class="py-2 px-4">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse ($data as $row)
            @php
              $isIzin = ($row->type ?? 'presensi') === 'izin';
              $stKey  = strtolower($row->status_key ?? '-');     // 'hadir' / 'telat' / 'izin' / 'sakit'
              $stLbl  = $row->status_label ?? strtoupper($stKey);
              $tglLbl = $row->date_label
                        ?? ($row->date_start === $row->date_end
                            ? \Carbon\Carbon::parse($row->date_start)->translatedFormat('l, d F Y')
                            : \Carbon\Carbon::parse($row->date_start)->translatedFormat('d M Y').' – '.\Carbon\Carbon::parse($row->date_end)->translatedFormat('d M Y'));
              $in  = $isIzin ? '—' : $fmtTime($row->jam_masuk ?? null);
              $out = $isIzin ? '—' : $fmtTime($row->jam_keluar ?? null);
              $tel = (!$isIzin && $stKey==='telat' && isset($row->telat_menit)) ? ('Telat '.$fmtTelat($row->telat_menit)) : null;
              $ket = $isIzin
                    ? trim(($row->keterangan ?? '').' '.(($row->approval ?? null) ? "({$row->approval})" : ''))
                    : ($tel ?: '—');
            @endphp
            <tr class="hover:bg-slate-50/70">
              <td class="py-2 px-4 whitespace-nowrap">{{ $tglLbl }}</td>
              <td class="py-2 px-4 text-center tabular-nums">{{ $in }}</td>
              <td class="py-2 px-4 text-center tabular-nums">{{ $out }}</td>
              <td class="py-2 px-4 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs ring-1 {{ $badge($stKey) }}">
                  {{ $stLbl }}
                </span>
              </td>
              <td class="py-2 px-4 text-xs">{{ $ket ?: '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="py-6 text-center text-slate-500">Belum ada data pada periode ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginasi --}}
    @if (method_exists($data,'links'))
      <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif
  </section>
@endsection
