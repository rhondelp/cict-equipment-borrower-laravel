<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }}</title>
    @include('admin.reports._styles')
    <style>
        @page { margin: 34pt 30pt 44pt 30pt; }
    </style>
</head>
<body>
    @if($official ?? false)
        @include('admin.reports._official_header')
        @include('admin.reports._official_table', ['chunkSize' => \App\Http\Controllers\ReportsController::PDF_TABLE_ROWS])
        @include('admin.reports._signatures')
    @else
        @include('admin.reports._header')
        @include('admin.reports._table', ['chunkSize' => \App\Http\Controllers\ReportsController::PDF_TABLE_ROWS])
    @endif
</body>
</html>
