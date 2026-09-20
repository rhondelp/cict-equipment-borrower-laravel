{{-- The white card every list and section sits in.

     This was `x-ui.table-card`, and it carried a block of descendant selectors
     that set the padding and type scale of `thead th` / `tbody td` — mostly to
     outrank DataTables' own stylesheet, which loaded before the Vite bundle.

     There are no <table> elements left in the app. The lists are CSS grids, so
     each row can restack on a phone without the DataTables Responsive plugin
     collapsing columns into child rows, and every cell's spacing is set where
     it is used. What is left is what the name now says: a panel. --}}
<div {{ $attributes->merge(['class' => 'overflow-hidden bg-white border rounded-xl border-neutral-200']) }}>
    {{ $slot }}
</div>
