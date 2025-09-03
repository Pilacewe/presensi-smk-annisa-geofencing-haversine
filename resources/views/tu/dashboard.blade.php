@extends('layouts.tu')
@section('title','Dashboard TU')

@section('content')
@php
  $fmtTelat = function ($m) {
      if (!$m) return null;
      $h = intdiv($m, 60); $mm = $m % 60;
      return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
  };

  // fallback aman jika ada variabel belum dipassing
  $totalGuru = $totalGuru ?? 0;   // = total pegawai lintas role (guru/tu/piket/kepsek)
  $hadir     = $hadir     ?? 0;
  $telat     = $telat     ?? 0;
  $izin      = $izin      ?? 0;
  $sakit     = $sakit     ?? 0;
  $belum     = $belum     ?? 0;

  $todayRows = $todayRows ?? collect();
  $belumList = $todayRows->where('status','belum')->take(6);
  $hadirList = $todayRows->where('status','hadir')->take(6);
  $telatList = $todayRows->where('status','telat')->take(6);

  $recent    = $recent ?? collect();
@endphp

{{-- ===== HERO ===== --}}
<div class="mb-6 rounded-[28px] overflow-hidden border border-slate-200 bg-gradient-to-r from-slate-50 via-indigo-50 to-sky-50">
  <div class="px-6 py-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <div class="relative">
        <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white grid place-items-center text-lg font-bold shadow">TU</div>
        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white"></div>
      </div>
      <div class="leading-tight">
        <p class="text-[11px] uppercase tracking-wider text-slate-500">Panel Tata Usaha</p>
        <p class="text-sm font-medium text-slate-800">Ringkasan presensi & tindakan cepat</p>
      </div>
    </div>
    <div class="text-right">
      <p class="text-[11px] text-slate-500">Waktu sekarang</p>
      <p id="nowClock" class="text-sm font-semibold tabular-nums">
        {{ \Carbon\Carbon::now(config('app.timezone'))->translatedFormat('l, d F Y · H:i') }} WIB
      </p>
    </div>
  </div>
</div>

{{-- ===== STAT CARDS ===== --}}
@php
  // Ikon inline
  $ic = [
    'users' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M16 14a4 4 0 1 1 6 3v3h-6v-3a4 4 0 0 1 0-3ZM9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0v1H2v-1Z"/></svg>',
    'check' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="m5 12 4 4L19 6"/></svg>',
    'late'  => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 8v5l3 2M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/></svg>',
    'note'  => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 3h6a2 2 0 0 1 2 2v14l-5-3-5 3V5a2 2 0 0 1 2-2Z"/></svg>',
    'heart' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-.91-.91a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.69-8.69a5.5 5.5 0 0 0 0-7.78Z"/></svg>',
    'warn'  => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path stroke-width="1.8" d="M12 9v4M12 17h.01"/></svg>',
  ];

  $cards = [
    ['label'=>'Total Pegawai',     'value'=>$totalGuru, 'ring'=>'ring-indigo-200', 'text'=>'text-slate-900',   'bg'=>'bg-indigo-50', 'icon'=>$ic['users']],
    ['label'=>'Hadir (hari ini)',  'value'=>$hadir,     'ring'=>'ring-emerald-200','text'=>'text-emerald-700','bg'=>'bg-emerald-50','icon'=>$ic['check']],
    ['label'=>'Telat (hari ini)',  'value'=>$telat,     'ring'=>'ring-amber-200',  'text'=>'text-amber-700', 'bg'=>'bg-amber-50',  'icon'=>$ic['late']],
    ['label'=>'Izin (hari ini)',   'value'=>$izin,      'ring'=>'ring-sky-200',    'text'=>'text-sky-700',   'bg'=>'bg-sky-50',    'icon'=>$ic['note']],
    ['label'=>'Sakit (hari ini)',  'value'=>$sakit,     'ring'=>'ring-rose-200',   'text'=>'text-rose-700',  'bg'=>'bg-rose-50',   'icon'=>$ic['heart']],
    ['label'=>'Belum Absen',       'value'=>$belum,     'ring'=>'ring-slate-200',  'text'=>'text-slate-900', 'bg'=>'bg-slate-50',  'icon'=>$ic['warn']],
  ];
@endphp
<div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
  @foreach($cards as $c)
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 {{ $c['ring'] }}">
      <div class="flex items-center justify-between">
        <p class="text-xs text-slate-500">{{ $c['label'] }}</p>
        <span class="w-8 h-8 grid place-items-center rounded-xl {{ $c['bg'] }} text-slate-700">{!! $c['icon'] !!}</span>
      </div>
      <p class="mt-2 text-[28px] leading-none font-extrabold tabular-nums {{ $c['text'] }}">{{ $c['value'] }}</p>
    </div>
  @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">
  {{-- ===== LOG TERBARU ===== --}}
  <section class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="px-5 py-4 flex items-center justify-between">
      <h3 class="font-semibold">Log Presensi Terbaru</h3>
      <a href="{{ route('tu.presensi.index') }}" class="text-sm text-indigo-700 hover:underline">Lihat semua</a>
    </div>

    @if($recent->isEmpty())
      <div class="px-5 pb-6"><p class="text-sm text-slate-500 italic">Belum ada log hari ini.</p></div>
    @else
      <ul class="divide-y">
        @foreach($recent as $r)
          @php
            $tgl = \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y');
            $in  = $r->jam_masuk  ? \Illuminate\Support\Carbon::parse($r->jam_masuk)->format('H:i')  : '—';
            $out = $r->jam_keluar ? \Illuminate\Support\Carbon::parse($r->jam_keluar)->format('H:i') : '—';

            $badge = match($r->status){
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
              'telat' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
              'izin'  => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
              'sakit' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
              default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
            };

            $telatDur = ($r->status === 'telat') ? ($fmtTelat($r->telat_menit ?? null)) : null;
            $roleChip = strtoupper($r->user?->role ?? '-');
          @endphp

          <li class="px-5 py-3 flex items-center justify-between gap-4">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p class="font-medium truncate">{{ $r->user?->name ?? '—' }}</p>
                <span class="text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 ring-1 ring-slate-200">{{ $roleChip }}</span>
              </div>
              <div class="mt-1 text-xs text-slate-600 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  {{ $tgl }}
                </span>
                <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full text-[11px] font-medium">
                  Masuk: {{ $in }}
                </span>
                <span class="bg-rose-50 text-rose-700 px-2 py-0.5 rounded-full text-[11px] font-medium">
                  Keluar: {{ $out }}
                </span>
                @if($telatDur)
                  <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full text-[11px] font-medium">
                    Telat {{ $telatDur }}
                  </span>
                @endif
              </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">
              {{ ucfirst($r->status ?? '-') }}
            </span>
          </li>
        @endforeach
      </ul>

      <div class="px-5 py-4 text-right">
        <a href="{{ route('tu.presensi.index') }}" class="text-sm text-indigo-700 hover:underline">Lihat Lainnya →</a>
      </div>
    @endif
  </section>

  {{-- ===== PANEL KANAN ===== --}}
  <aside class="space-y-6">
    {{-- Belum Absen --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-4 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900">Belum Absen ({{ $belumList->count() }})</h3>
        <a href="{{ route('tu.presensi.index',['tanggal'=>now()->toDateString()]) }}"
           class="text-sm text-indigo-700 hover:underline">Lihat Presensi</a>
      </div>
      <ul class="px-5 pb-4 space-y-2">
        @forelse($belumList as $row)
          <li class="text-sm text-slate-700 flex items-center justify-between">
            <span class="truncate">{{ $row->user->name }}</span>
            <span class="text-[11px] px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 ring-1 ring-slate-200">Belum</span>
          </li>
        @empty
          <li class="text-sm text-slate-500">Semua pegawai sudah melakukan presensi.</li>
        @endforelse
      </ul>
    </div>

    {{-- Sudah Hadir --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-4">
        <h3 class="font-semibold text-slate-900">Sudah Hadir</h3>
      </div>
      <ul class="px-5 pb-4 space-y-2">
        @forelse($hadirList as $row)
          <li class="text-sm text-slate-700 flex items-center justify-between">
            <span class="truncate">{{ $row->user->name }}</span>
            <span class="text-[11px] px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
              Masuk {{ $row->jam_masuk ? \Illuminate\Support\Str::of($row->jam_masuk)->substr(0,5) : '—' }}
            </span>
          </li>
        @empty
          <li class="text-sm text-slate-500">Belum ada yang hadir.</li>
        @endforelse
      </ul>
    </div>

    {{-- Telat --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-4">
        <h3 class="font-semibold text-slate-900">Telat ({{ $telatList->count() }})</h3>
      </div>
      <ul class="px-5 pb-4 space-y-2">
        @forelse($telatList as $row)
          @php $dur = $fmtTelat($row->telat_menit ?? null); @endphp
          <li class="text-sm text-slate-700 flex items-center justify-between">
            <span class="truncate">{{ $row->user->name }}</span>
            <span class="text-[11px] px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-200">
              Telat {{ $dur ?: '-' }}
            </span>
          </li>
        @empty
          <li class="text-sm text-slate-500">Tidak ada yang telat.</li>
        @endforelse
      </ul>
    </div>

    {{-- Tindakan Cepat --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
      <h3 class="font-semibold mb-3 text-slate-900">Tindakan Cepat</h3>
      <div class="space-y-3">
        <a class="block p-4 rounded-xl bg-slate-900 text-white hover:bg-slate-800"
           href="{{ route('tu.presensi.index') }}">
          Lihat Presensi Guru
        </a>
        <a class="block p-4 rounded-xl bg-slate-100 hover:bg-slate-200"
           href="{{ route('tu.riwayat') }}">
          Riwayat Presensi
        </a>
        <a class="block p-4 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700"
           href="{{ route('tu.export.index') }}">
          Export PDF/Excel
        </a>
      </div>
      <p class="mt-5 p-4 rounded-xl bg-slate-50 ring-1 ring-slate-200 text-xs text-slate-600 leading-relaxed">
        Gunakan <b>Presensi Manual</b> saat perangkat guru bermasalah/jaringan padam.
      </p>
    </div>
  </aside>
</div>

{{-- Jam ringan --}}
<script>
  const clockEl = document.getElementById('nowClock');
  if (clockEl) {
    setInterval(() => {
      const d = new Date();
      const pad = n => String(n).padStart(2,'0');
      const hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][d.getDay()];
      const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][d.getMonth()];
      clockEl.textContent = `${hari}, ${pad(d.getDate())} ${bulan} ${d.getFullYear()} · ${pad(d.getHours())}:${pad(d.getMinutes())} WIB`;
    }, 30000);
  }
</script>
@endsection
