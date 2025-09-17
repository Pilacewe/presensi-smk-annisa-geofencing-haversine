@extends('layouts.admin')
@section('title', $mode==='create' ? 'Buat Akun Kepsek' : 'Edit Kepsek')

@section('content')
<div class="max-w-screen-md lg:max-w-screen-lg mx-auto">
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="rounded-3xl overflow-hidden border bg-white shadow-sm">
    <div class="h-28 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-700"></div>

    <form method="POST" action="{{ $action }}" class="p-6 lg:p-8">
      @csrf
      @if($method!=='POST') @method($method) @endif

      <div class="-mt-20 mb-6 flex items-center gap-4">
        <div class="w-20 h-20 rounded-2xl bg-white ring-4 ring-white shadow grid place-items-center">
          <div class="w-full h-full rounded-xl bg-indigo-100 text-indigo-700 grid place-items-center text-xl font-extrabold">
            {{ \Illuminate\Support\Str::of($user->name ?? 'KS')->substr(0,2)->upper() }}
          </div>
        </div>
        <div>
          <h2 class="text-xl lg:text-2xl font-bold text-slate-900">
            {{ $mode==='create' ? 'Buat Akun Kepsek' : 'Edit Kepsek' }}
          </h2>
          <p class="text-sm text-slate-500">Email wajib valid. Password opsional saat edit.</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div>
          <label class="block text-sm text-slate-600">Nama</label>
          <input name="name" value="{{ old('name',$user->name) }}" required
                 class="mt-1 w-full rounded-xl border px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"/>
        </div>
        <div>
          <label class="block text-sm text-slate-600">Email</label>
          <input type="email" name="email" value="{{ old('email',$user->email) }}" required
                 class="mt-1 w-full rounded-xl border px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"/>
        </div>
        <div>
          <label class="block text-sm text-slate-600">Jabatan</label>
          <input name="jabatan" value="{{ old('jabatan',$user->jabatan ?? 'Kepala Sekolah') }}"
                 class="mt-1 w-full rounded-xl border px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"/>
        </div>

        @if($mode==='edit')
          <div>
            <label class="block text-sm text-slate-600 mb-1">Status</label>
            <label class="mr-5 inline-flex items-center gap-2 text-sm">
              <input type="radio" name="is_active" value="1" {{ old('is_active',$user->is_active ?? 1)==1?'checked':'' }}>
              <span>Aktif</span>
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="radio" name="is_active" value="0" {{ old('is_active',$user->is_active ?? 1)==0?'checked':'' }}>
              <span>Nonaktif</span>
            </label>
          </div>
        @else
          <div class="lg:col-span-1"></div>
        @endif

        <div>
          <label class="block text-sm text-slate-600">{{ $mode==='create' ? 'Password' : 'Password (opsional)' }}</label>
          <input type="password" name="password" {{ $mode==='create' ? 'required' : '' }}
                 class="mt-1 w-full rounded-xl border px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"/>
        </div>
        <div>
          <label class="block text-sm text-slate-600">Konfirmasi Password</label>
          <input type="password" name="password_confirmation" {{ $mode==='create' ? 'required' : '' }}
                 class="mt-1 w-full rounded-xl border px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"/>
        </div>
      </div>

      <div class="mt-6 flex items-center gap-2">
        <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700">
          {{ $mode==='create' ? 'Simpan' : 'Update' }}
        </button>
        <a href="{{ route('admin.kepsek.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-sm hover:bg-slate-200">Batal</a>
      </div>
    </form>
  </div>
</div>
@endsection
