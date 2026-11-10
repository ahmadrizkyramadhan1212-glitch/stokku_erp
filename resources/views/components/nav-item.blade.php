@props(['href', 'icon', 'label', 'active' => false, 'variant' => 'sidebar'])

@php
    $iconColor = $active
        ? 'text-teal-600 dark:text-teal-400'
        : 'text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200';

    $paths = [
        'home'     => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9.5Z"/>',
        'stock'    => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'shopping' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'report'   => '<path d="M3 3v18h18"/><path d="M7 16v-5"/><path d="M12 16V8"/><path d="M17 16v-3"/>',
        'priority' => '<path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><path d="m3 6 1 1 2-2"/><path d="m3 12 1 1 2-2"/><path d="m3 18 1 1 2-2"/>',
    ];
@endphp

@if($variant === 'bottom')
    <a href="{{ $href }}" title="{{ $label }}" aria-label="{{ $label }}"
       class="group w-12 h-12 rounded-full flex items-center justify-center transition-all {{ $active ? 'bg-teal-50 dark:bg-teal-950/50' : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}">
        <svg class="w-6 h-6 shrink-0 {{ $iconColor }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$icon] ?? '' !!}</svg>
    </a>
@else
    <a href="{{ $href }}"
       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all {{ $active ? 'bg-teal-50 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium' }}">
        <svg class="w-5 h-5 shrink-0 {{ $iconColor }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$icon] ?? '' !!}</svg>
        <span class="truncate">{{ $label }}</span>
    </a>
@endif
