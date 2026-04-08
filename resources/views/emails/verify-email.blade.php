<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mailadres verifiëren</title>
</head>
<body style="margin:0; padding:24px; background:linear-gradient(135deg,#0b1d4d,#102a6b 50%,#0a1a42); font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; margin:0 auto;">
        <tr>
            <td style="padding:0;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:linear-gradient(90deg,#ef4444 0%,#f59e0b 50%,#3b82f6 100%); border-radius:20px; overflow:hidden; box-shadow:0 20px 45px rgba(2,6,23,0.45);">
                    <tr>
                        <td style="padding:2px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fbff; border-radius:18px; overflow:hidden;">
                                <tr>
                                    <td style="padding:28px 24px 12px; text-align:center; background:linear-gradient(135deg,#133a93,#1f4db8 45%,#2f7cf0);">
                                        <img src="{{ $logoDataUri ?: rtrim(config('app.url'), '/') . '/images/logo.png' }}" alt="Fridtjof Nansen Groep 12" width="74" height="74" style="display:block; margin:0 auto 12px;" />
                                        <div style="font-size:12px; letter-spacing:0.18em; text-transform:uppercase; color:#dbeafe; font-weight:700;">
                                            Fridtjof Nansen Groep 12
                                        </div>
                                        <h1 style="margin:12px 0 4px; font-size:26px; line-height:1.2; color:#ffffff;">Bevestig je e-mailadres</h1>
                                        <p style="margin:0; font-size:13px; color:#dbeafe;">Scouting App account activatie</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:24px 24px 28px;">
                                        <p style="margin:0 0 14px; font-size:15px; line-height:1.65; color:#334155;">
                                            Welkom! Om je account te activeren moet je eerst je e-mailadres bevestigen.
                                            Klik op de knop hieronder om door te gaan.
                                        </p>

                                        <p style="margin:0 0 20px; text-align:center;">
                                            <a href="{{ $verifyUrl }}" style="display:inline-block; padding:12px 20px; background:#1f4db8; color:#ffffff; text-decoration:none; border-radius:12px; font-size:14px; font-weight:700;">
                                                E-mailadres bevestigen
                                            </a>
                                        </p>

                                        <div style="margin:0 0 14px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px; padding:12px 14px;">
                                            <p style="margin:0; font-size:14px; line-height:1.6; color:#1e3a8a;">
                                                Als je dit account niet zelf hebt aangemaakt, kun je deze e-mail veilig negeren.
                                            </p>
                                        </div>

                                        <p style="margin:18px 0 0; font-size:12px; line-height:1.5; color:#64748b;">
                                            Werkt de knop niet? Kopieer en plak deze link in je browser:<br>
                                            <a href="{{ $verifyUrl }}" style="color:#1f4db8; word-break:break-all;">{{ $verifyUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
