@extends('layouts.tu')
@section('title','Export Data Presensi')

@php
  $badge = fn($st) => [
    'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    'telat' => 'bg-amber-50  text-amber-700  ring-1 ring-amber-200',
    'izin'  => 'bg-sky-50    text-sky-700    ring-1 ring-sky-200',
    'sakit' => 'bg-rose-50   text-rose-700   ring-1 ring-rose-200',
  ][$st] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';

  $fmtTime = function($t){
    if (!$t) return '—';
    try { return \Carbon\Carbon::parse($t)->format('H:i'); } catch (\Exception $e) { return (string) $t; }
  };
@endphp

@section('content')
  {{-- Filter + tombol export --}}
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5 mb-6">
    <form class="grid md:grid-cols-4 gap-4" method="GET" action="">
      <div>
        <label class="text-sm font-medium">Guru</label>
        <select name="guru_id" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">Semua guru</option>
          @foreach($gurus as $g)
            <option value="{{ $g->id }}" @selected(($guruId ?? null)==$g->id)>{{ $g->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="text-sm font-medium">Dari</label>
        <input type="date" name="from" value="{{ $from }}" class="mt-1 w-full rounded-lg border-slate-300">
      </div>

      <div>
        <label class="text-sm font-medium">Sampai</label>
        <input type="date" name="to" value="{{ $to }}" class="mt-1 w-full rounded-lg border-slate-300">
      </div>

      <div class="flex items-end gap-2">
        <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Terapkan</button>

        {{-- Export pakai dataset yang sama --}}
        <a class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
           href="{{ route('tu.export.excel', ['guru_id'=>$guruId,'from'=>$from,'to'=>$to]) }}">
          Export Excel (CSV)
        </a>
        <a class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"
           href="{{ route('tu.export.pdf', ['guru_id'=>$guruId,'from'=>$from,'to'=>$to]) }}"
           target="_blank" rel="noopener">
          Export PDF
        </a>
      </div>
    </form>

    <div class="mt-3 text-xs text-slate-500">
      Menampilkan data periode <b>{{ $from }}</b> s/d <b>{{ $to }}</b>
      @if(!empty($guruId))
        • Guru: <b>{{ optional($gurus->firstWhere('id',$guruId))->name }}</b>
      @else
        • Guru: <b>Semua</b>
      @endif
    </div>
  </div>

  {{-- Preview tabel --}}
  @php $rows = $rows ?? collect(); @endphp
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left">Nama</th>
          <th class="px-4 py-3 text-left">Tanggal / Rentang</th>
          <th class="px-4 py-3 text-center">Masuk</th>
          <th class="px-4 py-3 text-center">Keluar</th>
          <th class="px-4 py-3 text-center">Status</th>
          <th class="px-4 py-3 text-left">Keterangan</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($rows as $r)
          @php
            $stKey   = strtolower($r->status_key ?? '-');
            $stLabel = strtoupper($r->status_label ?? '-');
            $isIzin  = ($r->type ?? '') === 'izin';
            $badgeCl = $badge($stKey);
          @endphp
          <tr class="hover:bg-slate-50/60 align-top">
            <td class="px-4 py-3 font-medium text-slate-800">{{ $r->user_name }}</td>
            <td class="px-4 py-3 text-slate-700">{{ $r->date_label }}</td>

            {{-- Untuk izin/sakit jam masuk/keluar selalu "—" --}}
            <td class="px-4 py-3 text-center tabular-nums">{{ $isIzin ? '—' : $fmtTime($r->jam_masuk) }}</td>
            <td class="px-4 py-3 text-center tabular-nums">{{ $isIzin ? '—' : $fmtTime($r->jam_keluar) }}</td>

            <td class="px-4 py-3 text-center">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium {{ $badgeCl }}">
                {{ $stLabel }}
              </span>
              @if($isIzin && !empty($r->approval))
                <div class="mt-1 text-[11px] text-slate-500">({{ $r->approval }})</div>
              @endif
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ $r->keterangan ?: '—' }}
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($rows,'links'))
    <div class="mt-4">{{ $rows->links() }}</div>
  @endif
@endsection
