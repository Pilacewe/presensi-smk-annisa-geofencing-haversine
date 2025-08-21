@extends('layouts.piket')
@section('title','Riwayat Presensi')

@section('content')
  <div class="mb-4">
    <h1 class="text-xl font-semibold">Riwayat Presensi</h1>
    <p class="text-xs text-slate-500">Filter & telusuri presensi guru.</p>
  </div>

  {{-- Filter card --}}
  <form class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4 mb-5 grid lg:grid-cols-5 gap-3">
    <div class="lg:col-span-2">
      <label class="text-xs text-slate-500">Guru</label>
      <select name="guru_id" class="mt-1 w-full rounded-lg border-slate-300">
        <option value="">— Semua Guru —</option>
        @foreach($guruList as $g)
          <option value="{{ $g->id }}" @selected($guruId == $g->id)>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-xs text-slate-500">Dari Tanggal</label>
      <input type="date" name="start" value="{{ $start }}" class="mt-1 w-full rounded-lg border-slate-300">
    </div>
    <div>
      <label class="text-xs text-slate-500">Sampai Tanggal</label>
      <input type="date" name="end" value="{{ $end }}" class="mt-1 w-full rounded-lg border-slate-300">
    </div>
    <div class="flex items-end gap-2">
      <button class="w-full px-3 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Filter</button>
      <a href="{{ route('piket.riwayat') }}"
         class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Reset</a>
    </div>
  </form>

  {{-- Tabel riwayat --}}
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50/70">
        <tr class="text-left text-slate-500 border-b">
          <th class="px-4 py-3">Tanggal</th>
          <th class="px-4 py-3">Guru</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody>
        @php
          $badge = function(string $st){
            return match ($st) {
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
              'izin'  => 'bg-amber-50 text-amber-700 ring-amber-200',
              'sakit' => 'bg-rose-50 text-rose-700 ring-rose-200',
              default => 'bg-slate-50 text-slate-700 ring-slate-200', // belum / lainnya
            };
          };
          $fmtJam = fn($v) => $v ? substr($v,0,5) : '—';
        @endphp

        @forelse($data as $r)
          <tr class="border-b last:border-0 hover:bg-slate-50/40">
            <td class="px-4 py-3 whitespace-nowrap">
              {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d M Y') }}
            </td>
            <td class="px-4 py-3">{{ $r->user?->name ?? '—' }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs ring-1 {{ $badge($r->status ?? '-') }}">
                {{ ucfirst($r->status ?? '-') }}
              </span>
            </td>
            <td class="px-4 py-3 tabular-nums">{{ $fmtJam($r->jam_masuk) }}</td>
            <td class="px-4 py-3 tabular-nums">{{ $fmtJam($r->jam_keluar) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">
              Tidak ada data untuk rentang waktu/filters ini.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div class="mt-4">
    {{ $data->links() }}
  </div>
@endsection
