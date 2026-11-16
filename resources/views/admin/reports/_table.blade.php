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
