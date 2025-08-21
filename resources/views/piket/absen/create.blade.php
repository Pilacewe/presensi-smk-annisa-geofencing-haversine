@extends('layouts.piket')
@section('title','Absen Manual Guru')

@section('content')
@if(session('success'))
  <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
    {{ session('success') }}
  </div>
@endif
@if(session('message'))
  <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
    {{ session('message') }}
  </div>
@endif

{{-- Normalisasi font kontrol tanggal/jam agar sama di semua browser --}}
<style>
  input[type="date"],
  input[type="time"],
  select {
    font-size: 1rem;           /* = text-base */
    line-height: 1.5rem;       /* = leading-6 */
  }
</style>

<div class="grid xl:grid-cols-3 gap-6 items-start">
  {{-- ================= FORM ================= --}}
  <section class="xl:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100">
      <p class="text-sm text-slate-600">
        Gunakan saat perangkat/jaringan bermasalah. Aksi dicatat sebagai <b>Piket</b>.
      </p>
      <span class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1 text-xs text-slate-600 ring-1 ring-slate-200">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke-width="1.8"/></svg>
        {{ now()->translatedFormat('l, d F Y — H:i') }} WIB
      </span>
    </div>

    <form method="POST" action="{{ route('piket.absen.store') }}" class="p-6 space-y-8">
      @csrf

      {{-- GURU --}}
      <div class="space-y-2">
        <label class="block text-sm font-medium">Guru</label>

        <div class="relative">
          <input id="searchGuru" type="text" placeholder="Cari cepat nama guru…"
                 class="w-full h-11 text-base rounded-xl border-slate-300 pr-10" autocomplete="off">
          <svg class="w-5 h-5 absolute right-3 top-2.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path d="m20 20-3-3" stroke-width="1.8"/></svg>
        </div>

        <select id="selectGuru" name="user_id" class="w-full h-11 text-base rounded-xl border-slate-300">
          <option value="">— Pilih Guru —</option>
          @foreach($gurus as $g)
            <option value="{{ $g->id }}" @selected(old('user_id')==$g->id)>{{ $g->name }}</option>
          @endforeach
        </select>
        @error('user_id')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

        @if($gurus->count() > 0)
          <div class="flex flex-wrap gap-2 pt-1">
            @foreach($gurus->take(8) as $g)
              <button type="button" data-guru="{{ $g->id }}"
                class="px-3 py-1.5 rounded-xl text-sm bg-slate-100 hover:bg-slate-200 text-slate-700">
                {{ \Illuminate\Support\Str::limit($g->name,18) }}
              </button>
            @endforeach
          </div>
        @endif
      </div>

      {{-- TIPE --}}
      <div class="space-y-2">
        <label class="block text-sm font-medium">Tipe presensi</label>
        <div class="inline-flex overflow-hidden rounded-xl ring-1 ring-slate-200">
          <input id="tipeMasuk" class="peer hidden" type="radio" name="tipe" value="masuk" @checked(old('tipe','masuk')==='masuk')>
          <label for="tipeMasuk"
                 class="px-5 py-2 text-sm cursor-pointer bg-white peer-checked:bg-indigo-600 peer-checked:text-white">
            Masuk
          </label>

          <input id="tipeKeluar" class="peer/kel hidden" type="radio" name="tipe" value="keluar" @checked(old('tipe')==='keluar')>
          <label for="tipeKeluar"
                 class="px-5 py-2 text-sm cursor-pointer bg-white peer-checked/kel:bg-emerald-600 peer-checked/kel:text-white">
            Keluar
          </label>
        </div>
        @error('tipe')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
      </div>

      {{-- TANGGAL & JAM (font seragam) --}}
      <div class="grid sm:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="block text-sm font-medium">Tanggal</label>
          <div class="flex gap-2">
            <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                   class="w-full h-11 text-base rounded-xl border-slate-300">
            <button type="button" data-add="0"  class="px-3 rounded-lg text-xs bg-slate-100 hover:bg-slate-200">Hari ini</button>
            <button type="button" data-add="-1" class="px-3 rounded-lg text-xs bg-slate-100 hover:bg-slate-200">Kemarin</button>
          </div>
          @error('tanggal')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-2">
          <label class="block text-sm font-medium">Jam</label>
          <div class="flex gap-2">
            <input type="time" name="jam" value="{{ old('jam', now()->format('H:i')) }}"
                   class="w-full h-11 text-base rounded-xl border-slate-300">
            <button type="button" data-jam="07:00" class="px-3 rounded-lg text-xs bg-slate-100 hover:bg-slate-200">07:00</button>
            <button type="button" data-jam="16:00" class="px-3 rounded-lg text-xs bg-slate-100 hover:bg-slate-200">16:00</button>
          </div>
          @error('jam')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="flex items-center justify-between pt-2">
        <p class="text-xs text-slate-500">
          Pastikan <b>guru</b>, <b>tanggal</b>, dan <b>jam</b> sudah benar. Aksi masuk ke riwayat guru.
        </p>
        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 5v14M5 12h14"/></svg>
          Simpan
        </button>
      </div>
    </form>
  </section>

  {{-- ================= TIPS ================= --}}
  <aside class="space-y-6">
    <div class="overflow-hidden bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
      <div class="px-6 py-5 bg-gradient-to-r from-indigo-50 via-slate-50 to-white border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-indigo-600/90 text-white grid place-items-center">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M5 12l5 5L20 7"/></svg>
          </div>
          <div>
            <p class="text-sm font-semibold">Tips pengisian</p>
            <p class="text-xs text-slate-500">Untuk kecepatan & konsistensi data</p>
          </div>
        </div>
      </div>
      <div class="px-6 py-5 space-y-3">
        <div class="flex gap-3"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-400"></span><p class="text-sm text-slate-700">Pilih <b>Masuk</b> untuk jam hadir, <b>Keluar</b> untuk jam pulang.</p></div>
        <div class="flex gap-3"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-400"></span><p class="text-sm text-slate-700">Gunakan tombol cepat <em>Hari ini/Kemarin</em> & <em>07:00/16:00</em> agar konsisten.</p></div>
        <div class="flex gap-3"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-400"></span><p class="text-sm text-slate-700">Periksa lagi sebelum simpan. Koreksi di hari yang sama jika perlu.</p></div>
      </div>
    </div>
  </aside>
</div>

<script>
  // filter guru via search
  const searchGuru = document.getElementById('searchGuru');
  const selectGuru = document.getElementById('selectGuru');
  searchGuru?.addEventListener('input', () => {
    const q = searchGuru.value.toLowerCase();
    [...selectGuru.options].forEach((opt,i)=>{
      if (i===0) return;
      opt.hidden = !opt.text.toLowerCase().includes(q);
    });
    const visible = [...selectGuru.options].filter((o,i)=>i>0 && !o.hidden);
    if (visible.length===1) selectGuru.value = visible[0].value;
  });

  // quick select guru
  document.querySelectorAll('[data-guru]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      selectGuru.value = btn.dataset.guru;
      selectGuru.dispatchEvent(new Event('change'));
    });
  });

  // date shortcut
  document.querySelectorAll('button[data-add]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const add = parseInt(btn.dataset.add,10);
      const input = document.querySelector('input[name="tanggal"]');
      const d = new Date();
      d.setDate(d.getDate()+add);
      input.value = d.toISOString().slice(0,10);
    });
  });

  // time shortcut
  document.querySelectorAll('button[data-jam]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelector('input[name="jam"]').value = btn.dataset.jam;
    });
  });
</script>
@endsection
