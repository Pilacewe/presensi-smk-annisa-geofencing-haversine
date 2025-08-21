    @extends('layouts.admin')

@section('title','Edit Presensi')
@section('subtitle','Perbarui status/tanggal dan jam masuk/keluar')

@section('actions')
  <a href="{{ route('admin.presensi.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm">Kembali</a>
@endsection

@section('content')
  @if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 px-4 py-3">
      <ul class="list-disc pl-5">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div>
          <h2 class="text-lg font-semibold">{{ $presensi->user?->name ?? '—' }}</h2>
          <p class="text-xs text-slate-500">
            Role: <span class="uppercase">{{ $presensi->user?->role ?? '-' }}</span>
          </p>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.presensi.update',$presensi) }}" class="grid sm:grid-cols-2 gap-4">
        @csrf @method('patch')

        <div>
          <label class="text-sm font-medium">Tanggal</label>
          <input type="date" name="tanggal" value="{{ old('tanggal', \Carbon\Carbon::parse($presensi->tanggal)->toDateString()) }}"
                 class="mt-1 w-full rounded-lg border-slate-300">
        </div>

        <div>
          <label class="text-sm font-medium">Status</label>
          <select name="status" class="mt-1 w-full rounded-lg border-slate-300">
            @foreach(['hadir','izin','sakit','alfa'] as $s)
              <option value="{{ $s }}" @selected(old('status',$presensi->status)===$s)>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="text-sm font-medium">Jam Masuk</label>
          <input type="time" name="jam_masuk"
                 value="{{ old('jam_masuk', $presensi->jam_masuk ? \Illuminate\Support\Str::of($presensi->jam_masuk)->substr(0,5) : '') }}"
                 class="mt-1 w-full rounded-lg border-slate-300">
        </div>

        <div>
          <label class="text-sm font-medium">Jam Keluar</label>
          <input type="time" name="jam_keluar"
                 value="{{ old('jam_keluar', $presensi->jam_keluar ? \Illuminate\Support\Str::of($presensi->jam_keluar)->substr(0,5) : '') }}"
                 class="mt-1 w-full rounded-lg border-slate-300">
          <p class="text-[11px] text-slate-500 mt-1">Harus ≥ jam masuk.</p>
        </div>

        <div class="sm:col-span-2">
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Simpan</button>
        </div>
      </form>
    </section>

    <aside class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 h-fit">
      <p class="text-xs text-slate-500">Info Terkait</p>
      <div class="mt-2 grid gap-2">
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3">
          <div class="text-xs text-slate-500">Tanggal</div>
          <div class="text-sm font-medium">{{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l, d F Y') }}</div>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3">
          <div class="text-xs text-slate-500">Masuk</div>
          <div class="text-sm tabular-nums">{{ $presensi->jam_masuk ?? '—' }}</div>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3">
          <div class="text-xs text-slate-500">Keluar</div>
          <div class="text-sm tabular-nums">{{ $presensi->jam_keluar ?? '—' }}</div>
        </div>
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3">
          <div class="text-xs text-slate-500">Status</div>
          <div class="text-sm capitalize">{{ $presensi->status }}</div>
        </div>
      </div>
    </aside>
  </div>
@endsection
