<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>{{ $playbook->title }} - Draaiboek</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; line-height: 1.45; }
        h1, p { margin: 0; }
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
        .content {
            border: 1px solid #dbe5f2;
            border-radius: 10px;
            background: #ffffff;
            padding: 12px;
            white-space: pre-wrap;
        }
        .section {
            margin-bottom: 10px;
            border: 1px solid #dbe5f2;
            border-radius: 10px;
            background: #ffffff;
            overflow: hidden;
        }
        .section-title {
            margin: 0;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #1e3a8a;
            background: #eaf2ff;
            border-bottom: 1px solid #dbe5f2;
        }
        .section-content {
            padding: 10px;
            white-space: pre-wrap;
        }
        .day-block {
            border: 1px solid #dbe5f2;
            border-radius: 8px;
            background: #f8fbff;
            padding: 8px;
            margin-bottom: 8px;
        }
        .day-title {
            font-size: 11px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 6px;
        }
        .daywatch {
            font-size: 11px;
            color: #334155;
            margin-bottom: 6px;
        }
        .planning-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .planning-table th,
        .planning-table td {
            border: 1px solid #dbe5f2;
            padding: 5px;
            font-size: 10px;
            vertical-align: top;
        }
        .planning-table th {
            background: #eaf2ff;
            color: #1e3a8a;
            font-weight: 700;
        }
        .game-explanation {
            font-size: 10px;
            color: #0f172a;
            white-space: pre-wrap;
        }
        .service-grid {
            padding: 10px;
        }
        .service-card {
            border: 1px solid #dbe5f2;
            border-radius: 8px;
            background: #f8fbff;
            padding: 8px;
            margin-bottom: 8px;
        }
        .service-title {
            font-size: 11px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 6px;
        }
        .service-line {
            margin-bottom: 3px;
            font-size: 11px;
            color: #0f172a;
        }
        .service-line strong {
            color: #334155;
        }
        .footer {
            margin-top: 10px;
            font-size: 10px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
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
                    <h1 class="title">{{ $playbook->title }}</h1>
                    <p class="subtitle">Kampdraaiboek - Fridtjof Nansen Groep 12</p>
                    <div>
                        <span class="pill">Jaar: {{ (int) $playbook->camp_year }}</span>
                        <span class="pill">Speltak: {{ ucfirst(str_replace('_', ' ', (string) $playbook->section)) }}</span>
                        <span class="pill">Kamptype: {{ (string) data_get($playbook->meta, 'camp_location', 'fram') === 'clubhuis' ? 'Clubhuis' : 'Fram' }}</span>
                        @if(trim((string) data_get($playbook->meta, 'camp_place', '')) !== '')
                            <span class="pill">Plaats: {{ (string) data_get($playbook->meta, 'camp_place', '') }}</span>
                        @endif
                        @if(trim((string) data_get($playbook->meta, 'camp_dates', '')) !== '')
                            <span class="pill">Datum: {{ (string) data_get($playbook->meta, 'camp_dates', '') }}</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($sections))
        @foreach($sections as $section)
            @php
                $isHulpdiensten = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'hulpdiensten';
                $isPlanning = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'planning per dag';
                $hasSectionContent = trim((string) ($section['content'] ?? '')) !== '';
                $hasEmergencyContent = $isHulpdiensten && collect((array) ($emergencyContacts ?? []))
                    ->flatten(1)
                    ->filter(fn ($value): bool => trim((string) $value) !== '')
                    ->isNotEmpty();
                $hasPlanningContent = $isPlanning && collect((array) ($dayPlans ?? []))
                    ->filter(function ($day): bool {
                        if (!is_array($day)) return false;
                        $rowsFilled = collect((array) ($day['planning_rows'] ?? []))
                            ->filter(fn ($row): bool => is_array($row))
                            ->contains(fn ($row): bool => trim((string) ($row['time'] ?? '')) !== '' || trim((string) ($row['program'] ?? '')) !== '' || trim((string) ($row['game'] ?? '')) !== '' || trim((string) ($row['needs'] ?? '')) !== '');
                        return trim((string) ($day['day_label'] ?? '')) !== ''
                            || trim((string) ($day['game_explanation'] ?? '')) !== ''
                            || collect((array) ($day['daywatch_ids'] ?? []))->isNotEmpty()
                            || $rowsFilled;
                    })
                    ->isNotEmpty();
            @endphp
            @if($hasSectionContent || $hasEmergencyContent || $hasPlanningContent)
                <div class="section">
                    <h3 class="section-title">{{ (string) ($section['title'] ?? 'Sectie') }}</h3>
                    @if($isHulpdiensten)
                        <div class="service-grid">
                            @foreach(['huisartsen' => 'Huisartsen', 'ziekenhuizen' => 'Ziekenhuizen', 'tandartsen' => 'Tandartsen'] as $key => $label)
                                @php $entry = (array) data_get($emergencyContacts ?? [], $key, []); @endphp
                                <div class="service-card">
                                    <p class="service-title">{{ $label }}</p>
                                    <p class="service-line"><strong>Naam:</strong> {{ (string) ($entry['name'] ?? '—') ?: '—' }}</p>
                                    <p class="service-line"><strong>Adres:</strong> {{ (string) ($entry['address'] ?? '—') ?: '—' }}</p>
                                    <p class="service-line"><strong>Postcode:</strong> {{ (string) ($entry['postal_code'] ?? '—') ?: '—' }}</p>
                                    <p class="service-line"><strong>Plaats:</strong> {{ (string) ($entry['city'] ?? '—') ?: '—' }}</p>
                                    <p class="service-line"><strong>010 nummer:</strong> {{ (string) ($entry['phone_010'] ?? '—') ?: '—' }}</p>
                                    <p class="service-line"><strong>Site:</strong> {{ (string) ($entry['website'] ?? '—') ?: '—' }}</p>
                                    <p class="service-line"><strong>Extra informatie:</strong> {{ (string) ($entry['extra_info'] ?? '—') ?: '—' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @elseif($isPlanning)
                        <div class="service-grid">
                            @foreach((array) ($dayPlans ?? []) as $day)
                                @php
                                    $daywatchNames = collect((array) ($day['daywatch_ids'] ?? []))
                                        ->map(fn ($id): string => (string) data_get($leaderTeamMap ?? [], (int) $id, 'Leiding #'.(int) $id))
                                        ->filter(fn ($name): bool => trim($name) !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <div class="day-block">
                                    <p class="day-title">{{ (string) ($day['day_label'] ?? 'Dag') }}</p>
                                    <p class="daywatch"><strong>Dagwacht:</strong> {{ $daywatchNames !== [] ? implode(', ', $daywatchNames) : 'Niet ingevuld' }}</p>
                                    <table class="planning-table">
                                        <thead>
                                            <tr>
                                                <th>Tijden</th>
                                                <th>Programma</th>
                                                <th>Spel</th>
                                                <th>Benodigdheden</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach((array) ($day['planning_rows'] ?? []) as $row)
                                                @if(trim((string) ($row['time'] ?? '')) !== '' || trim((string) ($row['program'] ?? '')) !== '' || trim((string) ($row['game'] ?? '')) !== '' || trim((string) ($row['needs'] ?? '')) !== '')
                                                    <tr>
                                                        <td>{{ (string) ($row['time'] ?? '') }}</td>
                                                        <td>{{ (string) ($row['program'] ?? '') }}</td>
                                                        <td>{{ (string) ($row['game'] ?? '') }}</td>
                                                        <td>{{ (string) ($row['needs'] ?? '') }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if(trim((string) ($day['game_explanation'] ?? '')) !== '')
                                        <p class="game-explanation"><strong>Speluitleg:</strong> {{ (string) ($day['game_explanation'] ?? '') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="section-content">{{ (string) ($section['content'] ?? '') }}</div>
                    @endif
                </div>
            @endif
        @endforeach
    @else
        <div class="content">{{ (string) ($playbook->content ?? '') }}</div>
    @endif
    <p class="footer">Gegenereerd op {{ now()->format('d-m-Y H:i') }}</p>
</body>
</html>
