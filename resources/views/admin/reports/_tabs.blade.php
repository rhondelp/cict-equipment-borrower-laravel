{{-- The report-type switcher. Each "tab" loads a report, so these are links
     in a nav marked with aria-current, not an ARIA tablist (that pattern is
     for panels swapped in place). They are reachable with Tab, and the arrow
     keys, Home and End move between them like tabs (script in reports.blade).

     A date range already chosen travels with the switch, so comparing the
     Borrower Report with Most Borrowed for the same month is one click. --}}
@php
    $tabs = ['activity' => 'Activity Log'] + array_map(fn ($definition) => $definition['tab'], \App\Http\Controllers\ReportsController::REPORTS);
    $carry = array_filter(request()->only(['from', 'to']), 'is_string');
@endphp
<nav aria-label="Report type" data-report-tabs>
    <ul class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
        @foreach($tabs as $key => $tabLabel)
            <li>
                <a href="{{ route('admin.reports', $key === 'activity' ? $carry : ['report' => $key] + $carry) }}"
                   data-report-tab="{{ $key }}"
                   @if($key === $report) aria-current="page" @endif
                   @class([
                       'flex min-h-[48px] h-full items-center justify-center rounded-lg border px-4 py-2 text-center text-base font-semibold leading-tight transition sm:text-lg',
                       'focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-500/40',
                       'border-primary-600 bg-primary-600 text-white shadow-sm' => $key === $report,
                       'border-neutral-300 bg-white text-neutral-800 hover:border-primary-300 hover:text-primary-700' => $key !== $report,
                   ])>
                    {{ $tabLabel }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
