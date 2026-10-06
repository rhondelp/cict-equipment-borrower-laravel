{{-- The report's header block, shared by the PDF and the print view so they
     cannot drift: the logo, the system name, the title, then what the report
     covers and who made it.

     $logoSrc is a file path for dompdf (read from disk; remote fetching is
     off) and a URL for the browser. $meta comes from
     ReportsController::pagedReport(). --}}
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
        <th scope="row">Date range</th>
        <td>{{ $meta['range'] }}</td>
        <th scope="row">Records</th>
        <td>{{ number_format($meta['total']) }} {{ str('activity')->plural($meta['total']) }}</td>
    </tr>
    <tr>
        <th scope="row">Filters</th>
        <td colspan="3">{{ $meta['filters'] }}</td>
    </tr>
    <tr>
        <th scope="row">Generated</th>
        <td colspan="3">{{ $meta['generated_at'] }} by {{ $meta['generated_by'] }}</td>
    </tr>
</table>
