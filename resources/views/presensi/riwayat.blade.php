@extends('layouts.presensi')
@section('title','Riwayat Presensi')
@section('content')

<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <h2 class="text-lg font-semibold">Riwayat Presensi</h2>

    <form method="GET" class="grid grid-cols-2 md:flex md:items-center gap-3">
      <select name="tahun" class="rounded-lg border-slate-300">
        @foreach ($listTahun as $th)
          <option value="{{ $th }}" @selected($th==$tahun)>{{ $th }}</option>
        @endforeach
      </select>
      <select name="bulan" class="rounded-lg border-slate-300">
        @foreach ($listBulan as $i=>$nama)
          <option value="{{ $i }}" @selected($i==$bulan)>{{ $nama }}</option>
        @endforeach
      </select>
      <select name="status" class="rounded-lg border-slate-300">
        <option value="">Semua status</option>
        @foreach (['hadir'=>'Hadir','izin'=>'Izin','sakit'=>'Sakit','alfa'=>'Alfa'] as $k=>$v)
          <option value="{{ $k }}" @selected($status==$k)>{{ $v }}</option>
        @endforeach
      </select>
      <button class="px-4 py-2 rounded-lg bg-slate-900 text-white">Filter</button>
    </form>
  </div>

  <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($data as $row)
      <div class="rounded-xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($row->tanggal)->translatedFormat('l, d F Y') }}</p>

        <div class="mt-3 text-sm grid grid-cols-2 gap-2">
          <div class="p-3 rounded-lg bg-slate-50">
            <p class="text-slate-500 text-xs">Masuk</p>
            <p class="font-semibold tabular-nums">{{ $row->jam_masuk ?? '-' }}</p>
          </div>
          <div class="p-3 rounded-lg bg-slate-50">
            <p class="text-slate-500 text-xs">Keluar</p>
            <p class="font-semibold tabular-nums">{{ $row->jam_keluar ?? '-' }}</p>
          </div>
        </div>

        <div class="mt-3 flex items-center justify-between">
          @php
            $badge = [
              'hadir'=>'bg-emerald-100 text-emerald-700',
              'izin'=>'bg-amber-100 text-amber-700',
              'sakit'=>'bg-sky-100 text-sky-700',
              'alfa'=>'bg-rose-100 text-rose-700'
            ][$row->status] ?? 'bg-slate-100 text-slate-700';
          @endphp
          <span class="px-2 py-1 rounded-md text-xs {{ $badge }}">{{ strtoupper($row->status) }}</span>

          <span class="text-xs text-slate-500">
            {{ number_format($row->latitude,5) }}, {{ number_format($row->longitude,5) }}
          </span>
        </div>
      </div>
    @empty
      <div class="col-span-full text-center text-slate-500 py-10">Belum ada data presensi.</div>
    @endforelse
  </div>

  <div class="mt-6">{{ $data->links() }}</div>
</div>
@endsection
