@extends('layouts.admin')

@section('title','Kelola Presensi')
@section('subtitle','Filter, tinjau, dan perbarui catatan presensi')

@section('actions')
  <a href="{{ route('tu.export.index') }}"
     class="px-3 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 text-sm">Export</a>
@endsection

@section('content')
  @if (session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-3">
      {{ session('success') }}
    </div>
  @endif

  {{-- Ringkas --}}
  <div class="grid sm:grid-cols-4 gap-3 mb-6">
    @php
      $statTile = function($label,$val,$cls){ return "
        <div class='rounded-2xl ring-1 $cls bg-white p-4'>
          <p class='text-xs text-slate-500'>$label</p>
          <p class='mt-1 text-2xl font-extrabold tabular-nums'>".$val."</p>
        </div>
      "; };
    @endphp
    {!! $statTile('Hadir',  $summary['hadir'] ?? 0, 'ring-emerald-200') !!}
    {!! $statTile('Izin',   $summary['izin']  ?? 0, 'ring-amber-200') !!}
    {!! $statTile('Sakit',  $summary['sakit'] ?? 0, 'ring-rose-200') !!}
    {!! $statTile('Alfa',   $summary['alfa']  ?? 0, 'ring-slate-200') !!}
  </div>

  {{-- Filter --}}
  <form class="mb-5 grid lg:grid-cols-6 gap-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4">
    <select name="role" class="rounded-lg border-slate-300">
      <option value="">– Semua Role –</option>
      @foreach($roles as $r)
        <option value="{{ $r }}" @selected($role===$r)>{{ strtoupper($r) }}</option>
      @endforeach
    </select>

    <select name="user_id" class="rounded-lg border-slate-300">
      <option value="">– Semua Pegawai –</option>
      @foreach($users as $u)
        <option value="{{ $u->id }}" @selected($user_id==$u->id)>{{ $u->name }} ({{ strtoupper($u->role) }})</option>
      @endforeach
    </select>

    <select name="status" class="rounded-lg border-slate-300">
      <option value="">– Semua Status –</option>
      @foreach(['hadir','izin','sakit','alfa'] as $s)
        <option value="{{ $s }}" @selected($status===$s)>{{ ucfirst($s) }}</option>
      @endforeach
    </select>

    <input type="date" name="start" value="{{ $start ?? $defS }}" class="rounded-lg border-slate-300">
    <input type="date" name="end"   value="{{ $end   ?? $defE }}" class="rounded-lg border-slate-300">

    <div class="flex items-center gap-2">
      <button class="px-3 py-2 rounded-lg bg-slate-900 text-white">Terapkan</button>
      <a href="{{ route('admin.presensi.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">Reset</a>
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
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($data as $r)
          @php
            $badge = match($r->status){
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
              'izin'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
              'sakit' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
              default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200'
            };
          @endphp
          <tr class="border-b last:border-0">
            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($r->tanggal)->format('Y-m-d') }}</td>
            <td class="px-4 py-3 font-medium truncate max-w-[220px]">{{ $r->user?->name ?? '—' }}</td>
            <td class="px-4 py-3 uppercase text-xs text-slate-500">{{ $r->user?->role }}</td>
            <td class="px-4 py-3">
              <span class="px-2.5 py-1 rounded-full text-xs {{ $badge }}">{{ ucfirst($r->status) }}</span>
            </td>
            <td class="px-4 py-3 tabular-nums">{{ $r->jam_masuk  ? \Illuminate\Support\Str::of($r->jam_masuk)->substr(0,5)  : '—' }}</td>
            <td class="px-4 py-3 tabular-nums">{{ $r->jam_keluar ? \Illuminate\Support\Str::of($r->jam_keluar)->substr(0,5) : '—' }}</td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('admin.presensi.edit',$r) }}"
                 class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200">
                Edit
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $data->links() }}</div>
@endsection
