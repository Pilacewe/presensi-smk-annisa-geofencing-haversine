@extends('layouts.admin')

@section('title','Izin Pegawai')
@section('subtitle','Kelola pengajuan izin/sakit lintas role')

@section('actions')
  <a href="{{ route('tu.export.index') }}" class="px-3 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 text-sm">
    Laporan / Export
  </a>
@endsection

@section('content')
  {{-- Flash --}}
  @if (session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-3">
      {{ session('success') }}
    </div>
  @endif

  {{-- Ringkasan --}}
  <div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="rounded-2xl ring-1 ring-amber-200 bg-white p-4">
      <p class="text-xs text-slate-500">Pending</p>
      <p class="mt-1 text-2xl font-extrabold tabular-nums text-amber-700">{{ $summary['pending'] ?? 0 }}</p>
    </div>
    <div class="rounded-2xl ring-1 ring-emerald-200 bg-white p-4">
      <p class="text-xs text-slate-500">Approved</p>
      <p class="mt-1 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $summary['approved'] ?? 0 }}</p>
    </div>
    <div class="rounded-2xl ring-1 ring-rose-200 bg-white p-4">
      <p class="text-xs text-slate-500">Rejected</p>
      <p class="mt-1 text-2xl font-extrabold tabular-nums text-rose-700">{{ $summary['rejected'] ?? 0 }}</p>
    </div>
  </div>

  {{-- Filter bar --}}
  <form class="mb-4 grid lg:grid-cols-5 gap-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4">
    <select name="status" class="rounded-lg border-slate-300">
      <option value="">– Semua Status –</option>
      <option value="pending"  @selected($status==='pending')>Pending</option>
      <option value="approved" @selected($status==='approved')>Approved</option>
      <option value="rejected" @selected($status==='rejected')>Rejected</option>
    </select>

    <select name="bulan" class="rounded-lg border-slate-300">
      <option value="">– Bulan –</option>
      @foreach($listBulan as $k=>$v)
        <option value="{{ $k }}" @selected($bulan==$k)>{{ $v }}</option>
      @endforeach
    </select>

    <select name="tahun" class="rounded-lg border-slate-300">
      <option value="">– Tahun –</option>
      @foreach($listTahun as $th)
        <option value="{{ $th }}" @selected($tahun==$th)>{{ $th }}</option>
      @endforeach
    </select>

    <select name="user_id" class="rounded-lg border-slate-300">
      <option value="">– Semua Pegawai –</option>
      @foreach($users as $u)
        <option value="{{ $u->id }}" @selected($userId==$u->id)>{{ $u->name }} ({{ strtoupper($u->role) }})</option>
      @endforeach
    </select>

    <div class="flex items-center gap-2">
      <button class="px-3 py-2 rounded-lg bg-slate-900 text-white">Terapkan</button>
      <a href="{{ route('admin.izin.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">Reset</a>
    </div>
  </form>

  {{-- Tabel --}}
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50">
        <tr class="text-left text-slate-500 border-b">
          <th class="px-4 py-3">Tanggal</th>
          <th class="px-4 py-3">Pegawai</th>
          <th class="px-4 py-3">Role</th>
          <th class="px-4 py-3">Jenis</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($data as $row)
          @php
            $badge = match($row->status){
              'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
              'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
              default    => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
            };
          @endphp
          <tr class="border-b last:border-0">
            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d') }}</td>
            <td class="px-4 py-3 font-medium">{{ $row->user?->name ?? '—' }}</td>
            <td class="px-4 py-3 uppercase text-xs text-slate-500">{{ $row->user?->role ?? '-' }}</td>
            <td class="px-4 py-3 capitalize">{{ $row->jenis }}</td>
            <td class="px-4 py-3">
              <span class="px-2.5 py-1 rounded-full text-xs {{ $badge }}">{{ ucfirst($row->status) }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('admin.izin.show',$row) }}"
                 class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200">
                Detail
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $data->links() }}
  </div>
@endsection
