@extends('layouts.admin')
@section('title','Edit Guru')

@section('content')
  <div class="max-w-3xl">
    <form action="{{ route('admin.guru.update',$user) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-5">
      @csrf @method('PATCH')

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold">Edit Akun</h2>
          <p class="text-xs text-slate-500">{{ $user->name }} · {{ $user->email }}</p>
        </div>
        <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : 'https://api.dicebear.com/8.x/initials/svg?seed='.urlencode($user->name) }}"
             class="w-12 h-12 rounded-full ring-1 ring-slate-200 object-cover" alt="">
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
          <input name="name" value="{{ old('name',$user->name) }}" class="mt-1 w-full rounded-lg border-slate-300" required>
        </div>
        <div>
          <label class="text-sm font-medium">Email</label>
          <input type="email" name="email" value="{{ old('email',$user->email) }}" class="mt-1 w-full rounded-lg border-slate-300" required>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Password baru (opsional)</label>
          <input type="password" name="password" class="mt-1 w-full rounded-lg border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium">Konfirmasi password</label>
          <input type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border-slate-300">
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Jabatan</label>
          <input name="jabatan" value="{{ old('jabatan',$user->jabatan) }}" class="mt-1 w-full rounded-lg border-slate-300">
        </div>
        <div class="flex items-end gap-3">
          <label class="text-sm font-medium">
            <input type="checkbox" name="is_active" value="1" class="mr-2" {{ old('is_active',$user->is_active) ? 'checked' : '' }}>
            Aktif
          </label>
        </div>
      </div>

      <div>
        <label class="text-sm font-medium">Ganti Avatar (opsional)</label>
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
