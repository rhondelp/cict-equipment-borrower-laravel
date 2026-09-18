{{-- Shared table wrapper — flat light theme.
     Usage: <x-ui.table-card><table id="...">...</table></x-ui.table-card>

     Readability: the type scale, padding and row-action touch targets for every
     admin table are set here with descendant variants instead of being repeated
     on each page's <th>/<td>. A descendant rule outranks the per-cell utilities
     (and DataTables' own padding, which loads before the Vite bundle), so the
     individual table pages need no edits. Body cells land on text-base, headers
     on text-sm, and row-action buttons get a 40px minimum target.

     Cell colours are deliberately not set here so each page keeps its own
     hierarchy (emphasised names vs. muted descriptions). --}}
<div {{ $attributes->merge(['class' => 'bg-white border border-neutral-200 rounded-lg overflow-hidden
    [&_thead_th]:px-5 [&_thead_th]:py-4 [&_thead_th]:text-sm [&_thead_th]:font-semibold [&_thead_th]:text-neutral-600
    [&_tbody_td]:px-5 [&_tbody_td]:py-4 [&_tbody_td]:text-base
    [&_tbody_td_button]:min-h-[40px] [&_tbody_td_button]:px-4 [&_tbody_td_button]:text-sm']) }}>
    {{ $slot }}
</div>
