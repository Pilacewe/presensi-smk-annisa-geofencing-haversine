@extends('layouts.admin')
@section('title','Tambah Guru')

@section('content')
  <div class="max-w-3xl">
    <form action="{{ route('admin.guru.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-5">
      @csrf

      <div>
        <h2 class="text-lg font-semibold">Buat Akun Guru</h2>
        <p class="text-xs text-slate-500">Lengkapi data di bawah ini.</p>
      </div>

      @if ($errors->any())
        <div class="rounded-lg border-l-4 border-rose-500 bg-rose-50 p-3 text-rose-700 text-sm">
          <ul class="list-disc ml-4">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Nama</label>
          <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-300" required>
        </div>
        <div>
          <label class="text-sm font-medium">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300" required>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Password</label>
          <input type="password" name="password" class="mt-1 w-full rounded-lg border-slate-300" required>
        </div>
        <div>
          <label class="text-sm font-medium">Konfirmasi Password</label>
          <input type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border-slate-300" required>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Jabatan (opsional)</label>
          <input name="jabatan" value="{{ old('jabatan') }}" class="mt-1 w-full rounded-lg border-slate-300">
        </div>
        <div class="flex items-end gap-3">
          <label class="text-sm font-medium">
            <input type="checkbox" name="is_active" value="1" class="mr-2" {{ old('is_active',1) ? 'checked' : '' }}>
            Aktif
          </label>
        </div>
      </div>

      <div>
        <label class="text-sm font-medium">Avatar (opsional)</label>
        <input type="file" name="avatar" accept="image/*"
               class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-slate-900 file:text-white hover:file:bg-slate-700">
      </div>

      <div class="flex items-center gap-3">
        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Simpan</button>
        <a href="{{ route('admin.guru.index') }}" class="text-slate-600 hover:underline text-sm">Batal</a>
      </div>
    </form>
  </div>
@endsection
