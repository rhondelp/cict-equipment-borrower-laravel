@props(['name'])
@php $paths = [
  'dashboard' => '<rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.5"/><rect x="9" y="1.5" width="5.5" height="5.5" rx="1.5"/><rect x="1.5" y="9" width="5.5" height="5.5" rx="1.5"/><rect x="9" y="9" width="5.5" height="5.5" rx="1.5"/>',
  'equipment' => '<rect x="1.6" y="4.2" width="12.8" height="9.2" rx="1.6"/><path d="M5.4 4.2V3a1.4 1.4 0 0 1 1.4-1.4h2.4A1.4 1.4 0 0 1 10.6 3v1.2"/>',
  'users'     => '<circle cx="6" cy="5.4" r="2.6"/><path d="M1.8 13.4c0-2.2 1.9-3.6 4.2-3.6s4.2 1.4 4.2 3.6M11.4 9.9c1.7.2 2.8 1.5 2.8 3.5" stroke-linecap="round"/>',
  'transaction' => '<path d="M2 5.2h11M10.4 2.6 13 5.2l-2.6 2.6M14 10.8H3M5.6 8.2 3 10.8l2.6 2.6" stroke-linecap="round" stroke-linejoin="round"/>',
  'request'   => '<rect x="3" y="2" width="10" height="12.4" rx="1.6"/><path d="M5.8 6h4.4M5.8 9h3" stroke-linecap="round"/>',
  'logs'      => '<rect x="2.2" y="3" width="11.6" height="10.4" rx="1.6"/><path d="M2.2 6.4h11.6"/>',
]; @endphp
<svg {{ $attributes->merge(['class' => 'w-4 h-4 shrink-0']) }} viewBox="0 0 16 16"
     fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
