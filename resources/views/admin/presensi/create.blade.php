@extends('layouts.admin')
@section('title','Tambah Presensi')

@section('content')
  <form method="POST" action="{{ route('admin.presensi.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div>
      <label class="text-sm font-medium">Pegawai</label>
      <select name="user_id" class="mt-1 w-full rounded-lg border-slate-300">
        <option value="">— Pilih Pegawai —</option>
        @foreach($users as $u)
          <option value="{{ $u->id }}" @selected(old('user_id')==$u->id)>{{ $u->name }} ({{ strtoupper($u->role) }})</option>
        @endforeach
      </select>
      @error('user_id')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid sm:grid-cols-3 gap-3">
      <div>
        <label class="text-sm font-medium">Tanggal</label>
        <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300">
        @error('tanggal')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm font-medium">Jam Masuk</label>
        <input type="time" name="jam_masuk" value="{{ old('jam_masuk') }}" class="mt-1 w-full rounded-lg border-slate-300">
        @error('jam_masuk')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm font-medium">Jam Keluar</label>
        <input type="time" name="jam_keluar" value="{{ old('jam_keluar') }}" class="mt-1 w-full rounded-lg border-slate-300">
        @error('jam_keluar')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm font-medium">Status</label>
        <select name="status" class="mt-1 w-full rounded-lg border-slate-300">
          @foreach(['hadir','izin','sakit','alfa'] as $s)
            <option value="{{ $s }}" @selected(old('status')==$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
        @error('status')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm font-medium">Keterangan</label>
        <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="mt-1 w-full rounded-lg border-slate-300" placeholder="opsional">
        @error('keterangan')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>
    </div>

    <div class="pt-2">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Simpan</button>
      <a href="{{ route('admin.presensi.index') }}" class="px-4 py-2 rounded-lg border">Batal</a>
    </div>
  </form>
@endsection
