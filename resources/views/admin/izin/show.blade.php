@extends('layouts.admin')

@section('title','Detail Izin')
@section('subtitle','Tinjau & ubah status pengajuan')

@section('actions')
  <a href="{{ route('admin.izin.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm">Kembali</a>
@endsection

@section('content')
  @if (session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-3">
      {{ session('success') }}
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Info utama --}}
    <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Pengajuan Izin</h2>
          <p class="text-xs text-slate-500">Diajukan: {{ $izin->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        @php
          $badge = match($izin->status){
            'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            default    => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
          };
        @endphp
        <span class="px-3 py-1 rounded-full text-xs {{ $badge }}">{{ ucfirst($izin->status) }}</span>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
          <p class="text-xs text-slate-500">Pegawai</p>
          <p class="font-medium">{{ $izin->user?->name ?? '—' }}</p>
          <p class="text-xs uppercase text-slate-400">{{ $izin->user?->role ?? '-' }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
          <p class="text-xs text-slate-500">Tanggal Izin</p>
          <p class="font-medium">{{ \Carbon\Carbon::parse($izin->tanggal)->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
          <p class="text-xs text-slate-500">Jenis</p>
          <p class="font-medium capitalize">{{ $izin->jenis }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
          <p class="text-xs text-slate-500">Diajukan pada</p>
          <p class="font-medium">{{ $izin->created_at?->format('Y-m-d H:i') }}</p>
        </div>
      </div>

      <div class="mt-4 rounded-xl bg-white">
        <p class="text-xs text-slate-500 mb-1">Keterangan</p>
        <div class="rounded-xl ring-1 ring-slate-200 p-4 min-h-[80px]">
          {{ $izin->keterangan ?: '—' }}
        </div>
      </div>
    </section>

    {{-- Panel tindakan --}}
    <aside class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 h-fit">
      <h3 class="font-semibold mb-3">Tindakan</h3>

      @if($izin->status === 'pending')
        <form method="POST" action="{{ route('admin.izin.updateStatus',$izin) }}" class="space-y-3"
              onsubmit="return confirm('Yakin memperbarui status?')">
          @csrf @method('patch')

          <label class="text-sm font-medium block">Catatan (opsional)</label>
          <textarea name="catatan" rows="3" class="w-full rounded-lg border-slate-300" placeholder="Catatan admin..."></textarea>

          <div class="flex items-center gap-2 pt-2">
            <button name="status" value="approved"
              class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Approve</button>
            <button name="status" value="rejected"
              class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">Reject</button>
          </div>
        </form>
      @else
        <p class="text-sm text-slate-600">Izin ini sudah <b>{{ strtoupper($izin->status) }}</b>.</p>
        <p class="text-xs text-slate-500 mt-1">Jika perlu ubah lagi, hubungi admin senior atau lakukan lewat database sesuai SOP.</p>
      @endif

      <div class="mt-4">
        <a href="{{ route('admin.izin.index') }}" class="inline-block px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm">
          Kembali ke daftar
        </a>
      </div>
    </aside>
  </div>
@endsection
