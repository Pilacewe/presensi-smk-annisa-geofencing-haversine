@extends('layouts.piket')
@section('title','Riwayat Presensi')

@section('content')
  <h1 class="text-xl font-semibold mb-1">Riwayat Presensi</h1>

  <form class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4 mb-4 grid sm:grid-cols-4 gap-3">
    <select name="guru_id" class="rounded-lg border-slate-300">
      <option value="">– Semua Guru –</option>
      @foreach($guruList as $g)
        <option value="{{ $g->id }}" @selected($guruId==$g->id)>{{ $g->name }}</option>
      @endforeach
    </select>
    <input type="date" name="start" value="{{ $start }}" class="rounded-lg border-slate-300">
    <input type="date" name="end"   value="{{ $end   }}" class="rounded-lg border-slate-300">
    <button class="px-3 py-2 rounded-lg bg-slate-900 text-white">Filter</button>
  </form>

  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500 border-b">
          <th class="px-4 py-3">Tanggal</th>
          <th class="px-4 py-3">Guru</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody>
        @forelse($data as $r)
          <tr class="border-b last:border-0">
            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($r->tanggal)->format('Y-m-d') }}</td>
            <td class="px-4 py-3">{{ $r->user?->name }}</td>
            <td class="px-4 py-3">{{ ucfirst($r->status) }}</td>
            <td class="px-4 py-3">{{ $r->jam_masuk ?? '—' }}</td>
            <td class="px-4 py-3">{{ $r->jam_keluar ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $data->links() }}
  </div>
@endsection
