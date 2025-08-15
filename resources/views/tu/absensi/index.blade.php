@extends('layouts.tu')
@section('title','Presensi TU')
@section('subtitle','Absen pribadi TU + Riwayat & Izin dalam satu halaman')

@section('content')
  @if (session('message'))
    <div class="mb-4 rounded-lg border-l-4 border-emerald-500 bg-emerald-50 p-3 text-emerald-700 text-sm">
      {{ session('message') }}
    </div>
  @endif

  {{-- Tabs --}}
  @php
    $tab = $tab ?? 'absen';
    $btn = function($name,$label) use($tab){
      $active = $tab===$name;
      return '<a href="'.route('tu.absensi.index',['tab'=>$name]).'"
                class="px-4 py-2 rounded-lg text-sm '.($active?'bg-slate-900 text-white':'bg-white ring-1 ring-slate-200 hover:bg-slate-50').'">'.$label.'</a>';
    };
  @endphp

  <div class="mb-6 flex items-center gap-2">
    {!! $btn('absen','Absensi') !!}
    {!! $btn('riwayat','Riwayat Saya') !!}
    {!! $btn('izin','Izin Saya') !!}
  </div>

  @if($tab==='absen')
    {{-- =================== TAB ABSEN (MASUK/KELUAR) =================== --}}
    <div class="grid lg:grid-cols-2 gap-6">
      <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Presensi Hari Ini</h2>
          <div class="text-right">
            <p class="text-xs text-slate-500">Sekarang</p>
            <p class="font-medium">{{ $now->translatedFormat('l, d F Y · H:i') }} WIB</p>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
          {{-- Masuk --}}
          <div class="rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Masuk (07:00–{{ $deadlineMasuk }})</p>
            <p class="text-4xl font-extrabold tabular-nums mt-1" id="clock1">--:--:--</p>
            <form method="POST" action="{{ route('tu.absensi.storeMasuk') }}" class="mt-4 space-y-2">
              @csrf
              <input type="hidden" name="latitude"  id="latIn">
              <input type="hidden" name="longitude" id="lngIn">
              <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700
                {{ ($todayRec && $todayRec->jam_masuk) ? 'pointer-events-none opacity-50' : '' }}">
                {{ ($todayRec && $todayRec->jam_masuk) ? 'Sudah Masuk' : 'Masuk' }}
              </button>
              <p id="warnIn" class="text-xs text-amber-600 hidden">Mengambil lokasi…</p>
            </form>
          </div>

          {{-- Keluar --}}
          <div class="rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Keluar (mulai {{ $mulaiKeluar }})</p>
            <p class="text-4xl font-extrabold tabular-nums mt-1" id="clock2">--:--:--</p>
            <form method="POST" action="{{ route('tu.absensi.storeKeluar') }}" class="mt-4 space-y-2">
              @csrf
              <input type="hidden" name="latitude"  id="latOut">
              <input type="hidden" name="longitude" id="lngOut">
              <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700
                {{ (!$todayRec || !$todayRec->jam_masuk || $todayRec->jam_keluar) ? 'pointer-events-none opacity-50' : '' }}">
                {{ ($todayRec && $todayRec->jam_keluar) ? 'Sudah Keluar' : 'Keluar' }}
              </button>
              <p id="warnOut" class="text-xs text-amber-600 hidden">Mengambil lokasi…</p>
            </form>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
        <h3 class="text-sm font-semibold mb-3">Lokasi Presensi</h3>
        <div id="map" class="w-full h-96 rounded-xl border"></div>
        <p class="mt-2 text-xs text-slate-500">Titik & radius area ditampilkan (server tetap memvalidasi).</p>
      </section>
    </div>

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
      // Jam live
      const pad=n=>String(n).padStart(2,'0');
      setInterval(()=>{
        const d=new Date(), t=`${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        document.getElementById('clock1').textContent=t;
        document.getElementById('clock2').textContent=t;
      },1000);

      // Peta
      const base={lat:{{ $base['lat'] }},lng:{{ $base['lng'] }},radius:{{ $base['radius'] }}};
      const map=L.map('map',{zoomControl:true}).setView([base.lat,base.lng],17);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:20,attribution:'&copy; OpenStreetMap'}).addTo(map);
      L.marker([base.lat,base.lng]).addTo(map);
      L.circle([base.lat,base.lng],{radius:base.radius,color:'#0ea5e9',fillColor:'#38bdf8',fillOpacity:.15}).addTo(map);

      // Lokasi
      function ambilLokasi(latEl,lngEl,warnEl){
        warnEl.classList.remove('hidden');
        if(!navigator.geolocation){ warnEl.textContent='Browser tidak mendukung lokasi.'; return; }
        navigator.geolocation.getCurrentPosition(pos=>{
          latEl.value=pos.coords.latitude; lngEl.value=pos.coords.longitude;
          warnEl.classList.add('hidden');
          const me=[pos.coords.latitude,pos.coords.longitude];
          L.marker(me,{title:'Posisi Anda',icon:L.divIcon({className:'',html:'<div class="w-3 h-3 rounded-full bg-emerald-500 border-2 border-emerald-800"></div>'})}).addTo(map);
          map.panTo(me);
        },()=>{ warnEl.textContent='Gagal ambil lokasi. Aktifkan GPS & izin lokasi.'; },{enableHighAccuracy:true,timeout:8000});
      }
      ambilLokasi(document.getElementById('latIn'),document.getElementById('lngIn'),document.getElementById('warnIn'));
      ambilLokasi(document.getElementById('latOut'),document.getElementById('lngOut'),document.getElementById('warnOut'));
    </script>
  @endif

  @if($tab==='riwayat')
    {{-- =================== TAB RIWAYAT (TU sendiri) =================== --}}
    <form method="get" class="mb-4 grid sm:grid-cols-4 gap-3 items-end">
      <input type="hidden" name="tab" value="riwayat">
      <div>
        <label class="text-xs text-slate-500">Tahun</label>
        <select name="tahun" class="mt-1 w-full rounded-lg border-slate-300">
          @foreach(range(now()->year-3, now()->year) as $t)
            <option value="{{ $t }}" @selected($t==$tahun)>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-slate-500">Bulan</label>
        <select name="bulan" class="mt-1 w-full rounded-lg border-slate-300">
          @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $k=>$v)
            <option value="{{ $k }}" @selected($k==$bulan)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-slate-500">Status</label>
        <select name="status" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">Semua</option>
          @foreach(['hadir','izin','sakit','alfa'] as $s)
            <option value="{{ $s }}" @selected($s==$status)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div><button class="px-4 py-2 rounded-lg bg-slate-900 text-white">Filter</button></div>
    </form>

    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
      <table class="min-w-[760px] w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-500 border-b">
            <th class="px-4 py-3">Tanggal</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Masuk</th>
            <th class="px-4 py-3">Keluar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($data as $r)
            <tr class="border-b last:border-0">
              <td class="px-4 py-3">{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('l, d F Y') }}</td>
              <td class="px-4 py-3">{{ ucfirst($r->status) }}</td>
              <td class="px-4 py-3">{{ $r->jam_masuk ?? '—' }}</td>
              <td class="px-4 py-3">{{ $r->jam_keluar ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $data->links() }}</div>
  @endif

  @if($tab==='izin')
    {{-- =================== TAB IZIN (TU sendiri) =================== --}}
    <div class="grid lg:grid-cols-3 gap-6">
      <section class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
        <h3 class="text-sm font-semibold mb-3">Pengajuan Izin</h3>
        <form method="post" action="{{ route('tu.absensi.izin.store') }}" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-4">
          @csrf
          <div>
            <label class="text-sm">Jenis</label>
            <select name="jenis" class="mt-1 w-full rounded-lg border-slate-300">
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
            </select>
          </div>
          <div>
            <label class="text-sm">Tanggal</label>
            <input type="date" name="tanggal" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border-slate-300">
          </div>
          <div class="sm:col-span-2">
            <label class="text-sm">Keterangan (opsional)</label>
            <textarea name="keterangan" rows="3" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Alasan singkat"></textarea>
          </div>
          <div class="sm:col-span-2">
            <label class="text-sm">Bukti (jpg/png/pdf, maks 2MB)</label>
            <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf"
              class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-slate-900 file:text-white hover:file:bg-slate-700">
          </div>
          <div class="sm:col-span-2">
            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white">Kirim Pengajuan</button>
          </div>
        </form>
      </section>

      <aside class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
        <h3 class="text-sm font-semibold mb-3">Riwayat Pengajuan</h3>
        @if(!$izinItems || $izinItems->isEmpty())
          <p class="text-sm text-slate-500">Belum ada pengajuan.</p>
        @else
          <ul class="space-y-3">
            @foreach($izinItems as $iz)
              <li class="p-3 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium capitalize">{{ $iz->jenis }}</p>
                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($iz->tanggal)->translatedFormat('l, d F Y') }}</p>
                  </div>
                  <span class="text-xs px-2 py-1 rounded-md
                    {{ $iz->status==='pending' ? 'bg-amber-100 text-amber-700' : ($iz->status==='approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700') }}">
                    {{ ucfirst($iz->status) }}
                  </span>
                </div>
                @if($iz->keterangan)
                  <p class="text-xs mt-2 text-slate-600">{{ $iz->keterangan }}</p>
                @endif
                @if($iz->status==='pending')
                  <form method="post" action="{{ route('tu.absensi.izin.destroy',$iz->id) }}" class="mt-2">@csrf @method('delete')
                    <button class="text-xs text-rose-600 hover:underline">Batalkan</button>
                  </form>
                @endif
              </li>
            @endforeach
          </ul>
          <div class="mt-3">{{ $izinItems->links() }}</div>
        @endif
      </aside>
    </div>
  @endif
@endsection
