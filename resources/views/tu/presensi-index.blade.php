@extends('layouts.tu')

@section('title','Lihat Presensi Guru')
@section('subtitle','Tampilan harian semua guru')

@section('content')
  {{-- ================= Filter Bar ================= --}}
  <form method="GET" class="mb-6">
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4 flex flex-col md:flex-row md:items-end md:justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="relative">
          <input type="date" name="tanggal" value="{{ $tanggal }}"
                 class="rounded-lg border-slate-300 pr-10">
          <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
            </svg>
          </span>
        </div>
        <div class="relative">
          <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari nama guru…"
                 class="w-64 rounded-lg border-slate-300 pr-9">
          <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-width="1.8" d="m21 21-4.35-4.35M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/>
            </svg>
          </span>
        </div>
      </div>
      <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-700">Terapkan</button>
    </div>
  </form>

  {{-- ================= Stat Cards ================= --}}
  @php
    $hadir = $stat['hadir'] ?? 0;
    $telat = $stat['telat'] ?? 0;
    $izin  = $stat['izin']  ?? 0;
    $sakit = $stat['sakit'] ?? 0;
    $belum = $stat['belum'] ?? 0;

    $cards = [
      ['label'=>'Hadir','value'=>$hadir,'ring'=>'ring-emerald-200','text'=>'text-emerald-700','icon'=>'✓'],
      ['label'=>'Telat','value'=>$telat,'ring'=>'ring-amber-200','text'=>'text-amber-700','icon'=>'⏰'],
      ['label'=>'Izin','value'=>$izin,'ring'=>'ring-sky-200','text'=>'text-sky-700','icon'=>'🛈'],
      ['label'=>'Sakit','value'=>$sakit,'ring'=>'ring-rose-200','text'=>'text-rose-700','icon'=>'❤'],
      ['label'=>'Belum Absen','value'=>$belum,'ring'=>'ring-slate-200','text'=>'text-slate-900','icon'=>'!'],
    ];
  @endphp

  <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach($cards as $c)
      <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 {{ $c['ring'] }}">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-500">{{ $c['label'] }} (hari ini)</p>
          <span class="w-7 h-7 grid place-items-center rounded-full bg-slate-100 text-slate-700 text-xs">
            {{ $c['icon'] }}
          </span>
        </div>
        <p class="mt-1 text-3xl font-extrabold tabular-nums {{ $c['text'] }}">{{ $c['value'] }}</p>
      </div>
    @endforeach
  </div>

  {{-- (Opsional) Panel kecil daftar yang belum absen pada halaman ini --}}
  @if(isset($belumList) && $belumList->count() > 0)
    <div class="mb-6 rounded-2xl bg-white ring-1 ring-slate-200 p-4">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">Belum Absen ({{ $belumList->count() }})</h3>
        <span class="text-xs text-slate-500">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</span>
      </div>
      <div class="mt-2 flex flex-wrap gap-2">
        @foreach($belumList as $b)
          <span class="px-2.5 py-1 rounded-full text-xs bg-slate-50 text-slate-700 ring-1 ring-slate-200">
            {{ $b->name }}
          </span>
        @endforeach
      </div>
    </div>
  @endif

  {{-- ================= Tabel ================= --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">Nama</th>
          <th class="text-left px-4 py-3">Jabatan</th>
          <th class="text-left px-4 py-3">Status</th>
          <th class="text-left px-4 py-3">Masuk</th>
          <th class="text-left px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($rows as $r)
          @php
            // status bisa: hadir | telat | izin | sakit | null (belum absen)
            $st = $r->status ? strtolower($r->status) : null;

            $badge = match($st){
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
              'telat' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
              'izin'  => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
              'sakit' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
              default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
            };

            // label: jika null, tampil "Belum Absen"
            $label = $st ? ucfirst($st) : 'Belum Absen';

            // jam tampil HH:MM
            $in  = $r->jam_masuk  ? \Illuminate\Support\Str::of($r->jam_masuk)->substr(0,5)  : '—';
            $out = $r->jam_keluar ? \Illuminate\Support\Str::of($r->jam_keluar)->substr(0,5) : '—';

            // telat menit opsional jika tersedia (mis. kolom telat_menit)
            $durTelat = null;
            if (!is_null($r->telat_menit) && $st === 'telat') {
              $h = intdiv((int)$r->telat_menit, 60); $m = ((int)$r->telat_menit) % 60;
              $durTelat = $h ? ($m ? "$h jam $m mnt" : "$h jam") : "$m mnt";
            }
          @endphp

          <tr class="hover:bg-slate-50/60">
            <td class="px-4 py-3 font-medium text-slate-900">{{ $r->name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ $r->jabatan ?? 'Guru' }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs {{ $badge }}">{{ $label }}</span>
                @if($durTelat)
                  <span class="text-[11px] text-amber-700">(+{{ $durTelat }})</span>
                @endif
              </div>
            </td>
            <td class="px-4 py-3 tabular-nums">{{ $in }}</td>
            <td class="px-4 py-3 tabular-nums">{{ $out }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $rows->links() }}
  </div>
@endsection
