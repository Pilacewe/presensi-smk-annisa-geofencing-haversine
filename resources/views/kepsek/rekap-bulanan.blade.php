@extends('layouts.kepsek')
@section('title','Rekap Bulanan')

@section('subtitle')
  Periode: <strong>{{ $start->translatedFormat('F Y') }}</strong>
@endsection

@section('actions')
  <button type="button" onclick="window.print()"
          class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 text-sm">
    Cetak
  </button>
@endsection

@section('content')
  @php
    // Koleksi & angka dasar
    $rows   = collect($rows);
    $days   = $start->daysInMonth;
    $sumHad = (int) $rows->sum('hadir');
    $sumTel = (int) $rows->sum('telat');
    $sumSkt = (int) $rows->sum('sakit');
    $sumIzn = (int) $rows->sum('izin');
    $sumAlp = (int) $rows->sum('alpha');
    $sumMasuk = $sumHad + $sumTel;
  @endphp

  {{-- Filter periode --}}
  <form method="GET" class="mb-5 flex flex-wrap items-end gap-2">
    <div>
      <label class="block text-sm text-slate-600">Bulan</label>
      <select name="bulan" class="mt-1 border rounded-lg px-3 py-2">
        @for($m=1;$m<=12;$m++)
          <option value="{{ $m }}" @selected($m==(int)$bulan)>{{ \Carbon\Carbon::create(null,$m,1)->translatedFormat('F') }}</option>
        @endfor
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Tahun</label>
      <input type="number" name="tahun" value="{{ $tahun }}" class="mt-1 border rounded-lg px-3 py-2" min="2000" max="{{ now()->year+1 }}">
    </div>
    <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Terapkan</button>

    <a href="{{ route('kepsek.rekap.bulanan', ['bulan'=>now()->month, 'tahun'=>now()->year]) }}"
       class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 text-sm">
      Bulan ini
    </a>
    <a href="{{ route('kepsek.rekap.bulanan') }}"
       class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 text-sm">
      Reset
    </a>
  </form>

  {{-- Ringkasan cepat --}}
  <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-5">
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Hari dalam Bulan</div>
      <div class="text-2xl font-bold">{{ $days }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Total Pegawai (tercatat)</div>
      <div class="text-2xl font-bold">{{ $rows->count() }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Hadir</div>
      <div class="text-2xl font-bold">{{ $sumHad }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Telat</div>
      <div class="text-2xl font-bold">{{ $sumTel }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Sakit + Izin</div>
      <div class="text-2xl font-bold">{{ $sumSkt + $sumIzn }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Alpha</div>
      <div class="text-2xl font-bold">{{ $sumAlp }}</div>
    </div>
  </div>

  {{-- Tabel rekap --}}
  <div class="bg-white rounded-2xl border shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50">
        <tr class="text-slate-600">
          <th class="px-4 py-3 text-left">#</th>
          <th class="px-4 py-3 text-left">Nama</th>
          <th class="px-4 py-3 text-left">Jabatan</th>
          <th class="px-4 py-3 text-left">Hadir</th>
          <th class="px-4 py-3 text-left">Telat</th>
          <th class="px-4 py-3 text-left">Sakit</th>
          <th class="px-4 py-3 text-left">Izin</th>
          <th class="px-4 py-3 text-left">Alpha</th>
          <th class="px-4 py-3 text-left">Total Masuk</th>
          <th class="px-4 py-3 text-left">Kehadiran (%)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $r)
          @php
            $totalMasuk = (int)$r->hadir + (int)$r->telat;
            $pct = $days ? round($totalMasuk / $days * 100) : 0;
          @endphp
          <tr class="border-t">
            <td class="px-4 py-3 text-slate-500">{{ $i+1 }}</td>
            <td class="px-4 py-3 font-medium">{{ $r->name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ $r->jabatan }}</td>

            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ (int)$r->hadir }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                {{ (int)$r->telat }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                {{ (int)$r->sakit }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200">
                {{ (int)$r->izin }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                {{ (int)$r->alpha }}
              </span>
            </td>

            <td class="px-4 py-3 font-semibold">{{ $totalMasuk }}</td>
            <td class="px-4 py-3">{{ $pct }}%</td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="px-4 py-10 text-center text-slate-500">
              Tidak ada data untuk periode ini.
            </td>
          </tr>
        @endforelse
      </tbody>

      @if($rows->isNotEmpty())
        <tfoot class="bg-slate-50/60 border-t">
          <tr class="text-slate-700 font-semibold">
            <td class="px-4 py-3" colspan="3">Total</td>
            <td class="px-4 py-3">{{ $sumHad }}</td>
            <td class="px-4 py-3">{{ $sumTel }}</td>
            <td class="px-4 py-3">{{ $sumSkt }}</td>
            <td class="px-4 py-3">{{ $sumIzn }}</td>
            <td class="px-4 py-3">{{ $sumAlp }}</td>
            <td class="px-4 py-3">{{ $sumMasuk }}</td>
            <td class="px-4 py-3">
              @php
                $maxMasuk = $rows->count()*$days;
                $overallPct = $maxMasuk ? round($sumMasuk / $maxMasuk * 100) : 0;
              @endphp
              {{ $overallPct }}%
            </td>
          </tr>
        </tfoot>
      @endif
    </table>
  </div>
@endsection
