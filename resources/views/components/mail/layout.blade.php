@props(['title', 'preheader' => ''])
@php
    $serif = "Amiri, 'Noto Naskh Arabic', 'Traditional Arabic', 'Geeza Pro', Georgia, serif";
    $sans = "Tahoma, 'Segoe UI', Arial, sans-serif";
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#e8eee3;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e8eee3;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
                    <tr>
                        <td align="center" style="background:#203322;border-radius:10px 10px 0 0;padding:30px 24px 24px;">
                            <div style="font-family:{{ $serif }};font-size:44px;line-height:1.2;color:#f6fbf2;font-weight:bold;">ميثاق</div>
                            <div style="font-family:{{ $sans }};font-size:13px;letter-spacing:1px;color:#d9c08f;padding-top:8px;">✦ &nbsp;دعوات إلكترونية تليق بمناسباتك&nbsp; ✦</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#203322;padding:0 14px 14px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fffdf8;border:1px solid #b8975f;border-radius:4px;">
                                <tr>
                                    <td style="padding:6px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d9c08f;border-radius:2px;">
                                            <tr>
                                                <td style="padding:34px 30px 30px;font-family:{{ $sans }};font-size:16px;line-height:1.95;color:#2b2f2a;text-align:right;">
                                                    <h1 style="margin:0 0 18px;font-family:{{ $serif }};font-size:30px;line-height:1.4;color:#203322;font-weight:bold;">{{ $title }}</h1>
                                                    {{ $slot }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f6fbf2;border-radius:0 0 10px 10px;border:1px solid #d6dfd0;border-top:0;padding:22px 24px;">
                            <div style="font-family:{{ $serif }};font-size:19px;color:#203322;">«لكل حكاية جميلة... ميثاقٌ يليق بها»</div>
                            <div style="font-family:{{ $sans }};font-size:12px;line-height:1.8;color:#5b6358;padding-top:10px;">
                                وصلتك هذه الرسالة لأن لك حساباً في <a href="{{ url('/') }}" style="color:#7a613b;">ميثاق</a>.
                                @if ($footer ?? false) {{ $footer }} @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
