<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <style>
    body{ font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; }
    table{ border-collapse: collapse; width:100%; }
    th,td{ border:1px solid #ddd; padding:6px 8px; }
    th{ background:#f3f4f6; }
    h3{ margin:0 0 6px 0; }
    .muted{ color:#6b7280; }
    .right{ text-align:right; }
  </style>
</head>
<body>
  <h3>Laporan Presensi</h3>
  <div class="muted">Periode: {{ $from->toDateString() }} s/d {{ $to->toDateString() }}</div>
  <br>
  <table>
    <thead>
      <tr>
        <th>Nama</th>
        <th>Jabatan</th>
        <th class="right">Hadir</th>
        <th class="right">Telat</th>
        <th class="right">Sakit</th>
        <th class="right">Izin</th>
        <th class="right">Alpha</th>
        <th class="right">Total</th>
        <th class="right">Rata Telat (mnt)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
      <tr>
        <td>{{ $r->name }}</td>
        <td>{{ $r->jabatan }}</td>
        <td class="right">{{ $r->hadir }}</td>
        <td class="right">{{ $r->telat }}</td>
        <td class="right">{{ $r->sakit }}</td>
        <td class="right">{{ $r->izin }}</td>
        <td class="right">{{ $r->alpha }}</td>
        <td class="right">{{ $r->total }}</td>
        <td class="right">{{ $r->rata_telat }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
