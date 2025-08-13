@extends('layouts.tu')
@section('title','Absensi Guru (Manual)')

@section('actions')
@if (session('warning'))
  <div class="px-3 py-2 rounded-lg text-sm bg-amber-100 text-amber-700">{{ session('warning') }}</div>
@endif
@if (session('message'))
  <div class="px-3 py-2 rounded-lg text-sm bg-slate-100 text-slate-700">{{ session('message') }}</div>
@endif
@if (session('success'))
  <div class="px-3 py-2 rounded-lg text-sm bg-emerald-100 text-emerald-700">{{ session('success') }}</div>
@endif
@endsection

@section('content')
<form method="POST" action="{{ route('tu.absen.store') }}" class="grid lg:grid-cols-3 gap-6">
  @csrf

  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
    <h3 class="font-semibold mb-3">Data Absensi</h3>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Guru</label>
        <select name="user_id" class="mt-1 w-full rounded-lg border-slate-300" required>
          <option value="">Pilih guru…</option>
          @foreach($gurus as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Mode</label>
        <select name="mode" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="masuk">Masuk</option>
          <option value="keluar">Keluar</option>
        </select>
      </div>

      <div>
        <label class="text-sm font-medium">Tanggal</label>
        <input type="date" name="tanggal" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium">Jam (opsional)</label>
        <input type="time" name="jam" class="mt-1 w-full rounded-lg border-slate-300">
      </div>
    </div>

    <input type="hidden" id="latIn"  name="latitude">
    <input type="hidden" id="lngIn"  name="longitude">

    <div class="mt-5">
      <button id="btnGetLoc" type="button" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Ambil Lokasi</button>
      <span id="locWarn" class="ml-2 text-xs text-amber-600 hidden">Mengambil lokasi… izinkan GPS.</span>
    </div>

    <p class="text-xs text-slate-500 mt-2">Sistem akan mengecek jarak dari area sekolah ({{ config('presensi.radius') }} m).</p>
  </div>

  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
    <h3 class="font-semibold mb-3">Submit</h3>
    <p class="text-sm text-slate-600">Pastikan data benar & lokasi sudah diambil.</p>
    <button class="mt-4 w-full px-4 py-2 rounded-lg bg-slate-900 text-white">Simpan Presensi</button>
  </div>
</form>

<script>
  const btn = document.getElementById('btnGetLoc');
  btn?.addEventListener('click', ()=>{
    document.getElementById('locWarn').classList.remove('hidden');
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((pos)=>{
        document.getElementById('latIn').value = pos.coords.latitude.toFixed(6);
        document.getElementById('lngIn').value = pos.coords.longitude.toFixed(6);
        document.getElementById('locWarn').textContent = 'Lokasi berhasil diambil.';
        document.getElementById('locWarn').classList.remove('text-amber-600');
        document.getElementById('locWarn').classList.add('text-emerald-600');
      }, ()=>{
        document.getElementById('locWarn').textContent = 'Gagal mengambil lokasi.';
      });
    }
  });
</script>
@endsection
  