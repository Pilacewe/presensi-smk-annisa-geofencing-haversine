@extends('layouts.tu')
@section('title','Detail Izin')

@section('content')
  <div class="max-w-xl bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
    <div class="grid gap-2 text-sm">
      <div class="flex justify-between">
        <span class="text-slate-500">Tanggal</span>
        <span class="font-medium">{{ \Carbon\Carbon::parse($izin->tanggal)->translatedFormat('d M Y') }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-500">Status</span>
        <span class="font-medium capitalize">{{ $izin->status }}</span>
      </div>
      <div>
        <p class="text-slate-500 mb-1">Alasan</p>
        <p>{{ $izin->alasan }}</p>
      </div>
      @if($izin->lampiran_path)
        <div>
          <p class="text-slate-500 mb-1">Lampiran</p>
          <a href="{{ asset('storage/'.$izin->lampiran_path) }}" class="text-sky-700 hover:underline" target="_blank">Lihat lampiran</a>
        </div>
      @endif
    </div>

    <div class="mt-4 flex gap-2">
      <a href="{{ route('tu.self.izin.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">Kembali</a>
      @if($izin->status==='pending')
        <form method="POST" action="{{ route('tu.self.izin.destroy',$izin) }}">@csrf @method('DELETE')
          <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700" onclick="return confirm('Batalkan pengajuan ini?')">Batalkan</button>
        </form>
      @endif
    </div>
  </div>
@endsection
