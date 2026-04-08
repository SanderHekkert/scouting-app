<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uitnodiging Scouting App</title>
</head>
<body style="margin:0; padding:24px; background:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; margin:0 auto;">
        <tr>
            <td style="padding:0;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:linear-gradient(90deg,#ef4444 0%,#f59e0b 50%,#3b82f6 100%); border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="padding:2px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 24px 8px; text-align:center; background:linear-gradient(135deg,#1f4db8,#2f7cf0);">
                            <img src="{{ rtrim(config('app.url'), '/') }}/images/logo.png" alt="Fridtjof Nansen Groep 12" width="64" height="64" style="display:block; margin:0 auto 10px;" />
                            <div style="font-size:12px; letter-spacing:0.18em; text-transform:uppercase; color:#dbeafe; font-weight:700;">
                                Fridtjof Nansen Groep 12
                            </div>
                            <h1 style="margin:10px 0 4px; font-size:24px; line-height:1.2; color:#ffffff;">Je bent uitgenodigd</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px 26px;">
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6; color:#334155;">
                                Je bent uitgenodigd om een account aan te maken in de Scouting App.
                                Via onderstaande knop kun je je gegevens invullen en je account activeren.
                            </p>

                            <p style="margin:0 0 20px; text-align:center;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block; padding:11px 18px; background:#1f4db8; color:#ffffff; text-decoration:none; border-radius:10px; font-size:14px; font-weight:700;">
                                    Account aanmaken
                                </a>
                            </p>

                            <p style="margin:0 0 10px; font-size:14px; line-height:1.6; color:#334155;">
                                Deze uitnodiging is geldig tot <strong>{{ $expiresAt }}</strong>.
                            </p>
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
