@extends('components.default')
@section('title', 'Reports - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $filters = $resolved['filters'];
    $filterErrors = $resolved['errors'];
    $total = $activities->total();

    // Group → badge tone. Informational colours, not good/bad news: blue for
    // loan activity, amber for requests (they wait on a decision), green for
    // returns; everything else stays neutral.
    $groupTones = ['Loans' => 'primary', 'Requests' => 'warning', 'Returns' => 'success'];

    $field = 'w-full min-h-[44px] rounded-md border border-neutral-300 bg-white px-3 py-2 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30';
    $label = 'block text-sm font-medium text-neutral-700';

    $today = today();
    $ranges = [
        'Last 7 days' => [$today->copy()->subDays(6), $today],
        'Last 30 days' => [$today->copy()->subDays(\App\Http\Controllers\ReportsController::DEFAULT_DAYS - 1), $today],
        'This month' => [$today->copy()->startOfMonth(), $today],
    ];
    if ($earliest) {
        $ranges['All time'] = [\Illuminate\Support\Carbon::parse($earliest)->startOfDay(), $today];
    }
    $rangeUrl = fn ($from, $to) => request()->fullUrlWithQuery(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'page' => null]);
    $isRange = fn ($from, $to) => $filters['from'] === $from->toDateString() && $filters['to'] === $to->toDateString();
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="Records" title="Reports">
        Everything recorded in the activity log, newest first.
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        @if(! $anythingRecorded)
            <x-ui.panel>
                <x-ui.empty-state icon="fa-chart-column" title="No activity recorded yet."
                                  message="Activity appears here as soon as equipment is lent, returned or changed." />
            </x-ui.panel>
        @else

            {{-- Filters. A plain GET form: the report is its URL, so it can be
                 bookmarked, shared, and handed to the exports unchanged. --}}
            <x-ui.panel>
                <form method="GET" action="{{ route('admin.reports') }}" data-report-filters class="p-4 space-y-4 sm:p-5">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                        <div class="lg:col-span-2">
                            <label for="report-type" class="{{ $label }}">Activity</label>
                            <select id="report-type" name="type" class="mt-1.5 {{ $field }}">
                                <option value="">All activity</option>
                                @foreach($typeGroups as $group => $types)
                                    <optgroup label="{{ $group }}">
                                        <option value="{{ $group }}" @selected(($filters['type'] ?? null) === $group)>Everything in {{ $group }}</option>
                                        @foreach($types as $key => $typeLabel)
                                            <option value="{{ $key }}" @selected(($filters['type'] ?? null) === $key)>{{ $typeLabel }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @isset($filterErrors['type'])
                                <p class="mt-1.5 text-sm font-medium text-danger-700">{{ $filterErrors['type'] }}</p>
                            @endisset
                        </div>

                        <div>
                            <label for="report-equipment" class="{{ $label }}">Equipment</label>
                            <select id="report-equipment" name="equipment_id" class="mt-1.5 {{ $field }}">
                                <option value="">All equipment</option>
                                @foreach($equipmentOptions as $item)
                                    <option value="{{ $item->id }}" @selected((string) ($filters['equipment_id'] ?? '') === (string) $item->id)>
                                        {{ $item->equipment_name }}{{ $item->retired_at ? ' (retired)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="report-user" class="{{ $label }}">Person <span class="font-normal text-neutral-600">(acted or affected)</span></label>
                            <select id="report-user" name="user_id" class="mt-1.5 {{ $field }}">
                                <option value="">Everyone</option>
                                @foreach($userOptions as $person)
                                    <option value="{{ $person->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $person->id)>
                                        {{ $person->name }} · {{ $person->user_type }}{{ $person->deactivated_at ? ' (deactivated)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="report-status" class="{{ $label }}">Status after</label>
                            <select id="report-status" name="status" class="mt-1.5 {{ $field }}">
                                <option value="">Any status</option>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="report-q" class="{{ $label }}">Search</label>
                            <input type="search" id="report-q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                                   placeholder="Names, items, details" class="mt-1.5 {{ $field }} placeholder:text-neutral-500">
                            @isset($filterErrors['q'])
                                <p class="mt-1.5 text-sm font-medium text-danger-700">{{ $filterErrors['q'] }}</p>
                            @endisset
                        </div>
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
                            @foreach($ranges as $rangeLabel => [$rFrom, $rTo])
                                <a href="{{ $rangeUrl($rFrom, $rTo) }}"
                                   @if($isRange($rFrom, $rTo)) aria-current="true" @endif
                                   class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                          {{ $isRange($rFrom, $rTo) ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700 hover:border-primary-300' }}">
                                    {{ $rangeLabel }}
                                </a>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.reports') }}"
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

            <x-ui.stat-strip :stats="$summary" />

            <x-ui.panel>
                @if($total === 0)
                    <x-ui.empty-state icon="fa-filter-circle-xmark" title="No activity matches these filters.">
                        <x-slot:action>
                            <div class="space-y-4">
                                <ul class="flex flex-wrap justify-center gap-2" aria-label="Filters applied">
                                    @foreach($applied as $name => $value)
                                        <li class="px-3 py-1 text-base border rounded-md border-neutral-300 bg-neutral-50 text-neutral-800">
                                            <span class="text-neutral-600">{{ $name }}:</span> {{ $value }}
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('admin.reports') }}"
                                   class="inline-flex min-h-[44px] items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-base font-semibold text-primary-700 hover:bg-neutral-50">
                                    Clear filters
                                </a>
                            </div>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-3 border-b sm:px-5 border-neutral-200">
                        <p class="text-base text-neutral-800" data-report-count>
                            Showing <span class="font-semibold tabular-nums">{{ number_format($activities->firstItem()) }}–{{ number_format($activities->lastItem()) }}</span>
                            of <span class="font-semibold tabular-nums">{{ number_format($total) }}</span> {{ str('activity')->plural($total) }}
                        </p>
                        <p class="text-sm text-neutral-600">
                            @foreach($applied as $name => $value)
                                <span class="whitespace-nowrap">{{ $name }}: {{ $value }}</span>@if(! $loop->last)<span aria-hidden="true"> · </span>@endif
                            @endforeach
                        </p>
                    </div>

                    {{-- The one <table> in the app. Every other list is a grid
                         that restacks on a phone; this is wide, dense data read
                         across a row, and the same markup has to print and turn
                         into a PDF. The wrapper scrolls both ways on its own, so
                         the header can stick inside it and a narrow screen
                         scrolls the table sideways instead of squeezing it.

                         Eight columns of 16px text do not fit a laptop's content
                         width, so each column has a floor that keeps a name to
                         two lines, the date and the status stack, and a hint
                         appears only while columns are actually off-screen. --}}
                    <p data-report-scroll-hint hidden
                       class="flex items-center gap-2 px-4 py-2 text-sm border-b sm:px-5 text-neutral-700 bg-neutral-50 border-neutral-200">
                        <i class="fas fa-arrows-left-right" aria-hidden="true"></i>
                        More columns to the right — scroll the table sideways.
                    </p>
                    <div data-report-scroll class="overflow-auto max-h-[75vh]" tabindex="0" role="region" aria-label="Activity report table">
                        <table class="w-full min-w-[60rem] text-base text-left text-neutral-900 border-collapse">
                            <caption class="sr-only">Activity report, newest first</caption>
                            <thead>
                                <tr>
                                    @foreach([
                                        'Date and time' => 'min-w-[8rem]', 'Activity' => 'min-w-[10rem]', 'Done by' => 'min-w-[9.5rem]',
                                        'Equipment' => 'min-w-[9rem]', 'Affected person' => 'min-w-[9rem]', 'Qty' => 'text-right',
                                        'Status' => 'min-w-[7.5rem]', 'Details' => 'min-w-[17rem]',
                                    ] as $heading => $width)
                                        <th scope="col"
                                            class="sticky top-0 z-10 px-3 py-3 text-sm font-semibold tracking-wide uppercase whitespace-nowrap bg-neutral-100 text-neutral-700 shadow-[inset_0_-1px_0_theme(colors.neutral.300)] {{ $width }}">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                @foreach($activities as $activity)
                                    <tr class="align-top hover:bg-neutral-50" data-report-row data-type="{{ $activity->type }}">
                                        <td class="px-3 py-3 tabular-nums">
                                            <time datetime="{{ $activity->occurred_at->toIso8601String() }}">
                                                <span class="block whitespace-nowrap">{{ $activity->occurred_at->format('M j, Y') }}</span>
                                                <span class="block whitespace-nowrap text-neutral-700">{{ $activity->occurred_at->format('g:i A') }}</span>
                                            </time>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="block font-semibold">{{ $activity->typeLabel() }}</span>
                                            <x-ui.badge :status="$activity->groupLabel()" :variant="$groupTones[$activity->groupLabel()] ?? 'neutral'"
                                                        class="mt-1 !px-2 !py-0.5 !text-xs whitespace-nowrap" />
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="block {{ $activity->actor_id || $activity->actor_role ? 'font-medium' : 'text-neutral-600' }}">{{ $activity->actorLabel() }}</span>
                                            @if($activity->actor_role)
                                                <span class="block text-sm text-neutral-600">{{ $activity->actor_role }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">{{ $activity->equipment_name ?? '—' }}</td>
                                        <td class="px-3 py-3">{{ $activity->subject_name ?? '—' }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">{{ $activity->quantity ?? '—' }}</td>
                                        <td class="px-3 py-3">
                                            @if($activity->status_from && $activity->status_to)
                                                <span class="block whitespace-nowrap text-neutral-700">{{ $activity->status_from }} <span aria-label="to">→</span></span>
                                                <span class="block font-semibold whitespace-nowrap">{{ $activity->status_to }}</span>
                                            @else
                                                <span class="{{ $activity->status_to ? 'font-semibold' : 'text-neutral-500' }}">{{ $activity->status_to ?? '—' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-pretty text-neutral-800">{{ $activity->details ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($activities->hasPages())
                        <nav class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t sm:px-5 border-neutral-200" aria-label="Report pages">
                            <p class="text-base text-neutral-700">Page {{ $activities->currentPage() }} of {{ $activities->lastPage() }}</p>
                            <div class="flex items-center gap-2">
                                @php
                                    $pager = [
                                        ['First', $activities->onFirstPage() ? null : $activities->url(1), 'fa-angles-left'],
                                        ['Previous', $activities->previousPageUrl(), 'fa-angle-left'],
                                        ['Next', $activities->nextPageUrl(), 'fa-angle-right'],
                                        ['Last', $activities->hasMorePages() ? $activities->url($activities->lastPage()) : null, 'fa-angles-right'],
                                    ];
                                @endphp
                                @foreach($pager as [$pagerLabel, $url, $icon])
                                    @if($url)
                                        <a href="{{ $url }}" rel="{{ in_array($pagerLabel, ['Previous', 'Next']) ? strtolower(substr($pagerLabel, 0, 4)) : 'nofollow' }}"
                                           class="inline-flex min-h-[44px] items-center gap-1.5 rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                                            @if(in_array($pagerLabel, ['First', 'Previous']))<i class="text-sm fas {{ $icon }}" aria-hidden="true"></i>@endif
                                            {{ $pagerLabel }}
                                            @if(in_array($pagerLabel, ['Next', 'Last']))<i class="text-sm fas {{ $icon }}" aria-hidden="true"></i>@endif
                                        </a>
                                    @else
                                        <span aria-disabled="true"
                                              class="inline-flex min-h-[44px] items-center gap-1.5 rounded-md border border-neutral-200 bg-neutral-50 px-3.5 py-2 text-base font-semibold text-neutral-400">
                                            {{ $pagerLabel }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </nav>
                    @endif
                @endif
            </x-ui.panel>
        @endif
    </main>
</div>
<script>
// The sideways-scroll hint: shown only while the table is wider than its box,
// and gone once the reader has reached the last column.
document.addEventListener('DOMContentLoaded', function () {
    const box = document.querySelector('[data-report-scroll]');
    const hint = document.querySelector('[data-report-scroll-hint]');
    if (!box || !hint) return;

    function sync() {
        hint.hidden = box.scrollWidth - box.clientWidth - box.scrollLeft < 2;
    }

    box.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
    sync();
});
</script>
@endsection
