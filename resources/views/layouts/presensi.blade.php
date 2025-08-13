<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','Presensi Pegawai')</title>
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
    : 'https://ui-avatars.com/api/?name='.urlencode($u?->name ?? 'User').'&background=6366F1&color=fff&size=160';

  $is = fn(...$r) => request()->routeIs($r);
@endphp
<body class="bg-slate-100 text-slate-800 antialiased">

  {{-- ================= MOBILE: drawer backdrop (tetap) ================= --}}
  <div id="drawerBackdrop" class="fixed inset-0 bg-slate-900/40 hidden md:hidden"></div>

  <div class="min-h-screen flex flex-col">

    {{-- ================= DESKTOP NAVBAR (baru) ================= --}}
    <header class="hidden md:block sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
      <div class="mx-auto max-w-7xl px-4">
        <div class="h-16 flex items-center justify-between gap-4">
          {{-- Brand --}}
          <a href="{{ route('presensi.index') }}" class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 grid place-items-center text-white font-bold">P</div>
            <div class="leading-tight">
              <p class="text-[11px] tracking-wide text-slate-500">Sistem</p>
              <p class="font-semibold">Presensi Pegawai</p>
            </div>
          </a>

          {{-- Menu tengah --}}
          <nav class="flex items-center gap-1">
            @php
              $link = function(string $href, string|array $match, string $label) use($is){
                $active = $is(...(array)$match);
                $cls = $active
                  ? 'text-slate-900 bg-slate-100 after:opacity-100'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 after:opacity-0';
                return <<<HTML
                  <a href="{$href}"
                     class="relative inline-flex items-center gap-2 px-3 py-2 rounded-lg transition
                            after:absolute after:left-3 after:-bottom-0.5 after:h-0.5 after:w-[calc(100%-1.5rem)]
                            after:bg-slate-900 after:rounded-full after:transition-opacity {$cls}">
                    <span class="text-sm font-medium">{$label}</span>
                  </a>
                HTML;
              };
            @endphp

            {!! $link(route('presensi.index'), 'presensi.index', 'Beranda',
              '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5"/></svg>') !!}

            {!! $link(route('presensi.riwayat'), 'presensi.riwayat', 'Riwayat',
              '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M21 12a9 9 0 1 1-9-9"/><path stroke-width="1.8" d="M12 7v6l4 2"/></svg>') !!}

            {!! $link(route('izin.index'), 'izin.*', 'Izin',
              '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M6 20h12a2 2 0 0 0 2-2V8l-5-5H6a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/><path stroke-width="1.8" d="M14 3v5h5"/></svg>') !!}

            {!! $link(route('profile.edit'), 'profile.edit', 'Akun',
              '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4" stroke-width="1.8"/></svg>') !!}
          </nav>

          {{-- User kanan --}}
          <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right leading-4">
              <p class="text-[11px] text-slate-500">{{ $u?->jabatan ?? ucfirst($u?->role ?? 'Pegawai') }}</p>
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

    {{-- ================= MOBILE TOPBAR + DRAWER (tetap seperti sebelumnya) ================= --}}
    <div class="md:hidden sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200">
      <div class="h-14 px-4 flex items-center justify-between">
        <button id="btnOpen" class="p-2 rounded-lg border border-slate-200">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="text-sm font-semibold truncate">@yield('title','Presensi Pegawai')</div>
        <img src="{{ $avatar }}" class="w-8 h-8 rounded-full object-cover border" alt="avatar">
      </div>
    </div>

    {{-- MOBILE SIDEBAR (hanya tampil di < md) --}}
    <aside id="sidebar"
      class="md:hidden fixed top-0 h-full w-72 -left-72 z-40 transition-all duration-300
             bg-white/80 backdrop-blur border-r border-slate-200">
      {{-- Brand --}}
      <div class="px-5 h-16 flex items-center gap-3 border-b border-slate-200/70">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 grid place-items-center text-white font-bold">P</div>
        <div class="leading-tight">
          <p class="text-[11px] uppercase tracking-wider text-slate-500">Sistem</p>
          <p class="font-semibold">Presensi Pegawai</p>
        </div>
      </div>

      {{-- Menu --}}
      <nav class="px-3 py-4 space-y-1">
        @php
          $m = function(string $href, string|array $match, string $label, string $svg) use($is){
            $active = $is(...(array)$match);
            $cls = $active ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100';
            $bar = $active ? '<span class="absolute left-0 top-0 h-full w-1 bg-indigo-400/90 rounded-r"></span>' : '';
            return <<<HTML
              <a href="{$href}" class="relative flex items-center gap-3 px-4 py-2.5 rounded-xl {$cls}">
                {$bar}{$svg}<span class="text-sm font-medium">{$label}</span>
              </a>
            HTML;
          };
        @endphp

        {!! $m(route('presensi.index'), 'presensi.index', 'Beranda',
          '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5"/></svg>') !!}

        {!! $m(route('presensi.formMasuk'), 'presensi.formMasuk', 'Presensi Masuk',
          '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M4 12h12M10 6l6 6-6 6"/></svg>') !!}

        {!! $m(route('presensi.formKeluar'), 'presensi.formKeluar', 'Presensi Keluar',
          '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M20 12H8M14 6l-6 6 6 6"/></svg>') !!}

        {!! $m(route('presensi.riwayat'), 'presensi.riwayat', 'Riwayat',
          '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M21 12a9 9 0 1 1-9-9"/><path stroke-width="1.8" d="M12 7v6l4 2"/></svg>') !!}

        {!! $m(route('izin.index'), 'izin.*', 'Izin',
          '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M6 20h12a2 2 0 0 0 2-2V8l-5-5H6a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/><path stroke-width="1.8" d="M14 3v5h5"/></svg>') !!}

        {!! $m(route('profile.edit'), 'profile.edit', 'Akun',
          '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4" stroke-width="1.8"/></svg>') !!}

        <div class="mt-3 px-4">
          <div class="flex items-center gap-3">
            <img src="{{ $avatar }}" class="w-10 h-10 rounded-full object-cover border" alt="avatar">
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ $u?->name ?? '-' }}</p>
              <p class="text-xs text-slate-500 truncate">{{ $u?->jabatan ?? ucfirst($u?->role ?? 'Pegawai') }}</p>
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

    {{-- ================= SUBHEADER (judul + actions) ================= --}}
    <section class="hidden md:block w-full bg-gradient-to-r from-slate-50 to-transparent border-b border-slate-200/70">
  <div class="mx-auto max-w-7xl px-4 py-6 flex items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-semibold">@yield('title','Presensi Pegawai')</h1>
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

  {{-- ============ JS: mobile drawer tetap seperti semula ============ --}}
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
