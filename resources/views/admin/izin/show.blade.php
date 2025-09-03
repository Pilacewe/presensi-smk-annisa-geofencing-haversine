@extends('layouts.admin')

@section('title','Detail Izin / Sakit')

@php
  $fmt = fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('l, d F Y');
  $badge = function($st){
    return [
      'pending'  => 'bg-amber-50 text-amber-700 ring-amber-200',
      'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
      'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ][$st] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
  };
  $thumb = $izin->bukti_path ?? $izin->bukti ?? null;
@endphp

@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ route('admin.izin.index') }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm">Kembali</a>
    @if($izin->status==='pending')
      <form method="POST" action="{{ route('admin.izin.approve',$izin->id) }}">
        @csrf @method('PATCH')
        <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Setujui</button>
      </form>
      <form method="POST" action="{{ route('admin.izin.reject',$izin->id) }}" onsubmit="return askReason(this)">
        @csrf @method('PATCH')
        <input type="hidden" name="reject_reason">
        <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Tolak</button>
      </form>
    @endif
    @if(Route::has('admin.izin.destroy'))
      <form method="POST" action="{{ route('admin.izin.destroy',$izin->id) }}"
            onsubmit="return confirm('Hapus permohonan ini?')">
        @csrf @method('DELETE')
        <button class="px-4 py-2 rounded-lg bg-white ring-1 ring-rose-200 text-rose-700 text-sm hover:bg-rose-50">Hapus</button>
      </form>
    @endif
  </div>
@endsection

@section('content')
  <div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-slate-900">{{ $izin->user?->name ?? '—' }}</h2>
          <p class="text-xs text-slate-500">Diajukan {{ $izin->created_at?->translatedFormat('d M Y · H:i') }}</p>
        </div>
        <span class="text-[11px] px-2.5 py-1 rounded-full ring-1 {{ $badge($izin->status) }}">
          {{ strtoupper($izin->status) }}
        </span>
      </div>

      <div class="mt-5 grid sm:grid-cols-2 gap-4">
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
          <p class="text-[11px] text-slate-500">Jenis</p>
          <p class="font-medium capitalize">{{ $izin->jenis }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
          <p class="text-[11px] text-slate-500">Rentang</p>
          <p class="font-medium">{{ $fmt($izin->tgl_mulai) }} — {{ $fmt($izin->tgl_selesai ?: $izin->tgl_mulai) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4 sm:col-span-2">
          <p class="text-[11px] text-slate-500">Keterangan</p>
          <p class="mt-1">{{ $izin->keterangan ?: '—' }}</p>
        </div>
      </div>

      @if($izin->status!=='pending')
        <div class="mt-5 grid sm:grid-cols-2 gap-4">
          <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 p-4">
            <p class="text-[11px] text-emerald-700">Diproses</p>
            <p class="font-medium text-emerald-700">
              {{ $izin->approved_at ? \Carbon\Carbon::parse($izin->approved_at)->translatedFormat('d M Y · H:i') : '—' }}
              @if($izin->approved_by) · oleh #{{ $izin->approved_by }} @endif
            </p>
          </div>
          @if($izin->status==='rejected')
            <div class="rounded-xl bg-rose-50 ring-1 ring-rose-200 p-4">
              <p class="text-[11px] text-rose-700">Alasan penolakan</p>
              <p class="font-medium text-rose-700">{{ $izin->reject_reason ?: '—' }}</p>
            </div>
          @endif
        </div>
      @endif
    </section>

    <aside class="rounded-2xl bg-white ring-1 ring-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-3">Bukti Lampiran</h3>
      @if($thumb)
        <a href="{{ \Illuminate\Support\Facades\Storage::url($thumb) }}" target="_blank" class="block">
          <img src="{{ \Illuminate\Support\Facades\Storage::url($thumb) }}" class="rounded-xl ring-1 ring-slate-200 w-full object-cover" alt="Bukti">
        </a>
        <a href="{{ \Illuminate\Support\Facades\Storage::url($thumb) }}" download
           class="mt-3 inline-flex items-center gap-2 text-sm text-indigo-700 hover:underline">
          Unduh bukti →
        </a>
      @else
        <p class="text-sm text-slate-500">Tidak ada lampiran.</p>
      @endif
      <p class="mt-4 text-[11px] text-slate-500 leading-relaxed">
        Pastikan bukti jelas. Jika kurang valid, gunakan tombol <b>Tolak</b> dan jelaskan alasannya.
      </p>
    </aside>
  </div>

  <script>
    function askReason(form){
      const val = prompt('Masukkan alasan penolakan (opsional):','');
      if(val===null) return false;
      form.querySelector('input[name="reject_reason"]').value = val || '';
      return true;
    }
  </script>
@endsection
