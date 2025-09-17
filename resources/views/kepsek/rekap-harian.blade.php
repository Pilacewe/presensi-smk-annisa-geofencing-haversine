@extends('layouts.kepsek')
@section('title','Rekap Harian')

@section('subtitle')
  {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
@endsection

@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ request()->fullUrlWithQuery(['date'=>date('Y-m-d')]) }}"
       class="px-3 py-2 rounded-lg text-sm bg-slate-100 hover:bg-slate-200">
      Hari ini
    </a>
    <a href="{{ route('kepsek.rekap.harian') }}"
       class="px-3 py-2 rounded-lg text-sm bg-white border hover:bg-slate-50">
      Reset
    </a>
  </div>
@endsection

@section('content')
  {{-- Filter tanggal (GET agar bisa di-share URL-nya) --}}
  <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-sm text-slate-600">Tanggal</label>
      <input type="date" name="date" value="{{ $tanggal }}"
             class="mt-1 border rounded-lg px-3 py-2">
    </div>
    <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
      Terapkan
    </button>
  </form>

  {{-- Ringkasan cepat --}}
  @php
    $tot = ['hadir'=>0,'telat'=>0,'sakit'=>0,'izin'=>0,'alpha'=>0];
    foreach($rows as $r){ $tot[$r->status] = ($tot[$r->status] ?? 0) + 1; }
    $count = count($rows);
  @endphp
  <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-xs text-slate-500">Total Baris</div>
      <div class="text-2xl font-semibold mt-1">{{ $count }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-xs text-slate-500">Hadir</div>
      <div class="text-2xl font-semibold mt-1">{{ $tot['hadir'] }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-xs text-slate-500">Telat</div>
      <div class="text-2xl font-semibold mt-1">{{ $tot['telat'] }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-xs text-slate-500">Sakit</div>
      <div class="text-2xl font-semibold mt-1">{{ $tot['sakit'] }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-xs text-slate-500">Izin</div>
      <div class="text-2xl font-semibold mt-1">{{ $tot['izin'] }}</div>
    </div>
    <div class="p-3 bg-white rounded-2xl border shadow-sm">
      <div class="text-xs text-slate-500">Alpha</div>
      <div class="text-2xl font-semibold mt-1">{{ $tot['alpha'] }}</div>
    </div>
  </div>

  {{-- Tabel data --}}
  <div class="bg-white rounded-2xl border shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50">
        <tr class="text-slate-600">
          <th class="text-left px-4 py-3">Nama</th>
          <th class="text-left px-4 py-3">Jabatan</th>
          <th class="text-left px-4 py-3">Status</th>
          <th class="text-left px-4 py-3">Masuk</th>
          <th class="text-left px-4 py-3">Keluar</th>
          @if(isset($rows[0]) && property_exists($rows[0], 'telat_menit'))
            <th class="text-left px-4 py-3">Telat (menit)</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @php
          $badge = function ($status) {
            $map = [
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
              'telat' => 'bg-amber-50 text-amber-700 ring-amber-200',
              'sakit' => 'bg-rose-50 text-rose-700 ring-rose-200',
              'izin'  => 'bg-teal-50 text-teal-700 ring-teal-200',
              'alpha' => 'bg-slate-100 text-slate-700 ring-slate-200',
            ];
            $cls = $map[$status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
            return "<span class=\"inline-flex px-2.5 py-1 rounded-full text-xs font-medium ring-1 {$cls}\">"
                   . ucfirst($status) . "</span>";
          };
        @endphp

        @forelse($rows as $r)
          <tr class="border-t">
            <td class="px-4 py-3">{{ $r->name }}</td>
            <td class="px-4 py-3">{{ $r->jabatan }}</td>
            <td class="px-4 py-3">{!! $badge($r->status) !!}</td>
            <td class="px-4 py-3">
              {{ $r->jam_masuk ? substr($r->jam_masuk,0,5) : '-' }}
            </td>
            <td class="px-4 py-3">
              {{ $r->jam_keluar ? substr($r->jam_keluar,0,5) : '-' }}
            </td>
            @if(isset($r->telat_menit))
              <td class="px-4 py-3">{{ $r->telat_menit ?? '-' }}</td>
            @endif
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
