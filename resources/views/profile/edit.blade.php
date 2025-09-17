@php
    // Pilih layout berdasarkan role
    $layout = auth()->user()?->role === 'tu'
        ? 'layouts.tu'          // navbar TU (dashboard TU)
        : 'layouts.presensi';   // navbar pegawai (guru/piket)
@endphp

@php
    // Pilih layout berdasarkan role
    $layout = auth()->user()?->role === 'piket'
        ? 'layouts.piket'          // navbar piket (dashboard piket)
        : 'layouts.presensi';   // navbar pegawai (guru/piket)
@endphp

@extends($layout)
  
@section('title','Akun')

@section('content')
@php
  $user = Auth::user();
  $avatar = $user->avatar_path ? asset('storage/'.$user->avatar_path)
           : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=6366F1&color=fff&size=160';
@endphp

<div class="grid lg:grid-cols-3 gap-6">

  {{-- Kartu Profil --}}
  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
    <div class="flex items-center gap-4">
      <img src="{{ $avatar }}" class="w-20 h-20 rounded-full object-cover border" alt="avatar">
      <div>
        <p class="text-lg font-semibold">{{ $user->name }}</p>
        <p class="text-sm text-slate-500">{{ $user->email }}</p>
        <p class="text-xs mt-1 text-slate-500">{{ $user->jabatan ?? '—' }}</p>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-4">Unggah foto profil agar lebih mudah dikenali.</p>
  </section>

  {{-- Update Profil --}}
  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 lg:col-span-2">
    @if (session('status')==='profile-updated')
      <div class="mb-4 rounded-lg bg-emerald-50 border-l-4 border-emerald-500 p-3 text-emerald-700 text-sm">
        Profil berhasil diperbarui.
      </div>
    @endif

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
      @csrf @method('patch')

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Nama</label>
          <input name="name" value="{{ old('name',$user->name) }}" class="mt-1 w-full rounded-lg border-slate-300">
          @error('name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="text-sm font-medium">Jabatan</label>
          <input name="jabatan" value="{{ old('jabatan',$user->jabatan) }}" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Guru / TU / Piket / ...">
          @error('jabatan')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
      </div>

      <div>
        <label class="text-sm font-medium">Email</label>
        <input name="email" type="email" value="{{ old('email',$user->email) }}" class="mt-1 w-full rounded-lg border-slate-300">
        @error('email')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>

      <div>
        <label class="text-sm font-medium">Foto Profil (jpg/png, maks 2MB)</label>
        <input type="file" name="avatar" accept=".jpg,.jpeg,.png"
          class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-slate-900 file:text-white hover:file:bg-slate-700">
        @error('avatar')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>

      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Simpan</button>
    </form>
  </section>

  {{-- Ubah Password --}}
  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 lg:col-span-2">
    <h3 class="text-sm font-semibold mb-3">Ubah Password</h3>
    <form method="post" action="{{ route('password.update') }}" class="grid sm:grid-cols-3 gap-4">
      @csrf @method('put')
      <input name="current_password" type="password" placeholder="Password sekarang" class="rounded-lg border-slate-300">
      <input name="password" type="password" placeholder="Password baru" class="rounded-lg border-slate-300">
      <input name="password_confirmation" type="password" placeholder="Ulangi password baru" class="rounded-lg border-slate-300">
      <div class="sm:col-span-3">
        <button class="px-4 py-2 rounded-lg bg-slate-900 text-white">Update Password</button>
      </div>
    </form>
  </section>

</div>
@endsection
