<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','Admin') — Presensi</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <style>
    /* ===== Base & Scroll ===== */
    html{scrollbar-gutter:stable both-edges}
    @supports not (scrollbar-gutter:stable){body{overflow-y:scroll}}

    /* ===== Layout variables ===== */
    :root{ --sbw: 18rem; }               /* lebar sidebar desktop   */
    body.sidebar-mini{ --sbw: 4.5rem; }  /* sidebar mini (desktop)  */

    /* ===== Sidebar ===== */
    #sidebar{ width: var(--sbw); }
    .with-sidebar-margin{ margin-left: var(--sbw); transition: margin .25s ease; }
    #sidebar{
      position: fixed; top:0; left:0; height:100%;
      background: linear-gradient(180deg,#ffffff 0%,#fbfbff 40%,#f6f7ff 100%);
      border-right:1px solid rgb(226 232 240);
      z-index: 50; transition: transform .25s ease, width .25s ease;
    }

    /* ===== Mobile drawer ===== */
    @media (max-width:1023.98px){
      :root{ --sbw: 0rem; }
      #sidebar{ transform: translateX(-100%); width: 18rem; }
      body.drawer-open #sidebar{ transform: translateX(0); }
      .with-sidebar-margin{ margin-left:0 !important; }
    }

    /* ===== Mini mode: sembunyikan teks ===== */
    body.sidebar-mini .hide-when-mini{ display:none !important; }
    body.sidebar-mini .nav-indicator{ display:none; }

    /* ===== Topbar ===== */
    .topbar-shadow{ box-shadow: 0 1px 0 rgba(15,23,42,.06); }

    /* ===== Nav state ===== */
    .nav-indicator{ width:3px; height:20px; border-radius:6px;
      background: linear-gradient(180deg,#4f46e5,#22c55e); }

    .nav-link-base{ transition: background .15s ease, color .15s ease, box-shadow .15s ease; }
    .nav-link-base:hover{ box-shadow: inset 0 0 0 1px rgba(99,102,241,.08); }

    /* ===== Icon buttons ===== */
    .btn-icon{
      display:inline-grid; place-items:center; width:2rem; height:2rem;
      border:1px solid rgb(226 232 240); border-radius:.5rem; background:#fff;
    }
    .btn-icon:hover{ background:#f8fafc; }

    /* ===== Edge Handle (">") ===== */
    #edgeHandle{
      position: fixed; top: 50%; transform: translateY(-50%);
      left: calc(var(--sbw) - .75rem);
      z-index: 60; width: 2rem; height: 2.25rem;
      display: none; border-radius: 999px; background: #fff;
      border: 1px solid rgb(226 232 240);
      box-shadow: 0 6px 20px rgba(15,23,42,.08);
    }
    @media (min-width:1024px){ body.sidebar-mini #edgeHandle{ display: grid; place-items:center; } }
    @media (max-width:1023.98px){
      body:not(.drawer-open) #edgeHandle{ display: grid; place-items:center; left: .5rem; }
    }

    /* ===== Tooltip saat mini (pakai attribute title) ===== */
    body.sidebar-mini a[data-title]{ position: relative; }
    body.sidebar-mini a[data-title]:hover::after{
      content: attr(data-title);
      position: absolute; left: calc(100% + .5rem); top: 50%; transform: translateY(-50%);
      white-space: nowrap; background:#0f172a; color:#fff; font-size:11px;
      padding:.25rem .5rem; border-radius:.4rem;
      box-shadow:0 6px 18px rgba(15,23,42,.25);
    }
  </style>
</head>

@php
  use Illuminate\Support\Str;

  $u = auth()->user();
  $avatar = $u?->avatar_path
      ? asset('storage/'.$u->avatar_path)
      : 'https://ui-avatars.com/api/?name='.urlencode($u?->name ?? 'Admin').'&background=6366f1&color=fff&size=160';

  $is = fn(...$r) => request()->routeIs($r);

  /* ===== Deteksi halaman ROOT (tanpa tombol Back) ===== */
  $routeName = request()->route()?->getName() ?? '';
  $isGenericRoot = Str::endsWith($routeName, ['.dashboard', '.index']);
  $extraRoot = request()->routeIs([
    'admin.dashboard',
    'admin.account.index',
    'admin.piket.index',
    'admin.guru.index',
    'admin.tu.index',
    'admin.presensi.index',
    'admin.izin.index',
    'admin.export.index',
    'admin.kepsek.dashboard',   // ⬅️ ditambahkan
    'tu.dashboard',
    'tu.export.index',
    'piket.dashboard',
    'presensi.index',
  ]);
  $isRoot   = $isGenericRoot || $extraRoot;
  $showBack = ! $isRoot;

  /* ===== SVG icon pack ===== */
  $ic = [
    'home'  => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5l9-7 9 7"/><path d="M5 10v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V10"/></svg>',
    'table' => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 20V10"/></svg>',
    'guru'  => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9l-10-5L2 9l10 5 10-5z"/><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/></svg>',
    'tu'    => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6V4h4v2"/><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>',
    'piket' => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3h8v4H8zM8 11h8M8 15h5"/></svg>',
    'report'=> '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12l4 4v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M16 4v4h4"/></svg>',
    'note'  => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6"/></svg>',
    'user'  => '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>',
    'toggle'=> '<svg viewBox="0 0 24 24" class="w-4.5 h-4.5 text-slate-700" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"></rect><line x1="9" y1="4" x2="9" y2="20"></line></svg>',
    // Ikon Kepsek (mahkota)
    'kepsek'=> '<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l5 5 4-6 4 6 5-5v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>',
  ];

  /* ===== Helper item nav ===== */
  $navItem = function(string $href, string|array $match, string $label, string $svg) use($is) {
    $active = $is(...(array)$match);
    $base   = 'relative nav-link-base flex items-center gap-3 px-3 py-2 rounded-xl text-sm';
    $cls    = $active
      ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
      : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900';
    $bar    = $active ? '<span class="nav-indicator absolute left-0 top-1/2 -translate-y-1/2"></span>' : '';
    $aria   = $active ? 'aria-current="page"' : '';
    $title  = e($label);
    return <<<HTML
      <a href="{$href}" class="{$base} {$cls}" {$aria} data-title="{$title}" title="{$title}">
        {$bar}{$svg}<span class="font-medium hide-when-mini">{$label}</span>
      </a>
    HTML;
  };
@endphp

<body class="bg-slate-50 text-slate-800 antialiased">

  <!-- Backdrop (mobile drawer) -->
  <div id="drawerBackdrop" class="fixed inset-0 bg-slate-900/30 z-40 hidden lg:hidden"></div>

  <!-- Edge handle (">") -->
  <button id="edgeHandle" aria-label="Buka menu">
    <svg class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M9 6l6 6-6 6"/></svg>
  </button>

  <div class="min-h-screen flex">

    <!-- ================= SIDEBAR ================= -->
    <aside id="sidebar">
      <!-- Header brand + toggle -->
      <div class="h-14 px-3 border-b border-slate-200 flex items-center justify-between gap-2">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white grid place-items-center font-semibold shadow-sm">P</div>
          <div class="leading-tight hide-when-mini">
            <p class="text-[11px] tracking-wide text-slate-500">Admin Panel</p>
            <p class="font-semibold text-slate-900">Presensi Pegawai</p>
          </div>
        </div>
        <button id="btnSidebarToggle" class="btn-icon" title="Tutup / Buka sidebar">
          {!! $ic['toggle'] !!}
        </button>
      </div>

      <!-- Menu -->
      <nav class="px-3 py-3 space-y-1">
        {!! $navItem(route('admin.dashboard'), 'admin.dashboard', 'Dashboard', $ic['home']) !!}

        <p class="px-3 mt-3 mb-1 text-[11px] uppercase tracking-wider text-slate-400 hide-when-mini">Presensi</p>
        {!! $navItem(route('admin.presensi.index'), 'admin.presensi.*', 'Kelola Presensi', $ic['table']) !!}

        <p class="px-3 mt-3 mb-1 text-[11px] uppercase tracking-wider text-slate-400 hide-when-mini">Data Pegawai</p>
        {!! $navItem(route('admin.guru.index'),  ['admin.guru.*'], 'Guru',  $ic['guru']) !!}
        {!! $navItem(route('admin.tu.index'),    ['admin.tu.*'],   'TU',    $ic['tu']) !!}
        {!! $navItem(route('admin.piket.index'), ['admin.piket.*'],'Piket', $ic['piket']) !!}
        {!! $navItem(route('admin.kepsek.index'), ['admin.kepsek.*'], 'Kepsek', $ic['kepsek']) !!}


        <p class="px-3 mt-3 mb-1 text-[11px] uppercase tracking-wider text-slate-400 hide-when-mini">Operasional</p>
        {!! $navItem(route('admin.reports.index'), ['admin.reports.*'], 'Laporan / Export', $ic['report']) !!}
        {!! $navItem(route('admin.izin.index'), 'admin.izin.*', 'Izin (Semua)', $ic['note']) !!}

        <p class="px-3 mt-3 mb-1 text-[11px] uppercase tracking-wider text-slate-400 hide-when-mini">Akun</p>
        {!! $navItem(route('admin.account.index'), 'admin.account.*', 'Akun Admin', $ic['user']) !!}
      </nav>

      <!-- Footer Sidebar -->
      <div class="mt-auto px-3 py-3 border-t border-slate-200">
        <div class="flex items-center gap-3">
          <img src="{{ $avatar }}" class="w-9 h-9 rounded-full object-cover border border-slate-200" alt="avatar">
          <div class="min-w-0 hide-when-mini">
            <p class="text-sm font-medium truncate text-slate-900">{{ $u?->name ?? '-' }}</p>
            <p class="text-xs text-slate-500 truncate">{{ strtoupper($u?->role ?? 'ADMIN') }}</p>
          </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2 hide-when-mini">@csrf
          <button class="w-full px-3 py-2 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 text-sm">Logout</button>
        </form>
      </div>
    </aside>

    <!-- ================= MAIN ================= -->
    <div id="mainWrap" class="flex-1 min-w-0 with-sidebar-margin transition-all duration-300">

      <!-- Topbar -->
      <header id="topbar" class="sticky top-0 z-40 bg-white/70 backdrop-blur border-b border-slate-200 transition-shadow">
        <div class="h-14 px-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            @if($showBack)
              <button id="btnBack" class="btn-icon text-slate-700" title="Kembali">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M15 18l-6-6 6-6"/></svg>
              </button>
            @endif
            <div class="ml-1">
              <h1 class="text-sm font-semibold leading-tight">@yield('title','Admin')</h1>
              @hasSection('subtitle')
                <p class="text-[11px] text-slate-500">@yield('subtitle')</p>
              @endif
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden sm:block">@yield('actions')</div>
            <details class="relative">
              <summary class="list-none cursor-pointer">
                <img src="{{ $avatar }}" class="w-9 h-9 rounded-full object-cover border border-slate-200" alt="avatar">
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

        @hasSection('actions')
          <div class="sm:hidden px-4 pb-2">@yield('actions')</div>
        @endif
      </header>

      <!-- Content -->
      <main class="px-4 py-6">
        <div class="max-w-7xl mx-auto">
          @yield('content')
        </div>
      </main>

      <footer class="py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} WR Media • Presensi v1.0
      </footer>
    </div>
  </div>

  <!-- ===== Interaksi: drawer/collapse/edge/back ===== -->
  <script>
    const body    = document.body;
    const bd      = document.getElementById('drawerBackdrop');
    const btnTog  = document.getElementById('btnSidebarToggle');
    const btnBack = document.getElementById('btnBack');
    const topbar  = document.getElementById('topbar');
    const edge    = document.getElementById('edgeHandle');

    const LS_KEY  = 'sb-mini';

    function isDesktop(){ return window.matchMedia('(min-width:1024px)').matches; }
    function openDrawer(){ body.classList.add('drawer-open'); bd?.classList.remove('hidden'); }
    function closeDrawer(){ body.classList.remove('drawer-open'); bd?.classList.add('hidden'); }

    function applyMiniFromStorage(){
      const saved = localStorage.getItem(LS_KEY);
      if (saved === '1' && isDesktop()) body.classList.add('sidebar-mini');
      else body.classList.remove('sidebar-mini');
    }
    applyMiniFromStorage();

    bd?.addEventListener('click', closeDrawer);

    function onResize(){
      if (isDesktop()){
        closeDrawer(); applyMiniFromStorage();
      } else {
        body.classList.remove('sidebar-mini');
      }
    }
    window.addEventListener('resize', onResize);

    btnTog?.addEventListener('click', ()=>{
      if (isDesktop()){
        body.classList.toggle('sidebar-mini');
        localStorage.setItem(LS_KEY, body.classList.contains('sidebar-mini') ? '1' : '0');
      } else {
        openDrawer();
      }
    });

    edge?.addEventListener('click', ()=>{
      if (isDesktop()){
        body.classList.remove('sidebar-mini'); localStorage.setItem(LS_KEY, '0');
      } else {
        openDrawer();
      }
    });

    btnBack?.addEventListener('click', (e)=>{
      e.preventDefault();
      if (window.history.length > 1) window.history.back();
      else window.location.href = "{{ route('admin.dashboard') }}";
    });

    function onScroll(){
      (window.scrollY > 2) ? topbar.classList.add('topbar-shadow') : topbar.classList.remove('topbar-shadow');
    }
    window.addEventListener('scroll', onScroll, {passive:true}); onScroll();
  </script>
</body>
</html>
