
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Export Presensi</title>
<style>
  body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; font-size: 12px; color:#111;}
  table{width:100%; border-collapse:collapse}
  th,td{border:1px solid #ddd; padding:6px}
  th{background:#f6f7fb; text-align:left}
  h2{margin:0 0 8px}
  .small{color:#666}
</style>
</head>
<body>
  <h2>Laporan Presensi</h2>
  <p class="small">Periode: {{ $from }} s/d {{ $to }}</p>
  <table>
    <thead>
      <tr><th>Nama</th><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ $r->user->name }}</td>
          <td>{{ $r->tanggal }}</td>
          <td>{{ $r->jam_masuk ?: '-' }}</td>
          <td>{{ $r->jam_keluar ?: '-' }}</td>
          <td>{{ ucfirst($r->status) }}</td>
        </tr>
      @empty
        <tr><td colspan="5">Tidak ada data.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
