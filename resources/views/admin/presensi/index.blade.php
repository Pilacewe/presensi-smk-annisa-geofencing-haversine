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

  {{-- ===== Ringkasan Kecil ===== --}}
  <div class="grid sm:grid-cols-4 gap-3 mb-6">
    @php
      $tile = fn($label,$val,$ring,$txt) => "
        <div class='rounded-2xl ring-1 $ring bg-white p-4'>
          <p class=\"text-xs text-slate-500\">$label</p>
          <p class=\"mt-1 text-2xl font-extrabold tabular-nums $txt\">$val</p>
        </div>
      ";
    @endphp
    {!! $tile('Hadir (incl. Telat)', $summary['hadir'] ?? 0, 'ring-emerald-200', 'text-emerald-700') !!}
    {!! $tile('Izin',                $summary['izin']  ?? 0, 'ring-sky-200',     'text-sky-700') !!}
    {!! $tile('Sakit',               $summary['sakit'] ?? 0, 'ring-rose-200',    'text-rose-700') !!}
    {!! $tile('Alfa',                $summary['alfa']  ?? 0, 'ring-slate-200',   'text-slate-700') !!}
  </div>

  {{-- ===== Filter Bar ===== --}}
  <form class="mb-5 grid lg:grid-cols-6 gap-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4" method="GET">
    {{-- Role: hanya GURU & TU --}}
    <select name="role" class="rounded-lg border-slate-300">
      <option value="">– Semua Role –</option>
      @foreach($roles as $r)
        <option value="{{ $r }}" @selected(($role ?? '')===$r)>{{ strtoupper($r) }}</option>
      @endforeach
    </select>

    {{-- Pegawai --}}
    <select name="user_id" class="rounded-lg border-slate-300">
      <option value="">– Semua Pegawai –</option>
      @foreach($users as $u)
        <option value="{{ $u->id }}" @selected(($user_id ?? '')==$u->id)>
          {{ $u->name }} ({{ strtoupper($u->role) }})
        </option>
      @endforeach
    </select>

    {{-- Status --}}
    <select name="status" class="rounded-lg border-slate-300">
      <option value="">– Semua Status –</option>
      @foreach(['hadir'=>'Hadir','telat'=>'Telat','izin'=>'Izin','sakit'=>'Sakit'] as $k=>$v)
        <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
      @endforeach
    </select>

    <input type="date" name="start" value="{{ $start ?? $defS }}" class="rounded-lg border-slate-300">
    <input type="date" name="end"   value={{ $end   ?? $defE }} class="rounded-lg border-slate-300">

    <div class="flex items-center gap-2">
      <button class="px-3 py-2 rounded-lg bg-slate-900 text-white">Terapkan</button>
      <a href="{{ route('admin.presensi.index') }}"
         class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">Reset</a>
    </div>
  </form>

  {{-- ===== Tabel ===== --}}
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 sticky top-0 z-10">
        <tr class="text-left text-slate-600 border-b">
          <th class="px-4 py-3">Tanggal</th>
          <th class="px-4 py-3">Pegawai</th>
          <th class="px-4 py-3">Role</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3 text-center">Masuk</th>
          <th class="px-4 py-3 text-center">Keluar</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($data as $r)
          @php
            $badge = match($r->status){
              'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
              'telat' => 'bg-amber-50  text-amber-700  ring-amber-200',
              'izin'  => 'bg-sky-50    text-sky-700    ring-sky-200',
              'sakit' => 'bg-rose-50   text-rose-700   ring-rose-200',
              default => 'bg-slate-50  text-slate-700  ring-slate-200'
            };
            $fmt = fn($t)=> $t ? \Illuminate\Support\Str::of($t)->substr(0,5) : '—';
          @endphp
          <tr class="hover:bg-slate-50/60">
            <td class="px-4 py-3 whitespace-nowrap tabular-nums">
              {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d M Y') }}
            </td>
            <td class="px-4 py-3 font-medium truncate max-w-[260px]">
              {{ $r->user?->name ?? '—' }}
            </td>
            <td class="px-4 py-3 uppercase text-xs text-slate-500">
              {{ $r->user?->role }}
            </td>
            <td class="px-4 py-3">
              <span class="px-2.5 py-1 rounded-full text-[11px] font-medium ring-1 {{ $badge }}">
                {{ strtoupper($r->status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-center tabular-nums">{{ $fmt($r->jam_masuk) }}</td>
            <td class="px-4 py-3 text-center tabular-nums">{{ $fmt($r->jam_keluar) }}</td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('admin.presensi.edit',$r) }}"
                 class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200">
                Edit
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada data pada filter ini.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $data->links() }}</div>
@endsection
