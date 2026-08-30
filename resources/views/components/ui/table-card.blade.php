{{-- Shared table wrapper — consistent outer border, padding, header separator, row dividers
     Usage: <x-ui.table-card><table id="...">...</table></x-ui.table-card>
     Applies: outer card border, overflow handling, DataTables spacing
--}}
<div {{ $attributes->merge(['class' => 'table-card']) }}>
    {{ $slot }}
</div>
