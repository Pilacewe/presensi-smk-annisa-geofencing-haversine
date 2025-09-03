@extends('layouts.tu')

@section('title','Riwayat Presensi')

@section('content')
@section('actions')
@php
  $badgeClass = function ($st) {
    return [
      'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
      'telat' => 'bg-amber-50  text-amber-700  ring-1 ring-amber-200',
      'izin'  => 'bg-sky-50    text-sky-700    ring-1 ring-sky-200',
      'sakit' => 'bg-rose-50   text-rose-700   ring-1 ring-rose-200',
    ][$st] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
  };
@endphp

<form method="GET" class="flex flex-wrap items-center gap-2">
  <select name="guru_id" class="rounded-lg border-slate-300">
    <option value="">Semua guru</option>
    @foreach($gurus as $g)
      <option value="{{ $g->id }}" @selected($guruId==$g->id)>{{ $g->name }}</option>
    @endforeach
  </select>

  <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-slate-300">
  <input type="date" name="to"   value="{{ $to   }}" class="rounded-lg border-slate-300">

  <select name="status" class="rounded-lg border-slate-300">
    <option value="">Semua status</option>
    @foreach (['hadir'=>'Hadir','telat'=>'Telat','izin'=>'Izin','sakit'=>'Sakit'] as $k=>$v)
      <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
    @endforeach
  </select>

  <button class="px-3 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">
    Terapkan
  </button>
</form>
@endsection

@section('content')
@php
  $fmtDate = fn($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('l, d F Y');
  $fmtRange= fn($s,$e) => \Illuminate\Support\Carbon::parse($s)->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($e)->format('d M Y');
  $fmtTime = fn($t) => $t ? \Illuminate\Support\Str::of($t)->substr(0,5) : '—';
  $fmtTelat = function ($m) {
    if (!$m) return null;
    $h = intdiv($m,60); $mm = $m % 60;
    return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
  };
@endphp

<div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
  <span class="px-2 py-1 rounded bg-slate-100 ring-1 ring-slate-200">
    Rentang: <b>{{ $from }}</b> – <b>{{ $to }}</b>
  </span>
  @if($guruId)
    @php $gSel = $gurus->firstWhere('id',$guruId); @endphp
    <span class="px-2 py-1 rounded bg-slate-100 ring-1 ring-slate-200">
      Guru: <b>{{ $gSel?->name }}</b>
    </span>
  @endif
  @if($status)
    <span class="px-2 py-1 rounded {{ $badgeClass($status) }}">
      Status: <b>{{ ucfirst($status) }}</b>
    </span>
  @endif
</div>

<div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 sticky top-0">
      <tr>
        <th class="px-4 py-3 text-left">Nama</th>
        <th class="px-4 py-3 text-left">Tanggal / Periode</th>
        <th class="px-4 py-3 text-center">Masuk</th>
        <th class="px-4 py-3 text-center">Keluar</th>
        <th class="px-4 py-3 text-center">Status</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($data as $row)
        @php
          $type = $row->type; // 'presensi' atau 'izin'
          $st   = strtolower($row->status ?? '-');
          $badge= $badgeClass($st);
        @endphp

        {{-- ===== Baris PRESENSI harian ===== --}}
        @if($type === 'presensi')
          @php
            $in  = $fmtTime($row->jam_masuk);
            $out = $fmtTime($row->jam_keluar);
            $tel = $st==='telat' ? $fmtTelat($row->telat_menit ?? null) : null;
          @endphp
          <tr class="hover:bg-slate-50/60">
            <td class="px-4 py-3 font-medium text-slate-800">{{ $row->user?->name ?? '—' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ $fmtDate($row->date_start) }}</td>
            <td class="px-4 py-3 text-center">
              <div class="font-medium tabular-nums">{{ $in }}</div>
              @if($tel)
                <div class="text-[11px] text-amber-700 mt-0.5">Telat {{ $tel }}</div>
              @endif
            </td>
            <td class="px-4 py-3 text-center font-medium tabular-nums">{{ $out }}</td>
            <td class="px-4 py-3 text-center"><span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-medium {{ $badge }}">{{ strtoupper($st) }}</span></td>
          </tr>
        @else
        {{-- ===== Baris IZIN/SAKIT rentang ===== --}}
          <tr class="hover:bg-sky-50/40">
            <td class="px-4 py-3 font-medium text-slate-800">{{ $row->user?->name ?? '—' }}</td>
            <td class="px-4 py-3 text-slate-700">
              {{ $fmtRange($row->date_start,$row->date_end) }}
              <div class="text-[11px] text-slate-500">
                @if($row->approval === 'approved')
                  Disetujui
                @elseif($row->approval === 'rejected')
                  Ditolak
                @else
                  Pending
                @endif
                @if($row->keterangan) · {{ $row->keterangan }} @endif
              </div>
            </td>
            <td class="px-4 py-3 text-center text-slate-400">—</td>
            <td class="px-4 py-3 text-center text-slate-400">—</td>
            <td class="px-4 py-3 text-center"><span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-medium {{ $badge }}">{{ strtoupper($st) }}</span></td>
          </tr>
        @endif
      @empty
        <tr>
          <td colspan="5" class="px-4 py-10 text-center text-slate-500">
            Tidak ada data pada rentang/filter ini.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">
  {{ $data->links() }}
</div>
@endsection