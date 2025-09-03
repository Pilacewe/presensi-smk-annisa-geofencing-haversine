@extends('layouts.presensi')
@section('title','Ajukan Izin')

@section('content')
<div class="max-w-3xl">
  <form action="{{ route('izin.store') }}" method="POST" enctype="multipart/form-data"
        class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-5">
    @csrf

    <div>
      <h2 class="text-lg font-semibold">Ajukan Izin</h2>
      <p class="text-xs text-slate-500">Isi data dengan benar. Lampiran bukti (surat dokter/dll) bersifat opsional.</p>
    </div>

    @if ($errors->any())
      <div class="rounded-lg border-l-4 border-rose-500 bg-rose-50 p-3 text-rose-700 text-sm">
        <ul class="list-disc ml-4">
          @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    @endif

    <div class="grid sm:grid-cols-3 gap-4">
      <div class="sm:col-span-1">
        <label class="text-sm font-medium">Jenis</label>
        <select name="jenis" class="mt-1 w-full rounded-lg border-slate-300" required>
          <option value="izin"  @selected(old('jenis')==='izin')>Izin</option>
          <option value="sakit" @selected(old('jenis')==='sakit')>Sakit</option>
          {{-- jika ingin "dinas", aktifkan baris di bawah & pastikan validasi + controller menerima 'dinas'
          <option value="dinas" @selected(old('jenis')==='dinas')>Dinas Luar</option>
          --}}
        </select>
      </div>

      <div>
        <label class="text-sm font-medium">Tanggal Mulai</label>
        <input type="date" name="tgl_mulai" id="tgl_mulai"
               value="{{ old('tgl_mulai') }}"
               class="mt-1 w-full rounded-lg border-slate-300" required>
      </div>
      <div>
        <label class="text-sm font-medium">Tanggal Selesai</label>
        <input type="date" name="tgl_selesai" id="tgl_selesai"
               value="{{ old('tgl_selesai') }}"
               class="mt-1 w-full rounded-lg border-slate-300" required>
      </div>
    </div>

    <div>
      <label class="text-sm font-medium">Keterangan</label>
      <textarea name="keterangan" rows="3" class="mt-1 w-full rounded-lg border-slate-300"
        placeholder="Contoh: Demam tinggi, istirahat & konsultasi dokter.">{{ old('keterangan') }}</textarea>
      <p id="durasiText" class="text-xs text-slate-500 mt-1">Durasi: -</p>
    </div>

    <div>
      <label class="text-sm font-medium">Bukti (jpg/png/pdf, maks 2MB) — opsional</label>
      {{-- NAMA FIELD HARUS 'bukti' agar cocok dengan controller --}}
      <input type="file" name="bukti" id="bukti" accept=".jpg,.jpeg,.png,.pdf"
             class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-slate-900 file:text-white hover:file:bg-slate-700">
      @error('bukti')<div class="text-xs text-rose-600 mt-1">{{ $message }}</div>@enderror

      {{-- preview kecil utk gambar --}}
      <img id="previewImg" class="mt-2 hidden max-h-32 rounded-lg ring-1 ring-slate-200" alt="preview">
      <p id="fileHint" class="text-[11px] text-slate-500 mt-1">Ukuran maksimum 2MB.</p>
    </div>

    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Kirim Pengajuan</button>
      <a href="{{ route('izin.index') }}" class="text-slate-600 hover:underline text-sm">Batal</a>
    </div>
  </form>
</div>

<script>
  const t1 = document.getElementById('tgl_mulai');
  const t2 = document.getElementById('tgl_selesai');
  const dur = document.getElementById('durasiText');
  const file = document.getElementById('bukti');
  const hint = document.getElementById('fileHint');
  const preview = document.getElementById('previewImg');

  function updateDurasi() {
    if (!t1.value || !t2.value) { dur.textContent = 'Durasi: -'; return; }
    const a = new Date(t1.value), b = new Date(t2.value);
    const days = Math.floor((b - a)/(1000*60*60*24)) + 1;
    dur.textContent = (isFinite(days) && days > 0) ? `Durasi: ${days} hari` : 'Range tanggal tidak valid';
  }
  t1.addEventListener('change', updateDurasi);
  t2.addEventListener('change', updateDurasi);

  // limit ukuran file 2MB + preview gambar
  file.addEventListener('change', (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) {
      hint.textContent = 'Ukuran file melebihi 2MB. Pilih file lain.';
      hint.classList.add('text-rose-600');
      e.target.value = '';
      preview.classList.add('hidden');
      return;
    } else {
      hint.textContent = 'Ukuran maksimum 2MB.';
      hint.classList.remove('text-rose-600');
    }
    // preview hanya untuk image
    if (f.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = ev => { preview.src = ev.target.result; preview.classList.remove('hidden'); };
      reader.readAsDataURL(f);
    } else {
      preview.classList.add('hidden');
    }
  });
</script>
@endsection
