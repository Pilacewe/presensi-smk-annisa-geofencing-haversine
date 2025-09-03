@extends('layouts.admin')

@section('title','Akun Piket Baru')

@section('content')
  <form method="POST" action="{{ route('admin.piket.store') }}" class="max-w-xl rounded-2xl bg-white ring-1 ring-slate-200 p-6">
    @csrf
    <div class="grid gap-4">
      <div>
        <label class="text-sm text-slate-600">Nama</label>
        <input name="name" value="{{ old('name') }}" class="mt-1 rounded-xl border-slate-300 w-full" required>
        @error('name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm text-slate-600">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 rounded-xl border-slate-300 w-full" required>
        @error('email')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-sm text-slate-600">Password</label>
        <input name="password" type="password" class="mt-1 rounded-xl border-slate-300 w-full" required>
        @error('password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded" checked id="is_active">
        <label for="is_active" class="text-sm">Aktif</label>
      </div>
      <div class="pt-2 flex gap-2">
        <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Simpan</button>
        <a href="{{ route('admin.piket.index') }}" class="px-4 py-2 rounded-xl bg-slate-100">Batal</a>
      </div>
    </div>
  </form>
@endsection
