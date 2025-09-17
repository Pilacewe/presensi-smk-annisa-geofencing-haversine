@extends('layouts.admin')

@section('title','Guru')
@section('subtitle','Kelola akun guru, ringkasan kehadiran, dan aktivitas terbaru')

@section('actions')
  <div class="flex items-center gap-2">
    {{-- Import --}}
    <form action="{{ route('admin.guru.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
      @csrf
      <input id="csvFile" name="file" type="file" accept=".csv" class="hidden">
      <label for="csvFile"
             class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm cursor-pointer hover:bg-slate-50">
        Import CSV
      </label>
    </form>
    {{-- Export --}}
    <a href="{{ route('admin.guru.export') }}"
       class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Export CSV</a>
    {{-- Tambah --}}
    <a href="{{ route('admin.guru.create') }}"
       class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700">
       + Tambah Guru
    </a>
  </div>
@endsection

@section('content')
<div class="max-w-screen-xl mx-auto space-y-6">
  @if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
      {{ session('success') }}
    </div>
  @endif

  @php
    $total    = $summary['total']    ?? 0;
    $aktif    = $summary['aktif']    ?? 0;
    $nonaktif = $summary['nonaktif'] ?? 0;

    $today = $todayStats ?? ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'belum'=>0];
    $mini  = [
      ['Hadir',$today['hadir'],'emerald'],
      ['Telat',$today['telat'],'amber'],
      ['Izin',$today['izin'],'sky'],
      ['Sakit',$today['sakit'],'rose'],
      ['Belum',$today['belum'],'slate'],
    ];

    $q       = trim((string)request('q',''));
    $active  = request('active');
    $isAll   = ($active==='' || is_null($active));
    $isAct   = ($active==='1' || $active===1);
    $isInact = ($active==='0' || $active===0);
  @endphp

  {{-- ===== Header Cards ===== --}}
  <div class="grid xl:grid-cols-3 gap-4">
    {{-- Kartu total + breakdown --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs text-slate-500">Total Guru</p>
          <p class="mt-1 text-3xl font-extrabold tabular-nums">{{ $total }}</p>
        </div>
        <div class="flex gap-2">
          <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 px-3 py-2 text-center">
            <p class="text-[11px] text-slate-600">Aktif</p>
            <p class="text-xl font-bold text-emerald-700 tabular-nums">{{ $aktif }}</p>
          </div>
          <div class="rounded-xl bg-rose-50 ring-1 ring-rose-200 px-3 py-2 text-center">
            <p class="text-[11px] text-slate-600">Nonaktif</p>
            <p class="text-xl font-bold text-rose-700 tabular-nums">{{ $nonaktif }}</p>
          </div>
        </div>
      </div>
      <div class="mt-4 h-px bg-slate-100"></div>
      <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
        @foreach($mini as [$label,$val,$c])
          <div class="rounded-xl ring-1 ring-{{ $c }}-200 bg-{{ $c }}-50/60 p-3 text-center">
            <p class="text-[11px] text-slate-600">{{ $label }}</p>
            <p class="text-xl font-extrabold text-{{ $c }}-700 tabular-nums">{{ $val }}</p>
          </div>
        @endforeach
      </div>
    </section>

    {{-- Izin Pending --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900">Izin Pending</h3>
        <a href="{{ route('admin.izin.index',['status'=>'pending']) }}" class="text-xs text-indigo-700 hover:underline">Kelola →</a>
      </div>
      @if(($pendingIzin ?? collect())->isEmpty())
        <p class="text-sm text-slate-500">Tidak ada antrian.</p>
      @else
        <ul class="space-y-2 max-h-48 overflow-auto pr-1">
          @foreach($pendingIzin as $p)
            <li class="rounded-xl border border-slate-200 p-3 flex items-center justify-between hover:bg-slate-50 transition">
              <div class="min-w-0">
                <p class="font-medium truncate">{{ $p->user?->name ?? '-' }}</p>
                <p class="text-[11px] text-slate-500">
                  {{ \Carbon\Carbon::parse($p->tgl_mulai)->format('d M') }} – {{ \Carbon\Carbon::parse($p->tgl_selesai)->format('d M') }} · {{ ucfirst($p->jenis) }}
                </p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[11px] bg-amber-50 text-amber-700 ring-1 ring-amber-200">Pending</span>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    {{-- Top Telat (bulan ini) --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900">Top Telat (bulan ini)</h3>
        <span class="text-xs text-slate-500">{{ now()->startOfMonth()->format('d M') }}–{{ now()->endOfMonth()->format('d M Y') }}</span>
      </div>
      <ul class="divide-y">
        @forelse($leaderboardTelat as $row)
          <li class="py-2 grid grid-cols-3 items-center gap-2">
            <a href="{{ route('admin.guru.show', $row->user_id) }}" class="truncate col-span-2 hover:underline">
              {{ $row->user?->name ?? '-' }}
            </a>
            <div class="justify-self-end flex items-center gap-2">
              <span class="px-2 py-0.5 text-xs rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200 tabular-nums">{{ $row->jml }}x</span>
              <span class="px-2 py-0.5 text-xs rounded-full bg-slate-50 text-slate-700 ring-1 ring-slate-200 tabular-nums">{{ (int)$row->menit }} mnt</span>
            </div>
          </li>
        @empty
          <li class="py-4 text-sm text-slate-500 text-center">Belum ada data.</li>
        @endforelse
      </ul>
    </section>
  </div>

  {{-- ===== Filter Pill + Bar ===== --}}
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      @php
        $pill = function($label,$query,$on){
          $base = 'px-3 py-1.5 rounded-full text-xs ring-1 transition';
          $cls  = $on ? 'bg-indigo-600 text-white ring-indigo-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50';
          $href = route('admin.guru.index',$query);
          return "<a href='{$href}' class='{$base} {$cls}'>{$label}</a>";
        };
      @endphp
      {!! $pill('Semua',    ['q'=>$q],              $isAll)   !!}
      {!! $pill('Aktif',    ['q'=>$q,'active'=>1], $isAct)   !!}
      {!! $pill('Nonaktif', ['q'=>$q,'active'=>0], $isInact) !!}
    </div>

    <div class="text-xs text-slate-500">
      Menampilkan <b class="text-slate-800">{{ $items->count() }}</b> dari <b class="text-slate-800">{{ $items->total() }}</b> guru.
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4 shadow-sm">
    <form class="grid md:grid-cols-[1fr,200px,auto,auto] gap-3">
      <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email / jabatan"
             class="rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300">
      <select name="active" class="rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300">
        <option value="">Semua status</option>
        <option value="1" @selected($isAct)>Aktif</option>
        <option value="0" @selected($isInact)>Nonaktif</option>
      </select>
      <button class="px-4 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800">Filter</button>
      <a href="{{ route('admin.guru.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200">Reset</a>
    </form>
    <div class="mt-3 flex items-center gap-2">
      <button id="btnCopyEmails" type="button"
              class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 text-xs hover:bg-slate-50">
        Salin semua email di halaman
      </button>
      <span id="copyHint" class="text-[11px] text-slate-400 hidden">Tersalin!</span>
    </div>
  </div>

  {{-- ===== Tabel Akun ===== --}}
  <div class="rounded-2xl bg-white ring-1 ring-slate-200 overflow-x-auto shadow-sm">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left">Nama</th>
          <th class="px-4 py-3 text-left">Email</th>
          <th class="px-4 py-3 text-left">Jabatan</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($items as $u)
          <tr class="align-top hover:bg-slate-50/50 transition">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <a href="{{ route('admin.guru.show',$u) }}"
                   class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 grid place-items-center text-xs font-bold hover:ring-2 hover:ring-indigo-300 transition">
                  {{ \Illuminate\Support\Str::of($u->name)->substr(0,2)->upper() }}
                </a>
                <div class="min-w-0">
                  <a href="{{ route('admin.guru.show',$u) }}" class="font-medium truncate hover:underline">
                    {{ $u->name }}
                  </a>
                  <p class="text-[11px] text-slate-500 uppercase">{{ $u->role ?? 'guru' }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 email-cell">{{ $u->email }}</td>
            <td class="px-4 py-3">{{ $u->jabatan ?: '—' }}</td>
            <td class="px-4 py-3">
              @if((int)$u->is_active === 1)
                <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">AKTIF</span>
              @else
                <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">NONAKTIF</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="relative inline-block">
                <details class="group select-none">
                  <summary class="list-none inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 text-xs hover:bg-slate-50 cursor-pointer">
                    Aksi
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M6 9l6 6 6-6"/></svg>
                  </summary>
                  <div class="absolute right-0 mt-2 w-40 rounded-xl border bg-white shadow-lg ring-1 ring-slate-200 overflow-hidden z-10">
                    <a href="{{ route('admin.guru.show',$u) }}" class="block px-3 py-2 text-xs hover:bg-slate-50">Detail</a>
                    <a href="{{ route('admin.guru.edit',$u) }}" class="block px-3 py-2 text-xs hover:bg-slate-50">Edit</a>
                    <form action="{{ route('admin.guru.reset',$u) }}" method="POST"
                          onsubmit="return confirm('Reset password untuk {{ $u->name }}?')">
                      @csrf
                      <button class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50">Reset PW</button>
                    </form>
                    <div class="h-px bg-slate-200"></div>
                    <form action="{{ route('admin.guru.destroy',$u) }}" method="POST"
                          onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                      @csrf @method('DELETE')
                      <button class="w-full text-left px-3 py-2 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                    </form>
                  </div>
                </details>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-4 py-12">
              <div class="flex flex-col items-center justify-center text-center gap-2">
                <div class="w-12 h-12 rounded-full bg-slate-100 grid place-items-center">
                  <svg class="w-6 h-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18"/>
                  </svg>
                </div>
                <p class="text-slate-700 font-medium">Belum ada data</p>
                <p class="text-slate-500 text-sm">Tambahkan guru baru atau ubah filter pencarian.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="pt-2">{{ $items->links() }}</div>

  {{-- ===== Visualisasi Leaderboard (Bulan Ini) ===== --}}
{{-- ===== Rekap Presensi Bulanan (Visualisasi) ===== --}}
<section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
  <div class="flex items-center justify-between gap-3">
    <h3 class="text-xl font-semibold text-slate-900">Rekap Presensi Bulanan</h3>

    {{-- Kontrol tampilan: Top N / Semua + Sort --}}
    <form class="flex items-center gap-2 text-xs" method="GET">
      {{-- persist filter lain bila perlu --}}
      <input type="hidden" name="q" value="{{ request('q') }}">
      <input type="hidden" name="active" value="{{ request('active') }}">

      <label class="inline-flex items-center gap-1">
        <span class="text-slate-500">Tampilkan</span>
        <select name="view" class="rounded-lg border-slate-300 py-1 px-2">
          <option value="top" @selected($view==='top')>Top</option>
          <option value="all" @selected($view==='all')>Semua</option>
        </select>
      </label>

      <label class="inline-flex items-center gap-1 {{ $view==='all' ? 'opacity-40 pointer-events-none' : '' }}">
        <span class="text-slate-500">N</span>
        <select name="n" class="rounded-lg border-slate-300 py-1 px-2">
          @foreach([8,12,16,20] as $n)
            <option value="{{ $n }}" @selected((int)$limit===$n)>{{ $n }}</option>
          @endforeach
        </select>
      </label>

      <label class="inline-flex items-center gap-1">
        <span class="text-slate-500">Urut</span>
        <select name="sort" class="rounded-lg border-slate-300 py-1 px-2">
          <option value="belum" @selected($sortBy==='belum')>Belum</option>
          <option value="telat" @selected($sortBy==='telat')>Telat</option>
          <option value="hadir" @selected($sortBy==='hadir')>Hadir</option>
          <option value="nama"  @selected($sortBy==='nama')>Nama</option>
        </select>
      </label>

      <select name="dir" class="rounded-lg border-slate-300 py-1 px-2">
        <option value="desc" @selected($dir==='desc')>↓</option>
        <option value="asc"  @selected($dir==='asc')>↑</option>
      </select>

      <button class="ml-1 px-3 py-1.5 rounded-lg bg-slate-900 text-white">Terapkan</button>
    </form>
  </div>

  <div class="mt-1 text-xs text-slate-500">{{ $chartPeriod }}</div>

  <div class="mt-4 grid lg:grid-cols-3 gap-4">
    {{-- Chart --}}
    <div class="lg:col-span-2 rounded-xl border border-slate-200 p-4">
      <div class="overflow-x-auto">
        <div style="min-width: {{ $chartCanvasWidth }}px">
          <div class="relative h-80">
            <canvas id="chartRekapBulanan"></canvas>
          </div>
        </div>
      </div>
      <p class="mt-3 text-[11px] text-slate-500">
        “Belum” dihitung sejak akun guru dibuat hingga periode ini (hari kerja yang sudah berjalan),
        lalu dikurangi Hadir/Telat/Izin/Sakit pada bulan <b>{{ $chartPeriod }}</b>.
      </p>
    </div>

    {{-- Ringkasan Top 5 --}}
    <div class="space-y-4">
      <div class="rounded-xl ring-1 ring-emerald-200 bg-emerald-50/50 p-4">
        <div class="flex items-center justify-between">
          <h4 class="font-semibold text-emerald-800">Guru Paling Rajin</h4>
          <span class="text-[11px] text-emerald-700">Top 5</span>
        </div>
        <ul class="divide-y divide-emerald-200/60 mt-2">
          @forelse($topRajin as $r)
            <li class="py-2 flex items-center justify-between">
              <span class="truncate">{{ $r['name'] }}</span>
              <span class="px-2 py-0.5 text-[11px] rounded-full bg-white text-emerald-700 ring-1 ring-emerald-300 tabular-nums">
                Hadir {{ (int)$r['hadir'] }}
              </span>
            </li>
          @empty
            <li class="py-4 text-sm text-emerald-800/70">Belum ada data.</li>
          @endforelse
        </ul>
      </div>

      <div class="rounded-xl ring-1 ring-amber-200 bg-amber-50/50 p-4">
        <div class="flex items-center justify-between">
          <h4 class="font-semibold text-amber-800">Paling Sering Telat</h4>
          <span class="text-[11px] text-amber-700">Top 5</span>
        </div>
        <ul class="divide-y divide-amber-200/60 mt-2">
          @forelse($topTelat as $t)
            <li class="py-2 grid grid-cols-3 items-center gap-2">
              <span class="truncate col-span-2">{{ $t['name'] }}</span>
              <div class="justify-self-end flex items-center gap-2">
                <span class="px-2 py-0.5 text-[11px] rounded-full bg-white text-amber-700 ring-1 ring-amber-300 tabular-nums">
                  {{ (int)$t['telat'] }}x
                </span>
              </div>
            </li>
          @empty
            <li class="py-4 text-sm text-amber-800/70">Belum ada data.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</section>



  {{-- Guru Terbaru --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold text-slate-900">Guru Terbaru</h3>
      @if(($recentUsers ?? collect())->isNotEmpty())
        <span class="text-xs text-slate-500">7 hari terakhir</span>
      @endif
    </div>
    @if(($recentUsers ?? collect())->isEmpty())
      <p class="text-sm text-slate-500">Belum ada penambahan terbaru.</p>
    @else
      <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($recentUsers as $ru)
          <li class="rounded-xl border border-slate-200 p-3 hover:bg-slate-50 transition">
            <a href="{{ route('admin.guru.show',$ru) }}" class="font-medium truncate hover:underline block">{{ $ru->name }}</a>
            <p class="text-xs text-slate-500 truncate">{{ $ru->email }}</p>
            <div class="mt-2 flex items-center justify-between text-[11px]">
              <span class="text-slate-500">Ditambah: {{ \Carbon\Carbon::parse($ru->created_at)->format('d M Y') }}</span>
              @if((int)$ru->is_active===1)
                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Aktif</span>
              @else
                <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 ring-1 ring-rose-200">Nonaktif</span>
              @endif
            </div>
          </li>
        @endforeach
      </ul>
    @endif
  </section>
</div>

{{-- ===== JS: Chart & Copy Email ===== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
{{-- Tambahkan di bawah (kamu sudah load chart.js di layout atau di halaman ini) --}}
<script>
(function(){
  const labels = @json($chartLabels ?? []);
  const dHadir = @json($chartHadir ?? []);
  const dTelat = @json($chartTelat ?? []);
  const dIzin  = @json($chartIzin  ?? []);
  const dSakit = @json($chartSakit ?? []);
  const dBelum = @json($chartBelum ?? []);

  // warna
  const C = {
    hadir: '#059669E6',  // emerald
    telat: '#F59E0BE6',  // amber
    izin:  '#0EA5E9E6',  // sky
    sakit: '#F43F5EE6',  // rose
    belum: '#6366F1CC',  // indigo
    belumText: '#4338CA',
  };

  // label angka di atas bar "Belum"
  const labelBelum = {
    id: 'labelBelum',
    afterDatasetsDraw(chart) {
      const dsIndex = chart.data.datasets.findIndex(d => d.label === 'Belum');
      if (dsIndex < 0) return;
      const meta = chart.getDatasetMeta(dsIndex);
      const vals = chart.data.datasets[dsIndex].data;
      const {ctx} = chart;
      ctx.save();
      ctx.fillStyle = C.belumText;
      ctx.font = 'bold 11px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.textAlign = 'center';
      meta.data.forEach((bar, i) => {
        const v = Number(vals[i] || 0);
        if (!v) return;
        ctx.fillText(v, bar.x, bar.y - 6);
      });
      ctx.restore();
    }
  };
  Chart.register(labelBelum);

  const ctx = document.getElementById('chartRekapBulanan')?.getContext('2d');
  if (!ctx) return;

  // === KUNCI: bikin 2 bar per kategori rapat (hampir tanpa celah) ===
  const barsPerCategory = 2;              // 1 stack tercatat + 1 stack belum
  const gapBetweenCats = 10;             // jarak antar-guru (px) — silakan kecilkan/besarkan
  const calcThickness = () => {
    const canvas = ctx.canvas;
    const width = (canvas.clientWidth || canvas.width);
    const available = width - Math.max(labels.length - 1, 0) * gapBetweenCats;
    // lebar bar = (lebar kategori / jumlah bar per kategori)
    return Math.max(18, Math.min(46, (available / Math.max(labels.length,1)) / barsPerCategory));
  };
  const barThickness = calcThickness();

  // rapatkan bar (tanpa celah internal)
  Chart.defaults.elements.bar.borderSkipped = false;

  const STACK_A = 'tercatat';
  const STACK_B = 'belum';

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Hadir', data: dHadir, backgroundColor: C.hadir, stack: STACK_A, barThickness, borderRadius: 5 },
        { label: 'Telat', data: dTelat, backgroundColor: C.telat, stack: STACK_A, barThickness, borderRadius: 5 },
        { label: 'Izin',  data: dIzin,  backgroundColor: C.izin,  stack: STACK_A, barThickness, borderRadius: 5 },
        { label: 'Sakit', data: dSakit, backgroundColor: C.sakit, stack: STACK_A, barThickness, borderRadius: 5 },
        { label: 'Belum', data: dBelum, backgroundColor: C.belum, stack: STACK_B, barThickness, borderRadius: 5 },
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: 'Rekap Presensi Bulanan — {{ $chartPeriod }}',
          font: { size: 14, weight: '600' },
          padding: { top: 4, bottom: 8 }
        },
        legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12 } },
        tooltip: {
          mode: 'index', intersect: false,
          callbacks: {
            footer: (items) => {
              const sumTercatat = items
                .filter(i => i.dataset.label !== 'Belum')
                .reduce((s, it) => s + Number(it.parsed.y || 0), 0);
              const belum = items.find(i => i.dataset.label === 'Belum');
              const vBelum = Number(belum?.parsed?.y || 0);
              return `Tercatat (H/T/I/S): ${sumTercatat}  •  Belum: ${vBelum}`;
            }
          }
        }
      },
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: {
          stacked: true,
          grid: { display: false },
          ticks: { autoSkip: false, maxRotation: 0, minRotation: 0, font: { size: 11 } },
          // buat kategori “penuh” biar bar saling nempel, jarak hanya antar kategori
          categoryPercentage: 1.0,
          barPercentage: 1.0
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      },
      // fallback kalau Chart.js mengabaikan barThickness (tergantung versi)
      datasets: { bar: { maxBarThickness: barThickness } }
    }
  });

  // optional: hitung ulang ketebalan saat resize
  window.addEventListener('resize', () => {
    // biarkan Chart.js handle responsive; ketebalan bar otomatis ikut karena width canvas berubah
  });
})();
</script>




@endsection
