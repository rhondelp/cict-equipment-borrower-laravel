{{-- An official report's header, shared by its PDF and print view: the logo,
     the system name, the report title, then the date range (or, for a
     snapshot report, the moment it describes), any filters, and when it was
     generated. Same classes as the activity log's _header. --}}
<table class="report-head" role="presentation">
    <tr>
        <td class="logo-cell"><img src="{{ $logoSrc }}" alt="CICT logo" width="48" height="48"></td>
        <td>
            <div class="report-system">CICT Equipment Borrower System</div>
            <h1 class="report-title">{{ $meta['title'] }}</h1>
        </td>
    </tr>
</table>

<table class="report-meta">
    <tr>
        <th scope="row">{{ $meta['range_label'] }}</th>
        <td>{{ $meta['range'] }}</td>
        <th scope="row">Records</th>
        <td>{{ number_format($meta['total']) }} {{ str($meta['noun'])->plural($meta['total']) }}</td>
    </tr>
    @if($meta['filters'] !== 'None')
        <tr>
            <th scope="row">Filters</th>
            <td colspan="3">{{ $meta['filters'] }}</td>
        </tr>
    @endif
    <tr>
        <th scope="row">Generated</th>
        <td colspan="3">{{ $meta['generated_at'] }} by {{ $meta['generated_by'] }}</td>
    </tr>
</table>
