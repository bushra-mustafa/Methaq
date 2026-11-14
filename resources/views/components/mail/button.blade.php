@props(['href'])
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0;">
    <tr>
        <td align="center" bgcolor="#203322" style="background:#203322;border-radius:6px;border-bottom:3px solid #b8975f;">
            <a href="{{ $href }}" style="display:inline-block;padding:14px 34px;font-family:Tahoma,Arial,sans-serif;font-size:16px;font-weight:bold;color:#f6fbf2;text-decoration:none;">{{ $slot }}</a>
        </td>
    </tr>
</table>
