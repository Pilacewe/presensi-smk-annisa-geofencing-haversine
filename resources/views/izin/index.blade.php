@extends('layouts.presensi')
@section('title','Izin / Sakit')

@section('content')
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h2 class="text-lg font-semibold">Izin / Sakit</h2>
      <p class="text-xs text-slate-500">Ajukan izin, unggah bukti (opsional), pantau status persetujuan.</p>
    </div>
    <a href="{{ route('izin.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Buat Pengajuan</a>
  </div>

  <form method="GET" class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
    <select name="jenis" class="rounded-lg border-slate-300">
      <option value="">Semua Jenis</option>
      @foreach (['izin'=>'Izin','sakit'=>'Sakit','dinas'=>'Dinas Luar'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('jenis')==$k)>{{ $v }}</option>
      @endforeach
    </select>
    <select name="status" class="rounded-lg border-slate-300">
      <option value="">Semua Status</option>
      @foreach (['pending'=>'Pending','approved'=>'Disetujui','rejected'=>'Ditolak'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>
      @endforeach
    </select>
    <button class="rounded-lg bg-slate-900 text-white px-4">Filter</button>
  </form>

  <div class="mt-6 grid gap-4">
    @forelse ($data as $iz)
      @php
        $badge = ['pending'=>'bg-amber-100 text-amber-700',
                  'approved'=>'bg-emerald-100 text-emerald-700',
                  'rejected'=>'bg-rose-100 text-rose-700'][$iz->status] ?? 'bg-slate-100 text-slate-700';
      @endphp
      <a href="{{ route('izin.show',$iz) }}"
        class="block rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">{{ ucfirst($iz->jenis) }}</p>
            <p class="font-semibold text-sm">
              {{ $iz->tgl_mulai->translatedFormat('d M Y') }}
              – {{ $iz->tgl_selesai->translatedFormat('d M Y') }}
              ({{ $iz->tgl_mulai->diffInDays($iz->tgl_selesai)+1 }} hari)
            </p>
            @if($iz->keterangan)
              <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $iz->keterangan }}</p>
            @endif
          </div>
          <span class="px-2 py-1 text-xs rounded-md {{ $badge }}">{{ strtoupper($iz->status) }}</span>
        </div>
      </a>
    @empty
      <div class="text-center text-slate-500 py-10">Belum ada pengajuan izin.</div>
    @endforelse
  </div>

  <div class="mt-6">{{ $data->links() }}</div>
</div>
@endsection
