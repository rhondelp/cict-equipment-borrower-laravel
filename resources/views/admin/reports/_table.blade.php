{{-- The detailed table, shared by the PDF and the print view. Same columns as
     the screen. The header row repeats on every page (thead is a
     table-header-group) and a row is never split across a page break.

     $chunkSize splits the rows into consecutive tables of that many rows.
     The PDF passes 100: dompdf lays a single table out in time and memory that
     grow faster than its row count (1,946 rows took 96 s and 3 GB as one
     table, 28 s and 554 MB as tables of 100). Each table repeats the header,
     so a header row also appears every 100 rows. The browser's print view
     passes nothing and gets one table.

     Markup is kept lean for the same reason — every element is a dompdf
     frame: <b> and <br> rather than nested spans, zebra rows by class rather
     than :nth-child.

     Widths sit on the header cells, not on <col>: dompdf ignores a colgroup
     and would give all eight columns the same width, leaving Details — the
     longest text — the narrowest. --}}
@php
    $chunks = $activities->chunk($chunkSize ?? max($activities->count(), 1));
    $row = 0;
@endphp
@if($activities->isEmpty())
    <p class="report-empty">No activity matches these filters.</p>
@else
    @foreach($chunks as $chunk)
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 9%">Date and time</th>
                    <th style="width: 13%">Activity</th>
                    <th style="width: 13%">Done by</th>
                    <th style="width: 12%">Equipment</th>
                    <th style="width: 12%">Affected person</th>
                    <th style="width: 4%" class="num">Qty</th>
                    <th style="width: 10%">Status</th>
                    <th style="width: 27%">Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $activity)
                    <tr @class(['alt' => $row++ % 2 === 1])>
                        <td>{{ $activity->occurred_at->format('M j, Y') }}<br>{{ $activity->occurred_at->format('g:i A') }}</td>
                        <td><b>{{ $activity->typeLabel() }}</b><br><small>{{ $activity->groupLabel() }}</small></td>
                        <td>{{ $activity->actorLabel() }}@if($activity->actor_role)<br><small>{{ $activity->actor_role }}</small>@endif</td>
                        <td>{{ $activity->equipment_name ?? '—' }}</td>
                        <td>{{ $activity->subject_name ?? '—' }}</td>
                        <td class="num">{{ $activity->quantity ?? '—' }}</td>
                        <td>@if($activity->status_from && $activity->status_to){{ $activity->status_from }} → <b>{{ $activity->status_to }}</b>@else{{ $activity->status_to ?? '—' }}@endif</td>
                        <td>{{ $activity->details ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif
