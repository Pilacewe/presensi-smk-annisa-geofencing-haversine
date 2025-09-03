@extends('layouts.admin')

@section('title','Dashboard Admin')
@section('subtitle','Kontrol penuh presensi, izin, dan operasional')

@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ route('admin.presensi.index') }}"
       class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition">
      Kelola Presensi
    </a>
    <a href="{{ route('admin.izin.index') }}"
       class="px-4 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-sm font-medium hover:bg-slate-50 transition">
      Kelola Izin
    </a>
  </div>
@endsection

@section('content')
@php
  $today   = $today ?? now(config('app.timezone'));
  $rc      = $roleCounts ?? ['guru'=>0,'tu'=>0,'piket'=>0,'kepsek'=>0];
  $att     = $att ?? ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'belum'=>0];
  $fmtTelat= fn($m)=>$m?(intdiv($m,60)?intdiv($m,60).' jam '.($m%60).' mnt':($m%60).' mnt'):'—';

  // Ringkasan operasional (fallback aman)
  $opDefaults = [
    'mStart'      => (string) config('presensi.jam_masuk_start',  '05:00'),
    'targetMasuk' => (string) config('presensi.jam_target_masuk', '07:00'),
    'kStart'      => (string) config('presensi.jam_keluar_start', '16:00'),
    'radius'      => (float)  config('presensi.radius', 150),
  ];
  $op = array_merge($opDefaults, (array) ($op ?? []));

  // Dataset untuk panel tambahan (fallback)
  $topTelat         = $topTelat         ?? collect(); // -> each: user_id, user->name, jml, menit
  $latestActivities = $latestActivities ?? collect(); // -> each: user->name, status, tanggal, jam_masuk/jam_keluar
  $tanpaPulangList  = $tanpaPulangList  ?? collect(); // -> each: user->name, tanggal, jam_masuk
@endphp

{{-- ================= HERO ================= --}}
<div class="mb-6 rounded-3xl overflow-hidden border border-slate-200 bg-gradient-to-r from-indigo-50 via-white to-sky-50 shadow-sm">
  <div class="px-6 py-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white grid place-items-center text-xl font-bold shadow">A</div>
      <div>
        <p class="text-[11px] uppercase tracking-wider text-slate-500">Panel Admin</p>
        <p class="text-base font-semibold text-slate-900">Ringkasan sistem & operasional presensi</p>
      </div>
    </div>
    <div class="text-right">
      <p class="text-xs text-slate-500">Waktu sekarang</p>
      <p class="text-sm font-semibold tabular-nums text-slate-900">{{ $today->translatedFormat('l, d F Y · H:i') }} WIB</p>
    </div>
  </div>
</div>

{{-- ================= TINDAKAN CEPAT (versi baru) ================= --}}
@php
  $actions = [
    [
      'title'=>'Review Telat',
      'desc'=>'Lihat daftar telat hari ini',
      'href'=>route('admin.presensi.index',['status'=>'telat','start'=>now()->toDateString(),'end'=>now()->toDateString()]),
      'ring'=>'ring-amber-200','bg'=>'bg-amber-50','icon'=>'
        <svg class="w-6 h-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.8" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9" stroke-width="1.8"/>
        </svg>'
    ],
    [
      'title'=>'Belum Absen',
      'desc'=>'Siapa yang belum check-in',
      'href'=>route('admin.presensi.index',['status'=>'belum']),
      'ring'=>'ring-slate-200','bg'=>'bg-slate-50','icon'=>'
        <svg class="w-6 h-6 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <rect x="3" y="4" width="18" height="14" rx="2" stroke-width="1.8"/>
          <path stroke-width="1.8" d="M7 8h5M7 12h3"/>
        </svg>'
    ],
    [
      'title'=>'Izin Pending',
      'desc'=>'Butuh persetujuan',
      'href'=>route('admin.izin.index',['status'=>'pending']),
      'ring'=>'ring-sky-200','bg'=>'bg-sky-50','icon'=>'
        <svg class="w-6 h-6 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.8" d="M4 4h16v16H4z"/><path stroke-width="1.8" d="M8 9h8M8 13h6"/>
        </svg>'
    ],
    [
      'title'=>'Export Bulanan',
      'desc'=>'Excel / PDF periode berjalan',
      'href'=>route('tu.export.index'),
      'ring'=>'ring-emerald-200','bg'=>'bg-emerald-50','icon'=>'
        <svg class="w-6 h-6 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.8" d="M12 3v10"/><path stroke-width="1.8" d="M7 10l5 5 5-5"/><path stroke-width="1.8" d="M5 21h14"/>
        </svg>'
    ],
  ];
@endphp
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
  @foreach($actions as $a)
    <a href="{{ $a['href'] }}" class="group rounded-2xl border border-slate-200 bg-white p-5 hover:shadow-sm transition">
      <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-2xl grid place-items-center {{ $a['bg'] }} {{ $a['ring'] }} ring-1">
          {!! $a['icon'] !!}
        </div>
        <div>
          <p class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ $a['title'] }}</p>
          <p class="text-sm text-slate-500">{{ $a['desc'] }}</p>
        </div>
      </div>
    </a>
  @endforeach
</div>

{{-- ================= OPERASIONAL PRESENSI ================= --}}
<section class="rounded-3xl bg-white ring-1 ring-slate-200 p-6 mb-10">
  <h3 class="text-xl font-semibold text-slate-900">Ringkasan Operasional Presensi</h3>
  <div class="mt-4 grid md:grid-cols-4 gap-4">
    <div class="rounded-2xl border border-slate-200 p-5">
      <p class="text-slate-500 text-sm">Masuk mulai</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-slate-900">{{ $op['mStart'] }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 p-5">
      <p class="text-slate-500 text-sm">Target masuk</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-slate-900">{{ $op['targetMasuk'] }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 p-5">
      <p class="text-slate-500 text-sm">Keluar mulai</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-slate-900">{{ $op['kStart'] }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 p-5">
      <p class="text-slate-500 text-sm">Radius (m)</p>
      <p class="mt-1 text-3xl font-extrabold tabular-nums text-slate-900">{{ (int)$op['radius'] }}</p>
    </div>
  </div>
  <p class="mt-3 text-[12px] text-slate-500">
    Ubah pengaturan di <code class="font-mono">config/presensi.php</code>.
  </p>
</section>

{{-- ================= KPI ================= --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
  @foreach([
    ['label'=>'Hadir (hari ini)', 'val'=>$att['hadir'] ?? 0, 'color'=>'emerald'],
    ['label'=>'Telat (hari ini)', 'val'=>$att['telat'] ?? 0, 'color'=>'amber'],
    ['label'=>'Izin (hari ini)',  'val'=>$att['izin']  ?? 0, 'color'=>'sky'],
    ['label'=>'Sakit (hari ini)', 'val'=>$att['sakit'] ?? 0, 'color'=>'rose'],
    ['label'=>'Belum Absen',      'val'=>$att['belum'] ?? 0, 'color'=>'slate'],
  ] as $c)
    <div class="rounded-2xl bg-white ring-1 ring-{{ $c['color'] }}-200 p-5 shadow-sm hover:shadow-md transition">
      <p class="text-xs text-slate-500">{{ $c['label'] }}</p>
      <p class="mt-2 text-3xl font-extrabold tabular-nums text-{{ $c['color'] }}-700">{{ $c['val'] }}</p>
    </div>
  @endforeach
</div>

<div class="grid xl:grid-cols-3 gap-6">
  {{-- Komposisi Pengguna --}}
  <section class="xl:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-slate-900">Komposisi Pengguna</h3>
      <span class="text-xs text-slate-500">Total: <b class="text-slate-900">{{ array_sum($rc) }}</b></span>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @foreach([['Guru','indigo',$rc['guru']??0],
                ['TU','emerald',$rc['tu']??0],
                ['Piket','amber',$rc['piket']??0],
                ['Kepsek','rose',$rc['kepsek']??0]] as [$label,$color,$val])
        <div class="rounded-xl border border-slate-200 p-5 text-center hover:bg-{{ $color }}-50/50 transition">
          <p class="text-xs text-slate-500">{{ $label }}</p>
          <p class="mt-2 text-3xl font-bold tabular-nums text-{{ $color }}-700">{{ $val }}</p>
          <span class="mt-2 inline-block text-[11px] px-2 py-0.5 rounded bg-{{ $color }}-50 text-{{ $color }}-700">Akun aktif</span>
        </div>
      @endforeach
    </div>
  </section>

  {{-- Perlu Perhatian --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-slate-900">Perlu Perhatian Hari Ini</h3>
      <span class="text-xs text-slate-500">{{ now()->translatedFormat('d M Y') }}</span>
    </div>
    <ul class="space-y-3">
      <li class="flex items-center justify-between rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
        <span class="text-sm">Belum absen</span>
        <span class="font-semibold tabular-nums">{{ $belumAbsen ?? 0 }}</span>
      </li>
      <li class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50/50 p-3">
        <span class="text-sm">Telat ≥ 30 menit</span>
        <span class="font-semibold tabular-nums text-amber-700">{{ $telat30 ?? 0 }}</span>
      </li>
      <li class="flex items-center justify-between rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
        <span class="text-sm">Sudah masuk, belum pulang</span>
        <span class="font-semibold tabular-nums">{{ $tanpaKeluar ?? 0 }}</span>
      </li>
    </ul>
  </section>
</div>

{{-- ================= BAWAH: Izin Pending + Ringkasan Bulan + Panel Baru ================= --}}
<div class="grid lg:grid-cols-3 gap-6 mt-8">
  {{-- Izin Pending --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-slate-900">Antrian Izin Pending</h3>
      <a href="{{ route('admin.izin.index') }}" class="text-sm text-indigo-700 hover:underline">Kelola →</a>
    </div>
    <p class="text-3xl font-extrabold tabular-nums text-amber-600">{{ $pendingIzinCount ?? 0 }}</p>
    @if(($pendingIzinList ?? collect())->isEmpty())
      <p class="mt-3 text-sm text-slate-500">Tidak ada antrian.</p>
    @else
      <ul class="mt-4 space-y-2">
        @foreach($pendingIzinList as $item)
          <li class="flex items-center justify-between text-sm rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
            <div class="min-w-0">
              <p class="font-medium truncate">{{ $item->user?->name ?? '-' }}</p>
              <p class="text-[11px] text-slate-500">
                {{ \Carbon\Carbon::parse($item->tgl_mulai)->format('d M Y') }} –
                {{ \Carbon\Carbon::parse($item->tgl_selesai)->format('d M Y') }} · {{ ucfirst($item->jenis) }}
              </p>
            </div>
            <a href="{{ route('admin.izin.index') }}"
               class="text-xs px-3 py-1 rounded-lg bg-white ring-1 ring-slate-200 hover:bg-slate-100 transition">Tinjau</a>
          </li>
        @endforeach
      </ul>
    @endif
  </section>

  {{-- Ringkasan Bulan Ini --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <h3 class="font-semibold text-slate-900 mb-3">Ringkasan Bulan Ini</h3>
    <div class="grid grid-cols-2 gap-3">
      <div class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
        <p class="text-xs text-slate-500">Total kehadiran</p>
        <p class="mt-1 text-2xl font-bold tabular-nums">{{ $totalKehadiran ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
        <p class="text-xs text-slate-500">Rata-rata telat</p>
        <p class="mt-1 text-2xl font-bold tabular-nums">{{ $fmtTelat($avgTelatMenit ?? 0) }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
        <p class="text-xs text-slate-500">Total izin</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-sky-600">{{ $bulanIzin ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
        <p class="text-xs text-slate-500">Total sakit</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-rose-600">{{ $bulanSakit ?? 0 }}</p>
      </div>
    </div>
    <p class="mt-3 text-[11px] text-slate-500">
      Periode: {{ $today->startOfMonth()->format('d M') }}–{{ $today->endOfMonth()->format('d M Y') }}
    </p>
  </section>

  {{-- Top Telat Bulan Ini (baru) --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold text-slate-900">Top Telat (bulan ini)</h3>
      <span class="text-xs text-slate-500">{{ now()->startOfMonth()->format('d M') }}–{{ now()->endOfMonth()->format('d M Y') }}</span>
    </div>
    <ul class="divide-y">
      @forelse($topTelat as $row)
        <li class="py-2 grid grid-cols-3 items-center gap-2">
          <a href="{{ route('admin.guru.show',$row->user_id ?? ($row->user->id ?? '')) }}" class="truncate col-span-2 hover:underline">
            {{ $row->user->name ?? '-' }}
          </a>
          <div class="justify-self-end flex items-center gap-2">
            <span class="px-2 py-0.5 text-xs rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200 tabular-nums">{{ (int)($row->jml ?? 0) }}x</span>
            <span class="px-2 py-0.5 text-xs rounded-full bg-slate-50 text-slate-700 ring-1 ring-slate-200 tabular-nums">{{ (int)($row->menit ?? 0) }} mnt</span>
          </div>
        </li>
      @empty
        <li class="py-4 text-sm text-slate-500 text-center">Belum ada data.</li>
      @endforelse
    </ul>
  </section>
</div>

{{-- ================= STRIP BAWAH: Aktivitas Terbaru + Belum Pulang ================= --}}
<div class="grid lg:grid-cols-2 gap-6 mt-8">
  {{-- Aktivitas Terbaru --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-slate-900">Aktivitas Terbaru</h3>
      <a class="text-xs text-indigo-700 hover:underline" href="{{ route('admin.presensi.index',['start'=>now()->toDateString(),'end'=>now()->toDateString()]) }}">Lihat hari ini →</a>
    </div>
    <ul class="divide-y">
      @forelse($latestActivities as $a)
        <li class="py-2 flex items-center justify-between">
          <div class="min-w-0">
            <p class="font-medium truncate">{{ $a->user->name ?? '-' }}</p>
            <p class="text-[11px] text-slate-500">
              {{ \Carbon\Carbon::parse($a->tanggal ?? now())->format('d M Y') }} ·
              @if(($a->status ?? '') === 'hadir' || ($a->jam_masuk ?? null))
                Masuk {{ $a->jam_masuk ?? '-' }}
              @elseif(($a->jam_keluar ?? null))
                Keluar {{ $a->jam_keluar ?? '-' }}
              @else
                {{ ucfirst($a->status ?? '-') }}
              @endif
            </p>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[11px] ring-1
            @class([
              'bg-emerald-50 text-emerald-700 ring-emerald-200' => ($a->status ?? '')==='hadir',
              'bg-amber-50 text-amber-700 ring-amber-200'       => ($a->status ?? '')==='telat',
              'bg-sky-50 text-sky-700 ring-sky-200'             => ($a->status ?? '')==='izin',
              'bg-rose-50 text-rose-700 ring-rose-200'          => ($a->status ?? '')==='sakit',
              'bg-slate-50 text-slate-700 ring-slate-200'       => !in_array(($a->status ?? ''),['hadir','telat','izin','sakit']),
            ])">
            {{ strtoupper($a->status ?? '-') }}
          </span>
        </li>
      @empty
        <li class="py-4 text-sm text-slate-500 text-center">Belum ada aktivitas terbaru.</li>
      @endforelse
    </ul>
  </section>

  {{-- Sudah Masuk, Belum Pulang --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-slate-900">Sudah Masuk, Belum Pulang</h3>
      <a class="text-xs text-indigo-700 hover:underline" href="{{ route('admin.presensi.index',['start'=>now()->toDateString(),'end'=>now()->toDateString()]) }}">Detail →</a>
    </div>
    <ul class="divide-y">
      @forelse($tanpaPulangList as $p)
        <li class="py-2 flex items-center justify-between">
          <div class="min-w-0">
            <p class="font-medium truncate">{{ $p->user->name ?? '-' }}</p>
            <p class="text-[11px] text-slate-500">
              {{ \Carbon\Carbon::parse($p->tanggal ?? now())->format('d M Y') }} · Masuk {{ $p->jam_masuk ?? '-' }}
            </p>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[11px] bg-slate-50 text-slate-700 ring-1 ring-slate-200">Menunggu pulang</span>
        </li>
      @empty
        <li class="py-4 text-sm text-slate-500 text-center">Tidak ada data.</li>
      @endforelse
    </ul>
  </section>
</div>
@endsection
