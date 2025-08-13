@extends('layouts.tu')
@section('title','Export Data')

@section('content')
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
  <form class="grid md:grid-cols-4 gap-4" method="GET" action="{{ route('tu.export.excel') }}">
    @csrf
    <div>
      <label class="text-sm font-medium">Guru</label>
      <select name="guru_id" class="mt-1 w-full rounded-lg border-slate-300">
        <option value="">Semua guru</option>
        @foreach($gurus as $g)
          <option value="{{ $g->id }}" @selected($guruId==$g->id)>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-sm font-medium">Dari</label>
      <input type="date" name="from" value="{{ $from }}" class="mt-1 w-full rounded-lg border-slate-300">
    </div>
    <div>
      <label class="text-sm font-medium">Sampai</label>
      <input type="date" name="to" value="{{ $to }}" class="mt-1 w-full rounded-lg border-slate-300">
    </div>
    <div class="flex items-end gap-2">
      <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white">Export Excel (CSV)</button>
      <a href="{{ route('tu.export.pdf',['guru_id'=>$guruId,'from'=>$from,'to'=>$to]) }}"
         class="px-4 py-2 rounded-lg bg-rose-600 text-white">Export PDF</a>
    </div>
  </form>
</div>
@endsection
