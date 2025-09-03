@extends('layouts.admin')

@section('title','Detail TU')
@section('subtitle','Profil, status hari ini, dan riwayat singkat')

@section('content')
  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Profil --}}
    <section class="lg:col-span-1 rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <div class="flex items-center gap-4">
        <img src="{{ $user->avatar_path ? asset('storage/'.$user->avatar_path) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=6366f1&color=fff&size=160' }}"
             class="w-20 h-20 rounded-2xl object-cover ring-1 ring-slate-200" alt="avatar">
        <div class="min-w-0">
          <h3 class="font-semibold text-slate-900 truncate">{{ $user->name }}</h3>
          <p class="text-sm text-slate-500 truncate">{{ $user->email }}</p>
          <p class="text-xs text-slate-500 mt-1">Jabatan: <b class="text-slate-700">{{ $user->jabatan ?: '—' }}</b></p>
          <p class="text-xs mt-1">
            @if($user->is_active)
              <span class="px-2 py-0.5 rounded-full text-[11px] bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">AKTIF</span>
            @else
              <span class="px-2 py-0.5 rounded-full text-[11px] bg-rose-50 text-rose-700 ring-1 ring-rose-200">NONAKTIF</span>
            @endif
          </p>
        </div>
      </div>

      <div class="mt-5 flex items-center gap-2">
        <a href="{{ route('admin.tu.edit',$user) }}" class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Edit</a>
        <form action="{{ route('admin.tu.reset',$user) }}" method="POST" onsubmit="return confirm('Reset password user ini?')">
          @csrf
          <button class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">Reset PW</button>
        </form>
        <form action="{{ route('admin.tu.destroy',$user) }}" method="POST" onsubmit="return confirm('Hapus user ini?')">
          @csrf @method('DELETE')
          <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Hapus</button>
        </form>
      </div>
    </section>

    {{-- Status hari ini + rekap bulan --}}
    <section class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 p-6">
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 p-4">
          <p class="text-xs text-slate-500">Status Hari Ini</p>
          @if($hariIni)
            <p class="mt-2 text-sm">
              <b class="uppercase">{{ $hariIni->status }}</b>
              @if($hariIni->jam_masuk) • Masuk: <b>{{ \Illuminate\Support\Str::of($hariIni->jam_masuk)->substr(0,5) }}</b>@endif
              @if($hariIni->jam_keluar) • Keluar: <b>{{ \Illuminate\Support\Str::of($hariIni->jam_keluar)->substr(0,5) }}</b>@endif
              @if($hariIni->telat_menit) • Telat: <b>{{ $hariIni->telat_menit }} mnt</b>@endif
            </p>
          @else
            <p class="mt-2 text-sm text-slate-500">Belum ada data hari ini.</p>
          @endif
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
          <p class="text-xs text-slate-500">Ringkasan Bulan Ini</p>
          <div class="mt-3 grid grid-cols-4 gap-2">
            @foreach([['Hadir',$rekap['hadir'],'emerald'],['Telat',$rekap['telat'],'amber'],['Izin',$rekap['izin'],'sky'],['Sakit',$rekap['sakit'],'rose']] as [$l,$v,$c])
              <div class="rounded-lg ring-1 ring-{{ $c }}-200 bg-{{ $c }}-50/50 p-3 text-center">
                <p class="text-[11px] text-slate-600">{{ $l }}</p>
                <p class="text-xl font-extrabold text-{{ $c }}-700 tabular-nums">{{ $v }}</p>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="mt-5">
        <p class="text-sm font-semibold text-slate-900">Riwayat Presensi Terakhir</p>
        <div class="mt-2 rounded-xl border border-slate-200 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
              <tr>
                <th class="px-3 py-2 text-left">Tanggal</th>
                <th class="px-3 py-2 text-left">Status</th>
                <th class="px-3 py-2 text-left">Masuk</th>
                <th class="px-3 py-2 text-left">Keluar</th>
                <th class="px-3 py-2 text-left">Telat</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              @forelse($riwayat as $r)
                <tr>
                  <td class="px-3 py-2">{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                  <td class="px-3 py-2 capitalize">{{ $r->status }}</td>
                  <td class="px-3 py-2">{{ $r->jam_masuk ? \Illuminate\Support\Str::of($r->jam_masuk)->substr(0,5) : '—' }}</td>
                  <td class="px-3 py-2">{{ $r->jam_keluar? \Illuminate\Support\Str::of($r->jam_keluar)->substr(0,5) : '—' }}</td>
                  <td class="px-3 py-2">{{ $r->telat_menit ? $r->telat_menit.' mnt' : '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Kosong.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
@endsection
