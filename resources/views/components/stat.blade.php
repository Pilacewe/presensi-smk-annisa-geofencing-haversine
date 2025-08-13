@php
  // warna utama utk ikon & angka
  $map = [
    'indigo'  => ['text' => 'text-indigo-600',  'bg' => 'bg-indigo-50',  'ring' => 'ring-indigo-200'],
    'emerald' => ['text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-200'],
    'amber'   => ['text' => 'text-amber-600',   'bg' => 'bg-amber-50',   'ring' => 'ring-amber-200'],
    'rose'    => ['text' => 'text-rose-600',    'bg' => 'bg-rose-50',    'ring' => 'ring-rose-200'],
    'slate'   => ['text' => 'text-slate-700',   'bg' => 'bg-slate-50',   'ring' => 'ring-slate-200'],
  ];
  $c = $map[$color] ?? $map['slate'];

  $svg = match($icon){
    'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'check' => '<path d="M20 6 9 17l-5-5"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6l3 3"/>',
    'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>',
    default => '<circle cx="12" cy="12" r="10" />',
  };
@endphp

<div class="rounded-xl bg-white shadow-sm p-4 ring-1 ring-slate-100">
  <div class="flex items-start justify-between">
    <div>
      <p class="text-xs text-slate-500">{{ $label }}</p>
      <p class="text-2xl font-semibold {{ $c['text'] }}">{{ $value }}</p>
    </div>
    <div class="w-9 h-9 rounded-lg grid place-items-center {{ $c['bg'] }} {{ $c['ring'] }}">
      <svg class="w-5 h-5 {{ $c['text'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $svg !!}</svg>
    </div>
  </div>
</div>
