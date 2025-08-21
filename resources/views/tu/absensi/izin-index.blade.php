{{-- Daftar izin pribadi TU --}}
<section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
  <div class="flex items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-semibold">Izin Saya</h2>
    <a href="{{ route('tu.absensi.izinCreate') }}"
       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 text-white text-sm">
      + Buat Izin
    </a>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="text-left text-slate-500">
        <tr class="border-b">
          <th class="py-2 pr-4">Tanggal</th>
          <th class="py-2 pr-4">Jenis</th>
          <th class="py-2 pr-4">Keterangan</th>
          <th class="py-2 pr-4">Status</th>
          <th class="py-2 pr-4">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse ($items as $izin)
          @php
            $statusBadge = [
              'pending'  => 'bg-amber-50  text-amber-700  ring-amber-200',
              'disetujui'=> 'bg-emerald-50 text-emerald-700 ring-emerald-200',
              'ditolak'  => 'bg-rose-50   text-rose-700   ring-rose-200',
            ][$izin->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
          @endphp
          <tr>
            <td class="py-2 pr-4 whitespace-nowrap">
              {{ \Carbon\Carbon::parse($izin->tanggal)->translatedFormat('l, d F Y') }}
            </td>
            <td class="py-2 pr-4 capitalize">{{ $izin->jenis }}</td>
            <td class="py-2 pr-4 max-w-[22rem]">
              <span class="line-clamp-2">{{ $izin->keterangan ?: '—' }}</span>
            </td>
            <td class="py-2 pr-4">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs ring-1 {{ $statusBadge }}">
                {{ ucfirst($izin->status) }}
              </span>
            </td>
            <td class="py-2 pr-4">
              <a href="{{ route('tu.absensi.izinShow', $izin) }}"
                 class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs">Detail</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="py-6 text-center text-slate-500">Belum ada pengajuan izin.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if (method_exists($items,'links'))
    <div class="mt-4">{{ $items->withQueryString()->links() }}</div>
  @endif
</section>
