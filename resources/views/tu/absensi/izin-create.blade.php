@extends('layouts.tu')

@section('title','Buat Izin (TU)')
@section('subtitle','Ajukan izin/sakit untuk akun TU Anda')

@section('content')
<section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 max-w-3xl">
  <h2 class="text-lg font-semibold mb-1">Form Pengajuan Izin</h2>
  <p class="text-sm text-slate-500 mb-4">Lengkapi data di bawah ini lalu kirim untuk diproses.</p>

  <form method="POST" action="{{ route('tu.absensi.izinStore') }}" class="space-y-5">
    @csrf

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Tanggal Mulai</label>
        <input type="date" name="tgl_mulai"
               value="{{ old('tgl_mulai', now()->timezone(config('app.timezone'))->toDateString()) }}"
               class="mt-1 w-full rounded-lg border-slate-300 @error('tgl_mulai') border-rose-300 ring-1 ring-rose-200 @enderror">
        @error('tgl_mulai') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="text-sm font-medium">Tanggal Selesai <span class="text-slate-400 text-xs">(opsional)</span></label>
        <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}"
               class="mt-1 w-full rounded-lg border-slate-300 @error('tgl_selesai') border-rose-300 ring-1 ring-rose-200 @enderror">
        @error('tgl_selesai') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Jenis</label>
        <select name="jenis"
                class="mt-1 w-full rounded-lg border-slate-300 @error('jenis') border-rose-300 ring-1 ring-rose-200 @enderror">
          <option value="izin"  @selected(old('jenis')==='izin')>Izin</option>
          <option value="sakit" @selected(old('jenis')==='sakit')>Sakit</option>
        </select>
        @error('jenis') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
      </div>
    </div>

    <div>
      <label class="text-sm font-medium">Keterangan <span class="text-slate-400 text-xs">(opsional)</span></label>
      <textarea name="keterangan" rows="4"
                class="mt-1 w-full rounded-lg border-slate-300 @error('keterangan') border-rose-300 ring-1 ring-rose-200 @enderror"
                placeholder="Contoh: keperluan keluarga / kontrol kesehatan / dsb.">{{ old('keterangan') }}</textarea>
      @error('keterangan') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3">
      <a href="{{ route('tu.absensi.index', ['tab'=>'izin']) }}"
         class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm">Kembali</a>
      <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800">
        Kirim Pengajuan
      </button>
    </div>
  </form>
</section>
@endsection
