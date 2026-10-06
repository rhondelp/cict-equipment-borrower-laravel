<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }}</title>
    @include('admin.reports._styles')
    {{-- The footer ("Page X of Y" and the report name) is stamped onto each
         page by ReportsController::exportPdf() after layout, inside this
         bottom margin. --}}
    <style>
        @page { margin: 34pt 30pt 44pt 30pt; }
    </style>
</head>
<body>
    @include('admin.reports._header')
    @include('admin.reports._table', ['chunkSize' => \App\Http\Controllers\ReportsController::PDF_TABLE_ROWS])
</body>
</html>
