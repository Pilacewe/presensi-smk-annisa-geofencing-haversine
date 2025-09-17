@extends('layouts.kepsek')
@section('title','Account')

@section('content')
@php
  $avatar = $user?->avatar_path
      ? asset('storage/'.$user->avatar_path)
      : 'https://ui-avatars.com/api/?name='.urlencode($user?->name ?? 'Kepsek').'&background=6366F1&color=fff&size=160';
@endphp

<div class="grid gap-6 md:grid-cols-3 max-w-6xl">

  {{-- Panel profil singkat --}}
  <section class="bg-white rounded-2xl border shadow-sm p-6">
    <div class="flex items-center gap-4">
      <img src="{{ $avatar }}" alt="avatar" class="w-16 h-16 rounded-full object-cover border">
      <div class="min-w-0">
        <div class="text-base font-semibold truncate">{{ $user->name }}</div>
        <div class="text-sm text-slate-500 truncate">{{ $user->jabatan ?? 'Kepala Sekolah' }}</div>
      </div>
    </div>

    <div class="mt-4 grid gap-2 text-sm">
      <div class="flex items-center justify-between">
        <span class="text-slate-500">Role</span>
        <span class="px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700">
          {{ strtoupper($user->role ?? 'KEPSEK') }}
        </span>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-slate-500">Status</span>
        <span class="px-2 py-0.5 rounded-md text-xs font-medium {{ ($user->is_active ?? 1) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
          {{ ($user->is_active ?? 1) ? 'Aktif' : 'Nonaktif' }}
        </span>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-slate-500">Email</span>
        <span class="truncate max-w-[10rem] text-slate-700">{{ $user->email }}</span>
      </div>
    </div>

    <p class="mt-4 text-xs text-slate-500">
      Tip: Kosongkan kolom password jika tidak ingin mengubah kata sandi.
    </p>
  </section>

  {{-- Form pengaturan akun --}}
  <section class="md:col-span-2">
    @if(session('ok'))
      <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800">
        {{ session('ok') }}
      </div>
    @endif

    <form action="{{ route('kepsek.account.update') }}" method="POST"
          class="bg-white p-6 rounded-2xl border shadow-sm grid gap-4">
      @csrf @method('PUT')

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-slate-600">Nama</label>
          <input name="name" value="{{ old('name',$user->name) }}"
                 class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          @error('name')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
        </div>

        <div>
          <label class="block text-sm text-slate-600">Jabatan</label>
          <input name="jabatan" value="{{ old('jabatan',$user->jabatan) }}"
                 class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          @error('jabatan')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
        </div>
      </div>

      <div>
        <label class="block text-sm text-slate-600">Email</label>
        <input name="email" type="email" value="{{ old('email',$user->email) }}"
               class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        @error('email')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm text-slate-600">Password (opsional)</label>
        <div class="mt-1 relative">
          <input id="pass" name="password" type="password" autocomplete="new-password"
                 class="w-full border rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                 placeholder="Kosongkan jika tidak ganti">
          <button type="button" id="togglePass"
                  class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-slate-500 hover:text-slate-700"
                  aria-label="Tampilkan/Sembunyikan password">
            <!-- eye icon -->
            <svg id="eyeOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-width="1.8" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3.2" stroke-width="1.8"/>
            </svg>
          </button>
        </div>
        @error('password')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
      </div>

      <div class="pt-2 flex items-center gap-2">
        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
          Simpan
        </button>
        <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">
          Batal
        </a>
      </div>
    </form>
  </section>
</div>

{{-- Toggle show/hide password --}}
<script>
  const pass = document.getElementById('pass');
  const btn  = document.getElementById('togglePass');
  btn?.addEventListener('click', () => {
    pass.type = pass.type === 'password' ? 'text' : 'password';
  });
</script>
@endsection
