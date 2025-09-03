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
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold text-slate-900">Visualisasi Leaderboard (Bulan Ini)</h3>
      <span class="text-xs text-slate-500">{{ now()->startOfMonth()->format('d M') }}–{{ now()->endOfMonth()->format('d M Y') }}</span>
    </div>

    {{-- Grid: Chart (2 kol) + Ringkasan (1 kol) --}}
    <div class="mt-4 grid lg:grid-cols-3 gap-4">
      {{-- Chart --}}
      <div class="lg:col-span-2">
        <div class="relative h-72 md:h-80">
          <canvas id="guruLeaderboard"></canvas>
        </div>
      </div>

      {{-- Ringkasan samping --}}
      <div class="space-y-4">
        <div class="rounded-xl ring-1 ring-emerald-200 bg-emerald-50/40 p-4">
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-emerald-800">Guru Paling Rajin</h4>
            <span class="text-[11px] text-emerald-700">Top 10</span>
          </div>
          <ul class="divide-y divide-emerald-200/60">
            @forelse($topRajin ?? [] as $r)
              <li class="py-2 flex items-center justify-between">
                <a class="truncate hover:underline" href="{{ route('admin.guru.show',$r->user_id) }}">
                  {{ $r->user?->name ?? '-' }}
                </a>
                <span class="px-2 py-0.5 text-[11px] rounded-full bg-white text-emerald-700 ring-1 ring-emerald-300 tabular-nums">
                  Hadir {{ (int)($r->jml_hadir ?? 0) }}
                </span>
              </li>
            @empty
              <li class="py-4 text-sm text-emerald-800/70">Belum ada data.</li>
            @endforelse
          </ul>
        </div>

        <div class="rounded-xl ring-1 ring-amber-200 bg-amber-50/40 p-4">
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-amber-800">Paling Sering Telat</h4>
            <span class="text-[11px] text-amber-700">Top 10</span>
          </div>
          <ul class="divide-y divide-amber-200/60">
            @forelse($topTelat ?? [] as $t)
              <li class="py-2 grid grid-cols-3 items-center gap-2">
                <a href="{{ route('admin.guru.show',$t->user_id) }}" class="truncate col-span-2 hover:underline">
                  {{ $t->user?->name ?? '-' }}
                </a>
                <div class="justify-self-end flex items-center gap-2">
                  <span class="px-2 py-0.5 text-[11px] rounded-full bg-white text-amber-700 ring-1 ring-amber-300 tabular-nums">
                    {{ (int)($t->jml_telat ?? 0) }}x
                  </span>
                  <span class="px-2 py-0.5 text-[11px] rounded-full bg-white text-slate-700 ring-1 ring-slate-200 tabular-nums">
                    {{ (int)($t->total_menit ?? 0) }} mnt
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
<script>
(function(){
  // Copy semua email di halaman
  const btnCopy = document.getElementById('btnCopyEmails');
  const hint    = document.getElementById('copyHint');
  btnCopy?.addEventListener('click', () => {
    const emails = Array.from(document.querySelectorAll('.email-cell'))
      .map(td => (td.textContent || '').trim())
      .filter(Boolean);
    if (emails.length === 0) return;
    navigator.clipboard.writeText(emails.join(', ')).then(() => {
      hint?.classList.remove('hidden');
      setTimeout(()=>hint?.classList.add('hidden'), 1200);
    });
  });

  // Chart Leaderboard
  const labels = @json($chartLabels ?? []);
  const dataH  = @json($chartHadir ?? []);
  const dataT  = @json($chartTelat ?? []);
  const dataI  = @json($chartIzin ?? []);
  const dataB  = @json($chartBelum ?? []);

  const ctx = document.getElementById('guruLeaderboard')?.getContext('2d');
  if(!ctx) return;

  const cH = 'rgba(5, 150, 105, .8)';   // emerald
  const cT = 'rgba(245, 158, 11, .85)'; // amber
  const cI = 'rgba(14, 165, 233, .85)'; // sky
  const cB = 'rgba(100, 116, 139, .8)'; // slate

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Hadir', data: dataH, backgroundColor: cH, stack: 's', borderRadius: 6 },
        { label: 'Telat', data: dataT, backgroundColor: cT, stack: 's', borderRadius: 6 },
        { label: 'Izin',  data: dataI, backgroundColor: cI, stack: 's', borderRadius: 6 },
        { label: 'Belum', data: dataB, backgroundColor: cB, stack: 's', borderRadius: 6 },
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,    // biar ikut tinggi container (h-72/h-80)
      plugins: {
        legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12 } },
        tooltip: { mode: 'index', intersect: false }
      },
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: {
          stacked: true,
          ticks: { autoSkip: true, maxRotation: 0, minRotation: 0 },
          grid: { display: false },
          // membuat bar tidak terlalu lebar
          // (Chart.js menghormati kategori & bar percentage di tingkat x)
          categoryPercentage: 0.6,
          barPercentage: 0.9,
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      },
      // batasi ketebalan bar agar tidak melebar
      datasets: {
        bar: { maxBarThickness: 38 }
      }
    }
  });
})();
</script>
@endsection
