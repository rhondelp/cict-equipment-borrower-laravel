{{-- One of the five official reports on screen: its filters, the export
     toolbar, then the table. The rows are the same arrays the CSV, PDF and
     print read (ReportsController::officialReport()); this is only the
     screen's rendering of them, at the screen's type size.

     Unlike the activity log, the toolbar shows even when the report is
     empty: "nothing is out" and "no defective items" are answers the office
     may need to print and sign. --}}
@php
    $filterErrors = $resolved['errors'];
    $total = count($rows);
    $noun = str($meta['noun'])->plural($total);

    $field = 'w-full min-h-[44px] rounded-md border border-neutral-300 bg-white px-3 py-2 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30';
    $label = 'block text-sm font-medium text-neutral-700';

    $today = today();
    $ranges = [
        'Last 7 days' => [$today->copy()->subDays(6), $today],
        'Last 30 days' => [$today->copy()->subDays(\App\Http\Controllers\ReportsController::DEFAULT_DAYS - 1), $today],
        'This month' => [$today->copy()->startOfMonth(), $today],
    ];
    $rangeUrl = fn ($from, $to) => request()->fullUrlWithQuery(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'page' => null]);
    $isRange = fn ($from, $to) => ($filters['from'] ?? null) === $from->toDateString() && ($filters['to'] ?? null) === $to->toDateString();

    $hasForm = $definition['dated'] || in_array($report, ['inventory', 'most-borrowed'], true);
    $narrowed = isset($filters['category']) || isset($filters['status']);

    $empty = [
        'borrowers' => ['fa-handshake', 'Nothing borrowed in this date range', 'No loans or issues were recorded between '.$meta['range'].'. Voided transactions are never listed.'],
        'inventory' => $narrowed
            ? ['fa-filter-circle-xmark', 'No equipment matches these filters', 'Retired equipment is not part of the inventory report.']
            : ['fa-boxes-stacked', 'No equipment in use', 'Equipment added on the inventory screen appears here. Retired items are left out.'],
        'not-returned' => ['fa-circle-check', 'Everything has been returned', 'No loan is out right now. Issued items are given for good, so they never appear here.'],
        'most-borrowed' => ['fa-chart-simple', 'Nothing borrowed in this date range', 'Nothing was lent or issued between '.$meta['range'].'. Try a wider range.'],
        'defective' => ['fa-screwdriver-wrench', 'No defective items', 'Every return that came back damaged or lost has a resolution recorded.'],
    ][$report];

    $exportQuery = request()->except('page');
    $tooMany = $total > \App\Http\Controllers\ReportsController::MAX_PAGED_ROWS;
    $exportButton = 'inline-flex min-h-[48px] items-center gap-2.5 rounded-md border px-5 py-2.5 text-base font-semibold';
@endphp

@if($hasForm)
    {{-- A GET form like the activity log's: the report is its URL. --}}
    <x-ui.panel>
        <form method="GET" action="{{ route('admin.reports') }}" data-report-filters class="p-4 space-y-4 sm:p-5">
            <input type="hidden" name="report" value="{{ $report }}">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @if($definition['dated'])
                    <div>
                        <label for="report-from" class="{{ $label }}">From</label>
                        <input type="date" id="report-from" name="from" value="{{ $filters['from'] }}" max="{{ $today->toDateString() }}"
                               class="mt-1.5 {{ $field }}" @if(isset($filterErrors['from']) || isset($filterErrors['range'])) aria-invalid="true" aria-describedby="report-range-error" @endif>
                    </div>
                    <div>
                        <label for="report-to" class="{{ $label }}">To <span class="font-normal text-neutral-600">(whole day included)</span></label>
                        <input type="date" id="report-to" name="to" value="{{ $filters['to'] }}"
                               class="mt-1.5 {{ $field }}" @if(isset($filterErrors['to']) || isset($filterErrors['range'])) aria-invalid="true" aria-describedby="report-range-error" @endif>
                    </div>
                @endif

                @if($report === 'inventory')
                    <div>
                        <label for="report-category" class="{{ $label }}">Category</label>
                        <select id="report-category" name="category" class="mt-1.5 {{ $field }}">
                            <option value="">All categories</option>
                            @foreach($categoryOptions as $category)
                                <option value="{{ $category }}" @selected(($filters['category'] ?? null) === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="report-status" class="{{ $label }}">Status</label>
                        <select id="report-status" name="status" class="mt-1.5 {{ $field }}">
                            <option value="">Any status</option>
                            @foreach(\App\Http\Controllers\ReportsController::INVENTORY_STATUSES as $value => $statusLabel)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                        @isset($filterErrors['status'])
                            <p class="mt-1.5 text-sm font-medium text-danger-700">{{ $filterErrors['status'] }}</p>
                        @endisset
                    </div>
                @endif

                @if($report === 'most-borrowed')
                    <div>
                        <label for="report-limit" class="{{ $label }}">Show</label>
                        <select id="report-limit" name="limit" class="mt-1.5 {{ $field }}">
                            @foreach(\App\Http\Controllers\ReportsController::TOP_LIMITS as $limit)
                                <option value="{{ $limit }}" @selected($filters['limit'] === $limit)>Top {{ $limit }}</option>
                            @endforeach
                        </select>
                        @isset($filterErrors['limit'])
                            <p class="mt-1.5 text-sm font-medium text-danger-700">{{ $filterErrors['limit'] }}</p>
                        @endisset
                    </div>
                @endif
            </div>

            @php
                $rangeError = $filterErrors['range'] ?? $filterErrors['from'] ?? $filterErrors['to'] ?? null;
            @endphp
            @if($rangeError)
                <p id="report-range-error" role="alert"
                   class="flex items-start gap-2 px-3 py-2 text-base font-medium border rounded-md border-danger-300 bg-danger-50 text-danger-700">
                    <i class="mt-1 text-sm fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ $rangeError }}</span>
                </p>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2" aria-label="Quick date ranges">
                    @if($definition['dated'])
                        @foreach($ranges as $rangeLabel => [$rFrom, $rTo])
                            <a href="{{ $rangeUrl($rFrom, $rTo) }}"
                               @if($isRange($rFrom, $rTo)) aria-current="true" @endif
                               class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                      {{ $isRange($rFrom, $rTo) ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700 hover:border-primary-300' }}">
                                {{ $rangeLabel }}
                            </a>
                        @endforeach
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.reports', ['report' => $report]) }}"
                       class="inline-flex min-h-[44px] items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Clear filters
                    </a>
                    <button type="submit"
                            class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-primary-600 px-5 py-2 text-base font-semibold text-white hover:bg-primary-700">
                        <i class="text-sm fas fa-filter" aria-hidden="true"></i> Apply filters
                    </button>
                </div>
            </div>
        </form>
    </x-ui.panel>
@endif

{{-- Export toolbar: the same three outputs and the same row cap as the
     activity log, carrying this page's query string (report included). --}}
<div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-white border sm:px-5 rounded-xl border-neutral-200" data-report-exports>
    <p class="text-base text-neutral-800">
        Export or print the {{ $meta['title'] }}
        <span class="text-neutral-600">— {{ number_format($total) }} {{ $noun }}, with the signature block on paper.</span>
    </p>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.reports.csv', $exportQuery) }}" data-export="csv"
           class="{{ $exportButton }} border-neutral-300 bg-white text-neutral-800 hover:bg-neutral-50">
            <i class="fas fa-file-csv text-success-700" aria-hidden="true"></i> Export CSV
        </a>
        @if($tooMany)
            <span aria-disabled="true" data-export="pdf" title="Over {{ number_format(\App\Http\Controllers\ReportsController::MAX_PAGED_ROWS) }} rows — narrow the filters or use CSV"
                  class="{{ $exportButton }} cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-500">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> Export PDF
            </span>
            <span aria-disabled="true" data-export="print" title="Over {{ number_format(\App\Http\Controllers\ReportsController::MAX_PAGED_ROWS) }} rows — narrow the filters or use CSV"
                  class="{{ $exportButton }} cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-500">
                <i class="fas fa-print" aria-hidden="true"></i> Print
            </span>
        @else
            <a href="{{ route('admin.reports.pdf', $exportQuery) }}" data-export="pdf"
               class="{{ $exportButton }} border-neutral-300 bg-white text-neutral-800 hover:bg-neutral-50">
                <i class="fas fa-file-pdf text-danger-700" aria-hidden="true"></i> Export PDF
            </a>
            <a href="{{ route('admin.reports.print', $exportQuery) }}" data-export="print" target="_blank" rel="noopener"
               class="{{ $exportButton }} border-primary-600 bg-primary-600 text-white hover:bg-primary-700">
                <i class="fas fa-print" aria-hidden="true"></i> Print
                <span class="sr-only">(opens in a new tab)</span>
            </a>
        @endif
    </div>
    @if($tooMany)
        <p class="w-full text-base text-warning-700">
            PDF and Print stop at {{ number_format(\App\Http\Controllers\ReportsController::MAX_PAGED_ROWS) }} rows. Narrow the dates or filters, or use Export CSV, which has no limit.
        </p>
    @endif
</div>

<x-ui.panel>
    @if($total === 0)
        <x-ui.empty-state :icon="$empty[0]" :title="$empty[1]" :message="$empty[2]" data-report-empty="{{ $report }}">
            @if($narrowed)
                <x-slot:action>
                    <a href="{{ route('admin.reports', ['report' => $report]) }}"
                       class="inline-flex min-h-[44px] items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-base font-semibold text-primary-700 hover:bg-neutral-50">
                        Clear filters
                    </a>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <div class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-3 border-b sm:px-5 border-neutral-200">
            <p class="text-base text-neutral-800" data-report-count>
                Showing <span class="font-semibold tabular-nums">{{ number_format($rowsPage->firstItem()) }}–{{ number_format($rowsPage->lastItem()) }}</span>
                of <span class="font-semibold tabular-nums">{{ number_format($total) }}</span> {{ $noun }}
            </p>
            <p class="text-sm text-neutral-600">
                {{ $meta['range_label'] }}: {{ $meta['range'] }}@if($meta['filters'] !== 'None') · {{ $meta['filters'] }}@endif
            </p>
        </div>

        <p data-report-scroll-hint hidden
           class="flex items-center gap-2 px-4 py-2 text-sm border-b sm:px-5 text-neutral-700 bg-neutral-50 border-neutral-200">
            <i class="fas fa-arrows-left-right" aria-hidden="true"></i>
            More columns to the right — scroll the table sideways.
        </p>
        <div data-report-scroll class="overflow-auto max-h-[75vh]" tabindex="0" role="region" aria-label="{{ $meta['title'] }} table">
            <table class="w-full min-w-[44rem] text-base text-left text-neutral-900 border-collapse" data-official-table="{{ $report }}">
                <caption class="sr-only">{{ $meta['title'] }}</caption>
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th scope="col"
                                @class([
                                    'sticky top-0 z-10 px-3 py-3 text-sm font-semibold tracking-wide uppercase whitespace-nowrap bg-neutral-100 text-neutral-700 shadow-[inset_0_-1px_0_theme(colors.neutral.300)]',
                                    'text-right' => $column['num'] ?? false,
                                ])>
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @foreach($rowsPage as $cells)
                        <tr class="align-top hover:bg-neutral-50" data-report-row>
                            @foreach($cells as $i => $cell)
                                <td @class(['px-3 py-3', 'text-right tabular-nums' => $columns[$i]['num'] ?? false])>
                                    @if(is_array($cell))
                                        <span class="block">{{ $cell['text'] }}</span>
                                        @if(! empty($cell['mark']))
                                            <span class="inline-flex items-center gap-1.5 mt-1 text-sm font-bold tracking-wide uppercase text-danger-700" data-mark>
                                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>{{ $cell['mark'] }}
                                            </span>
                                        @endif
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($rowsPage->hasPages())
            <nav class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t sm:px-5 border-neutral-200" aria-label="Report pages">
                <p class="text-base text-neutral-700">Page {{ $rowsPage->currentPage() }} of {{ $rowsPage->lastPage() }}</p>
                <div class="flex items-center gap-2">
                    @foreach([['Previous', $rowsPage->previousPageUrl(), 'prev'], ['Next', $rowsPage->nextPageUrl(), 'next']] as [$pagerLabel, $url, $rel])
                        @if($url)
                            <a href="{{ $url }}" rel="{{ $rel }}"
                               class="inline-flex min-h-[44px] items-center rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-base font-semibold text-neutral-700 hover:bg-neutral-50">{{ $pagerLabel }}</a>
                        @else
                            <span aria-disabled="true"
                                  class="inline-flex min-h-[44px] items-center rounded-md border border-neutral-200 bg-neutral-50 px-3.5 py-2 text-base font-semibold text-neutral-400">{{ $pagerLabel }}</span>
                        @endif
                    @endforeach
                </div>
            </nav>
        @endif
    @endif
</x-ui.panel>
