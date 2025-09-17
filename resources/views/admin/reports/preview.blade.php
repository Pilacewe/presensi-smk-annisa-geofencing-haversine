@extends('layouts.admin')
@section('title','Preview Laporan (PDF)')

{{-- Actions di topbar (sticky dari layout) --}}
@section('actions')
  @php
    // Fallback kalau Controller belum set (aman dipakai)
    $downloadUrl = $downloadUrl ?? route('admin.reports.export', request()->query() + ['format'=>'pdf']);
    $backUrl     = route('admin.reports.index', [
      'mode'    => $mode,
      'tahun'   => $tahun,
      'bulan'   => $bulan,
      'user_id' => $userId
    ]);
  @endphp
  <div class="flex items-center gap-2">
    <a href="{{ $downloadUrl }}"
       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16"/></svg>
      Unduh PDF
    </a>
    <a href="{{ $backUrl }}"
       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border hover:bg-slate-50">
      Kembali
    </a>
  </div>
@endsection

@section('content')
@php
  $sum     = fn($k)=>collect($rows)->sum($k);
  $avgTel  = number_format(collect($rows)->avg('rata_telat'),1);
  $pegText = $user? ($user->name.' ('.$user->jabatan.')') : 'Semua';
@endphp

<div id="previewWrap" class="bg-white rounded-2xl border shadow-sm p-5">
  <div class="flex items-start justify-between">
    <div>
      <h2 class="text-lg font-semibold">Laporan Presensi — {{ $period }}</h2>
      <p class="text-sm text-slate-600">
        Periode: {{ $from->toDateString() }} s/d {{ $to->toDateString() }}
        • Pegawai: <strong>{{ $pegText }}</strong>
      </p>
    </div>
    <div class="text-xs text-slate-500">Preview sebelum diunduh.</div>
  </div>

  <div class="mt-4 overflow-x-auto rounded-xl border">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-3 py-2 text-left">Nama</th>
          <th class="px-3 py-2 text-left">Jabatan</th>
          <th class="px-3 py-2 text-right">Hadir</th>
          <th class="px-3 py-2 text-right">Telat</th>
          <th class="px-3 py-2 text-right">Sakit</th>
          <th class="px-3 py-2 text-right">Izin</th>
          <th class="px-3 py-2 text-right">Alpha</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-right">Rata Telat (mnt)</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($rows as $r)
          <tr>
            <td class="px-3 py-2">{{ $r->name }}</td>
            <td class="px-3 py-2">{{ $r->jabatan }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->hadir }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->telat }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->sakit }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->izin }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->alpha }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->total }}</td>
            <td class="px-3 py-2 text-right font-mono">{{ $r->rata_telat }}</td>
          </tr>
        @empty
          <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
      <tfoot class="bg-slate-50">
        <tr>
          <td class="px-3 py-2 font-semibold">Total</td>
          <td></td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $sum('hadir') }}</td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $sum('telat') }}</td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $sum('sakit') }}</td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $sum('izin') }}</td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $sum('alpha') }}</td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $sum('total') }}</td>
          <td class="px-3 py-2 text-right font-mono font-semibold">{{ $avgTel }}</td>
        </tr>
      </tfoot>
    </table>
  </div>

  {{-- Tombol unduh di bawah (desktop/tablet) --}}
  <div class="mt-4 text-right hidden md:block">
    <a href="{{ $downloadUrl }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
      Unduh PDF
    </a>
  </div>
</div>

{{-- FAB Unduh PDF: mobile-only + hanya muncul bila konten panjang --}}
<a id="fabDownload"
   href="{{ $downloadUrl }}"
   class="hidden md:hidden fixed bottom-4 right-4 inline-flex items-center gap-2 px-4 py-2 rounded-full shadow-lg bg-rose-600 text-white hover:bg-rose-700">
  Unduh PDF
</a>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.getElementById('previewWrap');
    const fab  = document.getElementById('fabDownload');
    if (!wrap || !fab) return;

    // Tampilkan FAB hanya jika tinggi konten > tinggi viewport (halaman panjang)
    const needFab = wrap.scrollHeight > window.innerHeight * 0.92;
    if (needFab) fab.classList.remove('hidden');

    // Re-evaluate saat rotate / resize mobile
    window.addEventListener('resize', () => {
      const need = wrap.scrollHeight > window.innerHeight * 0.92;
      fab.classList.toggle('hidden', !need);
    });
  });
</script>
@endsection
