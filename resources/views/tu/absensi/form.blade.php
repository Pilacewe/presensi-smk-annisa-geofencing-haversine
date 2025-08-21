
@section('title', $mode==='masuk'?'Konfirmasi Masuk':'Konfirmasi Keluar')

@section('content')
<section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 max-w-3xl">
  <h2 class="text-lg font-semibold mb-1">
    {{ $mode==='masuk' ? 'Konfirmasi Presensi Masuk' : 'Konfirmasi Presensi Keluar' }}
  </h2>
  <p class="text-sm text-slate-500 mb-4">Sekarang: {{ $now->format('H:i') }} WIB</p>

  <form method="POST" action="{{ $mode==='masuk' ? route('tu.absensi.storeMasuk') : route('tu.absensi.storeKeluar') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-2 gap-2">
      <input id="lat" name="latitude"  class="rounded-lg border-slate-300" readonly placeholder="latitude">
      <input id="lng" name="longitude" class="rounded-lg border-slate-300" readonly placeholder="longitude">
    </div>
    <p id="info" class="text-xs text-slate-500">Mengambil lokasi…</p>
    <button id="btn" class="px-4 py-2 rounded-lg {{ $mode==='masuk' ? 'bg-indigo-600' : 'bg-emerald-600' }} text-white disabled:opacity-50" disabled>
      Konfirmasi {{ $mode==='masuk' ? 'Masuk' : 'Keluar' }}
    </button>
  </form>
</section>

<script>
  const lat = document.getElementById('lat');
  const lng = document.getElementById('lng');
  const btn = document.getElementById('btn');
  const info= document.getElementById('info');
  if(navigator.geolocation){
    navigator.geolocation.getCurrentPosition(pos=>{
      lat.value=pos.coords.latitude; lng.value=pos.coords.longitude;
      btn.disabled=false; info.textContent='Lokasi didapat.';
    },()=>{ info.textContent='Gagal mengambil lokasi.'; });
  }else{
    info.textContent='Browser tidak mendukung geolokasi.';
  }
</script>
@endsection
