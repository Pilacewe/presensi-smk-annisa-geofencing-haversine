@extends('layouts.kepsek')
@section('title','Dashboard Kepsek')
@section('subtitle','Ringkasan kehadiran hari ini & produktivitas bulan berjalan')

@section('content')
@php
  $hadir = $ringkas->hadir ?? 0;
  $telat = $ringkas->telat ?? 0;
  $sakit = $ringkas->sakit ?? 0;
  $izin  = $ringkas->izin  ?? 0;
  $alpha = $ringkas->alpha ?? 0;
  $avgTelat = isset($ringkas->avg_telat) ? round($ringkas->avg_telat, 1) : null;

  $latest      = collect($latest ?? []);
  $topTelat    = collect($topTelat ?? []);
  $piket       = collect($piket ?? []);

  $belumMasuk   = collect($belumMasuk ?? []);
  $sudahHadir   = collect($sudahHadirList ?? []);
  $telatList    = collect($telatList ?? []);
  $izinList     = collect($izinList ?? []);

  // aktivitas tampil maksimal 8
  $latestShown = $latest->take(8);
  $latestMore  = max(0, $latest->count() - 8);
@endphp

<div class="grid gap-4">

  {{-- KPI ringkas --}}
  <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
    @php
      $kpis = [
        ['label'=>'Total Pegawai','value'=>$totalPegawai,'bg'=>'indigo'],
        ['label'=>'Hadir','value'=>$hadir,'bg'=>'emerald'],
        ['label'=>'Telat','value'=>$telat,'bg'=>'amber'],
        ['label'=>'Sakit/Izin','value'=>($sakit+$izin),'bg'=>'sky'],
        ['label'=>'Alpha','value'=>$alpha,'bg'=>'rose'],
        ['label'=>'Rata-rata Telat','value'=> $avgTelat !== null ? number_format($avgTelat,1).' mnt' : '-','bg'=>'fuchsia'],
      ];
    @endphp

    @foreach($kpis as $k)
      <div class="p-4 bg-white rounded-2xl border shadow-sm">
        <div class="flex items-start gap-3">
          {{-- ikon dihilangkan sesuai permintaan; tetap sisakan spacing --}}
          <span class="inline-grid place-items-center h-9 w-9 rounded-xl bg-{{ $k['bg'] }}-50 text-{{ $k['bg'] }}-600"></span>
          <div class="flex-1">
            <div class="text-slate-500 text-xs">{{ $k['label'] }}</div>
            <div class="text-2xl font-bold leading-tight">{{ $k['value'] }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- MAIN GRID: left = aktivitas/status, right = tindakan cepat + sidebar --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- LEFT column: gunakan flex-col sehingga card bisa mengisi tinggi --}}
    <div class="lg:col-span-2 flex flex-col gap-4">

      {{-- STATUS QUICK-LISTS --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Belum Absen --}}
        <div class="p-4 bg-white rounded-2xl border shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold">Belum Absen <span class="text-sm text-slate-500">({{ $belumMasuk->count() }})</span></h3>
            <a href="{{ route('kepsek.rekap.harian') }}" class="text-indigo-600 text-sm">Rekap Harian</a>
          </div>

          @if($belumMasuk->isEmpty())
            <div class="text-sm text-slate-500">Semua guru sudah melakukan presensi masuk hari ini.</div>
          @else
            <ul class="space-y-2">
              @foreach($belumMasuk as $g)
                <li class="flex items-center justify-between">
                  <div>
                    <div class="font-medium">{{ $g->name }}</div>
                    <div class="text-xs text-slate-500">{{ $g->jabatan ?? '' }}</div>
                  </div>
                  <span class="text-xs px-2 py-1 rounded-md bg-slate-50 text-slate-600">Belum</span>
                </li>
              @endforeach
            </ul>
          @endif
        </div>

        {{-- Sudah Hadir --}}
        <div class="p-4 bg-white rounded-2xl border shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold">Sudah Hadir <span class="text-sm text-slate-500">({{ $sudahHadir->count() }})</span></h3>
            <a href="{{ route('kepsek.rekap.harian') }}" class="text-xs text-indigo-600">Lihat →</a>
          </div>
          @if($sudahHadir->isEmpty())
            <div class="text-sm text-slate-500">Belum ada yang hadir.</div>
          @else
            <ul class="space-y-2">
              @foreach($sudahHadir as $g)
                <li class="flex items-center justify-between">
                  <div>
                    <div class="font-medium">{{ $g->name }}</div>
                    <div class="text-xs text-slate-500">{{ $g->jabatan ?? '' }}</div>
                  </div>
                  <span class="text-xs px-2 py-1 rounded-md bg-emerald-50 text-emerald-700">Hadir</span>
                </li>
              @endforeach
            </ul>
          @endif
        </div>

        {{-- Telat --}}
        <div class="p-4 bg-white rounded-2xl border shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold">Telat <span class="text-sm text-slate-500">({{ $telatList->count() }})</span></h3>
            <a href="{{ route('kepsek.rekap.harian') }}" class="text-xs text-indigo-600">Lihat →</a>
          </div>
          @if($telatList->isEmpty())
            <div class="text-sm text-slate-500">Tidak ada yang telat hari ini.</div>
          @else
            <ul class="space-y-2">
              @foreach($telatList as $g)
                <li class="flex items-center justify-between">
                  <div>
                    <div class="font-medium">{{ $g->name }}</div>
                    <div class="text-xs text-slate-500">{{ $g->jabatan ?? '' }}</div>
                  </div>
                  <span class="text-xs px-2 py-1 rounded-md bg-amber-100 text-amber-700">Telat</span>
                </li>
              @endforeach
            </ul>
          @endif
        </div>

        {{-- Izin / Sakit --}}
        <div class="p-4 bg-white rounded-2xl border shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold">Izin / Sakit <span class="text-sm text-slate-500">({{ $izinList->count() }})</span></h3>
            <a href="{{ route('kepsek.rekap.harian') }}" class="text-xs text-indigo-600">Lihat →</a>
          </div>
          @if($izinList->isEmpty())
            <div class="text-sm text-slate-500">Tidak ada izin/sakit hari ini.</div>
          @else
            <ul class="space-y-2">
              @foreach($izinList as $g)
                <li class="flex items-center justify-between">
                  <div>
                    <div class="font-medium">{{ $g->name }}</div>
                    <div class="text-xs text-slate-500">{{ $g->jabatan ?? '' }}</div>
                  </div>
                  <span class="text-xs px-2 py-1 rounded-md bg-sky-50 text-sky-700">Izin</span>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>

      {{-- Aktivitas terbaru: atur flex-grow agar mengisi tinggi kolom --}}
      <div class="p-4 bg-white rounded-2xl border shadow-sm flex-1 min-h-[220px] flex flex-col">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold">Aktivitas Terbaru (Hari Ini)</h3>
          <a href="{{ route('kepsek.rekap.harian') }}" class="text-xs text-indigo-600 hover:text-indigo-700">Lihat semua →</a>
        </div>

        <div class="overflow-x-auto flex-1">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
              <tr class="text-slate-600">
                <th class="text-left px-3 py-2">Nama</th>
                <th class="text-left px-3 py-2">Status</th>
                <th class="text-left px-3 py-2">Masuk</th>
                <th class="text-left px-3 py-2">Keluar</th>
                <th class="text-left px-3 py-2">Telat (mnt)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($latestShown as $l)
                <tr class="border-t">
                  <td class="px-3 py-2">{{ $l->name }}</td>
                  <td class="px-3 py-2 capitalize">{{ $l->status }}</td>
                  <td class="px-3 py-2">{{ $l->jam_masuk ? substr($l->jam_masuk,0,5) : '-' }}</td>
                  <td class="px-3 py-2">{{ $l->jam_keluar ? substr($l->jam_keluar,0,5) : '-' }}</td>
                  <td class="px-3 py-2">{{ $l->telat_menit ?? 0 }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">Belum ada aktivitas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($latestMore > 0)
          <div class="mt-3 text-right">
            <a href="{{ route('kepsek.rekap.harian') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm hover:bg-slate-50">
              Lihat {{ $latestMore }} lainnya
            </a>
          </div>
        @endif
      </div>

    </div>

    {{-- RIGHT column: tindakan cepat di atas (sesuai permintaan) --}}
    <aside class="space-y-4">

      {{-- Tindakan Cepat --}}
      <div class="p-4 bg-white rounded-2xl border shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Tindakan Cepat</h3>
          <span class="text-xs text-slate-400">Akses cepat</span>
        </div>

        <div class="space-y-3">
          <a href="{{ route('piket.absen.create') }}" class="block">
            <div class="px-4 py-3 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 flex items-center justify-between">
              <span>Presensi Manual Guru</span>
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M9 5l7 7-7 7"/></svg>
            </div>
          </a>

          <a href="{{ route('kepsek.rekap.harian') }}" class="block">
            <div class="px-4 py-3 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-between">
              <span>Cek Guru (Hari Ini)</span>
              <svg class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M9 5l7 7-7 7"/></svg>
            </div>
          </a>

          <a href="{{ route('presensi.riwayat') }}" class="block">
            <div class="px-4 py-3 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-between">
              <span>Riwayat Presensi</span>
              <svg class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M9 5l7 7-7 7"/></svg>
            </div>
          </a>

          <div class="mt-2 rounded-md bg-slate-50 p-3 text-sm text-slate-600">
            Gunakan <strong>Presensi Manual</strong> saat perangkat guru bermasalah/jaringan padam. Perubahan akan tercatat sebagai tindakan <strong>Piket</strong>.
          </div>
        </div>
      </div>

      {{-- Notifikasi --}}
      <div class="p-4 bg-white rounded-2xl border shadow-sm">
        <div class="font-semibold mb-2">Notifikasi</div>
        <ul class="text-sm space-y-2">
          <li class="flex items-center justify-between">
            <span class="text-slate-600">Belum Presensi Masuk</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100">{{ $belumPresensi }}</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-slate-600">Belum Presensi Pulang</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100">{{ $belumCheckout }}</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-slate-600">Izin pending</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100">{{ $izinPending }}</span>
          </li>
        </ul>
        <div class="mt-3 text-xs text-slate-500">*Klik rekap untuk tindak lanjut.</div>
      </div>

      {{-- Top Telat --}}
      <div class="p-4 bg-white rounded-2xl border shadow-sm">
        <div class="font-semibold mb-2">Top Telat (7 hari)</div>
        <ol class="text-sm space-y-2">
          @forelse($topTelat as $t)
            <li class="flex items-center justify-between">
              <span>{{ $t->name }}</span>
              <span class="text-slate-600">{{ $t->total_telat }} mnt</span>
            </li>
          @empty
            <li class="text-slate-500">Tidak ada data.</li>
          @endforelse
        </ol>
      </div>

      {{-- Roster Piket --}}
      <div class="p-4 bg-white rounded-2xl border shadow-sm">
        <div class="font-semibold mb-2">Roster Piket Hari Ini</div>
        <ul class="text-sm space-y-2">
          @forelse($piket as $p)
            <li class="flex items-center justify-between">
              <span>{{ $p->name }}</span>
              <span class="text-slate-500">{{ $p->jabatan }}</span>
            </li>
          @empty
            <li class="text-slate-500">Tidak ada roster.</li>
          @endforelse
        </ul>
      </div>

    </aside>

  </div>

</div>
@endsection
