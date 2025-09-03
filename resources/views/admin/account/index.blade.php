@extends('layouts.admin')

@section('title','Akun')
@section('subtitle','Profil admin & pengaturan aplikasi')

@section('content')
@if(session('success'))
  <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
    {{ session('success') }}
  </div>
@endif
@if ($errors->any())
  <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
    <ul class="list-disc ml-5 text-sm">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

@php
  $avatar = $u->avatar_path
    ? asset('storage/'.$u->avatar_path)
    : 'https://ui-avatars.com/api/?name='.urlencode($u->name).'&background=6366f1&color=fff&size=160';
@endphp

{{-- ===== HERO ===== --}}
<div class="mb-6 rounded-[26px] border border-slate-200 bg-gradient-to-r from-slate-50 via-indigo-50 to-sky-50">
  <div class="px-6 py-6 flex items-center justify-between gap-6">
    <div class="flex items-center gap-4 min-w-0">
      <img src="{{ $avatar }}" class="w-14 h-14 rounded-2xl object-cover border border-slate-200 shadow-sm" alt="">
      <div class="min-w-0">
        <p class="text-[11px] uppercase tracking-wider text-slate-500">Akun Admin</p>
        <p class="text-sm font-semibold truncate text-slate-900">{{ $u->name }}</p>
        <p class="text-[11px] text-slate-500">{{ $u->email }}</p>
      </div>
    </div>
    <div class="hidden md:flex items-center gap-2">
      <a href="#profile" class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm">Profil</a>
      <a href="#settings" class="px-3 py-2 rounded-xl bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Pengaturan</a>
    </div>
  </div>
</div>

{{-- ===== TOP CARDS ===== --}}
<div class="grid lg:grid-cols-3 gap-4 mb-6">
  {{-- Storage link --}}
  <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs text-slate-500">Status Storage Link</p>
        <p class="mt-1 font-medium {{ $storageOk ? 'text-emerald-700' : 'text-amber-700' }}">
          {{ $storageOk ? 'Tersambung' : 'Belum tersambung' }}
        </p>
        <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ $storageMsg }}</p>
      </div>
      <span class="px-2.5 py-1 rounded-lg text-[11px] {{ $storageOk ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }} ring-1">
        {{ $storageOk ? 'AKTIF' : 'CEK' }}
      </span>
    </div>
  </div>

  {{-- Keamanan --}}
  <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <p class="text-xs text-slate-500">Keamanan</p>
    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
      <span class="px-3 py-2 rounded-xl bg-slate-50 ring-1 ring-slate-200">Gunakan password kuat</span>
      <span class="px-3 py-2 rounded-xl bg-slate-50 ring-1 ring-slate-200">Ganti berkala</span>
    </div>
  </div>

  {{-- Preferensi ringkas --}}
  <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <p class="text-xs text-slate-500">Preferensi</p>
    <div class="mt-2 text-sm text-slate-700">
      <div>Zona waktu: <b>{{ $settings['timezone'] }}</b></div>
      <div>Ringkasan harian: <b>{{ str_pad($settings['digest_hour'],2,'0',STR_PAD_LEFT) }}:00</b></div>
      <div>Email izin baru: <b>{{ $settings['notif_email_on_izin'] ? 'YA' : 'TIDAK' }}</b></div>
    </div>
  </div>
</div>

{{-- ===== BODY: NAV VERTIKAL + CONTENT ===== --}}
<div class="grid lg:grid-cols-[230px,1fr] gap-4">
  {{-- Side menu --}}
  <aside class="rounded-2xl bg-white border border-slate-200 p-3">
    @php
      $menu = [
        ['id'=>'profile','label'=>'Profil & Kontak','icon'=>'👤'],
        ['id'=>'avatar','label'=>'Foto Profil','icon'=>'🖼️'],
        ['id'=>'password','label'=>'Ubah Password','icon'=>'🔒'],
        ['id'=>'settings','label'=>'Pengaturan Presensi','icon'=>'🧭'],
        ['id'=>'preferences','label'=>'Preferensi Notifikasi','icon'=>'🔔'],
        ['id'=>'sessions','label'=>'Manajemen Sesi','icon'=>'💻'],
        ['id'=>'danger','label'=>'Zona Bahaya','icon'=>'⛔'],
      ];
    @endphp
    <nav class="space-y-1">
      @foreach($menu as $m)
        <a href="#{{ $m['id'] }}"
           class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-slate-50 text-sm text-slate-700">
          <span class="text-base">{{ $m['icon'] }}</span>
          <span>{{ $m['label'] }}</span>
        </a>
      @endforeach
    </nav>
  </aside>

  {{-- Content --}}
  <section class="space-y-6">

    {{-- Profil --}}
    <div id="profile" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-4">Profil & Kontak</h3>
      <form method="POST" action="{{ route('admin.account.profile.update') }}" class="grid md:grid-cols-2 gap-4">
        @csrf @method('PATCH')
        <div>
          <label class="text-sm text-slate-600">Nama</label>
          <input type="text" name="name" value="{{ old('name',$u->name) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Email</label>
          <input type="email" name="email" value="{{ old('email',$u->email) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div class="md:col-span-2">
          <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Simpan</button>
        </div>
      </form>
    </div>

    {{-- Avatar --}}
    <div id="avatar" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-4">Foto Profil</h3>
      <div class="flex items-start gap-5">
        <img src="{{ $avatar }}" class="w-20 h-20 rounded-xl object-cover border border-slate-200" alt="">
        <form action="{{ route('admin.account.avatar.update') }}" method="POST" enctype="multipart/form-data" class="grid md:grid-cols-[1fr,auto] gap-3 flex-1">
          @csrf
          <input type="file" name="avatar" class="rounded-xl border-slate-300">
          <button class="px-4 py-2 rounded-xl bg-slate-900 text-white">Upload</button>
        </form>
      </div>
    </div>

    {{-- Password --}}
    <div id="password" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-4">Ubah Password</h3>
      <form method="POST" action="{{ route('admin.account.password.update') }}" class="grid md:grid-cols-3 gap-4">
        @csrf @method('PATCH')
        <div>
          <label class="text-sm text-slate-600">Password saat ini</label>
          <input type="password" name="current_password" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Password baru</label>
          <input type="password" name="password" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Konfirmasi</label>
          <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div class="md:col-span-3">
          <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Update Password</button>
        </div>
      </form>
    </div>

    {{-- Pengaturan Presensi --}}
    <div id="settings" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-4">Pengaturan Presensi</h3>
      <form method="POST" action="{{ route('admin.account.settings.update') }}" class="grid md:grid-cols-3 gap-4">
        @csrf @method('PATCH')
        <div>
          <label class="text-sm text-slate-600">Jam target masuk</label>
          <input type="time" name="jam_target_masuk" value="{{ old('jam_target_masuk',$settings['jam_target_masuk']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Jam mulai scan masuk</label>
          <input type="time" name="jam_masuk_start" value="{{ old('jam_masuk_start',$settings['jam_masuk_start']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Jam mulai scan keluar</label>
          <input type="time" name="jam_keluar_start" value="{{ old('jam_keluar_start',$settings['jam_keluar_start']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Radius (meter)</label>
          <input type="number" min="50" max="1000" name="radius" value="{{ old('radius',$settings['radius']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Office Latitude</label>
          <input type="text" name="office_lat" value="{{ old('office_lat',$settings['office_lat']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm text-slate-600">Office Longitude</label>
          <input type="text" name="office_lng" value="{{ old('office_lng',$settings['office_lng']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>

        <div class="md:col-span-3 h-px bg-slate-200 my-1"></div>

        <div>
          <label class="text-sm text-slate-600">Zona waktu</label>
          <select name="timezone" class="mt-1 w-full rounded-xl border-slate-300">
            @php $tzList = ['Asia/Jakarta','Asia/Makassar','Asia/Jayapura','UTC']; @endphp
            @foreach($tzList as $tz)
              <option value="{{ $tz }}" @selected(old('timezone',$settings['timezone'])==$tz)>{{ $tz }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm text-slate-600">Ringkasan harian (jam)</label>
          <input type="number" min="0" max="23" name="digest_hour" value="{{ old('digest_hour',$settings['digest_hour']) }}" class="mt-1 w-full rounded-xl border-slate-300">
        </div>
        <div class="flex items-center gap-2 pt-6">
          <input type="hidden" name="notif_email_on_izin" value="0">
          <input id="cekEmailIzin" type="checkbox" name="notif_email_on_izin" value="1"
                 @checked(old('notif_email_on_izin',$settings['notif_email_on_izin'])) class="rounded border-slate-300">
          <label for="cekEmailIzin" class="text-sm text-slate-700">Kirim email saat ada izin baru</label>
        </div>

        <div class="md:col-span-3">
          <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Simpan Pengaturan</button>
        </div>
      </form>
    </div>

    {{-- Preferensi (informasi) --}}
    <div id="preferences" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-2">Preferensi Notifikasi</h3>
      <p class="text-sm text-slate-600">Preferensi disimpan pada formulir “Pengaturan Presensi”.</p>
      <ul class="mt-3 text-sm text-slate-700 list-disc ml-5">
        <li>Email masuk saat ada izin baru.</li>
        <li>Ringkasan harian pada jam terpilih.</li>
      </ul>
    </div>

    {{-- Sesi --}}
    <div id="sessions" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-slate-900 mb-4">Manajemen Sesi</h3>
      <p class="text-sm text-slate-600 mb-3">
        Akhiri semua sesi di perangkat lain (kecuali perangkat ini). Wajib masukkan password saat ini.
      </p>
      <form action="{{ route('admin.account.sessions.endOthers') }}" method="POST" class="grid md:grid-cols-[1fr,auto] gap-3 max-w-xl">
        @csrf
        <input type="password" name="password" placeholder="Password saat ini" class="rounded-xl border-slate-300">
        <button class="px-4 py-2 rounded-xl bg-rose-600 text-white">Akhiri Sesi Lain</button>
      </form>
      @unless($canListSessions)
        <p class="text-xs text-slate-500 mt-3">Catatan: untuk melihat daftar sesi secara detail, gunakan session driver <code>database</code>.</p>
      @endunless
    </div>

    {{-- Danger --}}
    <div id="danger" class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
      <h3 class="font-semibold text-rose-700 mb-3">Zona Bahaya</h3>
      <div class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl border border-rose-200 bg-rose-50">
        <div class="text-sm">
          <p class="font-medium text-rose-700">Hapus Foto Profil</p>
          <p class="text-rose-700/80">Tindakan ini tidak bisa dibatalkan.</p>
        </div>
        <form action="{{ route('admin.account.avatar.delete') }}" method="POST" onsubmit="return confirm('Hapus foto profil?')">
          @csrf @method('DELETE')
          <button class="px-4 py-2 rounded-xl bg-rose-600 text-white">Hapus Foto</button>
        </form>
      </div>
    </div>

  </section>
</div>
@endsection
