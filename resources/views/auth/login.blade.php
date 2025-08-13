
@extends('layouts.guest')
@section('content')
<div class="min-h-screen grid place-items-center bg-gradient-to-br from-indigo-600 to-violet-600 px-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-xl bg-slate-900 text-white grid place-items-center font-bold">P</div>
      <div>
        <p class="text-xs text-slate-500">WR Media</p>
        <h1 class="font-semibold">Sistem Presensi</h1>
      </div>
    </div>

    @if ($errors->any())
      <div class="mb-4 rounded-lg border-l-4 border-rose-500 bg-rose-50 p-3 text-rose-700 text-sm">
        <ul class="list-disc ml-4">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
      @csrf
      <div>
        <label class="text-sm font-medium">Email</label>
        <input id="email" name="email" type="email" required autofocus class="mt-1 w-full rounded-lg border-slate-300 focus:border-indigo-600 focus:ring-indigo-600" />
      </div>
      <div>
        <label class="text-sm font-medium">Password</label>
        <input id="password" name="password" type="password" required class="mt-1 w-full rounded-lg border-slate-300 focus:border-indigo-600 focus:ring-indigo-600" />
      </div>
      <div class="flex items-center justify-between text-sm">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600" />
          <span>Ingat saya</span>
        </label>
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="text-indigo-700 hover:underline">Lupa password?</a>
        @endif
      </div>
      <button class="w-full py-2.5 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">Masuk</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="text-indigo-700 hover:underline">Daftar</a></p>
  </div>
</div>
@endsection