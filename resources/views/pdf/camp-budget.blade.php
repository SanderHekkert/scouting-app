<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>{{ $budget->title }} - Begroting</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        .meta { margin-bottom: 14px; color: #444; }
        .section { margin: 14px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f7fa; }
        .right { text-align: right; }
        .totals { margin-top: 16px; }
        .totals td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $budget->title }}</h1>
    <div class="meta">Jaar: {{ $budget->camp_year }} | Speltak: {{ $budget->section }}</div>

    @if(!empty($budget->content))
        <p>{{ $budget->content }}</p>
    @endif

    @foreach($sections as $section)
        <div class="section">
            <h3>{{ $section['title'] }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Post</th>
                        <th class="right">Aantal</th>
                        <th class="right">Bedrag</th>
                        <th class="right">Totaal</th>
                        <th>Notitie</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($section['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="right">{{ number_format((float)($row['quantity'] ?? 0), 2, ',', '.') }}</td>
                            <td class="right">EUR {{ number_format((float)$row['amount'], 2, ',', '.') }}</td>
                            <td class="right">EUR {{ number_format((float)$row['amount'] * (float)($row['quantity'] ?? 0), 2, ',', '.') }}</td>
                            <td>{{ $row['note'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Geen regels</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <table class="totals">
        <tr><td>Totaal bijdragen</td><td class="right">EUR {{ number_format((float)$totals['income'], 2, ',', '.') }}</td></tr>
        <tr><td>Totaal uitgaven</td><td class="right">EUR {{ number_format((float)$totals['expenses'], 2, ',', '.') }}</td></tr>
        <tr><td>Verschil</td><td class="right">EUR {{ number_format((float)$totals['difference'], 2, ',', '.') }}</td></tr>
    </table>
</body>
</html>
