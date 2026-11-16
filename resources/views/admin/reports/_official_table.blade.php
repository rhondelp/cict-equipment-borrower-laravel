@php
    $chunks = collect($rows)->chunk($chunkSize ?? max(count($rows), 1));
    $row = 0;
@endphp
@if($rows === [])
    <p class="report-empty">{{ $emptyMessage }}</p>
@else
    @foreach($chunks as $chunk)
        <table class="report-table">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th style="width: {{ $column['width'] }}%"{!! ($column['num'] ?? false) ? ' class="num"' : '' !!}>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $cells)
                    <tr @class(['alt' => $row++ % 2 === 1])>
                        @foreach($cells as $i => $cell)
                            <td{!! ($columns[$i]['num'] ?? false) ? ' class="num"' : '' !!}>@if(is_array($cell)){{ $cell['text'] }}@if(! empty($cell['mark']))<br><b class="mark">{{ $cell['mark'] }}</b>@endif @else{{ $cell }}@endif</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif
