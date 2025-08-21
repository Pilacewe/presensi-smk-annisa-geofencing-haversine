<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','Dashboard Piket') — Presensi</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <style>
    html{scrollbar-gutter:stable both-edges}
    @supports not (scrollbar-gutter:stable){body{overflow-y:scroll}}
  </style>
</head>
@php
  $u = auth()->user();
  $avatar = $u?->avatar_path
      ? asset('storage/'.$u->avatar_path)
      : 'https://ui-avatars.com/api/?name='.urlencode($u?->name ?? 'User').'&background=0EA5E9&color=fff&size=160';

  $is = fn(...$r) => request()->routeIs($r);
  $link = function(string $href, string|array $match, string $label) use($is) {
    $active = $is(...(array)$match);
    $cls = $active
      ? 'text-slate-900 bg-slate-100 after:opacity-100'
      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 after:opacity-0';
    return <<<HTML
      <a href="{$href}"
         class="relative inline-flex items-center px-3 py-2 rounded-lg transition
                after:absolute after:left-3 after:-bottom-0.5 after:h-0.5 after:w-[calc(100%-1.5rem)]
                after:bg-slate-900 after:rounded-full after:transition-opacity {$cls}">
        <span class="text-sm font-medium">{$label}</span>
      </a>
    HTML;
  };
@endphp
<body class="bg-slate-100 text-slate-800 antialiased">

  {{-- Backdrop drawer (mobile) --}}
  <div id="drawerBackdrop" class="fixed inset-0 bg-slate-900/40 hidden md:hidden"></div>

  <div class="min-h-screen flex flex-col">

    {{-- ================= DESKTOP NAVBAR (text only) ================= --}}
    <header class="hidden md:block sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
      <div class="mx-auto max-w-7xl px-4">
        <div class="h-16 flex items-center justify-between gap-4">

          {{-- Brand --}}
          <a href="{{ route('piket.dashboard') }}" class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-sky-500 grid place-items-center text-white font-bold">P</div>
            <div class="leading-tight">
              <p class="text-[11px] tracking-wide text-slate-500">Sistem</p>
              <p class="font-semibold">Presensi Pegawai</p>
            </div>
          </a>

          {{-- Menu tengah (tanpa ikon) --}}
          <nav class="flex items-center gap-1">
            {!! $link(route('piket.dashboard'),   'piket.dashboard',   'Dashboard') !!}
            {!! $link(route('piket.cek'),         'piket.cek',         'Ngecek Guru') !!}
            {!! $link(route('piket.rekap'),       'piket.rekap',       'Rekap Harian') !!}
            {!! $link(route('piket.absen.create'),     'piket.absen.create',     'Presensi Manual') !!}
           
            {!! $link(route('piket.riwayat'),     'piket.riwayat',     'Riwayat') !!}
      
          </nav>

          {{-- User kanan: avatar -> dropdown (Profil / Logout) --}}
          <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right leading-4">
              <p class="text-[11px] text-slate-500">{{ $u?->jabatan ?? 'Piket' }}</p>
              <p class="text-sm font-medium truncate max-w-[12rem]">{{ $u?->name ?? '-' }}</p>
            </div>

            <details class="relative">
              <summary class="list-none cursor-pointer">
                <img src="{{ $avatar }}" class="w-9 h-9 rounded-full object-cover border" alt="avatar">
              </summary>
              <div class="absolute right-0 mt-2 w-44 rounded-xl border bg-white shadow-lg ring-1 ring-slate-200 overflow-hidden">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">Profil</a>
                <div class="h-px bg-slate-200"></div>
                <form method="POST" action="{{ route('logout') }}" class="p-1">
                  @csrf
                  <button class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 rounded-md">Logout</button>
                </form>
              </div>
            </details>
          </div>

        </div>
      </div>
    </header>

    {{-- ================= MOBILE TOPBAR + DRAWER ================= --}}
    <div class="md:hidden sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200">
      <div class="h-14 px-4 flex items-center justify-between">
        <button id="btnOpen" class="p-2 rounded-lg border border-slate-200">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="text-sm font-semibold truncate">@yield('title','Dashboard Piket')</div>
        <img src="{{ $avatar }}" class="w-8 h-8 rounded-full object-cover border" alt="avatar">
      </div>
    </div>

    <aside id="sidebar"
      class="md:hidden fixed top-0 h-full w-72 -left-72 z-40 transition-all duration-300
             bg-white/80 backdrop-blur border-r border-slate-200">
      {{-- Brand --}}
      <div class="px-5 h-16 flex items-center gap-3 border-b border-slate-200/70">
        <div class="w-10 h-10 rounded-2xl bg-sky-500 grid place-items-center text-white font-bold">P</div>
        <div class="leading-tight">
          <p class="text-[11px] uppercase tracking-wider text-slate-500">Sistem</p>
          <p class="font-semibold">Presensi Pegawai</p>
        </div>
      </div>

      {{-- Menu (mobile) --}}
      <nav class="px-3 py-4 space-y-1">
        @php
          $m = function(string $href, string|array $match, string $label) use($is){
            $active = $is(...(array)$match);
            $cls = $active ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100';
            $bar = $active ? '<span class="absolute left-0 top-0 h-full w-1 bg-sky-400/90 rounded-r"></span>' : '';
            return <<<HTML
              <a href="{$href}" class="relative flex items-center gap-3 px-4 py-2.5 rounded-xl {$cls}">
                {$bar}<span class="text-sm font-medium">{$label}</span>
              </a>
            HTML;
          };
        @endphp

        {!! $m(route('piket.dashboard'), 'piket.dashboard', 'Dashboard') !!}
        {!! $m(route('piket.cek'),       'piket.cek',       'Ngecek Guru') !!}
        {!! $m(route('piket.rekap'),     'piket.rekap',     'Rekap Harian') !!}
        {!! $m(route('piket.absen.create'),   'piket.absen.create',   'Presensi Manual') !!}
        {!! $m(route('piket.riwayat'),   'piket.riwayat',   'Riwayat') !!}

        <div class="mt-3 px-4">
          <div class="flex items-center gap-3">
            <img src="{{ $avatar }}" class="w-10 h-10 rounded-full object-cover border" alt="avatar">
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ $u?->name ?? '-' }}</p>
              <p class="text-xs text-slate-500 truncate">{{ $u?->jabatan ?? 'Piket' }}</p>
            </div>
          </div>
          <div class="mt-3 flex gap-2">
            <a href="{{ route('profile.edit') }}" class="flex-1 text-center px-3 py-2 rounded-lg text-sm bg-slate-100 hover:bg-slate-200">Profil</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
              <button class="px-3 py-2 rounded-lg text-sm bg-rose-600 text-white hover:bg-rose-700">Logout</button>
            </form>
          </div>
        </div>
      </nav>
    </aside>

    {{-- ================= SUBHEADER (desktop only) ================= --}}
    <section class="hidden md:block w-full bg-gradient-to-r from-slate-50 to-transparent border-b border-slate-200/70">
      <div class="mx-auto max-w-7xl px-4 py-6 flex items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-semibold">@yield('title','Dashboard Piket')</h1>
          @hasSection('subtitle')
            <p class="text-xs text-slate-500">@yield('subtitle')</p>
          @endif
        </div>
        <div>@yield('actions')</div>
      </div>
    </section>

    {{-- ================= CONTENT ================= --}}
    <main class="mx-auto max-w-7xl px-4 py-8 w-full">
      @yield('content')
    </main>

    <footer class="py-10 text-center text-xs text-slate-500">
      &copy; {{ date('Y') }} WR Media • Presensi v1.0
    </footer>
  </div>

  {{-- Drawer JS --}}
  <script>
    const drawer   = document.getElementById('sidebar');
    const backdrop = document.getElementById('drawerBackdrop');
    const openBtn  = document.getElementById('btnOpen');

    function openDrawer(){ drawer.style.left='0'; backdrop.classList.remove('hidden'); }
    function closeDrawer(){ drawer.style.left='-18rem'; backdrop.classList.add('hidden'); }

    openBtn?.addEventListener('click', openDrawer);
    backdrop?.addEventListener('click', closeDrawer);
    window.addEventListener('resize', ()=>{ if (window.innerWidth>=768) closeDrawer(); });
  </script>
</body>
</html>