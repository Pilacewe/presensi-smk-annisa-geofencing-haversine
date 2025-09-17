@extends('layouts.admin')
@section('title','Laporan / Export')

@section('content')
@php
  // Nama bulan utk dropdown
  $bulanMap = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];

  // Param utk link export
  $exportParams = [
    'mode'    => $mode,
    'tahun'   => $tahun,
    'bulan'   => $bulan,
    'user_id' => $userId ?? null,
  ];

  $selectedUser = collect($users ?? [])->firstWhere('id', $userId ?? null);
@endphp

{{-- ============== FILTER BAR ============== --}}
<div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
  <form method="GET" class="flex flex-wrap items-end gap-3">
    {{-- Mode --}}
    <label class="w-full sm:w-[170px]">
      <span class="block text-xs text-slate-500">Mode</span>
      <select name="mode" class="mt-1 w-full border rounded-lg px-3 py-2">
        <option value="bulan" {{ $mode==='bulan' ? 'selected' : '' }}>Per Bulan</option>
        <option value="tahun" {{ $mode==='tahun' ? 'selected' : '' }}>Per Tahun</option>
      </select>
    </label>

    {{-- Bulan --}}
    <label class="w-full sm:w-[140px] {{ $mode==='tahun' ? 'opacity-50 pointer-events-none' : '' }}">
      <span class="block text-xs text-slate-500">Bulan</span>
      <select name="bulan" class="mt-1 w-full border rounded-lg px-3 py-2">
        @foreach($bulanMap as $i => $nm)
          <option value="{{ $i }}" {{ (int)$bulan === $i ? 'selected' : '' }}>{{ $nm }}</option>
        @endforeach
      </select>
    </label>

    {{-- Tahun --}}
    <label class="w-full sm:w-[140px]">
      <span class="block text-xs text-slate-500">Tahun</span>
      <select name="tahun" class="mt-1 w-full border rounded-lg px-3 py-2">
        @for($y = now()->year; $y >= now()->year - 5; $y--)
          <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
      </select>
    </label>

    {{-- Pegawai --}}
    <label class="flex-1 min-w-[260px]">
      <span class="block text-xs text-slate-500">Pegawai</span>
      <select name="user_id" class="mt-1 w-full border rounded-lg px-3 py-2">
        <option value="">Semua Pegawai</option>
        @foreach(($users ?? []) as $u)
          <option value="{{ $u->id }}" {{ (string)($userId ?? '') === (string)$u->id ? 'selected' : '' }}>
            {{ $u->name }} — {{ $u->jabatan }}
          </option>
        @endforeach
      </select>
    </label>

    {{-- Actions kanan --}}
    <div class="ml-auto flex items-end gap-2">
      <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M20 6L9 17l-5-5"/></svg>
        Terapkan
      </button>

      <a href="{{ route('admin.reports.index') }}"
         class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border hover:bg-slate-50">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 5v4h4"/></svg>
        Reset
      </a>

      {{-- Export dropdown --}}
      <div class="relative">
        <button id="expBtn" type="button"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border hover:bg-slate-50">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16"/></svg>
          Export
          <svg class="w-4 h-4 -mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M6 9l6 6 6-6"/></svg>
        </button>

        <div id="expMenu"
             class="hidden absolute right-0 mt-2 w-52 rounded-xl border bg-white shadow-xl ring-1 ring-slate-200 overflow-hidden z-10">
          <a class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-indigo-50"
             href="{{ route('admin.reports.export', $exportParams + ['format' => 'xlsx']) }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.6"/>
              <path d="M9 8l6 8M15 8l-6 8" stroke-width="1.6"/>
            </svg>
            Excel (.xlsx)
          </a>
          <a class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-indigo-50"
             href="{{ route('admin.reports.export', $exportParams + ['format' => 'csv']) }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.6"/>
              <path d="M7 9h10M7 13h8" stroke-width="1.6"/>
            </svg>
            CSV (.csv)
          </a>
          <div class="h-px bg-slate-200"></div>
          {{-- ⬇️ PDF ke PREVIEW, bukan langsung download --}}
          <a class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50"
             href="{{ route('admin.reports.preview', $exportParams) }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M6 4h9l4 4v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke-width="1.6"/>
              <path d="M15 4v4h4" stroke-width="1.6"/>
              <path d="M8 15h3M8 11h6" stroke-width="1.6"/>
            </svg>
            PDF (.pdf)
          </a>
        </div>
      </div>
    </div>
  </form>
</div>

{{-- ============== INFO PERIODE + CONTENT ============== --}}
<div class="bg-white rounded-2xl border shadow-sm p-4">
  <div class="text-sm text-slate-600 mb-3">
    Periode:
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100">
      <strong>{{ $from->toDateString() }}</strong> s/d <strong>{{ $to->toDateString() }}</strong>
    </span>
    @if($selectedUser)
      • Pegawai: <strong>{{ $selectedUser->name }}</strong> ({{ $selectedUser->jabatan }})
    @else
      • Pegawai: <em>Semua</em>
    @endif
  </div>

  @php
    $coll      = collect($rows);
    $sumH      = $coll->sum('hadir');
    $sumT      = $coll->sum('telat');
    $sumS      = $coll->sum('sakit');
    $sumI      = $coll->sum('izin');
    $sumA      = $coll->sum('alpha');
    $sumTotal  = $coll->sum('total');
    $avgTelAll = round($coll->avg('rata_telat'), 1);
  @endphp

  @if(($sumH + $sumT + $sumS + $sumI + $sumA) === 0)
    <div class="rounded-xl border bg-slate-50 p-8 text-center">
      <div class="mx-auto mb-3 w-10 h-10 rounded-full grid place-items-center bg-slate-200/70 text-slate-600">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="1.6"/><path d="M12 8v5m0 3h.01" stroke-width="1.8"/></svg>
      </div>
      <p class="font-medium text-slate-800">Belum ada data presensi pada periode ini.</p>
      <p class="text-sm text-slate-500">Silakan ubah bulan/tahun atau pilih pegawai lain, lalu tekan <span class="font-medium">Terapkan</span>.</p>
    </div>
  @else
    <div class="overflow-x-auto rounded-xl border">
      <table class="min-w-full text-sm">
        <thead class="bg-gradient-to-r from-slate-50 to-slate-100 text-slate-700">
          <tr>
            <th class="px-4 py-3 text-left">Nama</th>
            <th class="px-4 py-3 text-left">Jabatan</th>
            <th class="px-3 py-3 text-right">Hadir</th>
            <th class="px-3 py-3 text-right">Telat</th>
            <th class="px-3 py-3 text-right">Sakit</th>
            <th class="px-3 py-3 text-right">Izin</th>
            <th class="px-3 py-3 text-right">Alpha</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Rata Telat (mnt)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($rows as $r)
            @php
              $ini = mb_strtoupper(mb_substr($r->name,0,1));
              $lateBadge = ($r->rata_telat ?? 0) >= 10
                ? 'bg-amber-100 text-amber-700'
                : 'bg-emerald-100 text-emerald-700';
            @endphp
            <tr class="odd:bg-white even:bg-slate-50 hover:bg-indigo-50/60 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 grid place-items-center text-xs font-semibold">{{ $ini }}</span>
                  <span class="font-medium text-slate-900">{{ $r->name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ $r->jabatan }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ $r->hadir }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ $r->telat }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ $r->sakit }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ $r->izin }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ $r->alpha }}</td>
              <td class="px-4 py-3 text-right font-mono font-semibold">{{ $r->total }}</td>
              <td class="px-4 py-3 text-right">
                <span class="inline-flex justify-end min-w-[3.5rem] px-2 py-0.5 rounded-md {{ $lateBadge }} font-mono">
                  {{ $r->rata_telat }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-slate-50">
          <tr class="text-slate-700">
            <td class="px-4 py-3 font-semibold">Total</td>
            <td class="px-4 py-3"></td>
            <td class="px-3 py-3 text-right font-mono font-semibold">{{ $sumH }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold">{{ $sumT }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold">{{ $sumS }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold">{{ $sumI }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold">{{ $sumA }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold">{{ $sumTotal }}</td>
            <td class="px-4 py-3 text-right">
              <span class="inline-flex justify-end min-w-[3.5rem] px-2 py-0.5 rounded-md bg-slate-200 text-slate-800 font-mono">
                {{ number_format($avgTelAll,1) }}
              </span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  @endif
</div>

{{-- Export dropdown script --}}
<script>
  const expBtn  = document.getElementById('expBtn');
  const expMenu = document.getElementById('expMenu');
  expBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    expMenu?.classList.toggle('hidden');
  });
  document.addEventListener('click', (e) => {
    if (!expBtn?.contains(e.target) && !expMenu?.contains(e.target)) {
      expMenu?.classList.add('hidden');
    }
  });
</script>
@endsection
