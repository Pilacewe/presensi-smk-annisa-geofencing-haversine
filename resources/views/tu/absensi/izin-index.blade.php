@extends('layouts.tu')
@section('title','Izin Saya')
@section('subtitle','Pengajuan izin pribadi TU')

@section('actions')
  <a href="{{ route('tu.self.izin.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Ajukan Izin</a>
@endsection

@section('content')
  @if (session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 border-l-4 border-emerald-500 p-3 text-emerald-700 text-sm">
      {{ session('success') }}
    </div>
  @endif
  @if (session('message'))
    <div class="mb-4 rounded-lg bg-amber-50 border-l-4 border-amber-500 p-3 text-amber-700 text-sm">
      {{ session('message') }}
    </div>
  @endif

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
    <table class="min-w-[720px] w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500 border-b">
          <th class="px-4 py-3">Tanggal</th>
          <th class="px-4 py-3">Alasan</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $iz)
          <tr class="border-b last:border-0">
            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($iz->tanggal)->translatedFormat('d M Y') }}</td>
            <td class="px-4 py-3">{{ $iz->alasan }}</td>
            <td class="px-4 py-3 capitalize">{{ $iz->status }}</td>
            <td class="px-4 py-3">
              <a href="{{ route('tu.self.izin.show',$iz) }}" class="text-sky-700 hover:underline">Detail</a>
              @if($iz->status==='pending')
                <form method="POST" action="{{ route('tu.self.izin.destroy',$iz) }}" class="inline">@csrf @method('DELETE')
                  <button class="text-rose-600 hover:underline" onclick="return confirm('Batalkan pengajuan?')">Batalkan</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada pengajuan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $items->links() }}</div>
@endsection
