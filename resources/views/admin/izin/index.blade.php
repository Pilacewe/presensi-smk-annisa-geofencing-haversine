@extends('layouts.admin')
@section('title','Izin Pegawai')

@php
  $fmtDate = fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('d M Y');
  $badge = fn($st) => [
    'pending'  => 'bg-amber-50  text-amber-700  ring-amber-200',
    'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'rejected' => 'bg-rose-50   text-rose-700   ring-rose-200',
  ][$st] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
@endphp

@section('actions')
  <form method="GET" class="flex items-center gap-2">
    <select name="status" class="rounded-lg border-slate-300 text-sm">
      <option value="">Semua status</option>
      <option value="pending"  @selected(($status ?? '')==='pending')>Pending</option>
      <option value="approved" @selected(($status ?? '')==='approved')>Approved</option>
      <option value="rejected" @selected(($status ?? '')==='rejected')>Rejected</option>
    </select>
    <select name="jenis" class="rounded-lg border-slate-300 text-sm">
      <option value="">Semua jenis</option>
      <option value="izin"  @selected(($jenis ?? '')==='izin')>Izin</option>
      <option value="sakit" @selected(($jenis ?? '')==='sakit')>Sakit</option>
    </select>
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama/keterangan…" class="rounded-lg border-slate-300 text-sm">
    <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm">Terapkan</button>
    <a href="{{ route('admin.izin.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 text-sm">Reset</a>
  </form>
@endsection

@section('content')
  <div class="grid sm:grid-cols-3 gap-4 mb-4">
    <div class="rounded-xl bg-white ring-1 ring-amber-200 p-4"><p class="text-xs">Pending</p><p class="text-2xl font-bold tabular-nums text-amber-700">{{ $summary['pending'] ?? 0 }}</p></div>
    <div class="rounded-xl bg-white ring-1 ring-emerald-200 p-4"><p class="text-xs">Approved</p><p class="text-2xl font-bold tabular-nums text-emerald-700">{{ $summary['approved'] ?? 0 }}</p></div>
    <div class="rounded-xl bg-white ring-1 ring-rose-200 p-4"><p class="text-xs">Rejected</p><p class="text-2xl font-bold tabular-nums text-rose-700">{{ $summary['rejected'] ?? 0 }}</p></div>
  </div>

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left">Pemohon</th>
          <th class="px-4 py-3 text-left">Jenis</th>
          <th class="px-4 py-3 text-left">Rentang</th>
          <th class="px-4 py-3 text-left">Keterangan</th>
          <th class="px-4 py-3 text-center">Bukti</th>
          <th class="px-4 py-3 text-center">Status</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($items as $r)
          @php
            $ext      = \Illuminate\Support\Str::lower(pathinfo($r->bukti ?? '', PATHINFO_EXTENSION));
            $isImg    = in_array($ext, ['jpg','jpeg','png','gif','webp']);
            $rentang  = $fmtDate($r->tgl_mulai) . ' – ' . $fmtDate($r->tgl_selesai ?: $r->tgl_mulai);
            $buktiUrl = $r->bukti ? route('admin.izin.bukti', $r->id) : null;
          @endphp
          <tr class="hover:bg-slate-50/60 align-top">
            <td class="px-4 py-3 font-medium text-slate-800">{{ $r->user?->name ?? '—' }}</td>
            <td class="px-4 py-3 capitalize">{{ $r->jenis }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ $rentang }}</td>
            <td class="px-4 py-3">{{ $r->keterangan ?: '—' }}</td>
            
            <td class="px-4 py-3 text-center">
              @if($buktiUrl)
                @if($isImg)
                  <a href="{{ $buktiUrl }}" target="_blank" class="inline-block">
                    <img src="{{ $buktiUrl }}" alt="bukti" class="h-12 w-auto rounded-lg ring-1 ring-slate-200 object-cover">
                  </a>
                @else
                  <a href="{{ $buktiUrl }}" target="_blank"
                    class="inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-100 ring-1 ring-slate-200 text-xs">
                    Lihat berkas
                  </a>
                @endif
              @else
                <span class="text-slate-400">—</span>
              @endif
            </td>
            <td class="px-4 py-3 text-center">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium ring-1 {{ $badge($r->status) }}">{{ strtoupper($r->status) }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                @if($r->status==='pending')
                  {{-- SETUJUI --}}
              <form action="{{ route('admin.izin.approve',$r->id) }}" method="POST" onsubmit="return confirm('Setujui izin ini?')">
                @csrf
                @method('PATCH')
                <button class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs hover:bg-emerald-700">Setujui</button>
              </form>

              {{-- TOLAK --}}
              <form action="{{ route('admin.izin.reject',$r->id) }}" method="POST" onsubmit="return confirm('Tolak izin ini?')">
                @csrf
                @method('PATCH')
                <input type="hidden" name="reject_reason" value="">
                <button class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs hover:bg-rose-700">Tolak</button>
              </form>
                @endif

                {{-- tombol hapus selalu tersedia --}}
                <form action="{{ route('admin.izin.destroy',$r->id) }}" method="POST" onsubmit="return confirm('Hapus pengajuan ini? Tindakan akan menghapus presensi otomatisnya juga.')">
                  @csrf @method('DELETE')
                  <button class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 text-xs hover:bg-slate-50">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $items->links() }}</div>
@endsection
