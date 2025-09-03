@extends('layouts.admin')

@section('title','Profil Guru')
@section('subtitle','Detail akun, kehadiran, dan aktivitas')

@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ route('admin.guru.edit',$u) }}"
       class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Edit</a>
    <form action="{{ route('admin.guru.reset',$u) }}" method="POST"
          onsubmit="return confirm('Reset password untuk {{ $u->name }}?')">
      @csrf
      <button class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Reset PW</button>
    </form>
    <form action="{{ route('admin.guru.destroy',$u) }}" method="POST"
          onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
      @csrf @method('DELETE')
      <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Hapus</button>
    </form>
  </div>
@endsection

@section('content')
@php
  $avatar = $u?->avatar_path
      ? asset('storage/'.$u->avatar_path)
      : 'https://ui-avatars.com/api/?name='.urlencode($u->name).'&background=6366f1&color=fff&size=200';

  $badgeStatus = function($st){
    return match($st){
      'hadir' => ['Hadir','bg-emerald-50 text-emerald-700 ring-emerald-200'],
      'telat' => ['Telat','bg-amber-50 text-amber-700 ring-amber-200'],
      'izin'  => ['Izin','bg-sky-50 text-sky-700 ring-sky-200'],
      'sakit' => ['Sakit','bg-rose-50 text-rose-700 ring-rose-200'],
      default => ['—','bg-slate-50 text-slate-600 ring-slate-200'],
    };
  };

  [$todayLabel, $todayCls] = $badgeStatus($todayStatusKey);
  $activeCls = ((int)($u->is_active ?? 1) === 1)
    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
    : 'bg-rose-50 text-rose-700 ring-rose-200';

  $fmtTime = fn($t) => $t ? \Illuminate\Support\Str::of($t)->substr(0,5) : '—';
  $fmtDate = fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('d M Y');
@endphp

{{-- ===== HERO ===== --}}
<div class="rounded-3xl border border-slate-200 bg-gradient-to-r from-indigo-50 via-white to-sky-50 p-6 mb-6 shadow-sm">
  <div class="flex items-start gap-5">
    <img src="{{ $avatar }}" class="w-20 h-20 rounded-2xl object-cover border border-slate-200" alt="avatar">
    <div class="min-w-0">
      <div class="flex items-center gap-3 flex-wrap">
        <h2 class="text-xl font-semibold truncate">{{ $u->name }}</h2>
        <span class="px-2.5 py-1 rounded-full text-[11px] ring-1 {{ $activeCls }}">
          {{ ((int)($u->is_active ?? 1)===1) ? 'AKTIF' : 'NONAKTIF' }}
        </span>
        @if($online)
          <span class="px-2.5 py-1 rounded-full text-[11px] ring-1 bg-emerald-50 text-emerald-700 ring-emerald-200">ONLINE</span>
        @else
          <span class="px-2.5 py-1 rounded-full text-[11px] ring-1 bg-slate-50 text-slate-600 ring-slate-200">OFFLINE</span>
        @endif
      </div>
      <p class="text-sm text-slate-600 truncate">{{ $u->email }}</p>
      <p class="text-xs text-slate-500 mt-1">
        Role: <b class="text-slate-700 uppercase">{{ $u->role ?? 'guru' }}</b>
        @if($u->jabatan)
          • Jabatan: <b class="text-slate-700">{{ $u->jabatan }}</b>
        @endif
      </p>
    </div>
  </div>

  <div class="mt-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
      <p class="text-xs text-slate-500">Status Hari Ini</p>
      <div class="mt-2">
        <span class="px-2.5 py-1 rounded-full text-[11px] ring-1 {{ $todayCls }}">{{ strtoupper($todayLabel) }}</span>
      </div>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
      <p class="text-xs text-slate-500">Jam Masuk</p>
      <p class="text-xl font-bold tabular-nums">{{ $fmtTime($todayIn) }}</p>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
      <p class="text-xs text-slate-500">Jam Keluar</p>
      <p class="text-xl font-bold tabular-nums">{{ $fmtTime($todayOut) }}</p>
    </div>
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
      <p class="text-xs text-slate-500">Rata-rata Telat (bulan ini)</p>
      <p class="text-xl font-bold tabular-nums">{{ $avgTelatMenit }} mnt</p>
    </div>
  </div>
</div>

{{-- ===== GRID 2 kolom ===== --}}
<div class="grid xl:grid-cols-3 gap-6">
  {{-- Kiri (2 kolom) --}}
  <div class="xl:col-span-2 space-y-6">

    {{-- Ringkasan Bulan Ini --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-slate-900">Ringkasan Bulan Ini</h3>
        <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($mStart)->format('d M') }}–{{ \Carbon\Carbon::parse($mEnd)->format('d M Y') }}</span>
      </div>
      <div class="grid sm:grid-cols-4 gap-3">
        <div class="rounded-xl ring-1 ring-emerald-200 bg-emerald-50/60 p-4 text-center">
          <p class="text-xs text-slate-600">Hadir</p>
          <p class="text-2xl font-extrabold text-emerald-700 tabular-nums">{{ (int)($monthly->m_hadir ?? 0) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-amber-200 bg-amber-50/60 p-4 text-center">
          <p class="text-xs text-slate-600">Telat</p>
          <p class="text-2xl font-extrabold text-amber-700 tabular-nums">{{ (int)($monthly->m_telat ?? 0) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-sky-200 bg-sky-50/60 p-4 text-center">
          <p class="text-xs text-slate-600">Izin</p>
          <p class="text-2xl font-extrabold text-sky-700 tabular-nums">{{ (int)($monthly->m_izin ?? 0) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-rose-200 bg-rose-50/60 p-4 text-center">
          <p class="text-xs text-slate-600">Sakit</p>
          <p class="text-2xl font-extrabold text-rose-700 tabular-nums">{{ (int)($monthly->m_sakit ?? 0) }}</p>
        </div>
      </div>
      <div class="mt-4 grid sm:grid-cols-2 gap-3">
        <div class="rounded-xl border border-slate-200 p-4">
          <p class="text-xs text-slate-600">Total Kehadiran</p>
          <p class="text-xl font-bold tabular-nums">{{ $totalKehadiranBulan }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
          <p class="text-xs text-slate-600">Total Telat (menit)</p>
          <p class="text-xl font-bold tabular-nums">{{ (int)($monthly->total_telat_menit ?? 0) }}</p>
        </div>
      </div>
    </section>

    {{-- Riwayat Presensi Singkat --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-slate-900">Riwayat Presensi (10 terbaru)</h3>
        <a href="{{ route('admin.presensi.index', ['user_id'=>$u->id]) }}"
           class="text-xs text-indigo-700 hover:underline">Lihat semua →</a>
      </div>
      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-4 py-2 text-left">Tanggal</th>
              <th class="px-4 py-2 text-left">Status</th>
              <th class="px-4 py-2 text-left">Masuk</th>
              <th class="px-4 py-2 text-left">Keluar</th>
              <th class="px-4 py-2 text-left">Telat (mnt)</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @forelse($recentPresensi as $r)
              @php
                [$lbl,$cls] = $badgeStatus($r->status);
              @endphp
              <tr class="hover:bg-slate-50/60">
                <td class="px-4 py-2">{{ $fmtDate($r->tanggal) }}</td>
                <td class="px-4 py-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] ring-1 {{ $cls }}">{{ strtoupper($lbl) }}</span>
                </td>
                <td class="px-4 py-2 tabular-nums">{{ $fmtTime($r->jam_masuk) }}</td>
                <td class="px-4 py-2 tabular-nums">{{ $fmtTime($r->jam_keluar) }}</td>
                <td class="px-4 py-2 tabular-nums">{{ (int)($r->telat_menit ?? 0) }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>

  {{-- Kanan (1 kolom) --}}
  <div class="space-y-6">
    {{-- Kartu Kontak & Aksi --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <h3 class="font-semibold text-slate-900 mb-3">Kontak & Aksi</h3>
      <div class="space-y-2 text-sm">
        <div class="flex items-center justify-between">
          <span class="text-slate-600">Email</span>
          <span class="font-medium text-slate-900 truncate">{{ $u->email }}</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-600">Jabatan</span>
          <span class="font-medium text-slate-900 truncate">{{ $u->jabatan ?: '—' }}</span>
        </div>
      </div>
      <div class="mt-4 flex items-center gap-2">
        <a href="mailto:{{ $u->email }}" class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Kirim Email</a>
        <a href="{{ route('admin.guru.edit',$u) }}" class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Edit Profil</a>
      </div>
    </section>

    {{-- Ringkasan 7 Hari Terakhir --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <h3 class="font-semibold text-slate-900 mb-3">7 Hari Terakhir</h3>
      <div class="grid grid-cols-4 gap-2 text-center">
        <div class="rounded-lg ring-1 ring-emerald-200 bg-emerald-50/60 p-3">
          <p class="text-[11px] text-slate-600">Hadir</p>
          <p class="text-xl font-extrabold text-emerald-700 tabular-nums">{{ (int)($weekly->hadir ?? 0) }}</p>
        </div>
        <div class="rounded-lg ring-1 ring-amber-200 bg-amber-50/60 p-3">
          <p class="text-[11px] text-slate-600">Telat</p>
          <p class="text-xl font-extrabold text-amber-700 tabular-nums">{{ (int)($weekly->telat ?? 0) }}</p>
        </div>
        <div class="rounded-lg ring-1 ring-sky-200 bg-sky-50/60 p-3">
          <p class="text-[11px] text-slate-600">Izin</p>
          <p class="text-xl font-extrabold text-sky-700 tabular-nums">{{ (int)($weekly->izin ?? 0) }}</p>
        </div>
        <div class="rounded-lg ring-1 ring-rose-200 bg-rose-50/60 p-3">
          <p class="text-[11px] text-slate-600">Sakit</p>
          <p class="text-xl font-extrabold text-rose-700 tabular-nums">{{ (int)($weekly->sakit ?? 0) }}</p>
        </div>
      </div>
    </section>

    {{-- Izin Terakhir --}}
    <section class="rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-slate-900">Izin Terakhir</h3>
        <a href="{{ route('admin.izin.index',['q'=>$u->name]) }}" class="text-xs text-indigo-700 hover:underline">Kelola izin →</a>
      </div>
      @if(($recentIzin ?? collect())->isEmpty())
        <p class="text-sm text-slate-500">Tidak ada pengajuan izin terbaru.</p>
      @else
        <ul class="space-y-2">
          @foreach($recentIzin as $iz)
            @php
              $cls = match($iz->status){
                'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'pending'  => 'bg-amber-50 text-amber-700 ring-amber-200',
                'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
                default    => 'bg-slate-50 text-slate-600 ring-slate-200'
              };
            @endphp
            <li class="rounded-xl border border-slate-200 p-3 flex items-center justify-between">
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ ucfirst($iz->jenis) }} · {{ $fmtDate($iz->tgl_mulai) }} – {{ $fmtDate($iz->tgl_selesai ?: $iz->tgl_mulai) }}</p>
                <p class="text-[11px] text-slate-500 truncate">{{ $iz->keterangan ?: '—' }}</p>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[11px] ring-1 {{ $cls }}">{{ strtoupper($iz->status) }}</span>
            </li>
          @endforeach
        </ul>
      @endif
    </section>
  </div>
</div>
@endsection
