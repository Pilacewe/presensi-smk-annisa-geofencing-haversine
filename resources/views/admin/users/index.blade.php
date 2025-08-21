@extends('layouts.admin')
@section('title','Kelola Akun')
@section('subtitle','Daftar seluruh akun pegawai dan admin')

@section('actions')
  <a href="{{ route('admin.users.create') }}" class="px-3 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">+ Tambah User</a>
@endsection

@section('content')
  <form class="mb-4 flex flex-wrap gap-2">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama/email"
           class="border rounded px-3 py-2 text-sm" />
    <select name="role" class="border rounded px-3 py-2 text-sm">
      <option value="">Semua Role</option>
      @foreach(['admin','guru','tu','piket','kepsek'] as $r)
        <option value="{{ $r }}" @selected(request('role')==$r)>{{ strtoupper($r) }}</option>
      @endforeach
    </select>
    <button class="px-3 py-2 bg-slate-800 text-white text-sm rounded">Filter</button>
  </form>

  <div class="overflow-x-auto bg-white rounded-xl shadow ring-1 ring-slate-200">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-700">
        <tr>
          <th class="text-left px-4 py-2">Nama</th>
          <th class="text-left px-4 py-2">Email</th>
          <th class="text-left px-4 py-2">Role</th>
          <th class="text-right px-4 py-2">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @foreach($users as $u)
          <tr>
            <td class="px-4 py-2">{{ $u->name }}</td>
            <td class="px-4 py-2">{{ $u->email }}</td>
            <td class="px-4 py-2 uppercase">{{ $u->role }}</td>
            <td class="px-4 py-2 text-right space-x-1">
              <a href="{{ route('admin.users.edit',$u) }}" class="text-indigo-600 hover:underline">Edit</a>
              <form action="{{ route('admin.users.destroy',$u) }}" method="POST" class="inline" onsubmit="return confirm('Hapus user ini?')">
                @csrf @method('DELETE')
                <button class="text-rose-600 hover:underline">Hapus</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $users->links() }}</div>
@endsection
