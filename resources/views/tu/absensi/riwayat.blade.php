{{-- Riwayat presensi TU (pribadi) --}}
@php
  $now   = now()->timezone(config('app.timezone'));
  $tahun = (int) request('tahun', $now->year);
  $bulan = (int) request('bulan', $now->month);
  $status= request('status');
  $years = range($now->year, $now->year - 4);
  $bulanMap = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
@endphp

<section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
  <div class="flex items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-semibold">Riwayat Presensi</h2>

    {{-- Filter --}}
    <form method="GET" action="{{ route('tu.absensi.index') }}" class="flex flex-wrap items-center gap-2">
      <input type="hidden" name="tab" value="riwayat">

      <select name="tahun" class="rounded-lg border-slate-300 text-sm">
        @foreach ($years as $y)
          <option value="{{ $y }}" @selected($tahun==$y)>{{ $y }}</option>
        @endforeach
      </select>

      <select name="bulan" class="rounded-lg border-slate-300 text-sm">
        @foreach ($bulanMap as $i=>$label)
          <option value="{{ $i }}" @selected($bulan==$i)>{{ $label }}</option>
        @endforeach
      </select>

      <select name="status" class="rounded-lg border-slate-300 text-sm">
        <option value="" @selected($status==null)>Semua status</option>
        <option value="hadir" @selected($status==='hadir')>Hadir</option>
        <option value="izin"  @selected($status==='izin')>Izin</option>
        <option value="sakit" @selected($status==='sakit')>Sakit</option>
        <option value="alfa"  @selected($status==='alfa')>Alfa</option>
      </select>

      <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm">Terapkan</button>
      <a href="{{ route('tu.absensi.index',['tab'=>'riwayat']) }}"
         class="px-3 py-2 rounded-lg bg-slate-100 text-sm">Reset</a>
    </form>
  </div>

  {{-- Tabel --}}
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="text-left text-slate-500">
        <tr class="border-b">
          <th class="py-2 pr-4">Tanggal</th>
          <th class="py-2 pr-4">Masuk</th>
          <th class="py-2 pr-4">Keluar</th>
          <th class="py-2 pr-4">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
      @forelse ($data as $row)
        @php
          $badge = [
            'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'izin'  => 'bg-amber-50  text-amber-700  ring-amber-200',
            'sakit' => 'bg-sky-50    text-sky-700    ring-sky-200',
            'alfa'  => 'bg-rose-50   text-rose-700   ring-rose-200',
          ][$row->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
        @endphp
        <tr>
          <td class="py-2 pr-4 whitespace-nowrap">
            {{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('l, d F Y') }}
          </td>
          <td class="py-2 pr-4 tabular-nums">{{ $row->jam_masuk ? \Illuminate\Support\Str::of($row->jam_masuk)->substr(0,5) : '—' }}</td>
          <td class="py-2 pr-4 tabular-nums">{{ $row->jam_keluar ? \Illuminate\Support\Str::of($row->jam_keluar)->substr(0,5) : '—' }}</td>
          <td class="py-2 pr-4">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs ring-1 {{ $badge }}">
              {{ ucfirst($row->status) }}
            </span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="py-6 text-center text-slate-500">Belum ada data pada periode ini.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginasi --}}
  @if (method_exists($data,'links'))
    <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
  @endif
</section>
