<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Export Presensi</title>
<style>
  @page { size: A4; margin: 18mm 14mm; }
  body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif; color:#111; font-size: 12px; }
  h1   { font-size: 18px; margin: 0 0 2px; }
  .meta{ color:#555; font-size: 11px; margin-bottom: 10px; }
  table{ width:100%; border-collapse: collapse; }
  th,td{ border:1px solid #dcdfe5; padding:6px 8px; }
  th   { background:#f6f7fb; text-align:left; }
  tbody tr:nth-child(even){ background:#fafbff; }
  .right { text-align:right; }
  .center{ text-align:center; }
  .badge { display:inline-block; padding:2px 6px; border-radius: 999px; font-size: 10px; font-weight: 600; border:1px solid transparent; }
  .b-hadir{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
  .b-telat{ background:#fffbeb; color:#b45309; border-color:#fde68a; }
  .b-izin { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
  .b-sakit{ background:#fff1f2; color:#be123c; border-color:#fecdd3; }
  .muted{ color:#6b7280; }
  .nowrap{ white-space: nowrap; }
  /* Hindari patah baris di tengah row saat multi-halaman */
  tr { page-break-inside: avoid; }
</style>
</head>
<body>

  <header style="margin-bottom:12px;">
    <h1>Laporan Presensi</h1>
    <div class="meta">
      Periode: <b>{{ $from }}</b> s/d <b>{{ $to }}</b>
      @if(!empty($guruId) && !empty($guruName)) • Guru: <b>{{ $guruName }}</b> @else • Guru: <b>Semua</b> @endif
      • Dicetak: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
  </header>

  <table>
    <thead>
      <tr>
        <th style="width:28%;">Nama</th>
        <th style="width:20%;">Tanggal</th>
        <th class="center" style="width:14%;">Masuk</th>
        <th class="center" style="width:14%;">Keluar</th>
        <th class="center" style="width:14%;">Status</th>
      </tr>
    </thead>
    <tbody>
      @php
        $fmtTime = function($t){
          if (!$t) return '—';
          try { return \Carbon\Carbon::parse($t)->format('H:i'); } catch (\Exception $e) { return (string) $t; }
        };
        $badgeClass = fn($st)=>[
          'hadir'=>'b-hadir','telat'=>'b-telat','izin'=>'b-izin','sakit'=>'b-sakit'
        ][$st] ?? '';
      @endphp

      @forelse($rows as $r)
        @php
          $tgl = \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y');
          $st  = strtolower($r->status ?? '-');
        @endphp
        <tr>
          <td>{{ $r->user->name ?? '—' }}</td>
          <td class="nowrap">{{ $tgl }}</td>
          <td class="center nowrap">{{ $fmtTime($r->jam_masuk) }}</td>
          <td class="center nowrap">{{ $fmtTime($r->jam_keluar) }}</td>
          <td class="center">
            <span class="badge {{ $badgeClass($st) }}">{{ strtoupper($st) }}</span>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="center muted" style="padding:14px;">Tidak ada data.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Optional: memunculkan prompt print saat dibuka di browser --}}
  <script>
    try {
      if (typeof window !== 'undefined' && window.print) {
        setTimeout(()=>window.print(), 300);
      }
    } catch(e){}
  </script>
</body>
</html>
