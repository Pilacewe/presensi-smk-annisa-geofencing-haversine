@extends('layouts.pegawai')

@section('content')
<div class="bg-white rounded-xl shadow-md p-6 w-full max-w-md text-center">
    <h2 class="text-2xl font-bold mb-4 text-purple-700">Presensi Hari Ini</h2>
    <p class="mb-6 text-gray-600">Silakan klik tombol di bawah untuk melakukan presensi berdasarkan lokasi Anda.</p>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('guru.presensi.store') }}">
        @csrf
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-6 rounded-lg transition">
            Presensi Sekarang
        </button>
    </form>
</div>

<script>
    navigator.geolocation.getCurrentPosition(function(position) {
        document.getElementById('latitude').value = position.coords.latitude;
        document.getElementById('longitude').value = position.coords.longitude;
    }, function(error) {
        alert("Gagal mendapatkan lokasi. Aktifkan GPS.");
    });
</script>
@endsection
