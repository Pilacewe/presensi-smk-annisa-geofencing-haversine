@extends('layouts.tu')

@section('title','Riwayat Presensi')

@section('actions')
<form class="flex flex-wrap items-center gap-2" method="GET">
  <select name="guru_id" class="rounded-lg border-slate-300">
    <option value="">Semua guru</option>
    @foreach($gurus as $g)
      <option value="{{ $g->id }}" @selected($guruId==$g->id)>{{ $g->name }}</option>
    @endforeach
  </select>
  <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-slate-300">
  <input type="date" name="to"   value="{{ $to }}"   class="rounded-lg border-slate-300">
  <select name="status" class="rounded-lg border-slate-300">
    <option value="">Semua status</option>
    <option value="hadir" @selected($status==='hadir')>Hadir</option>
    <option value="izin"  @selected($status==='izin')>Izin</option>
    <option value="sakit" @selected($status==='sakit')>Sakit</option>
  </select>
  <button class="px-3 py-2 rounded-lg bg-slate-900 text-white">Terapkan</button>
</form>
@endsection

@section('content')
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="px-4 py-3 text-left">Nama</th>
        <th class="px-4 py-3">Tanggal</th>
        <th class="px-4 py-3">Masuk</th>
        <th class="px-4 py-3">Keluar</th>
        <th class="px-4 py-3">Status</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($data as $d)
      <tr>
        <td class="px-4 py-3">{{ $d->user->name }}</td>
        <td class="px-4 py-3 text-center">{{ $d->tanggal }}</td>
        <td class="px-4 py-3 text-center">{{ $d->jam_masuk ?: '-' }}</td>
        <td class="px-4 py-3 text-center">{{ $d->jam_keluar ?: '-' }}</td>
        <td class="px-4 py-3 text-center">{{ ucfirst($d->status) }}</td>
      </tr>
      @empty
        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $data->links() }}</div>
@endsection
