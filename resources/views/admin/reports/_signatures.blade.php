{{-- The signature block that closes every official report. "Prepared By" is
     the admin who generated it; "Noted By" is config('office.noted_by'). Each
     name sits on its signature line, with the position under it. An empty
     config leaves the line blank to be filled in by hand.

     One table, kept whole: the block never splits from itself across a page
     break (break-inside for browsers, page-break-inside for dompdf). --}}
<div class="signatures" data-signatures>
    <table class="signature-table" role="presentation">
        <tr>
            <td>
                <div class="signature-label">Prepared By:</div>
                <div class="signature-line" data-prepared-by>{{ $meta['prepared_by'] }}&nbsp;</div>
                <div class="signature-position">&nbsp;</div>
            </td>
            <td class="signature-gap"></td>
            <td>
                <div class="signature-label">Noted By:</div>
                <div class="signature-line" data-noted-by>{{ $meta['noted_by']['name'] }}&nbsp;</div>
                <div class="signature-position">{{ $meta['noted_by']['position'] }}&nbsp;</div>
            </td>
        </tr>
    </table>
</div>
