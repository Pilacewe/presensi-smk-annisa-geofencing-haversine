@extends('layouts.tu')
@section('title','Ajukan Izin')

@section('content')
  <div class="max-w-xl">
    <form method="POST" action="{{ route('tu.self.izin.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-4">
      @csrf
      <div>
        <label class="text-sm font-medium">Tanggal</label>
        <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300">
        @error('tanggal')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm font-medium">Alasan</label>
        <textarea name="alasan" rows="3" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Izin/sakit/dll">{{ old('alasan') }}</textarea>
        @error('alasan')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm font-medium">Lampiran (opsional: jpg/png/pdf, max 2MB)</label>
        <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 w-full text-sm">
        @error('lampiran')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
      <div class="flex gap-2">
        <a href="{{ route('tu.self.izin.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">Batal</a>
        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Kirim Pengajuan</button>
      </div>
    </form>
  </div>
@endsection
