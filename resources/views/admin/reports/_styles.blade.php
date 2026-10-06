{{-- Styles shared by the PDF (dompdf) and the print view (a browser), so the
     two look the same. Written for dompdf's CSS 2.1 subset: no flexbox, no
     grid, no custom properties — tables do the layout. DejaVu Sans is
     dompdf's built-in Unicode font, so "Ñ", "×" and "→" all render. --}}
<style>
    body {
        margin: 0;
        font-family: "DejaVu Sans", Arial, sans-serif;
        font-size: 9.5pt;
        line-height: 1.35;
        color: #0f172a;
    }
    .report-head { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
    .report-head td { vertical-align: middle; padding: 0; }
    .report-head .logo-cell { width: 54pt; padding-right: 10pt; }
    .report-head img { width: 48pt; height: 48pt; }
    .report-system { font-size: 9pt; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase; color: #1d4ed8; }
    .report-title { margin: 2pt 0 0; font-size: 16pt; font-weight: bold; color: #0f172a; }

    .report-meta { width: 100%; border-collapse: collapse; margin-bottom: 10pt; border: 0.75pt solid #cbd5e1; }
    .report-meta th, .report-meta td { padding: 4pt 6pt; text-align: left; vertical-align: top; border-bottom: 0.5pt solid #e2e8f0; }
    .report-meta th { width: 72pt; font-weight: bold; color: #334155; background: #f1f5f9; white-space: nowrap; }
    .report-meta td { color: #0f172a; }

    .report-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9pt; line-height: 1.2; }
    .report-table thead { display: table-header-group; }
    .report-table tr { page-break-inside: avoid; }
    .report-table th {
        padding: 4pt 4pt; text-align: left; vertical-align: bottom;
        font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em;
        color: #1e293b; background: #e2e8f0; border-bottom: 1pt solid #94a3b8;
    }
    .report-table td { padding: 3.5pt 4pt; vertical-align: top; border-bottom: 0.5pt solid #cbd5e1; word-wrap: break-word; }
    .report-table tr.alt td { background: #f8fafc; }
    .report-table .num { text-align: right; }
    .report-table small { font-size: 7.5pt; color: #475569; }
    .report-empty { padding: 14pt; text-align: center; color: #475569; border: 0.75pt solid #cbd5e1; }
</style>
