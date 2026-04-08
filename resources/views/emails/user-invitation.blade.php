<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uitnodiging Scouting App</title>
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
                            <h1 style="margin:12px 0 4px; font-size:26px; line-height:1.2; color:#ffffff;">Je bent uitgenodigd</h1>
                            <p style="margin:0; font-size:13px; color:#dbeafe;">Scouting App account aanmaken</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 24px 28px;">
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.65; color:#334155;">
                                Je bent uitgenodigd om een account aan te maken in de Scouting App.
                                Via onderstaande knop kun je je gegevens invullen en je account activeren.
                            </p>

                            <p style="margin:0 0 20px; text-align:center;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block; padding:12px 20px; background:#1f4db8; color:#ffffff; text-decoration:none; border-radius:12px; font-size:14px; font-weight:700;">
                                    Account aanmaken
                                </a>
                            </p>

                            <div style="margin:0 0 14px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px; padding:12px 14px;">
                                <p style="margin:0; font-size:14px; line-height:1.6; color:#1e3a8a;">
                                    <strong>Let op:</strong> deze uitnodiging is 24 uur geldig (tot <strong>{{ $expiresAt }}</strong>).
                                </p>
                            </div>

                            <p style="margin:0; font-size:14px; line-height:1.6; color:#334155;">
                                Na het invullen ontvang je nog een verificatiemail. Na verificatie kun je direct inloggen.
                            </p>

                            <p style="margin:18px 0 0; font-size:12px; line-height:1.5; color:#64748b;">
                                Werkt de knop niet? Kopieer en plak deze link in je browser:<br>
                                <a href="{{ $acceptUrl }}" style="color:#1f4db8; word-break:break-all;">{{ $acceptUrl }}</a>
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
