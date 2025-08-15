
@extends('layouts.presensi')
@section('title','Presensi Pegawai')
@section('content')
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Kartu: Presensi Masuk -->
    <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Presensi Hari Ini</h2>
        <div class="text-right">
          <p class="text-xs text-slate-500">Tanggal</p>
          <p class="font-medium">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <!-- Card Masuk -->
        <div class="rounded-xl border border-slate-200 p-5">
          <p class="text-sm text-slate-500 mb-1">Presensi masuk</p>
          <div class="text-4xl font-extrabold tabular-nums" id="jamMasukDisplay">--:--:--</div>
          <p class="mt-2 text-xs text-slate-500">
  Buka: {{ config('presensi.jam_masuk_start') }}–{{ config('presensi.jam_masuk_end') }} WIB
</p>

          <form method="POST" action="{{ route('presensi.storeMasuk') }}" class="mt-5 flex items-center gap-3">
            @csrf
            <input type="hidden" name="mode" value="masuk">
            <input type="hidden" name="latitude" id="latIn">
            <input type="hidden" name="longitude" id="lngIn">
            <a href="{{ route('presensi.formMasuk') }}"  class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Masuk</a>
            <span id="locWarnIn" class="text-xs text-amber-600 hidden">Mengambil lokasi… izinkan GPS.</span>
          </form>
        </div>

        <!-- Card Keluar -->
        <div class="rounded-xl border border-slate-200 p-5">
          <p class="text-sm text-slate-500 mb-1">Presensi keluar</p>
          <div class="text-4xl font-extrabold tabular-nums" id="jamKeluarDisplay">--:--:--</div>
          <p class="mt-2 text-xs text-slate-500">
  Mulai keluar: {{ config('presensi.jam_keluar_start') }} WIB
</p>

          <form method="POST" action="{{ route('presensi.storeKeluar') }}" class="mt-5 flex items-center gap-3">
            @csrf
            <input type="hidden" name="mode" value="keluar">
            <input type="hidden" name="latitude" id="latOut">
            <input type="hidden" name="longitude" id="lngOut">
            <a href="{{ route('presensi.formKeluar') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white">Keluar</a>
            <span id="locWarnOut" class="text-xs text-amber-600 hidden">Mengambil lokasi… izinkan GPS.</span>
          </form>
        </div>
      </div>

      @if (session('message'))
        <div class="mt-5 rounded-lg border-l-4 border-emerald-500 bg-emerald-50 p-4 text-emerald-700">{{ session('message') }}</div>
      @endif

      @error('latitude')
        <div class="mt-5 rounded-lg border-l-4 border-rose-500 bg-rose-50 p-4 text-rose-700">Lokasi wajib diizinkan.</div>
      @enderror
    </section>

    <!-- Samping: Info singkat & Statistik pribadi -->
    <aside class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
      <div class="flex items-center gap-4 mb-4">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white grid place-items-center font-bold">{{ Str::of(auth()->user()->name)->substr(0,1)->upper() }}</div>
        <div>
          <p class="font-semibold leading-tight">{{ auth()->user()->name }}</p>
          <p class="text-xs text-slate-500">{{ strtoupper(auth()->user()->role) }}</p>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-3 text-center">
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="text-xs text-slate-500">Hadir</p>
          <p class="text-xl font-bold">{{ $stat['hadir'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="text-xs text-slate-500">Sakit</p>
          <p class="text-xl font-bold">{{ $stat['sakit'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="text-xs text-slate-500">Izin</p>
          <p class="text-xl font-bold">{{ $stat['izin'] ?? 0 }}</p>
        </div>
      </div>

      <a href="{{ route('presensi.riwayat') }}" class="mt-4 inline-flex items-center gap-2 text-sm text-indigo-700 hover:underline">Lihat riwayat →</a>
    </aside>
  </div>

  <script>
    // Jam realtime
    const jamMasuk = document.getElementById('jamMasukDisplay');
    const jamKeluar = document.getElementById('jamKeluarDisplay');
    const tick = ()=>{
      const d = new Date();
      const pad = (n)=> String(n).padStart(2,'0');
      const t = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
      jamMasuk.textContent = t;
      jamKeluar.textContent = t;
    };
    setInterval(tick,1000); tick();

    // Ambil lokasi untuk Masuk & Keluar
    function ambilLokasi(targetLat, targetLng, warnEl, btn) {
      warnEl.classList.remove('hidden');
      btn.setAttribute('disabled', true);
      if (!navigator.geolocation) {
        warnEl.textContent = 'Browser tidak mendukung geolokasi.';
        return;
      }
      navigator.geolocation.getCurrentPosition((pos)=>{
        targetLat.value = pos.coords.latitude;
        targetLng.value = pos.coords.longitude;
        warnEl.classList.add('hidden');
        btn.removeAttribute('disabled');
      },()=>{
        warnEl.textContent = 'Gagal mengambil lokasi. Aktifkan GPS & izin lokasi.';
      },{enableHighAccuracy:true, timeout:8000});
    }

    ambilLokasi(document.getElementById('latIn'), document.getElementById('lngIn'), document.getElementById('locWarnIn'), document.getElementById('btnMasuk'));
    ambilLokasi(document.getElementById('latOut'), document.getElementById('lngOut'), document.getElementById('locWarnOut'), document.getElementById('btnKeluar'));
  </script>
@endsection
