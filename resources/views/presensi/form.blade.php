@extends('layouts.presensi')
@section('title', $mode==='masuk'?'Konfirmasi Presensi Masuk':'Konfirmasi Presensi Keluar')

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
    <h2 class="text-lg font-semibold mb-1">
      {{ $mode==='masuk' ? 'Konfirmasi Presensi Masuk' : 'Konfirmasi Presensi Keluar' }}
    </h2>
    <p class="text-sm text-slate-500 mb-4">
      {{ $mode==='masuk' ? "Batas masuk: $deadlineMasuk" : "Mulai keluar: $mulaiKeluar" }} · Sekarang: {{ $now->format('H:i') }}
    </p>

    @if (session('message'))
      <div class="mb-4 rounded-lg border-l-4 border-amber-500 bg-amber-50 p-3 text-amber-700">{{ session('message') }}</div>
    @endif

    <form method="POST" action="{{ $mode==='masuk' ? route('presensi.storeMasuk') : route('presensi.storeKeluar') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm text-slate-600">Koordinat Anda</label>
        <div class="grid grid-cols-2 gap-2">
          <input id="lat" name="latitude"  class="rounded-lg border-slate-300" readonly>
          <input id="lng" name="longitude" class="rounded-lg border-slate-300" readonly>
        </div>
        <p id="dist" class="mt-2 text-xs text-slate-500">Mengambil lokasi…</p>
      </div>

      <button id="btnConfirm"
        class="px-4 py-2 rounded-lg {{ $mode==='masuk' ? 'bg-indigo-600' : 'bg-emerald-600' }} text-white disabled:opacity-50"
        {{ $mode==='masuk' ? ($allowMasuk?'':'disabled') : ($allowKeluar?'':'disabled') }}>
        Konfirmasi {{ $mode==='masuk' ? 'Masuk' : 'Keluar' }}
      </button>

      @if($mode==='masuk' && !$allowMasuk)
        <p class="text-xs text-rose-600">Presensi masuk ditutup pukul {{ $deadlineMasuk }}.</p>
      @endif
      @if($mode==='keluar' && !$allowKeluar)
        <p class="text-xs text-rose-600">Presensi keluar mulai pukul {{ $mulaiKeluar }}.</p>
      @endif
    </form>
  </section>

  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
    <h3 class="text-sm font-semibold mb-3">Lokasi Presensi</h3>
    <div id="map" class="w-full h-96 rounded-xl border"></div>
    <p class="mt-2 text-xs text-slate-500">Titik & radius area ditampilkan di peta (server tetap memvalidasi).</p>
  </section>
</div>
{{-- Leaflet assets (CDN, gratis, tak perlu API key) --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  const base = { lat: {{ $base['lat'] }}, lng: {{ $base['lng'] }}, radius: {{ $base['radius'] }} };

  // Inisialisasi peta
  const map = L.map('map', { zoomControl: true }).setView([base.lat, base.lng], 17);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20, attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  // Titik & radius geofence
  L.marker([base.lat, base.lng], { title: 'Titik Presensi' }).addTo(map);
  L.circle([base.lat, base.lng], { radius: base.radius, color: '#0ea5e9', fillColor: '#38bdf8', fillOpacity: 0.15 }).addTo(map);

  // Ambil lokasi pengguna
  const latI = document.getElementById('lat');
  const lngI = document.getElementById('lng');
  const dist = document.getElementById('dist');
  const btn  = document.getElementById('btnConfirm');
  let meMarker, accCircle;

  function haversine(lat1,lon1,lat2,lon2){
    const R=6371000, toRad=d=>d*Math.PI/180;
    const dLat=toRad(lat2-lat1), dLon=toRad(lon2-lon1);
    const a=Math.sin(dLat/2)**2+Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLon/2)**2;
    return 2*R*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
      const me = [pos.coords.latitude, pos.coords.longitude];
      latI.value = me[0]; lngI.value = me[1];

      meMarker = L.marker(me, { title: 'Posisi Anda', icon: L.divIcon({className:'', html:'<div class=\"w-3 h-3 rounded-full bg-emerald-500 border-2 border-emerald-800\"></div>'}) }).addTo(map);
      if (pos.coords.accuracy) {
        accCircle = L.circle(me, { radius: pos.coords.accuracy, color:'#16a34a', fillColor:'#22c55e', fillOpacity:0.1 }).addTo(map);
      }
      map.panTo(me);

      const d = haversine(me[0], me[1], base.lat, base.lng);
      dist.textContent = `Jarak ke titik presensi: ± ${Math.round(d)} m`;
    }, () => {
      dist.textContent = 'Tidak bisa mengambil lokasi. Aktifkan GPS & izin lokasi.';
      btn.setAttribute('disabled', true);
    }, { enableHighAccuracy:true, timeout:8000 });
  } else {
    dist.textContent = 'Browser tidak mendukung geolokasi.';
    btn.setAttribute('disabled', true);
  }
</script>
@endsection
