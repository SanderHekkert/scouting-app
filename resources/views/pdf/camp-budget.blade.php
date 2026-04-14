<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>{{ $budget->title }} - Begroting</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        .top-accent {
            height: 7px;
            background: linear-gradient(90deg, #cf2a2a, #f4c33f, #2563eb);
            border-radius: 999px;
            margin-bottom: 14px;
        }
        .header {
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            background: #f8fbff;
            padding: 14px;
            margin-bottom: 14px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: none;
            vertical-align: top;
        }
        .logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #dbe5f2;
            padding: 4px;
        }
        .logo-fallback {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            border: 1px solid #dbe5f2;
            background: #2563eb;
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            line-height: 64px;
        }
        .title {
            font-size: 21px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .subtitle {
            font-size: 11px;
            color: #475569;
            margin-bottom: 8px;
        }
        .meta-row {
            margin-top: 8px;
            font-size: 11px;
            color: #334155;
        }
        .pill {
            display: inline-block;
            border: 1px solid #bfd5ff;
            background: #eaf2ff;
            color: #1e3a8a;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 10px;
            margin-right: 6px;
            margin-top: 3px;
        }
        .section {
            margin: 12px 0 14px;
            border: 1px solid #dbe5f2;
            border-radius: 10px;
            overflow: hidden;
        }
        .section-title {
            background: #eaf2ff;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
            border-bottom: 1px solid #dbe5f2;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 10px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }
        .right { text-align: right; white-space: nowrap; }
        .empty { color: #64748b; font-style: italic; }
        .totals {
            width: 100%;
            margin-top: 10px;
            border-collapse: separate;
            border-spacing: 0 6px;
        }
        .totals td {
            border: 1px solid #dbe5f2;
            background: #f8fbff;
            padding: 8px 10px;
            font-weight: 700;
        }
        .totals .label-cell { color: #1e3a8a; }
        .totals .value-cell { color: #0f172a; }
        .totals .difference .label-cell,
        .totals .difference .value-cell {
            border-color: #bfd5ff;
            background: #eaf2ff;
        }
        .footer {
            margin-top: 8px;
            font-size: 10px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $money = static fn (float|int|string|null $value): string => 'EUR '.number_format((float) ($value ?? 0), 2, ',', '.');
        $sectionLabel = str_replace('_', ' ', (string) $budget->section);
        $locationLabel = (string) ($campLocation ?? 'fram') === 'clubhuis' ? 'Clubhuis' : 'Fram';
    @endphp

    <div class="top-accent"></div>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:76px;">
                    @if(!empty($logoDataUri))
                        <img src="{{ $logoDataUri }}" alt="Fridtjof Nansen Groep 12" class="logo">
                    @else
                        <div class="logo-fallback">FN12</div>
                    @endif
                </td>
                <td>
                    <h1 class="title">{{ $budget->title }}</h1>
                    <p class="subtitle">Kampbegroting - Fridtjof Nansen Groep 12</p>
                    <div>
                        <span class="pill">Jaar: {{ $budget->camp_year }}</span>
                        <span class="pill">Speltak: {{ ucfirst($sectionLabel) }}</span>
                        <span class="pill">Locatie: {{ $locationLabel }}</span>
                        <span class="pill">Kampdagen: {{ (int) ($campDays ?? 1) }}</span>
                    </div>
                    <p class="meta-row">Status: {{ ucfirst(str_replace('_', ' ', (string) ($budget->status ?? 'draft'))) }}</p>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($budget->content))
        <div class="section" style="padding: 10px;">
            <p style="font-weight: 700; margin-bottom: 4px;">Toelichting</p>
            <p>{{ $budget->content }}</p>
        </div>
    @endif

    @foreach($sections as $section)
        <div class="section">
            <h3 class="section-title">{{ $section['title'] }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Post</th>
                        <th class="right">Aantal</th>
                        <th class="right">Bedrag</th>
                        <th class="right">Totaal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($section['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="right">{{ number_format((float)($row['quantity'] ?? 0), 0, ',', '.') }}</td>
                            <td class="right">{{ $money($row['effective_amount'] ?? 0) }}</td>
                            <td class="right">{{ $money($row['computed_total'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="4">Geen regels</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <table class="totals">
        <tr>
            <td class="label-cell">Totaal bijdragen</td>
            <td class="right value-cell">{{ $money($totals['income'] ?? 0) }}</td>
        </tr>
        <tr>
            <td class="label-cell">Totaal uitgaven</td>
            <td class="right value-cell">{{ $money($totals['expenses'] ?? 0) }}</td>
        </tr>
        <tr class="difference">
            <td class="label-cell">Verschil</td>
            <td class="right value-cell">{{ $money($totals['difference'] ?? 0) }}</td>
        </tr>
    </table>
    <p class="footer">Gegenereerd op {{ now()->format('d-m-Y H:i') }}</p>
</body>
</html>
