@extends('layouts.piket')

@section('title','Dashboard Piket')
@section('subtitle','Monitoring kehadiran & pengaturan petugas piket')

@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ route('piket.absen.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-width="1.8" d="M9 5h6M9 3h6a2 2 0 0 1 2 2v1h2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h2V5a2 2 0 0 1 2-2Z"/>
      </svg>
      Presensi Manual
    </a>
  </div>
@endsection

@section('content')
@if(session('success'))
  <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
    {{ session('success') }}
  </div>
@endif

@php
  $fmtTelat = function($m){ if(!$m) return null; $h=intdiv($m,60); $mm=$m%60; return $h?($mm?"$h jam $mm menit":"$h jam"):"$mm menit"; };
@endphp

{{-- ===== HERO ===== --}}
<div class="mb-6 rounded-[28px] border border-slate-200 bg-gradient-to-r from-slate-50 via-indigo-50 to-sky-50">
  <div class="px-6 py-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <div class="relative">
        <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white grid place-items-center text-lg font-bold">P</div>
        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white"></div>
      </div>
      <div class="leading-tight">
        <p class="text-[11px] uppercase tracking-wider text-slate-500">Panel Piket</p>
        <p class="text-sm font-medium text-slate-800">Atur petugas & pantau kehadiran guru</p>
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

{{-- ===== Set Petugas Hari Ini + Progress ===== --}}
<div class="mb-6 grid lg:grid-cols-3 gap-4">
  <section class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    @php $tgl = \Carbon\Carbon::now(config('app.timezone')); @endphp
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="font-semibold text-slate-900">Petugas Piket — {{ $tgl->translatedFormat('l, d M Y') }}</h3>
        <p class="text-sm text-slate-600">Nama ini otomatis terbaca admin (menu Piket).</p>
      </div>
      <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-[11px] text-slate-700 ring-1 ring-slate-200">Hari ke-{{ $tgl->isoFormat('D') }}</span>
    </div>

    @if(!$rosterToday)
      <form action="{{ route('piket.dashboard.start') }}" method="POST" class="mt-4 grid md:grid-cols-[1fr,auto] gap-3">
        @csrf
        <div>
          <label class="text-xs text-slate-500">Pilih pegawai</label>
          <select name="user_id" class="w-full rounded-xl border-slate-300">
            <option value="">— pilih petugas —</option>
            @foreach($pegawai as $p)
              <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
          </select>
          @error('user_id') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end gap-3">
          <input type="text" name="catatan" class="w-60 rounded-xl border-slate-300" placeholder="Catatan (opsional)">
          <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700 shadow-sm">Simpan</button>
        </div>
      </form>
    @else
      <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-slate-600">Petugas hari ini</p>
            <p class="text-2xl font-bold text-emerald-900">{{ $rosterToday->user?->name ?? '—' }}</p>
          </div>
          <div class="text-right">
            <span class="px-2.5 py-1 rounded-full bg-white ring-1 ring-emerald-200 text-[11px] text-emerald-700">Sehari penuh</span>
            @if($rosterToday->catatan)
              <p class="mt-2 text-xs text-emerald-900/80">Catatan: {{ $rosterToday->catatan }}</p>
            @endif
          </div>
        </div>
      </div>
    @endif
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-sm font-medium text-slate-800">Progress Presensi Hari Ini</p>
      <p class="text-sm font-semibold tabular-nums text-slate-900">{{ $pct }}%</p>
    </div>
    <div class="mt-3 h-3 rounded-full bg-slate-100 overflow-hidden">
      <div class="h-3 rounded-full bg-gradient-to-r from-emerald-400 via-amber-400 to-emerald-600" style="width: {{ $pct }}%"></div>
    </div>
    <p class="mt-2 text-xs text-slate-500">Hadir {{ $hadir }} + Telat {{ $telat }} dari {{ $totalGuru }} guru.</p>
  </section>
</div>

{{-- ===== KPI ===== --}}
@php
  $cards = [
    ['label'=>'Total Guru','value'=>$totalGuru,'ring'=>'ring-indigo-200','icon'=>'Σ','t'=>'text-slate-900'],
    ['label'=>'Hadir (hari ini)','value'=>$hadir,'ring'=>'ring-emerald-200','icon'=>'✓','t'=>'text-emerald-700'],
    ['label'=>'Telat (hari ini)','value'=>$telat,'ring'=>'ring-amber-200','icon'=>'⏰','t'=>'text-amber-700'],
    ['label'=>'Izin','value'=>$izin,'ring'=>'ring-amber-200','icon'=>'i','t'=>'text-amber-700'],
    ['label'=>'Sakit','value'=>$sakit,'ring'=>'ring-rose-200','icon'=>'+','t'=>'text-rose-700'],
    ['label'=>'Belum Absen','value'=>$belum,'ring'=>'ring-slate-200','icon'=>'!','t'=>'text-slate-900'],
  ];
@endphp
<div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
  @foreach($cards as $c)
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 {{ $c['ring'] }}">
      <div class="flex items-center justify-between">
        <p class="text-xs text-slate-500">{{ $c['label'] }}</p>
        <span class="w-7 h-7 grid place-items-center rounded-full bg-slate-100 text-slate-700 text-xs">{{ $c['icon'] }}</span>
      </div>
      <p class="mt-2 text-[28px] leading-none font-extrabold tabular-nums {{ $c['t'] }}">{{ $c['value'] }}</p>
    </div>
  @endforeach
</div>

{{-- ===== Dua kolom: Log & Ringkasan ===== --}}
<div class="grid xl:grid-cols-3 gap-6">
  {{-- LOG AKTIVITAS --}}
  <section class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="px-5 py-4 flex items-center justify-between">
      <h3 class="font-semibold text-slate-900">Aktivitas Terbaru</h3>
      <div class="flex items-center gap-2">
        <a href="{{ route('piket.cek') }}" class="px-3 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-sm shadow-sm">Cek Guru</a>
        <a href="{{ route('piket.riwayat') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm shadow-sm">Riwayat</a>
      </div>
    </div>

    @php
      $recent = $recent ?? collect();
    @endphp

    @if ($recent->isEmpty())
      <div class="px-5 pb-6"><p class="text-sm text-slate-500">Belum ada aktivitas yang terekam.</p></div>
    @else
      <ul class="divide-y">
        @foreach($recent as $r)
          @php
            $st = strtolower($r->status ?? '-');
            $badge = match($st){
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
              'telat' => 'bg-amber-50  text-amber-700  ring-1 ring-amber-200',
              'izin'  => 'bg-amber-50  text-amber-700  ring-1 ring-amber-200',
              'sakit' => 'bg-rose-50   text-rose-700   ring-1 ring-rose-200',
              default => 'bg-slate-50  text-slate-700  ring-1 ring-slate-200',
            };
            $tglRow = \Illuminate\Support\Carbon::parse($r->tanggal, config('app.timezone'));
            $durTelat = $st==='telat' ? $fmtTelat($r->telat_menit ?? null) : null;
          @endphp
          <li class="px-5 py-3 flex items-center justify-between">
            <div class="min-w-0">
              <p class="font-medium truncate text-slate-900">{{ $r->user?->name ?? '—' }}</p>
              <p class="text-xs text-slate-500">{{ $tglRow->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <span class="px-2.5 py-1 rounded-full {{ $badge }}">
                {{ strtoupper($st) }} @if($durTelat) · {{ $durTelat }} @endif
              </span>
              <div class="text-right text-slate-700 leading-tight">
                <div>Masuk: <b>{{ $r->jam_masuk ?? '—' }}</b></div>
                <div>Keluar: <b>{{ $r->jam_keluar ?? '—' }}</b></div>
              </div>
            </div>
          </li>
        @endforeach
      </ul>
      <div class="px-5 py-4 text-right">
        <a href="{{ route('piket.riwayat') }}" class="text-sm text-indigo-700 hover:underline">Ke halaman Riwayat →</a>
      </div>
    @endif
  </section>

  {{-- RINGKASAN SAMPING --}}
  <aside class="space-y-6">
    {{-- Belum Absen --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-4 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900">Belum Absen ({{ $belumList->count() }})</h3>
        <a href="{{ route('piket.rekap') }}" class="text-sm text-indigo-700 hover:underline">Rekap Harian</a>
      </div>
      <ul class="px-5 pb-4 space-y-2">
        @forelse($belumList as $row)
          <li class="text-sm text-slate-700 flex items-center justify-between">
            <span class="truncate">{{ $row->name }}</span>
            <span class="text-[11px] px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 ring-1 ring-slate-200">Belum</span>
          </li>
        @empty
          <li class="text-sm text-slate-500">Semua guru sudah absen.</li>
        @endforelse
      </ul>
    </div>

    {{-- Sudah Hadir --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-4"><h3 class="font-semibold text-slate-900">Sudah Hadir</h3></div>
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
      <div class="px-5 py-4"><h3 class="font-semibold text-slate-900">Telat ({{ $telatList->count() }})</h3></div>
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

    {{-- Izin / Sakit Hari Ini --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-4"><h3 class="font-semibold text-slate-900">Izin / Sakit ({{ $izinList->count() }})</h3></div>
      <ul class="px-5 pb-4 space-y-2">
        @forelse($izinList as $row)
          <li class="text-sm text-slate-700 flex items-center justify-between">
            <span class="truncate">{{ $row->user->name }}</span>
            <span class="text-[11px] px-2 py-0.5 rounded-lg
              {{ $row->jenis==='sakit' ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
              {{ strtoupper($row->jenis) }}
            </span>
          </li>
        @empty
          <li class="text-sm text-slate-500">Tidak ada izin/sakit hari ini.</li>
        @endforelse
      </ul>
    </div>

    {{-- Tindakan Cepat --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
      <h3 class="font-semibold mb-3 text-slate-900">Tindakan Cepat</h3>
      <div class="space-y-3">
        <a href="{{ route('piket.absen.create') }}"
           class="flex items-center justify-between w-full px-4 py-3 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">
          <span>Presensi Manual Guru</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('piket.cek') }}"
           class="flex items-center justify-between w-full px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
          <span>Cek Guru (Hari Ini)</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('piket.riwayat') }}"
           class="flex items-center justify-between w-full px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200">
          <span>Riwayat Presensi</span>
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M9 18l6-6-6-6"/></svg>
        </a>
      </div>
      <p class="mt-5 p-4 rounded-xl bg-slate-50 ring-1 ring-slate-200 text-xs text-slate-600 leading-relaxed">
        Gunakan <b>Presensi Manual</b> saat perangkat guru bermasalah/jaringan padam. Perubahan tercatat sebagai tindakan <b>Piket</b>.
      </p>
    </div>
  </aside>
</div>

{{-- Clock --}}
<script>
  const clockEl = document.getElementById('nowClock');
  if (clockEl) {
    setInterval(() => {
      const d = new Date(), pad = n => String(n).padStart(2,'0');
      const hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][d.getDay()];
      const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][d.getMonth()];
      clockEl.textContent = `${hari}, ${pad(d.getDate())} ${bulan} ${d.getFullYear()} · ${pad(d.getHours())}:${pad(d.getMinutes())} WIB`;
    }, 30000);
  }
</script>
@endsection
