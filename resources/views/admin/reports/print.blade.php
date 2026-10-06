<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $meta['title'] }} — {{ $meta['range'] }}</title>
    @include('admin.reports._styles')
    <style>
        /* On screen: the sheet on a grey desk, at a size people can read. */
        @media screen {
            body { background: #e2e8f0; padding: 16px; font-size: 11pt; }
            .sheet { max-width: 1120px; margin: 0 auto; padding: 28px 32px; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; }
            .report-table { table-layout: auto; font-size: 10.5pt; }
            .report-table th { font-size: 9.5pt; }
            .report-table small { font-size: 8.5pt; }
        }

        .print-toolbar {
            max-width: 1120px; margin: 0 auto 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            font-family: "Poppins", "DejaVu Sans", Arial, sans-serif;
        }
        .print-toolbar a, .print-toolbar button {
            box-sizing: border-box; display: inline-flex; align-items: center; height: 46px; padding: 0 18px; border-radius: 6px;
            font: 600 16px/1 "Poppins", "DejaVu Sans", Arial, sans-serif; text-decoration: none; cursor: pointer;
        }
        .print-toolbar button { color: #fff; background: #2563eb; border: 1px solid #2563eb; }
        .print-toolbar button:hover { background: #1d4ed8; }
        .print-toolbar a { color: #334155; background: #fff; border: 1px solid #cbd5e1; }
        .print-toolbar a:hover { background: #f8fafc; }
        .print-toolbar p { margin: 0 0 0 auto; font-size: 15px; color: #334155; }
        .print-toolbar :focus-visible { outline: 3px solid rgba(37, 99, 235, 0.45); outline-offset: 2px; }

        @media print {
            @page { size: A4 landscape; margin: 12mm; }
            body { background: #fff; padding: 0; font-size: 9.5pt; }
            .sheet { padding: 0; border: 0; }
            .print-toolbar { display: none !important; }
            .report-table thead { display: table-header-group; }
            .report-table tr, .report-table td { break-inside: avoid; page-break-inside: avoid; }
            .report-table tr.alt td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar" data-print-toolbar>
        <button type="button" onclick="window.print()">Print again</button>
        <a href="{{ $backUrl }}">Back to the report</a>
        <p>{{ number_format($meta['total']) }} {{ str('activity')->plural($meta['total']) }} · A4 landscape</p>
    </div>

    <div class="sheet">
        @include('admin.reports._header')
        @include('admin.reports._table')
    </div>

    <script>
        // Opened from the Print button: go straight to the print dialog.
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
