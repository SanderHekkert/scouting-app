<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uitnodiging Scouting App</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
    <p>Je bent uitgenodigd om een account aan te maken in de Scouting App.</p>
    <p>
        Klik op deze link om je account in te vullen:
        <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
    </p>
    <p>Deze link is geldig tot: <strong>{{ $expiresAt }}</strong>.</p>
    <p>Na het invullen ontvang je nog een e-mail om je adres te verifiëren. Daarna kun je inloggen.</p>
</body>
</html>
