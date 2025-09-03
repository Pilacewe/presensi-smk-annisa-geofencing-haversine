@extends('layouts.tu')

@section('title','Presensi TU')
@section('subtitle','Absen pribadi TU + Riwayat & Izin dalam satu halaman')

@section('content')
{{-- Alert aturan jam --}}
@if (session('success'))
  <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-3">
    {{ session('success') }}
  </div>
@endif
@if (session('message'))
  <div class="mb-4 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 px-4 py-3">
    {{ session('message') }}
  </div>
@endif

@php
  // target jam telat (mengikuti pegawai)
  $targetMasuk = config('presensi.jam_target_masuk','07:00');

  // helper kecil untuk format menit -> "X jam Y menit" / "Y menit"
  $fmtTelat = function ($m) {
      if (!$m) return null;
      $h = intdiv((int)$m, 60);
      $mm = ((int)$m) % 60;
      return $h ? ($mm ? "$h jam $mm menit" : "$h jam") : "$mm menit";
  };

  // ambil HH:MM dari field waktu
  $hhmm = fn($v) => $v ? \Illuminate\Support\Str::of($v)->substr(0,5) : '—';
@endphp

<div class="mb-4 rounded-lg bg-emerald-50/60 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
  Presensi masuk hanya <b>{{ $mulaiMasuk }}–{{ $akhirMasuk }}</b>. Presensi keluar <b>mulai {{ $mulaiKeluar }}</b>.
  <span class="inline-block ml-2 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px]">
    Target tepat waktu {{ $targetMasuk }}
  </span>
</div>

{{-- Tabs --}}
<div class="flex flex-wrap gap-2 mb-6">
  @php
    $pill = function($label,$href,$active){
      return $active
        ? "<a href=\"$href\" class=\"px-4 py-2 rounded-xl bg-slate-900 text-white text-sm\">$label</a>"
        : "<a href=\"$href\" class=\"px-4 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50\">$label</a>";
    };
  @endphp
  {!! $pill('Presensi', route('tu.absensi.index',['tab'=>'absen']), $tab==='absen') !!}
  {!! $pill('Riwayat Saya', route('tu.absensi.index',['tab'=>'riwayat']), $tab==='riwayat') !!}
  {!! $pill('Izin Saya', route('tu.absensi.index',['tab'=>'izin']), $tab==='izin') !!}
</div>

@if ($tab==='absen')
  {{-- ====== TAB ABSEN ====== --}}
  <div class="grid lg:grid-cols-2 gap-6">

    {{-- Kartu: Presensi Hari Ini --}}
    <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Presensi Hari Ini</h2>
          <p class="text-xs text-slate-500">{{ $now->translatedFormat('l, d F Y') }} · <span id="nowClock">--:--:--</span> WIB</p>
        </div>
        <div class="text-right">
          <p class="text-[11px] text-slate-500">Akurasi lokasi</p>
          <p class="text-sm"><span id="accChip" class="inline-block px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600">-</span></p>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        {{-- Card Masuk --}}
        <div class="rounded-xl border border-slate-200 p-5">
          <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500">Masuk ({{ $mulaiMasuk }}–{{ $akhirMasuk }})</p>
            <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
              Target {{ $targetMasuk }}
            </span>
          </div>
          <div class="mt-2 text-4xl font-extrabold tabular-nums" id="clockIn">--:--:--</div>

          {{-- STATUS HARI INI (Masuk) --}}
          @if ($todayRec?->jam_masuk)
            @php
              $stToday = strtolower($todayRec->status ?? '');
              // pakai telat_menit bila ada; fallback hitung dari jam_masuk vs target
              $telatM = $todayRec->telat_menit ?? null;
              if (is_null($telatM) && $stToday === 'telat') {
                  try {
                      $tgt = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $targetMasuk);
                      $jm  = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $todayRec->jam_masuk);
                      $telatM = max(0, $tgt->diffInMinutes($jm, false));
                  } catch (\Throwable $e) {
                      $telatM = null;
                  }
              }
              $durTelat = $stToday === 'telat' ? $fmtTelat($telatM) : null;
            @endphp

            @if ($stToday === 'telat')
              <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                Telat {{ $durTelat ?? '—' }} (jam {{ $hhmm($todayRec->jam_masuk) }}).
              </div>
            @else
              <div class="mt-3 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                Sudah masuk pukul {{ $hhmm($todayRec->jam_masuk) }} (tepat waktu).
              </div>
            @endif
          @endif

          <form method="POST" action="{{ route('tu.absensi.storeMasuk') }}" class="mt-5 flex items-center gap-3">
            @csrf
            <input type="hidden" name="latitude"  id="latIn">
            <input type="hidden" name="longitude" id="lngIn">
            <button id="btnIn"
              class="px-4 py-2 rounded-lg bg-indigo-600 text-white disabled:opacity-50 disabled:cursor-not-allowed">
              Masuk
            </button>
            <span id="warnIn" class="text-xs text-amber-600 hidden">Mengambil lokasi… izinkan GPS.</span>
          </form>
        </div>

        {{-- Card Keluar --}}
        <div class="rounded-xl border border-slate-200 p-5">
          <p class="text-sm text-slate-500 mb-1">Keluar (mulai {{ $mulaiKeluar }})</p>
          <div class="text-4xl font-extrabold tabular-nums" id="clockOut">--:--:--</div>

          @if ($todayRec?->jam_keluar)
            <div class="mt-3 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
              Sudah keluar pukul {{ $hhmm($todayRec->jam_keluar) }}.
            </div>
          @endif

          <form method="POST" action="{{ route('tu.absensi.storeKeluar') }}" class="mt-5 flex items-center gap-3">
            @csrf
            <input type="hidden" name="latitude"  id="latOut">
            <input type="hidden" name="longitude" id="lngOut">
            <button id="btnOut"
              class="px-4 py-2 rounded-lg bg-emerald-600 text-white disabled:opacity-50 disabled:cursor-not-allowed">
              Keluar
            </button>
            <span id="warnOut" class="text-xs text-amber-600 hidden">Mengambil lokasi… izinkan GPS.</span>
          </form>
        </div>
      </div>

      <div class="mt-5 text-xs text-slate-500">
        <span id="distInfo" class="inline-block px-2 py-1 rounded bg-slate-50 ring-1 ring-slate-200">Jarak ke titik presensi: —</span>
      </div>
    </section>

    {{-- ====== KARTU PETA ====== --}}
    <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
      <div class="px-6 pt-6 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-semibold">Lokasi Presensi</h3>
          <p class="text-xs text-slate-500">Titik & radius area presensi ditampilkan di peta.</p>
        </div>
        <span id="distBadge"
          class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-slate-100 text-slate-700 ring-1 ring-slate-200">
          Mengambil lokasi…
        </span>
      </div>

      <div id="osmap" class="h-80 w-full z-0"></div>

      <div class="px-6 py-4 text-[11px] text-slate-500 border-t">
        <div class="flex items-center gap-3 flex-wrap">
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-emerald-700/60"></span> Posisi Anda
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-sky-500"></span> Titik Presensi
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-3 rounded-sm bg-sky-200 ring-1 ring-sky-400"></span> Radius ({{ (int)$base['radius'] }} m)
          </span>
        </div>
      </div>
    </section>
  </div>

@elseif ($tab==='riwayat')
  {{-- ====== TAB RIWAYAT ====== --}}
  @include('tu.absensi.riwayat', ['data'=>$data])

@else
  {{-- ====== TAB IZIN ====== --}}
  @include('tu.absensi.izin-index', ['items'=>$izinItems])
@endif

{{-- ====== Assets Leaflet (khusus peta) ====== --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>.leaflet-container{ z-index:0 !important; }</style>

{{-- ====== Script Jam, Geolokasi & Peta ====== --}}
<script>
  // Jam realtime
  const nowClock = document.getElementById('nowClock');
  const clockIn  = document.getElementById('clockIn');
  const clockOut = document.getElementById('clockOut');
  function tick(){
    const d = new Date();
    const pad = n => String(n).padStart(2,'0');
    const t = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    if(nowClock) nowClock.textContent = t;
    if(clockIn)  clockIn.textContent  = t;
    if(clockOut) clockOut.textContent = t;
  }
  setInterval(tick, 1000); tick();

  // Geofence client-side (opsional, server tetap menentukan)
  const base = { lat: {{ $base['lat'] }}, lng: {{ $base['lng'] }}, radius: {{ $base['radius'] }} };

  const latIn  = document.getElementById('latIn');
  const lngIn  = document.getElementById('lngIn');
  const latOut = document.getElementById('latOut');
  const lngOut = document.getElementById('lngOut');

  const btnIn  = document.getElementById('btnIn');
  const btnOut = document.getElementById('btnOut');

  const warnIn  = document.getElementById('warnIn');
  const warnOut = document.getElementById('warnOut');
  const accChip = document.getElementById('accChip');
  const distInfo= document.getElementById('distInfo');

  if(btnIn)  btnIn.disabled  = true;
  if(btnOut) btnOut.disabled = true;

  function haversine(lat1,lon1,lat2,lon2){
    const R=6371000, toRad=d=>d*Math.PI/180;
    const dLat=toRad(lat2-lat1), dLon=toRad(lon2-lon1);
    const a=Math.sin(dLat/2)**2+Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLon/2)**2;
    return 2*R*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
  }

  // ========== PETA ==========
  const mapEl = document.getElementById('osmap');
  let map, meMarker, accCircle;

  function initMap(){
    if(!mapEl) return;
    map = L.map('osmap', { zoomControl: true }).setView([base.lat, base.lng], 17);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 20, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    L.marker([base.lat, base.lng], { title:'Titik Presensi' }).addTo(map);
    L.circle([base.lat, base.lng], {
      radius: base.radius, color:'#0ea5e9', weight:1.5, fillColor:'#38bdf8', fillOpacity:0.15
    }).addTo(map);
  }

  const distBadge = document.getElementById('distBadge');

  function setCoords(pos){
    const {latitude, longitude, accuracy} = pos.coords;

    if(latIn){ latIn.value = latitude; lngIn.value = longitude; }
    if(latOut){ latOut.value = latitude; lngOut.value = longitude; }

    if(btnIn)  btnIn.disabled  = false;
    if(btnOut) btnOut.disabled = false;
    if(warnIn) warnIn.classList.add('hidden');
    if(warnOut) warnOut.classList.add('hidden');

    if(accChip && accuracy) accChip.textContent = `± ${Math.round(accuracy)} m`;

    const d = Math.round(haversine(latitude, longitude, base.lat, base.lng));
    if(distInfo) distInfo.textContent = `Jarak ke titik presensi: ± ${d} m (radius ${base.radius} m)`;

    if(map){
      if(!meMarker){
        meMarker = L.marker([latitude, longitude], {
          title:'Posisi Anda',
          icon: L.divIcon({className:'', html:'<div class="w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-emerald-700/60"></div>'})
        }).addTo(map);
      }else{
        meMarker.setLatLng([latitude, longitude]);
      }

      if(accuracy){
        if(!accCircle){
          accCircle = L.circle([latitude, longitude], {
            radius: accuracy, color:'#16a34a', weight:1, fillColor:'#22c55e', fillOpacity:0.08
          }).addTo(map);
        }else{
          accCircle.setLatLng([latitude, longitude]).setRadius(accuracy);
        }
      }

      const bounds = L.latLngBounds([[base.lat, base.lng],[latitude, longitude]]).pad(0.35);
      map.fitBounds(bounds);
    }

    if(distBadge){
      const inRadius = d <= base.radius;
      distBadge.textContent = `Jarak ke titik presensi: ± ${d} m`;
      distBadge.className = 'inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full ring-1 ' +
        (inRadius ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                  : 'bg-rose-50 text-rose-700 ring-rose-200');
    }
  }

  function onGeoError(){
    if(warnIn){ warnIn.classList.remove('hidden'); warnIn.textContent = 'Gagal mengambil lokasi. Aktifkan GPS & izin lokasi.'; }
    if(warnOut){ warnOut.classList.remove('hidden'); warnOut.textContent = 'Gagal mengambil lokasi. Aktifkan GPS & izin lokasi.'; }
    if(distBadge){
      distBadge.textContent = 'Tidak dapat mengambil lokasi — aktifkan GPS & izinkan lokasi.';
      distBadge.className = 'inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200';
    }
  }

  // Mulai
  initMap();
  if(navigator.geolocation){
    if(warnIn)  warnIn.classList.remove('hidden');
    if(warnOut) warnOut.classList.remove('hidden');
    navigator.geolocation.getCurrentPosition(setCoords, onGeoError, {enableHighAccuracy:true, timeout:10000});
  }else{
    onGeoError();
  }
</script>
@endsection
