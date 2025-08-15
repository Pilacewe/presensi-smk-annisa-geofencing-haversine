@extends('layouts.piket')
@section('title','Ngecek Guru')

@section('content')
  <h1 class="text-xl font-semibold mb-1">Ngecek Guru</h1>
  <p class="text-sm text-slate-500 mb-4">Status presensi tanggal {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</p>

  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500 border-b">
          <th class="px-4 py-3">Guru</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Masuk</th>
          <th class="px-4 py-3">Keluar</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $g)
          @php $todayRec = $g->presensis->first(); @endphp
          <tr class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ $g->name }}</td>
            <td class="px-4 py-3">
              @if(!$todayRec)
                <span class="px-2 py-1 rounded-md bg-slate-100">Belum absen</span>
              @else
                @php
                  $badge = match($todayRec->status){
                    'hadir' => 'bg-emerald-100 text-emerald-700',
                    'izin'  => 'bg-amber-100 text-amber-700',
                    'sakit' => 'bg-rose-100 text-rose-700',
                    default => 'bg-slate-100',
                  };
                @endphp
                <span class="px-2 py-1 rounded-md {{ $badge }}">{{ ucfirst($todayRec->status) }}</span>
              @endif
            </td>
            <td class="px-4 py-3">{{ $todayRec->jam_masuk  ?? '—' }}</td>
            <td class="px-4 py-3">{{ $todayRec->jam_keluar ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $items->links() }}
  </div>
@endsection
