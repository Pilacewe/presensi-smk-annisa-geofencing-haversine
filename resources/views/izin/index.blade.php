@extends('layouts.presensi')
@section('title','Izin / Sakit')

@section('content')
@php
  // Map badge & label
  $badge = fn($s) => [
    'pending'  => 'bg-amber-50  text-amber-700  ring-1 ring-amber-200',
    'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    'rejected' => 'bg-rose-50   text-rose-700   ring-1 ring-rose-200',
  ][$s] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';

  $jenisLabel = ['izin'=>'Izin','sakit'=>'Sakit','dinas'=>'Dinas Luar'];
  $statusLabel= ['pending'=>'Pending','approved'=>'Disetujui','rejected'=>'Ditolak'];

  // hitung ringkas (berdasarkan data halaman ini)
  $coll = $data->getCollection();
  $cPending  = $coll->where('status','pending')->count();
  $cApproved = $coll->where('status','approved')->count();
  $cRejected = $coll->where('status','rejected')->count();
@endphp

<div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6">

  {{-- Header --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h2 class="text-lg font-semibold">Izin / Sakit</h2>
      <p class="text-xs text-slate-500">Ajukan izin, unggah bukti (opsional), dan pantau status persetujuan.</p>
    </div>
    <a href="{{ route('izin.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
      {{-- plus icon --}}
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 5v14M5 12h14"/></svg>
      Buat Pengajuan
    </a>
  </div>

  {{-- Filter --}}
  <form method="GET" class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
    <select name="jenis" class="rounded-lg border-slate-300">
      <option value="">Semua Jenis</option>
      @foreach ($jenisLabel as $k=>$v)
        <option value="{{ $k }}" @selected(request('jenis')==$k)>{{ $v }}</option>
      @endforeach
    </select>
    <select name="status" class="rounded-lg border-slate-300">
      <option value="">Semua Status</option>
      @foreach ($statusLabel as $k=>$v)
        <option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>
      @endforeach
    </select>
    <button class="rounded-lg bg-slate-900 text-white px-4 hover:bg-slate-800">Terapkan</button>
  </form>

  {{-- Ringkas status (halaman ini) --}}
  <div class="mt-5 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl p-4 ring-1 ring-amber-200 bg-white">
      <p class="text-[11px] uppercase tracking-wider text-amber-700">Pending</p>
      <p class="text-2xl font-extrabold tabular-nums text-amber-700">{{ $cPending }}</p>
    </div>
    <div class="rounded-xl p-4 ring-1 ring-emerald-200 bg-white">
      <p class="text-[11px] uppercase tracking-wider text-emerald-700">Disetujui</p>
      <p class="text-2xl font-extrabold tabular-nums text-emerald-700">{{ $cApproved }}</p>
    </div>
    <div class="rounded-xl p-4 ring-1 ring-rose-200 bg-white">
      <p class="text-[11px] uppercase tracking-wider text-rose-700">Ditolak</p>
      <p class="text-2xl font-extrabold tabular-nums text-rose-700">{{ $cRejected }}</p>
    </div>
  </div>

  {{-- Daftar (cards) --}}
  <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @forelse ($data as $iz)
      @php
        // Pastikan tgl_* Carbon (kalau bukan, parse dulu)
        $mulai = $iz->tgl_mulai instanceof \Illuminate\Support\Carbon
                  ? $iz->tgl_mulai
                  : \Illuminate\Support\Carbon::parse($iz->tgl_mulai);
        $selesai = $iz->tgl_selesai instanceof \Illuminate\Support\Carbon
                  ? $iz->tgl_selesai
                  : \Illuminate\Support\Carbon::parse($iz->tgl_selesai);
        $dur = $mulai && $selesai ? $mulai->diffInDays($selesai) + 1 : 1;
        $hasLampiran = !empty($iz->lampiran) || !empty($iz->file);
      @endphp

      <a href="{{ route('izin.show', $iz) }}"
         class="block rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition group">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              {{-- jenis icon --}}
              @if(($iz->jenis ?? '')==='sakit')
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-rose-50 ring-1 ring-rose-200 text-rose-700">
                  <!-- heart -->
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/></svg>
                </span>
              @elseif(($iz->jenis ?? '')==='dinas')
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-sky-50 ring-1 ring-sky-200 text-sky-700">
                  <!-- briefcase -->
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M10 6V4a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v2M3 10h18M5 8h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/></svg>
                </span>
              @else
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-indigo-50 ring-1 ring-indigo-200 text-indigo-700">
                  <!-- calendar -->
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M16 2v4M8 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/></svg>
                </span>
              @endif

              <p class="text-sm font-semibold truncate">{{ $jenisLabel[$iz->jenis] ?? ucfirst($iz->jenis) }}</p>
            </div>

            <p class="mt-1 text-xs text-slate-500">
              {{ $mulai?->translatedFormat('d M Y') }} — {{ $selesai?->translatedFormat('d M Y') }}
              <span class="text-slate-400">•</span> {{ $dur }} hari
              @if($hasLampiran)
                <span class="ml-1 inline-flex items-center gap-1 text-[11px] px-1.5 py-0.5 rounded bg-slate-100 ring-1 ring-slate-200">
                  <!-- paperclip -->
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M21.44 11.05 12.7 19.8a5 5 0 0 1-7.07-7.07l8.49-8.49A3.5 3.5 0 0 1 19.08 7l-8.49 8.49a2 2 0 0 1-2.83-2.83l7.78-7.78"/></svg>
                  lampiran
                </span>
              @endif
            </p>

            @if($iz->keterangan)
              <p class="mt-2 text-xs text-slate-600 line-clamp-2">{{ $iz->keterangan }}</p>
            @endif
          </div>

          <span class="shrink-0 px-2 py-1 rounded-md text-[11px] {{ $badge($iz->status) }}">
            {{ strtoupper($statusLabel[$iz->status] ?? $iz->status) }}
          </span>
        </div>

        {{-- hover arrow --}}
        <div class="mt-3 text-right text-indigo-700 opacity-0 group-hover:opacity-100 transition text-xs">
          Lihat detail →
        </div>
      </a>
    @empty
      {{-- Empty state --}}
      <div class="col-span-full">
        <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center">
          <div class="mx-auto w-10 h-10 rounded-2xl bg-slate-100 grid place-items-center mb-3">
            <svg class="w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.6" d="M12 5v14M5 12h14"/></svg>
          </div>
          <p class="text-slate-600">Belum ada pengajuan izin.</p>
          <a href="{{ route('izin.create') }}" class="mt-3 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
            Buat Pengajuan
          </a>
        </div>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  <div class="mt-6">
    {{ $data->links() }}
  </div>
</div>
@endsection
