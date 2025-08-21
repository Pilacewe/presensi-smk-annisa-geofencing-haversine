<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','Admin') — Presensi</title>
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
      : 'https://ui-avatars.com/api/?name='.urlencode($u?->name ?? 'Admin').'&background=111827&color=fff&size=160';

  $is = fn(...$r) => request()->routeIs($r);
  $navItem = function(string $href, string|array $match, string $label, string $svg) use($is) {
    $active = $is(...(array)$match);
    $cls = $active
      ? 'bg-slate-900 text-white'
      : 'text-slate-300 hover:bg-slate-800/70 hover:text-white';
    $bar = $active ? '<span class="absolute left-0 top-0 h-full w-1 bg-indigo-400/90 rounded-r"></span>' : '';
    return <<<HTML
      <a href="{$href}" class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl {$cls}">
        {$bar}{$svg}<span class="text-sm font-medium">{$label}</span>
      </a>
    HTML;
  };
@endphp
<body class="bg-slate-100 text-slate-800 antialiased">

  {{-- Backdrop drawer (mobile) --}}
  <div id="drawerBackdrop" class="fixed inset-0 bg-slate-900/40 hidden lg:hidden z-40"></div>

  <div class="min-h-screen flex">

    {{-- ================= SIDEBAR ================= --}}
    <aside id="sidebar"
      class="fixed lg:sticky top-0 h-full lg:h-screen w-72 -left-72 lg:left-0 z-50
             transition-all duration-300
             bg-slate-950 text-slate-200">

      {{-- Brand --}}
      <div class="h-16 px-4 border-b border-white/10 flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 grid place-items-center text-white font-bold">
          P
        </div>
        <div class="leading-tight">
          <p class="text-[11px] tracking-wide text-slate-400">Admin Panel</p>
          <p class="font-semibold text-white">Presensi Pegawai</p>
        </div>
      </div>

      {{-- Menu --}}
      <nav class="px-3 py-4 space-y-1">

        {!! $navItem(route('admin.dashboard'), 'admin.dashboard', 'Dashboard', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-width="1.8" d="M3 12l9-9 9 9"/><path stroke-width="1.8" d="M9 21V9h6v12"/>
          </svg>
        ') !!}

        {{-- Kelola Presensi --}}
        <p class="px-3 mt-4 mb-1 text-[11px] uppercase tracking-wider text-slate-400">Presensi</p>
        {!! $navItem(route('admin.presensi.index'), 'admin.presensi.*', 'Kelola Presensi', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="4" width="18" height="14" rx="2" stroke-width="1.8"/>
            <path stroke-width="1.8" d="M3 8h18M7 12h4"/>
          </svg>
        ') !!}

        {{-- Data Master Pegawai per Role (opsional: tautkan ke halaman manajemen user per role) --}}
        <p class="px-3 mt-4 mb-1 text-[11px] uppercase tracking-wider text-slate-400">Data Pegawai</p>
        {!! $navItem(route('admin.users.index', ['role'=>'guru']),  ['admin.users.index','admin.users.guru'],  'Guru', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="9" cy="7" r="4" stroke-width="1.8"/><path stroke-width="1.8" d="M17 11l4 4-4 4M13 21H3a7 7 0 0 1 14 0"/>
          </svg>
        ') !!}
        {!! $navItem(route('admin.users.index', ['role'=>'tu']),    ['admin.users.tu'],    'TU', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-width="1.8" d="M4 4h16v6H4z"/><path stroke-width="1.8" d="M10 10v10M14 10v10M4 20h16"/>
          </svg>
        ') !!}
        {!! $navItem(route('admin.users.index', ['role'=>'piket']), ['admin.users.piket'], 'Piket', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-width="1.8" d="M12 1v22M3 5h18M3 12h18M3 19h18"/>
          </svg>
        ') !!}
        {!! $navItem(route('admin.users.index', ['role'=>'kepsek']),['admin.users.kepsek'],'Kepsek', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-width="1.8" d="M12 3l9 6-9 6-9-6 9-6z"/><path stroke-width="1.8" d="M12 15v6"/>
          </svg>
        ') !!}

        {{-- Izin & Laporan --}}
        <p class="px-3 mt-4 mb-1 text-[11px] uppercase tracking-wider text-slate-400">Operasional</p>
        {!! $navItem(route('tu.export.index'), ['tu.export.*','admin.export.*'], 'Laporan / Export', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17h16v4H4z"/>
          </svg>
        ') !!}
        {!! $navItem(route('admin.izin.index'), 'admin.izin.*', 'Izin (Semua)', '
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <rect x="3" y="4" width="18" height="14" rx="2" stroke-width="1.8"/>
          <path stroke-width="1.8" d="M7 10h10M7 14h6"/>
        </svg>
      ') !!}

        {{-- Pengaturan/Profil --}}
        <p class="px-3 mt-4 mb-1 text-[11px] uppercase tracking-wider text-slate-400">Akun</p>
        {!! $navItem(route('profile.edit'), 'profile.edit', 'Profil', '
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="7" r="4" stroke-width="1.8"/><path stroke-width="1.8" d="M20 21a8 8 0 1 0-16 0"/>
          </svg>
        ') !!}
      </nav>

      {{-- Footer & Logout di sidebar (mobile terlihat, desktop tidak masalah) --}}
      <div class="mt-auto px-3 py-4 border-t border-white/10">
        <div class="flex items-center gap-3">
          <img src="{{ $avatar }}" class="w-9 h-9 rounded-full object-cover border border-white/20" alt="avatar">
          <div class="min-w-0">
            <p class="text-sm font-medium truncate text-white">{{ $u?->name ?? '-' }}</p>
            <p class="text-xs text-slate-400 truncate">{{ strtoupper($u?->role ?? 'ADMIN') }}</p>
          </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
          <button class="w-full px-3 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700 text-sm">Logout</button>
        </form>
      </div>
    </aside>

    {{-- ================= MAIN ================= --}}
    <div class="flex-1 min-w-0 lg:ml-72">

      {{-- Topbar --}}
      <header class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-slate-200">
        <div class="h-16 px-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <button id="btnOpen" class="lg:hidden p-2 rounded-lg border border-slate-200">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
              <h1 class="text-base font-semibold leading-tight">@yield('title','Admin')</h1>
              @hasSection('subtitle')
                <p class="text-[11px] text-slate-500">@yield('subtitle')</p>
              @endif
            </div>
          </div>
          <div class="flex items-center gap-3">
            {{-- Actions --}}
            <div class="hidden sm:block">@yield('actions')</div>
            {{-- Profile dropdown --}}
            <details class="relative">
              <summary class="list-none cursor-pointer">
                <img src="{{ $avatar }}" class="w-9 h-9 rounded-full object-cover border" alt="avatar">
              </summary>
              <div class="absolute right-0 mt-2 w-44 rounded-xl border bg-white shadow-lg ring-1 ring-slate-200 overflow-hidden">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">Profil</a>
                <div class="h-px bg-slate-200"></div>
                <form method="POST" action="{{ route('logout') }}" class="p-1">@csrf
                  <button class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 rounded-md">Logout</button>
                </form>
              </div>
            </details>
          </div>
        </div>

        {{-- Sub-actions (mobile) --}}
        @hasSection('actions')
          <div class="sm:hidden px-4 pb-3">@yield('actions')</div>
        @endif
      </header>

      {{-- Content --}}
      <main class="px-4 py-8">
        <div class="max-w-7xl mx-auto">
          @yield('content')
        </div>
      </main>

      <footer class="py-8 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} WR Media • Presensi v1.0
      </footer>
    </div>
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
    window.addEventListener('resize', ()=>{ if (window.innerWidth>=1024) closeDrawer(); });
  </script>
</body>
</html>
