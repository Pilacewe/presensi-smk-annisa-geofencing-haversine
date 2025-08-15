<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Dashboard Piket')</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
@php
  $u = auth()->user();
  $avatar = $u?->avatar_path
    ? asset('storage/'.$u->avatar_path)
    : 'https://ui-avatars.com/api/?name='.urlencode($u?->name ?? 'User').'&background=0EA5E9&color=fff&size=128';
@endphp
<body class="bg-slate-100 text-slate-800 antialiased">

  {{-- NAVBAR (desktop) --}}
  <header class="hidden md:block sticky top-0 z-40 bg-white/90 backdrop-blur border-b">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-sky-500 grid place-items-center text-white font-bold">P</div>
        <div>
          <p class="text-xs text-slate-500 leading-3">Sistem</p>
          <p class="font-semibold leading-3">Dashboard Piket</p>
        </div>
      </div>

      <nav class="flex items-center gap-6 text-sm">
        <a href="{{ route('piket.dashboard') }}" class="{{ request()->routeIs('piket.dashboard') ? 'text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Beranda</a>
        <a href="{{ route('piket.cek') }}"       class="{{ request()->routeIs('piket.cek') ? 'text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Ngecek Guru</a>
        <a href="{{ route('piket.rekap') }}"     class="{{ request()->routeIs('piket.rekap') ? 'text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Rekap Harian</a>
        <a href="{{ route('piket.riwayat') }}"   class="{{ request()->routeIs('piket.riwayat') ? 'text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Riwayat</a>

        {{-- Link ke UI Presensi (absen pribadi) --}}
        <a href="{{ route('presensi.index') }}"  class="text-sky-700 hover:underline">Presensi</a>

        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit') ? 'text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Akun</a>
        <form method="POST" action="{{ route('logout') }}" class="ml-2">@csrf
          <button class="px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-700">Logout</button>
        </form>
      </nav>

      <div class="flex items-center gap-3">
        <img src="{{ $avatar }}" class="w-9 h-9 rounded-full object-cover border" alt="avatar">
        <div class="leading-4">
          <p class="text-xs text-slate-500">Piket</p>
          <p class="text-sm font-medium">{{ $u?->name }}</p>
        </div>
      </div>
    </div>
  </header>

  {{-- NAV BOTTOM (mobile – konsisten dengan UI presensi) --}}
  <div class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t">
    <div class="max-w-3xl mx-auto px-4 h-14 grid grid-cols-5 text-xs">
      <a href="{{ route('piket.dashboard') }}" class="grid place-items-center {{ request()->routeIs('piket.dashboard')?'text-slate-900':'text-slate-500' }}">Beranda</a>
      <a href="{{ route('piket.cek') }}"       class="grid place-items-center {{ request()->routeIs('piket.cek')?'text-slate-900':'text-slate-500' }}">Cek</a>
      <a href="{{ route('presensi.index') }}"  class="grid place-items-center text-sky-700 font-semibold">Presensi</a>
      <a href="{{ route('piket.rekap') }}"     class="grid place-items-center {{ request()->routeIs('piket.rekap')?'text-slate-900':'text-slate-500' }}">Rekap</a>
      <a href="{{ route('piket.riwayat') }}"   class="grid place-items-center {{ request()->routeIs('piket.riwayat')?'text-slate-900':'text-slate-500' }}">Riwayat</a>
    </div>
  </div>

  <main class="max-w-7xl mx-auto px-4 md:px-6 pt-6 pb-20 md:pb-10">
    @yield('content')
  </main>

</body>
</html>
