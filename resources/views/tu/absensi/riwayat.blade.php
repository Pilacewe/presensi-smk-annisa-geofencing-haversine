@extends('layouts.tu')
@section('title','Riwayat Absensi Saya (TU)')

@section('content')
  <form method="get" class="mb-4 grid sm:grid-cols-4 gap-3 items-end">
    <div>
      <label class="text-xs text-slate-500">Tahun</label>
      <select name="tahun" class="mt-1 w-full rounded-lg border-slate-300">
        @foreach($listTahun as $t)
          <option value="{{ $t }}" @selected($t==$tahun)>{{ $t }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-xs text-slate-500">Bulan</label>
      <select name="bulan" class="mt-1 w-full rounded-lg border-slate-300">
        @foreach($listBulan as $k=>$v)
          <option value="{{ $k }}" @selected($k==$bulan)>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-xs text-slate-500">Status</label>
      <select name="status" class="mt-1 w-full rounded-lg border-slate-300">
        <option value="">Semua</option>
        @foreach(['hadir','izin','sakit','alfa'] as $s)
          <option value="{{ $s }}" @selected($s==$status)>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <button class="px-4 py-2 rounded-lg bg-slate-900 text-white">Filter</button>
    </div>
  </form>

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
    <table class="min-w-[760px] w-full text-sm">
      <thead>
        <tr class="text-left text-xs text-slate-500 border-b">
          <th class="px-4 py-3">Tanggal</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody>
        @forelse($data as $r)
          <tr class="border-b last:border-0">
            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('l, d F Y') }}</td>
            <td class="px-4 py-3">{{ ucfirst($r->status) }}</td>
            <td class="px-4 py-3">{{ $r->jam_masuk ?? '—' }}</td>
            <td class="px-4 py-3">{{ $r->jam_keluar ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $data->links() }}</div>
@endsection
