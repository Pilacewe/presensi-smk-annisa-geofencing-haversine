@extends('layouts.admin')
@section('title','Kepsek')
@section('subtitle','Kelola satu akun Kepala Sekolah')

@section('actions')
  @if(!$kepsek)
    <a href="{{ route('admin.kepsek.create') }}"
       class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700">
      + Buat Akun Kepsek
    </a>
  @else
    <a href="{{ route('admin.kepsek.edit',$kepsek) }}"
       class="px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-sm hover:bg-slate-50">
      Edit Profil
    </a>
  @endif
@endsection

@section('content')
<div class="max-w-screen-xl mx-auto space-y-6">
  {{-- Flash --}}
  @if(session('ok'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">{{ $errors->first() }}</div>
  @endif

  @if(!$kepsek)
    {{-- Kosong -> CTA --}}
    <div class="rounded-3xl border bg-white p-12 text-center shadow-sm">
      <div class="mx-auto mb-5 w-14 h-14 rounded-2xl grid place-items-center bg-slate-100 text-slate-500">
        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 3v14m7-7H5"/></svg>
      </div>
      <h3 class="text-2xl font-bold text-slate-900">Belum ada akun Kepsek</h3>
      <p class="text-slate-500 mt-1">Buat satu akun untuk Kepala Sekolah.</p>
      <a href="{{ route('admin.kepsek.create') }}"
         class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
        Buat Akun Kepsek
      </a>
    </div>
  @else
    {{-- ================== Kartu profil lebar: hero + body ================== --}}
    <section class="rounded-3xl overflow-hidden border bg-white shadow-sm">
      {{-- HERO pakai foto --}}
      <div class="relative h-48 bg-center bg-cover"
          style="background-image:url('{{ asset('images/kepsek-hero.jpg') }}')">
        {{-- overlay biar teks tetap kebaca --}}
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/60 via-slate-900/30 to-transparent"></div>

        <div class="relative h-full">
          <div class="absolute left-6 right-6 bottom-6 flex items-center gap-5">
            {{-- Avatar --}}
            <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-white ring-4 ring-white shadow grid place-items-center overflow-hidden">
              @if(!empty($kepsek->avatar_path))
                <img src="{{ asset('storage/'.$kepsek->avatar_path) }}" class="w-full h-full object-cover" alt="Avatar">
              @else
                <div class="w-full h-full rounded-xl bg-indigo-100 text-indigo-700 grid place-items-center text-xl font-extrabold">
                  {{ \Illuminate\Support\Str::of($kepsek->name)->substr(0,2)->upper() }}
                </div>
              @endif
            </div>

            {{-- Identitas --}}
            <div class="min-w-0">
              <h1 class="text-white text-2xl md:text-3xl font-bold leading-tight drop-shadow-sm truncate">
                {{ $kepsek->name }}
              </h1>
              <p class="text-white/90 text-sm md:text-base truncate">{{ $kepsek->email }}</p>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] bg-white/20 text-white ring-1 ring-white/40">
                  {{ $kepsek->jabatan ?: 'Kepala Sekolah' }}
                </span>
                @if((int)$kepsek->is_active===1)
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] bg-emerald-300/30 text-white ring-1 ring-white/40">AKTIF</span>
                @else
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] bg-rose-300/30 text-white ring-1 ring-white/40">NONAKTIF</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- BODY: action bar + meta --}}
      <div class="p-6 lg:p-8">
        {{-- Action bar (tanpa dropdown, tidak ketutupan apa pun) --}}
        <div class="flex flex-wrap items-center gap-2 justify-end">
          {{-- Toggle aktif/nonaktif --}}
          <form action="{{ route('admin.kepsek.update',$kepsek) }}" method="POST" class="inline">
            @csrf @method('PATCH')
            <input type="hidden" name="name" value="{{ $kepsek->name }}">
            <input type="hidden" name="email" value="{{ $kepsek->email }}">
            <input type="hidden" name="jabatan" value="{{ $kepsek->jabatan }}">
            <input type="hidden" name="is_active" value="{{ $kepsek->is_active ? 0 : 1 }}">
            <button class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border hover:bg-slate-50 text-sm">
              @if($kepsek->is_active)
                <svg class="w-4 h-4 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M5 12h14"/></svg>
                Nonaktifkan
              @else
                <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M12 5v14m-7-7h14"/></svg>
                Aktifkan
              @endif
            </button>
          </form>

          {{-- Reset password (modal) --}}
          <button id="btnOpenReset"
                  class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border hover:bg-slate-50 text-sm">
            <svg class="w-4 h-4 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-width="1.8" d="M12 3v2m6.36 2.64-1.41 1.41M21 12h-2M6.05 6.05 4.64 7.46M5 12H3m3.05 5.95L4.64 16.54M12 19v2"/>
            </svg>
            Reset Password
          </button>

          {{-- Edit --}}
          <a href="{{ route('admin.kepsek.edit',$kepsek) }}"
             class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M3 21v-3l12-12 3 3-12 12H3z"/></svg>
            Edit
          </a>

          {{-- Hapus --}}
          <form action="{{ route('admin.kepsek.destroy',$kepsek) }}" method="POST" class="inline"
                onsubmit="return confirm('Hapus akun Kepsek? Tindakan ini tidak bisa dibatalkan.');">
            @csrf @method('DELETE')
            <button class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 text-sm">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" d="M3 6h18M8 6v12m8-12v12M5 6l1 14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-14"/></svg>
              Hapus
            </button>
          </form>
        </div>

        {{-- Meta ringkas --}}
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <div class="rounded-2xl border bg-slate-50/60 p-4">
            <div class="text-slate-500 text-xs">Dibuat</div>
            <div class="mt-1 text-slate-900 font-semibold">
              {{ \Carbon\Carbon::parse($kepsek->created_at)->format('d M Y · H:i') }}
            </div>
          </div>
          <div class="rounded-2xl border bg-slate-50/60 p-4">
            <div class="text-slate-500 text-xs">Terakhir diperbarui</div>
            <div class="mt-1 text-slate-900 font-semibold">
              {{ \Carbon\Carbon::parse($kepsek->updated_at)->format('d M Y · H:i') }}
            </div>
          </div>
          <div class="rounded-2xl border bg-slate-50/60 p-4">
            <div class="text-slate-500 text-xs">Terakhir login</div>
            <div class="mt-1 text-slate-900 font-semibold">
              {{ $kepsek->last_login_at ? \Carbon\Carbon::parse($kepsek->last_login_at)->format('d M Y · H:i') : '—' }}
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- ================== Modal Reset Password ================== --}}
    <div id="resetModal" class="hidden fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px] opacity-0 transition-opacity" data-close></div>
      <div class="absolute inset-x-4 top-[12%] md:inset-x-auto md:left-1/2 md:-translate-x-1/2 md:w-[460px]
                  origin-top rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 p-6
                  scale-95 opacity-0 transition-all duration-200">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Reset Password Kepsek</h3>
            <p class="text-xs text-slate-500">Masukkan password baru untuk {{ $kepsek->name }}.</p>
          </div>
          <button class="p-1.5 rounded-lg hover:bg-slate-100" data-close aria-label="Tutup">
            <svg class="w-5 h-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/>
            </svg>
          </button>
        </div>
        <form method="POST" action="{{ route('admin.kepsek.reset',$kepsek) }}" class="space-y-3">
          @csrf
          <div>
            <label class="block text-sm text-slate-600">Password Baru</label>
            <input type="password" name="new_password" required class="mt-1 w-full rounded-lg border px-3 py-2">
          </div>
          <div>
            <label class="block text-sm text-slate-600">Konfirmasi Password</label>
            <input type="password" name="new_password_confirmation" required class="mt-1 w-full rounded-lg border px-3 py-2">
          </div>
          <div class="pt-2 flex items-center justify-end gap-2">
            <button type="button" class="px-3 py-2 rounded-lg border hover:bg-slate-50" data-close>Batal</button>
            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Reset</button>
          </div>
        </form>
      </div>
    </div>
    {{-- ========================================================== --}}
  @endif
</div>

@if($kepsek)
<script>
  (function () {
    const btn   = document.getElementById('btnOpenReset');
    const modal = document.getElementById('resetModal');
    if (!btn || !modal) return;

    const backdrop = modal.querySelector('[data-close]');
    const panel    = modal.querySelector('.origin-top');

    const show = () => {
      modal.classList.remove('hidden');
      requestAnimationFrame(() => {
        backdrop.classList.remove('opacity-0');
        panel.classList.remove('opacity-0','scale-95');
      });
    };
    const hide = () => {
      backdrop.classList.add('opacity-0');
      panel.classList.add('opacity-0','scale-95');
      setTimeout(() => modal.classList.add('hidden'), 180);
    };

    btn.addEventListener('click', show);
    modal.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', hide));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') hide(); });
  })();
</script>
@endif
@endsection
