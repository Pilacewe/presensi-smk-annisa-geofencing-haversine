@extends('layouts.tu')

@section('title','Detail Izin (TU)')
@section('subtitle','Rincian pengajuan izin Anda')

@section('content')
@php
  $badge = [
    'pending'   => 'bg-amber-50  text-amber-700  ring-amber-200',
    'disetujui' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'ditolak'   => 'bg-rose-50   text-rose-700   ring-rose-200',
  ][$izin->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
@endphp

<section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 max-w-3xl">
  <div class="flex items-start justify-between gap-3">
    <div>
      <h2 class="text-lg font-semibold">Detail Pengajuan</h2>
      <p class="text-sm text-slate-500">Dibuat: {{ $izin->created_at->timezone(config('app.timezone'))->translatedFormat('d F Y · H:i') }} WIB</p>
    </div>
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs ring-1 {{ $badge }}">
      {{ ucfirst($izin->status) }}
    </span>
  </div>

  <div class="mt-6 grid sm:grid-cols-2 gap-4">
    <div class="rounded-xl bg-slate-50 p-4">
      <p class="text-xs text-slate-500">Tanggal</p>
      <p class="text-sm font-medium">
        {{ \Carbon\Carbon::parse($izin->tanggal)->translatedFormat('l, d F Y') }}
      </p>
    </div>

    <div class="rounded-xl bg-slate-50 p-4">
      <p class="text-xs text-slate-500">Jenis</p>
      <p class="text-sm font-medium capitalize">{{ $izin->jenis }}</p>
    </div>
  </div>

  <div class="mt-4 rounded-xl bg-slate-50 p-4">
    <p class="text-xs text-slate-500">Keterangan</p>
    <p class="text-sm leading-relaxed">{{ $izin->keterangan ?: '—' }}</p>
  </div>

  <div class="mt-6 flex items-center gap-3">
    <a href="{{ route('tu.absensi.index', ['tab'=>'izin']) }}"
       class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm">Kembali</a>

    {{-- Jika ingin menambahkan opsi batal saat pending, aktifkan ini + route destroy
    @if($izin->status === 'pending')
      <form method="POST" action="{{ route('tu.absensi.izinDestroy', $izin) }}"
            onsubmit="return confirm('Batalkan pengajuan ini?');">
        @csrf @method('DELETE')
        <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">
          Batalkan
        </button>
      </form>
    @endif
    --}}
  </div>
</section>
@endsection
