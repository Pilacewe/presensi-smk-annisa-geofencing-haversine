@extends('layouts.presensi')
@section('title','Detail Izin')

@section('content')
<div class="max-w-3xl bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-4">
  @if (session('success'))
    <div class="rounded-lg border-l-4 border-emerald-500 bg-emerald-50 p-3 text-emerald-700">{{ session('success') }}</div>
  @endif
  @if (session('message'))
    <div class="rounded-lg border-l-4 border-amber-500 bg-amber-50 p-3 text-amber-700">{{ session('message') }}</div>
  @endif

  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-semibold">Detail Pengajuan</h2>
      <p class="text-xs text-slate-500">Nomor: #{{ $izin->id }}</p>
    </div>
    @php
      $badge = ['pending'=>'bg-amber-100 text-amber-700',
                'approved'=>'bg-emerald-100 text-emerald-700',
                'rejected'=>'bg-rose-100 text-rose-700'][$izin->status] ?? 'bg-slate-100 text-slate-700';
    @endphp
    <span class="px-2 py-1 rounded-md text-xs {{ $badge }}">{{ strtoupper($izin->status) }}</span>
  </div>

  <div class="grid sm:grid-cols-2 gap-4">
    <div class="rounded-lg bg-slate-50 p-3">
      <p class="text-xs text-slate-500">Jenis</p>
      <p class="font-medium">{{ ucfirst($izin->jenis) }}</p>
    </div>
    <div class="rounded-lg bg-slate-50 p-3">
      <p class="text-xs text-slate-500">Rentang Tanggal</p>
      <p class="font-medium">
        {{ $izin->tgl_mulai->translatedFormat('d M Y') }} – {{ $izin->tgl_selesai->translatedFormat('d M Y') }}
        ({{ $izin->tgl_mulai->diffInDays($izin->tgl_selesai)+1 }} hari)
      </p>
    </div>
  </div>

  @if($izin->keterangan)
  <div class="rounded-lg bg-slate-50 p-3">
    <p class="text-xs text-slate-500">Keterangan</p>
    <p class="text-sm">{{ $izin->keterangan }}</p>
  </div>
  @endif

  @if($izin->lampiran_path)
  <div class="rounded-lg bg-slate-50 p-3">
    <p class="text-xs text-slate-500">Lampiran</p>
    <a href="{{ asset('storage/'.$izin->lampiran_path) }}" target="_blank" class="text-indigo-700 hover:underline text-sm">Lihat / Unduh lampiran</a>
  </div>
  @endif

  <div class="flex items-center gap-3">
    <a href="{{ route('izin.index') }}" class="text-sm text-slate-600 hover:underline">Kembali</a>
    @if($izin->status==='pending')
      <form action="{{ route('izin.destroy',$izin) }}" method="POST" onsubmit="return confirm('Batalkan pengajuan ini?')">
        @csrf @method('DELETE')
        <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Batalkan Pengajuan</button>
      </form>
    @endif
  </div>
</div>
@endsection
