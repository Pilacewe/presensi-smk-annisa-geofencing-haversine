@extends('layouts.tu')


@section('title','Lihat Presensi Guru')
@section('subtitle','Tampilan harian semua guru')

@section('actions')
  <a href="{{ route('tu.absen.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
    Absensi Manual
  </a>
@endsection

@section('content')
  {{-- Filter bar --}}
  <form method="GET" class="mb-6 grid md:grid-cols-[1fr_auto_auto] gap-3">
    <input type="date" name="tanggal" value="{{ $tanggal }}"
      class="w-full rounded-lg border-slate-300">
    <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari nama guru…"
      class="w-full md:w-64 rounded-lg border-slate-300">
    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-700">Terapkan</button>
  </form>

  {{-- Stat cards --}}
  <div class="grid sm:grid-cols-4 gap-4 mb-6">
    <div class="rounded-xl bg-white p-4 shadow-sm">
      <p class="text-xs text-slate-500">Hadir</p>
      <p class="text-2xl font-semibold text-emerald-600">{{ $stat['hadir'] }}</p>
    </div>
    <div class="rounded-xl bg-white p-4 shadow-sm">
      <p class="text-xs text-slate-500">Izin</p>
      <p class="text-2xl font-semibold text-amber-600">{{ $stat['izin'] }}</p>
    </div>
    <div class="rounded-xl bg-white p-4 shadow-sm">
      <p class="text-xs text-slate-500">Sakit</p>
      <p class="text-2xl font-semibold text-rose-600">{{ $stat['sakit'] }}</p>
    </div>
    <div class="rounded-xl bg-white p-4 shadow-sm">
      <p class="text-xs text-slate-500">Belum absen</p>
      <p class="text-2xl font-semibold text-slate-700">{{ $stat['belum'] }}</p>
    </div>
  </div>

  {{-- Tabel --}}
  <div class="rounded-xl bg-white shadow-sm overflow-hidden">
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
            $status = $r->status ?: 'belum';
            $badge = match($status){
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
              'izin'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
              'sakit' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
              default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
            };
            $label = $status === 'belum' ? 'Belum absen' : ucfirst($status);
          @endphp
          <tr class="hover:bg-slate-50/60">
            <td class="px-4 py-3 font-medium">{{ $r->name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ $r->jabatan ?? 'Guru' }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs {{ $badge }}">{{ $label }}</span>
            </td>
            <td class="px-4 py-3">{{ $r->jam_masuk ?: '-' }}</td>
            <td class="px-4 py-3">{{ $r->jam_keluar ?: '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $rows->links() }}</div>
@endsection
