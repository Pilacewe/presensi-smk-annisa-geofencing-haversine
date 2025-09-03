@extends('layouts.admin')

@section('title','Edit TU')
@section('subtitle','Perbarui data akun TU')

@section('content')
  <form action="{{ route('admin.tu.update',$user) }}" method="POST" enctype="multipart/form-data"
        class="max-w-3xl bg-white rounded-2xl ring-1 ring-slate-200 p-6 space-y-5">
    @csrf @method('PATCH')

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
        <input type="text" name="name" class="mt-1 w-full rounded-lg border-slate-300" value="{{ old('name',$user->name) }}" required>
      </div>
      <div>
        <label class="text-sm font-medium">Email</label>
        <input type="email" name="email" class="mt-1 w-full rounded-lg border-slate-300" value="{{ old('email',$user->email) }}" required>
      </div>
      <div>
        <label class="text-sm font-medium">Jabatan</label>
        <input type="text" name="jabatan" class="mt-1 w-full rounded-lg border-slate-300" value="{{ old('jabatan',$user->jabatan) }}">
      </div>
      <div>
        <label class="text-sm font-medium">Status</label>
        <select name="is_active" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="1" @selected($user->is_active==1)>Aktif</option>
          <option value="0" @selected($user->is_active==0)>Nonaktif</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Password baru (opsional)</label>
        <input type="text" name="password" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Kosongkan jika tidak ganti">
      </div>
      <div>
        <label class="text-sm font-medium">Avatar (opsional)</label>
        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="mt-1 w-full text-sm">
        @if($user->avatar_path)
          <img src="{{ asset('storage/'.$user->avatar_path) }}" class="h-14 mt-2 rounded-lg ring-1 ring-slate-200" alt="avatar">
        @endif
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Simpan</button>
      <a href="{{ route('admin.tu.index') }}" class="text-slate-600 hover:underline text-sm">Batal</a>
    </div>
  </form>
@endsection
