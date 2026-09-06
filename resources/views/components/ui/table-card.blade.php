{{-- Shared table wrapper — flat light theme.
     Usage: <x-ui.table-card><table id="...">...</table></x-ui.table-card> --}}
<div {{ $attributes->merge(['class' => 'bg-white border border-neutral-200 rounded-lg overflow-hidden']) }}>
    {{ $slot }}
</div>
