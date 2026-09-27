{{-- Expects $rows from App\Support\DealTerms::rows(): [label, value, emphasised?] --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
    <tr>
        <td style="padding:20px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                @foreach($rows as $row)
                    <tr>
                        <td style="padding:6px 0; color:#6c757d; font-size:13px; vertical-align:top; {{ !empty($row[2]) ? 'border-top:1px solid #dee2e6; padding-top:12px;' : '' }}">{{ $row[0] }}</td>
                        <td style="padding:6px 0 6px 16px; color:#1a1a2e; text-align:right; vertical-align:top; {{ !empty($row[2]) ? 'font-weight:700; font-size:18px; border-top:1px solid #dee2e6; padding-top:12px;' : 'font-weight:600;' }}">{{ $row[1] }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
