<!DOCTYPE html>
<html lang="pl">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#24324a;">
    <div style="max-width:620px;margin:0 auto;padding:24px;">
        <div style="background:#fff;border:1px solid #e6eaf0;border-radius:14px;overflow:hidden;">
            <div style="background:linear-gradient(117deg,#4E7FA7,#1473C0);color:#fff;padding:22px 28px;">
                <div style="font-size:18px;font-weight:700;">{{ $shop }} — rekrutacja</div>
                <div style="opacity:.9;font-size:14px;margin-top:2px;">Nowe zgłoszenie kandydata</div>
            </div>
            <div style="padding:24px 28px;">
                <table style="width:100%;border-collapse:collapse;font-size:15px;">
                    <tr>
                        <td style="padding:8px 0;color:#74726c;width:160px;">Stanowisko</td>
                        <td style="padding:8px 0;font-weight:600;">{{ $d['position'] ?? 'Aplikacja spontaniczna' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#74726c;">Imię i nazwisko</td>
                        <td style="padding:8px 0;font-weight:600;">{{ $d['name'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#74726c;">E-mail</td>
                        <td style="padding:8px 0;"><a href="mailto:{{ $d['email'] }}" style="color:#1473c0;">{{ $d['email'] }}</a></td>
                    </tr>
                    @if(!empty($d['phone']))
                    <tr>
                        <td style="padding:8px 0;color:#74726c;">Telefon</td>
                        <td style="padding:8px 0;"><a href="tel:{{ $d['phone'] }}" style="color:#1473c0;">{{ $d['phone'] }}</a></td>
                    </tr>
                    @endif
                </table>

                @if(!empty($d['message']))
                    <div style="margin-top:18px;padding-top:18px;border-top:1px solid #eef1f6;">
                        <div style="color:#74726c;font-size:13px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">List motywacyjny</div>
                        <div style="white-space:pre-wrap;line-height:1.6;">{{ $d['message'] }}</div>
                    </div>
                @endif

                <div style="margin-top:18px;padding-top:18px;border-top:1px solid #eef1f6;font-size:14px;color:#3a3d47;">
                    📎 CV kandydata znajduje się w załączniku tej wiadomości.
                </div>
            </div>
        </div>
        <div style="text-align:center;color:#9aa4b3;font-size:12px;margin-top:14px;">
            Wiadomość wygenerowana automatycznie przez formularz rekrutacyjny {{ $shop }}.<br>
            Odpowiedz na tego maila, aby napisać bezpośrednio do kandydata.
        </div>
    </div>
</body>
</html>
