@extends('layouts.admin')

@section('title','Piket')
@section('subtitle','Kelola akun piket & roster petugas')

@section('actions')
  <a href="{{ route('admin.piket.create') }}"
     class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700">
    + Tambah Akun Piket
  </a>
@endsection

@section('content')
@if(session('success'))
  <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
    {{ session('success') }}
  </div>
@endif

{{-- ==== Statistik Akun ==== --}}
@php $sum = $summary ?? ['total'=>0,'aktif'=>0,'nonaktif'=>0]; @endphp
<div class="grid md:grid-cols-3 gap-4 mb-6">
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
    <p class="text-xs text-slate-500">Total Akun Piket</p>
    <p class="mt-1 text-3xl font-extrabold tabular-nums">{{ $sum['total'] }}</p>
  </div>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-emerald-200">
    <p class="text-xs text-slate-500">Aktif</p>
    <p class="mt-1 text-3xl font-extrabold text-emerald-700 tabular-nums">{{ $sum['aktif'] }}</p>
  </div>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-rose-200">
    <p class="text-xs text-slate-500">Nonaktif</p>
    <p class="mt-1 text-3xl font-extrabold text-rose-700 tabular-nums">{{ $sum['nonaktif'] }}</p>
  </div>
</div>

{{-- ==== Petugas Hari Ini ==== --}}
<section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 mb-6">
  <div class="flex items-center justify-between">
    <h3 class="font-semibold text-slate-900">Petugas Piket Hari Ini</h3>
    <span class="text-xs text-slate-500">{{ $today->translatedFormat('l, d M Y') }}</span>
  </div>

  @if($rosterToday)
    <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
      <p class="text-sm text-slate-500">Nama Petugas</p>
      <p class="text-2xl font-bold text-indigo-800">
        {{ $rosterToday->user?->name ?? $rosterToday->name ?? '—' }}
      </p>
      <p class="text-xs text-slate-600 mt-1">
        Shift: {{ ucfirst($rosterToday->shift ?? 'pagi') }}
      </p>
      @if($rosterToday->note)
        <p class="text-xs text-slate-600 mt-1">Catatan: {{ $rosterToday->note }}</p>
      @endif
    </div>
  @else
    <p class="mt-4 text-sm text-slate-500">Belum ada roster untuk hari ini.</p>
  @endif
</section>

{{-- ==== Jadwal Piket 7 Hari ke Depan ==== --}}
<section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 mb-8">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-semibold text-slate-900">Jadwal Piket (7 hari ke depan)</h3>
    <span class="text-xs text-slate-500">Urut tanggal naik</span>
  </div>

  @if($rosterNext->isEmpty())
    <p class="text-sm text-slate-500">Belum ada data roster.</p>
  @else
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Tanggal</th>
            <th class="px-4 py-3 text-left">Petugas</th>
            <th class="px-4 py-3 text-left">Catatan</th>
            <th class="px-4 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @foreach($rosterNext as $r)
            <tr class="hover:bg-slate-50/40">
              <td class="px-4 py-3">{{ \Carbon\Carbon::parse($r->date)->translatedFormat('l, d M Y') }}</td>
              <td class="px-4 py-3">{{ $r->user?->name ?? $r->name ?? '—' }}</td>
              <td class="px-4 py-3">{{ $r->note ?? '—' }}</td>
              <td class="px-4 py-3 text-right">
                <form action="{{ route('admin.piket.roster.destroy',$r) }}" method="POST"
                      onsubmit="return confirm('Hapus roster tanggal ini?')">
                  @csrf @method('DELETE')
                  <button class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs hover:bg-rose-700">Hapus</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</section>

{{-- ==== Daftar Akun Piket ==== --}}
<section class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
  <h3 class="font-semibold text-slate-900 mb-3">Daftar Akun Piket</h3>

  <form class="grid md:grid-cols-[1fr,150px,auto] gap-3 mb-4">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email"
           class="rounded-xl border-slate-300">
    <select name="active" class="rounded-xl border-slate-300">
      <option value="">Semua status</option>
      <option value="1" @selected($active==='1')>Aktif</option>
      <option value="0" @selected($active==='0')>Nonaktif</option>
    </select>
    <button class="px-4 py-2 rounded-xl bg-slate-900 text-white">Filter</button>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left">Nama</th>
          <th class="px-4 py-3 text-left">Email</th>
          <th class="px-4 py-3 text-center">Status</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($users as $u)
          <tr class="hover:bg-slate-50/50">
            <td class="px-4 py-3 font-medium">{{ $u->name }}</td>
            <td class="px-4 py-3">{{ $u->email }}</td>
            <td class="px-4 py-3 text-center">
              @if((int)$u->is_active===1)
                <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">AKTIF</span>
              @else
                <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">NONAKTIF</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-2">
                <form action="{{ route('admin.piket.reset',$u) }}" method="POST"
                      onsubmit="return confirm('Reset password {{ $u->name }}?')">
                  @csrf
                  <button class="px-2.5 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 text-xs hover:bg-slate-50">Reset PW</button>
                </form>
                <a href="{{ route('admin.piket.edit',$u) }}"
                   class="px-2.5 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 text-xs hover:bg-slate-50">Edit</a>
                <form action="{{ route('admin.piket.destroy',$u) }}" method="POST"
                      onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                  @csrf @method('DELETE')
                  <button class="px-2.5 py-1.5 rounded-lg bg-rose-600 text-white text-xs hover:bg-rose-700">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada akun piket.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $users->links() }}</div>
</section>
@endsection
