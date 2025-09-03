{{-- resources/views/admin/tu/index.blade.php --}}
@extends('layouts.admin')

@section('title','TU')
@section('subtitle','Kelola akun TU, ringkasan kehadiran, dan aktivitas')

@section('actions')
  <div class="flex items-center gap-2">
    <form action="{{ route('admin.tu.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
      @csrf
      <input id="csvFile" name="file" type="file" accept=".csv" class="hidden">
      <label for="csvFile" class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm cursor-pointer hover:bg-slate-50">
        Import CSV
      </label>
    </form>
    <a href="{{ route('admin.tu.export') }}"
       class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Export CSV</a>
    <a href="{{ route('admin.tu.create') }}"
       class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700">
       + Tambah TU
    </a>
  </div>
@endsection

@section('content')
  @if (session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
      {{ session('success') }}
    </div>
  @endif

  @php
    $sum = $summary ?? ['total'=>0,'aktif'=>0,'nonaktif'=>0];
    $today = $todayStats ?? ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'belum'=>0];
    $mini  = [
      ['Hadir',$today['hadir'],'emerald'],
      ['Telat',$today['telat'],'amber'],
      ['Izin',$today['izin'],'sky'],
      ['Sakit',$today['sakit'],'rose'],
      ['Belum',$today['belum'],'slate'],
    ];
  @endphp

  {{-- ===== Hero Cards ===== --}}
  <div class="grid xl:grid-cols-3 gap-4 mb-6">
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs text-slate-500">Total TU</p>
          <p class="mt-1 text-3xl font-extrabold tabular-nums">{{ $sum['total'] }}</p>
        </div>
        <div class="flex gap-2">
          <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 px-3 py-2 text-center">
            <p class="text-[11px] text-slate-600">Aktif</p>
            <p class="text-xl font-bold text-emerald-700 tabular-nums">{{ $sum['aktif'] }}</p>
          </div>
          <div class="rounded-xl bg-rose-50 ring-1 ring-rose-200 px-3 py-2 text-center">
            <p class="text-[11px] text-slate-600">Nonaktif</p>
            <p class="text-xl font-bold text-rose-700 tabular-nums">{{ $sum['nonaktif'] }}</p>
          </div>
        </div>
      </div>
      <div class="mt-4 h-px bg-slate-100"></div>
      <div class="mt-4 grid grid-cols-5 gap-2">
        @foreach($mini as [$label,$val,$c])
          <div class="rounded-xl ring-1 ring-{{ $c }}-200 bg-{{ $c }}-50/60 p-3 text-center">
            <p class="text-[11px] text-slate-600">{{ $label }}</p>
            <p class="text-xl font-extrabold text-{{ $c }}-700 tabular-nums">{{ $val }}</p>
          </div>
        @endforeach
      </div>
    </section>

    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900">Izin Pending (TU)</h3>
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
                  {{ \Carbon\Carbon::parse($p->tgl_mulai)->format('d M') }} –
                  {{ \Carbon\Carbon::parse($p->tgl_selesai)->format('d M') }} · {{ ucfirst($p->jenis) }}
                </p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[11px] bg-amber-50 text-amber-700 ring-1 ring-amber-200">Pending</span>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900">Top Telat (bulan ini)</h3>
        <span class="text-xs text-slate-500">{{ now()->startOfMonth()->format('d M') }}–{{ now()->endOfMonth()->format('d M Y') }}</span>
      </div>
      <ul class="divide-y">
        @forelse($leaderboardTelat as $row)
          <li class="py-2 grid grid-cols-3 items-center gap-2">
            <a href="{{ route('admin.tu.show',$row->user_id) }}" class="truncate col-span-2 hover:underline">
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

  {{-- ===== Quick Actions khusus TU (BARU) ===== --}}
  

  {{-- ===== Filter bar ===== --}}
  <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
    <div class="flex items-center gap-2">
      @php
        $pill = function($label,$query,$on){
          $base='px-3 py-1.5 rounded-full text-xs ring-1 transition';
          $cls=$on?'bg-indigo-600 text-white ring-indigo-600':'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50';
          return '<a class="'.$base.' '.$cls.'" href="'.route('admin.tu.index',$query).'">'.$label.'</a>';
        };
        $isAll=($active===''||is_null($active)); $isAct=($active==='1'||$active===1); $isInact=($active==='0'||$active===0);
      @endphp
      {!! $pill('Semua',    ['q'=>$q],                     $isAll)   !!}
      {!! $pill('Aktif',    ['q'=>$q,'active'=>1],         $isAct)   !!}
      {!! $pill('Nonaktif', ['q'=>$q,'active'=>0],         $isInact) !!}
    </div>
    <div class="text-xs text-slate-500">
      Menampilkan <b class="text-slate-800">{{ $items->count() }}</b> dari <b class="text-slate-800">{{ $items->total() }}</b> TU.
    </div>
  </div>

  <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4 mb-4">
    <form class="grid md:grid-cols-[1fr,200px,auto,auto] gap-3">
      <input id="searchInput" type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email / jabatan (tekan / untuk fokus)"
             class="rounded-xl border-slate-300 focus:ring-indigo-200">
      <select name="active" class="rounded-xl border-slate-300">
        <option value="">Semua status</option>
        <option value="1" @selected($isAct)>Aktif</option>
        <option value="0" @selected($isInact)>Nonaktif</option>
      </select>
      <button class="px-4 py-2 rounded-xl bg-slate-900 text-white">Filter</button>
      <a href="{{ route('admin.tu.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200">Reset</a>
    </form>
  </div>

  {{-- ===== Table ===== --}}
  <div class="rounded-2xl bg-white ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm relative z-0">
        <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
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
            <tr class="hover:bg-slate-50/50 transition">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <a href="{{ route('admin.tu.show',$u) }}"
                     class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 grid place-items-center text-xs font-bold hover:ring-2 hover:ring-indigo-300">
                    {{ \Illuminate\Support\Str::of($u->name)->substr(0,2)->upper() }}
                  </a>
                  <div class="min-w-0">
                    <a href="{{ route('admin.tu.show',$u) }}" class="font-medium truncate hover:underline">
                      {{ $u->name }}
                    </a>
                    <p class="text-[11px] text-slate-500 uppercase">TU</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">{{ $u->email }}</td>
              <td class="px-4 py-3">{{ $u->jabatan ?: '—' }}</td>
              <td class="px-4 py-3">
                @if((int)$u->is_active===1)
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">AKTIF</span>
                @else
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">NONAKTIF</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <details class="relative group select-none">
                  <summary class="list-none inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 text-xs hover:bg-slate-50 cursor-pointer">
                    Aksi
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M6 9l6 6 6-6"/></svg>
                  </summary>
                  <div class="absolute right-0 mt-2 w-40 rounded-xl border bg-white shadow-lg ring-1 ring-slate-200 overflow-hidden z-50">
                    <a href="{{ route('admin.tu.show',$u) }}" class="block px-3 py-2 text-xs hover:bg-slate-50">Detail</a>
                    <a href="{{ route('admin.tu.edit',$u) }}" class="block px-3 py-2 text-xs hover:bg-slate-50">Edit</a>
                    <form action="{{ route('admin.tu.reset',$u) }}" method="POST"
                          onsubmit="return confirm('Reset password untuk {{ $u->name }}?')">
                      @csrf
                      <button class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50">Reset PW</button>
                    </form>
                    <div class="h-px bg-slate-200"></div>
                    <form action="{{ route('admin.tu.destroy',$u) }}" method="POST"
                          onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                      @csrf @method('DELETE')
                      <button class="w-full text-left px-3 py-2 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                    </form>
                  </div>
                </details>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-4">{{ $items->links() }}</div>

  {{-- ===== bawah: leaderboard hadir + terbaru ===== --}}
  <div class="grid lg:grid-cols-2 gap-4 mt-8">
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900">Leaderboard Kehadiran (bulan ini)</h3>
        <span class="text-xs text-slate-500">{{ now()->startOfMonth()->format('d M') }}–{{ now()->endOfMonth()->format('d M Y') }}</span>
      </div>
      <ul class="divide-y">
        @forelse($leaderboardHadir as $row)
          <li class="py-2 flex items-center justify-between">
            <a href="{{ route('admin.tu.show',$row->user_id) }}" class="truncate hover:underline">{{ $row->user?->name ?? '-' }}</a>
            <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 tabular-nums">{{ $row->jml }}</span>
          </li>
        @empty
          <li class="py-4 text-sm text-slate-500 text-center">Belum ada data.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900">TU Terbaru</h3>
        @if(($recentUsers ?? collect())->isNotEmpty())
          <span class="text-xs text-slate-500">7 hari terakhir</span>
        @endif
      </div>
      @if(($recentUsers ?? collect())->isEmpty())
        <p class="text-sm text-slate-500">Belum ada penambahan terbaru.</p>
      @else
        <ul class="grid sm:grid-cols-2 gap-3">
          @foreach($recentUsers as $ru)
            <li class="rounded-xl border border-slate-200 p-3 hover:bg-slate-50 transition">
              <a href="{{ route('admin.tu.show',$ru) }}" class="font-medium truncate hover:underline block">{{ $ru->name }}</a>
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

  {{-- ===== Aktivitas Terakhir (BARU, ringan) ===== --}}
  <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 mt-6">
    <h3 class="font-semibold text-slate-900 mb-3">Aktivitas Terakhir</h3>
    <ul class="space-y-2">
      @forelse(($pendingIzin ?? collect())->take(3) as $p)
        <li class="flex items-center gap-3 text-sm">
          <span class="w-2 h-2 rounded-full bg-amber-400"></span>
          <span class="truncate"><b>{{ $p->user?->name ?? '-' }}</b> mengajukan {{ $p->jenis }} ({{ \Carbon\Carbon::parse($p->tgl_mulai)->format('d M') }}–{{ \Carbon\Carbon::parse($p->tgl_selesai)->format('d M Y') }})</span>
        </li>
      @empty
        <li class="text-sm text-slate-500">Tidak ada aktivitas menonjol.</li>
      @endforelse
    </ul>
  </section>

  {{-- ===== UX helpers ===== --}}
  <button id="backToTop"
    class="fixed bottom-6 right-6 hidden px-3 py-2 rounded-full bg-slate-900 text-white text-xs shadow hover:bg-slate-800">
    ↑ Ke atas
  </button>

  <script>
    // Shortcut "/" fokus ke kolom cari
    window.addEventListener('keydown', (e) => {
      if (e.key === '/' && !/input|textarea|select/i.test(document.activeElement.tagName)) {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
      }
    });

    // Tombol back to top
    const btnTop = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) btnTop?.classList.remove('hidden');
      else btnTop?.classList.add('hidden');
    });
    btnTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  </script>
@endsection
