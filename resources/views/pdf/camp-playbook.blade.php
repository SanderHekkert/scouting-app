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
        .vaarschema-info {
            border: 1px solid #bfd5ff;
            background: #eaf2ff;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 10px;
            color: #1e3a8a;
        }
        .vaarschema-link {
            color: #1d4ed8;
            text-decoration: underline;
            word-break: break-all;
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
        .page-break {
            page-break-after: always;
        }
        .page-break-before {
            page-break-before: always;
        }
        .cover-page {
            height: 100%;
            border: 1px solid #dbe5f2;
            border-radius: 14px;
            background: #f8fbff;
            overflow: hidden;
            display: table;
            width: 100%;
        }
        .cover-main {
            display: table-cell;
            vertical-align: middle;
            padding: 18px 20px;
        }
        .cover-photo-wrap {
            width: 100%;
            height: 380px;
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            background: #ffffff;
            display: table;
            overflow: hidden;
            margin-top: 14px;
        }
        .cover-photo-wrap > div {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .cover-photo {
            width: 100%;
            max-height: 380px;
            object-fit: contain;
        }
        .cover-photo-fallback {
            background: linear-gradient(130deg, #1d4ed8, #2563eb 45%, #0ea5e9);
            color: #ffffff;
            text-align: center;
            width: 100%;
            height: 380px;
            line-height: 380px;
            font-size: 34px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .cover-meta {
            text-align: center;
        }
        .cover-title {
            margin: 0 0 6px;
            font-size: 30px;
            font-weight: 700;
            color: #0f172a;
            text-align: center;
        }
        .cover-subtitle {
            margin: 0;
            font-size: 13px;
            color: #334155;
            text-align: center;
        }
        .cover-header-card {
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px 12px;
            margin-top: 12px;
            text-align: left;
        }
        .cover-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cover-header-table td {
            border: none;
            vertical-align: top;
        }
        .toc-page {
            border: 1px solid #dbe5f2;
            border-radius: 14px;
            background: #ffffff;
            padding: 20px;
        }
        .toc-title {
            margin: 0 0 10px;
            font-size: 24px;
            color: #0f172a;
        }
        .toc-list {
            margin: 0;
            padding-left: 18px;
        }
        .toc-list li {
            margin-bottom: 7px;
            font-size: 12px;
            color: #0f172a;
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
    @php
        $sectionTitles = collect((array) ($sections ?? []))
            ->map(fn ($section): string => trim((string) ($section['title'] ?? '')))
            ->filter(fn (string $title): bool => $title !== '')
            ->values()
            ->all();
    @endphp
    <div class="cover-page">
        <div class="cover-main">
            <div class="cover-meta">
            <p class="cover-title">{{ $playbook->title }}</p>
            <p class="cover-subtitle">Kampdraaiboek - Fridtjof Nansen Groep 12</p>

            <div class="cover-header-card">
                <table class="cover-header-table">
                    <tr>
                        <td style="width:76px;">
                            @if(!empty($logoDataUri))
                                <img src="{{ $logoDataUri }}" alt="Fridtjof Nansen Groep 12" class="logo">
                            @else
                                <div class="logo-fallback">FN12</div>
                            @endif
                        </td>
                        <td>
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

            <div class="cover-photo-wrap">
                <div>
                    @if(!empty($coverPhotoDataUri))
                        <img src="{{ $coverPhotoDataUri }}" alt="Cover foto kampdraaiboek" class="cover-photo">
                    @else
                        <div class="cover-photo-fallback">DRAAIBOEK</div>
                    @endif
                </div>
            </div>
        </div>
        </div>
    </div>
    <div class="toc-page page-break-before">
        <p class="toc-title">Inhoud</p>
        @if($sectionTitles !== [])
            <ol class="toc-list">
                @foreach($sectionTitles as $title)
                    <li>{{ $title }}</li>
                @endforeach
            </ol>
        @else
            <p>Geen hoofdstukken gevonden.</p>
        @endif
    </div>
    <div class="top-accent page-break-before"></div>

    @if(!empty($sections))
        @foreach($sections as $section)
            @php
                $isHulpdiensten = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'hulpdiensten';
                $isMonsterrol = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'monsterrol';
                $isTaakverdeling = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'taakverdeling';
                $isTaakUitleg = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'taak uitleg';
                $isAlgemeneAfspraken = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'algemene afspraken';
                $isSpeltakAfspraken = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'speltak afspraken';
                $isCorveerooster = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'corveerooster';
                $isVinindeling = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'vinindeling';
                $isPlanning = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'planning per dag';
                $isVaarschema = mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'vaarschema';
                $isFramCamp = (string) data_get($playbook->meta, 'camp_location', 'fram') === 'fram';
                $hasSectionContent = trim((string) ($section['content'] ?? '')) !== '';
                $hasTaakverdelingContent = $isTaakverdeling && collect((array) ($taskDistributionRows ?? []))
                    ->contains(function ($row): bool {
                        $responsibles = collect((array) data_get($row, 'responsibles', data_get($row, 'responsible', [])))
                            ->map(fn ($name): string => trim((string) $name))
                            ->filter(fn (string $name): bool => $name !== '')
                            ->values()
                            ->all();
                        return trim((string) data_get($row, 'task', '')) !== '' || $responsibles !== [];
                    });
                $hasTaakUitlegContent = $isTaakUitleg && collect((array) ($taskExplanationItems ?? []))
                    ->contains(function ($item): bool {
                        if (!is_array($item)) {
                            return false;
                        }
                        $bulletsFilled = collect((array) ($item['bullets'] ?? []))
                            ->contains(fn ($bullet): bool => trim((string) $bullet) !== '');
                        return trim((string) ($item['title'] ?? '')) !== '' || $bulletsFilled;
                    });
                $hasAlgemeneAfsprakenContent = $isAlgemeneAfspraken && collect((array) ($generalAgreementsItems ?? []))
                    ->contains(function ($item): bool {
                        if (!is_array($item)) {
                            return false;
                        }
                        $bulletsFilled = collect((array) ($item['bullets'] ?? []))
                            ->contains(fn ($bullet): bool => trim((string) $bullet) !== '');
                        return trim((string) ($item['title'] ?? '')) !== '' || $bulletsFilled;
                    });
                $hasSpeltakAfsprakenContent = $isSpeltakAfspraken && collect((array) ($speltakAgreementsItems ?? []))
                    ->contains(function ($item): bool {
                        if (!is_array($item)) {
                            return false;
                        }
                        $bulletsFilled = collect((array) ($item['bullets'] ?? []))
                            ->contains(fn ($bullet): bool => trim((string) $bullet) !== '');
                        return trim((string) ($item['title'] ?? '')) !== '' || $bulletsFilled;
                    });
                $hasSpeltakHygieneContent = $isSpeltakAfspraken && collect((array) ($speltakHygieneRows ?? []))
                    ->contains(fn ($row): bool => trim((string) data_get($row, 'topic', '')) !== '' || trim((string) data_get($row, 'jerrycans', '')) !== '' || trim((string) data_get($row, 'kraanwater', '')) !== '' || trim((string) data_get($row, 'buitenboordwater', '')) !== '' || trim((string) data_get($row, 'desinfectans', '')) !== '');
                $hasCorveeContent = $isCorveerooster && collect((array) ($corveeRows ?? []))
                    ->contains(fn ($row): bool => trim((string) data_get($row, 'day', '')) !== '' || trim((string) data_get($row, 'date', '')) !== '' || trim((string) data_get($row, 'daywatch', '')) !== '' || trim((string) data_get($row, 'dienstvin', '')) !== '' || trim((string) data_get($row, 'dekhuis', '')) !== '' || trim((string) data_get($row, 'achteronder_en_dekken', '')) !== '' || trim((string) data_get($row, 'wc_en_klusjes', '')) !== '');
                $hasVinindelingContent = $isVinindeling && collect((array) ($vinindelingRows ?? []))
                    ->contains(function ($row): bool {
                        if (!is_array($row)) {
                            return false;
                        }
                        $vins = collect((array) ($row['vins'] ?? []))
                            ->filter(fn ($vin): bool => is_array($vin))
                            ->values();

                        if ($vins->isEmpty()) {
                            $vins = collect((array) ($row['fin_names'] ?? []))
                                ->map(fn ($name): array => ['vin_name' => trim((string) $name), 'member_names' => []])
                                ->values();
                        }

                        $hasVins = $vins->contains(function ($vin): bool {
                            $vinName = trim((string) data_get($vin, 'vin_name', data_get($vin, 'name', '')));
                            $hasMembers = collect((array) data_get($vin, 'member_names', data_get($vin, 'members', [])))
                                ->contains(fn ($member): bool => trim((string) $member) !== '');

                            return $vinName !== '' || $hasMembers;
                        });

                        return trim((string) ($row['role'] ?? '')) !== '' || $hasVins;
                    });
                $hasMonsterrolContent = $isMonsterrol && collect((array) ($monsterrolRows ?? []))
                    ->flatten(1)
                    ->contains(function ($row): bool {
                        if (!is_array($row)) {
                            return false;
                        }

                        return trim((string) ($row['first_name'] ?? '')) !== ''
                            || trim((string) ($row['last_name'] ?? '')) !== ''
                            || trim((string) ($row['functie'] ?? '')) !== ''
                            || trim((string) ($row['on_board'] ?? '')) !== ''
                            || trim((string) ($row['off_board'] ?? '')) !== '';
                    });
                $hasEmergencyContent = $isHulpdiensten && collect(['huisartsen', 'ziekenhuizen', 'tandartsen'])
                    ->contains(function (string $category) use ($emergencyContacts): bool {
                        $rows = data_get($emergencyContacts ?? [], $category, []);
                        $rowsList = is_array($rows) && array_is_list($rows) ? $rows : [$rows];

                        return collect($rowsList)
                            ->filter(fn ($row): bool => is_array($row))
                            ->contains(function (array $row): bool {
                                return trim((string) ($row['name'] ?? '')) !== ''
                                    || trim((string) ($row['address'] ?? '')) !== ''
                                    || trim((string) ($row['postal_code'] ?? '')) !== ''
                                    || trim((string) ($row['city'] ?? '')) !== ''
                                    || trim((string) ($row['phone_010'] ?? '')) !== ''
                                    || trim((string) ($row['website'] ?? '')) !== ''
                                    || trim((string) ($row['extra_info'] ?? '')) !== '';
                            });
                    });
                $hasVaarschemaContent = $isVaarschema && $isFramCamp && collect((array) ($vaarschemaRows ?? []))
                    ->contains(fn ($row): bool => trim((string) data_get($row, 'date', '')) !== '' || trim((string) data_get($row, 'from', '')) !== '' || trim((string) data_get($row, 'to', '')) !== '' || trim((string) data_get($row, 'depart_at', '')) !== '' || trim((string) data_get($row, 'arrive_at', '')) !== '' || trim((string) data_get($row, 'tide_margin_minutes', '')) !== '');
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
            @if($hasSectionContent || $hasTaakverdelingContent || $hasTaakUitlegContent || $hasAlgemeneAfsprakenContent || $hasSpeltakAfsprakenContent || $hasSpeltakHygieneContent || $hasCorveeContent || $hasVinindelingContent || $hasMonsterrolContent || $hasEmergencyContent || $hasPlanningContent || $hasVaarschemaContent)
                <div class="section">
                    <h3 class="section-title">{{ (string) ($section['title'] ?? 'Sectie') }}</h3>
                    @if($isTaakverdeling)
                        <div class="service-grid">
                            <table class="planning-table">
                                <thead>
                                    <tr>
                                        <th>Taak</th>
                                        <th>Verantwoordelijke</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach((array) ($taskDistributionRows ?? []) as $row)
                                        @php
                                            $responsibles = collect((array) data_get($row, 'responsibles', data_get($row, 'responsible', [])))
                                                ->map(fn ($name): string => trim((string) $name))
                                                ->filter(fn (string $name): bool => $name !== '')
                                                ->values()
                                                ->all();
                                        @endphp
                                        @if(trim((string) ($row['task'] ?? '')) !== '' || $responsibles !== [])
                                            <tr>
                                                <td>{{ (string) ($row['task'] ?? '') }}</td>
                                                <td>{{ $responsibles !== [] ? implode(', ', $responsibles) : '' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($isTaakUitleg)
                        <div class="service-grid">
                            @foreach((array) ($taskExplanationItems ?? []) as $item)
                                @php
                                    $filledBullets = collect((array) ($item['bullets'] ?? []))
                                        ->map(fn ($bullet): string => trim((string) $bullet))
                                        ->filter(fn (string $bullet): bool => $bullet !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                @if(trim((string) ($item['title'] ?? '')) !== '' || $filledBullets !== [])
                                    <div class="day-block">
                                        <p class="day-title">{{ (string) ($item['title'] ?? 'Taak') }}</p>
                                        @if($filledBullets !== [])
                                            <ul style="margin: 0; padding-left: 16px;">
                                                @foreach($filledBullets as $bullet)
                                                    <li style="margin-bottom: 3px; font-size: 11px; color: #0f172a;">{{ $bullet }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($isAlgemeneAfspraken)
                        <div class="service-grid">
                            @foreach((array) ($generalAgreementsItems ?? []) as $item)
                                @php
                                    $filledBullets = collect((array) ($item['bullets'] ?? []))
                                        ->map(fn ($bullet): string => trim((string) $bullet))
                                        ->filter(fn (string $bullet): bool => $bullet !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                @if(trim((string) ($item['title'] ?? '')) !== '' || $filledBullets !== [])
                                    <div class="day-block">
                                        <p class="day-title">{{ (string) ($item['title'] ?? 'Afspraken') }}</p>
                                        @if($filledBullets !== [])
                                            <ul style="margin: 0; padding-left: 16px;">
                                                @foreach($filledBullets as $bullet)
                                                    <li style="margin-bottom: 3px; font-size: 11px; color: #0f172a;">{{ $bullet }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($isSpeltakAfspraken)
                        <div class="service-grid">
                            @foreach((array) ($speltakAgreementsItems ?? []) as $item)
                                @php
                                    $filledBullets = collect((array) ($item['bullets'] ?? []))
                                        ->map(fn ($bullet): string => trim((string) $bullet))
                                        ->filter(fn (string $bullet): bool => $bullet !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                @if(trim((string) ($item['title'] ?? '')) !== '' || $filledBullets !== [])
                                    <div class="day-block">
                                        <p class="day-title">{{ (string) ($item['title'] ?? 'Afspraken') }}</p>
                                        @if($filledBullets !== [])
                                            <ul style="margin: 0; padding-left: 16px;">
                                                @foreach($filledBullets as $bullet)
                                                    <li style="margin-bottom: 3px; font-size: 11px; color: #0f172a;">{{ $bullet }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            @endforeach

                            <div class="day-block">
                                <p class="day-title">Hygiëne en gezondheid tabel</p>
                                <table class="planning-table">
                                    <thead>
                                        <tr>
                                            <th>Onderwerp</th>
                                            <th>Jerrycans</th>
                                            <th>Kraanwater</th>
                                            <th>Buitenboordwater</th>
                                            <th>Desinfectans</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach((array) ($speltakHygieneRows ?? []) as $row)
                                            @if(trim((string) ($row['topic'] ?? '')) !== '' || trim((string) ($row['jerrycans'] ?? '')) !== '' || trim((string) ($row['kraanwater'] ?? '')) !== '' || trim((string) ($row['buitenboordwater'] ?? '')) !== '' || trim((string) ($row['desinfectans'] ?? '')) !== '')
                                                <tr>
                                                    <td>{{ (string) ($row['topic'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['jerrycans'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['kraanwater'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['buitenboordwater'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['desinfectans'] ?? '') }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif($isCorveerooster)
                        <div class="service-grid">
                            <table class="planning-table">
                                <thead>
                                    <tr>
                                        <th>Dag</th>
                                        <th>Datum</th>
                                        <th>Dagwacht</th>
                                        <th>Dienstvin</th>
                                        <th>Dekhuis</th>
                                        <th>Achteronder &amp; Dekken</th>
                                        <th>WC &amp; klusjes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach((array) ($corveeRows ?? []) as $row)
                                        @if(trim((string) ($row['day'] ?? '')) !== '' || trim((string) ($row['date'] ?? '')) !== '' || trim((string) ($row['daywatch'] ?? '')) !== '' || trim((string) ($row['dienstvin'] ?? '')) !== '' || trim((string) ($row['dekhuis'] ?? '')) !== '' || trim((string) ($row['achteronder_en_dekken'] ?? '')) !== '' || trim((string) ($row['wc_en_klusjes'] ?? '')) !== '')
                                            <tr>
                                                <td>{{ (string) ($row['day'] ?? '') }}</td>
                                                <td>{{ (string) ($row['date'] ?? '') }}</td>
                                                <td>{{ (string) ($row['daywatch'] ?? '') }}</td>
                                                <td>{{ (string) ($row['dienstvin'] ?? '') }}</td>
                                                <td>{{ (string) ($row['dekhuis'] ?? '') }}</td>
                                                <td>{{ (string) ($row['achteronder_en_dekken'] ?? '') }}</td>
                                                <td>{{ (string) ($row['wc_en_klusjes'] ?? '') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($isVinindeling)
                        <div class="service-grid">
                            @php
                                $defaultVinHeaders = ['De Regisseurs', 'De Acteurs', 'De Cameraploeg'];
                                $vinRows = collect((array) ($vinindelingRows ?? []))
                                    ->filter(fn ($row): bool => is_array($row))
                                    ->map(function (array $row): array {
                                        $vins = collect((array) ($row['vins'] ?? []))
                                            ->filter(fn ($vin): bool => is_array($vin))
                                            ->map(function (array $vin): array {
                                                $memberNames = collect((array) ($vin['member_names'] ?? $vin['members'] ?? []))
                                                    ->map(fn ($name): string => trim((string) $name))
                                                    ->filter(fn (string $name): bool => $name !== '')
                                                    ->values()
                                                    ->all();

                                                return [
                                                    'vin_name' => trim((string) ($vin['vin_name'] ?? $vin['name'] ?? '')),
                                                    'member_names' => $memberNames,
                                                ];
                                            })
                                            ->values()
                                            ->all();

                                        if ($vins === []) {
                                            $vins = collect((array) ($row['fin_names'] ?? []))
                                                ->map(fn ($name): array => ['vin_name' => trim((string) $name), 'member_names' => []])
                                                ->values()
                                                ->all();
                                        }

                                        return [
                                            'role' => trim((string) ($row['role'] ?? '')),
                                            'vins' => $vins,
                                        ];
                                    })
                                    ->values();

                                $vinHeaders = $vinRows
                                    ->flatMap(fn (array $row): array => (array) ($row['vins'] ?? []))
                                    ->map(fn (array $vin): string => trim((string) ($vin['vin_name'] ?? '')))
                                    ->filter(fn (string $name): bool => $name !== '')
                                    ->unique()
                                    ->take(3)
                                    ->values();

                                if ($vinHeaders->count() < 3) {
                                    $vinHeaders = $vinHeaders->concat(collect($defaultVinHeaders)->slice($vinHeaders->count(), 3 - $vinHeaders->count()))->values();
                                }
                            @endphp
                            <table class="planning-table">
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        @foreach($vinHeaders as $header)
                                            <th>{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vinRows as $row)
                                        @php
                                            $rowVins = collect((array) ($row['vins'] ?? []))->values();
                                            $hasAnySelection = $vinHeaders->contains(function (string $header, int $index) use ($rowVins): bool {
                                                $members = collect((array) data_get($rowVins->get($index), 'member_names', []))
                                                    ->map(fn ($name): string => trim((string) $name))
                                                    ->filter(fn (string $name): bool => $name !== '')
                                                    ->values()
                                                    ->all();
                                                return $members !== [];
                                            });
                                        @endphp
                                        @if(trim((string) ($row['role'] ?? '')) !== '' || $hasAnySelection)
                                            <tr>
                                                <td>{{ (string) ($row['role'] ?? '') }}</td>
                                                @foreach($vinHeaders as $headerIndex => $header)
                                                    @php
                                                        $members = collect((array) data_get($rowVins->get($headerIndex), 'member_names', []))
                                                            ->map(fn ($name): string => trim((string) $name))
                                                            ->filter(fn (string $name): bool => $name !== '')
                                                            ->values()
                                                            ->all();
                                                    @endphp
                                                    <td>{{ $members !== [] ? implode(', ', $members) : '' }}</td>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($isMonsterrol)
                        <div class="service-grid">
                            <div class="day-block">
                                <p class="day-title">Staf en vaarbemanning</p>
                                <table class="planning-table">
                                    <thead>
                                        <tr>
                                            <th>Voornaam</th>
                                            <th>Achternaam</th>
                                            <th>Functie</th>
                                            @if($isFramCamp)
                                                <th>Aan boord</th>
                                                <th>Van boord</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach((array) data_get($monsterrolRows ?? [], 'crew', []) as $row)
                                            @if(trim((string) ($row['first_name'] ?? '')) !== '' || trim((string) ($row['last_name'] ?? '')) !== '' || trim((string) ($row['functie'] ?? '')) !== '' || trim((string) ($row['on_board'] ?? '')) !== '' || trim((string) ($row['off_board'] ?? '')) !== '')
                                                <tr>
                                                    <td>{{ (string) ($row['first_name'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['last_name'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['functie'] ?? '') }}</td>
                                                    @if($isFramCamp)
                                                        <td>{{ (string) ($row['on_board'] ?? '') }}</td>
                                                        <td>{{ (string) ($row['off_board'] ?? '') }}</td>
                                                    @endif
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="day-block">
                                <p class="day-title">{{ ucfirst(str_replace('_', ' ', (string) $playbook->section)) }}</p>
                                <table class="planning-table">
                                    <thead>
                                        <tr>
                                            <th>Voornaam</th>
                                            <th>Achternaam</th>
                                            <th>Functie</th>
                                            @if($isFramCamp)
                                                <th>Aan boord</th>
                                                <th>Van boord</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach((array) data_get($monsterrolRows ?? [], 'speltak', []) as $row)
                                            @if(trim((string) ($row['first_name'] ?? '')) !== '' || trim((string) ($row['last_name'] ?? '')) !== '' || trim((string) ($row['functie'] ?? '')) !== '' || trim((string) ($row['on_board'] ?? '')) !== '' || trim((string) ($row['off_board'] ?? '')) !== '')
                                                <tr>
                                                    <td>{{ (string) ($row['first_name'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['last_name'] ?? '') }}</td>
                                                    <td>{{ (string) ($row['functie'] ?? '') }}</td>
                                                    @if($isFramCamp)
                                                        <td>{{ (string) ($row['on_board'] ?? '') }}</td>
                                                        <td>{{ (string) ($row['off_board'] ?? '') }}</td>
                                                    @endif
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif($isHulpdiensten)
                        <div class="service-grid">
                            @foreach(['huisartsen' => 'Huisartsen', 'ziekenhuizen' => 'Ziekenhuizen', 'tandartsen' => 'Tandartsen'] as $key => $label)
                                @php
                                    $entries = data_get($emergencyContacts ?? [], $key, []);
                                    $entryRows = is_array($entries) && array_is_list($entries) ? $entries : [$entries];
                                @endphp
                                @foreach($entryRows as $entryIndex => $entry)
                                    @php $entry = (array) $entry; @endphp
                                    <div class="service-card">
                                        <p class="service-title">{{ $label }}{{ count($entryRows) > 1 ? ' #'.($entryIndex + 1) : '' }}</p>
                                        <p class="service-line"><strong>Naam:</strong> {{ (string) ($entry['name'] ?? '—') ?: '—' }}</p>
                                        <p class="service-line"><strong>Adres:</strong> {{ (string) ($entry['address'] ?? '—') ?: '—' }}</p>
                                        <p class="service-line"><strong>Postcode:</strong> {{ (string) ($entry['postal_code'] ?? '—') ?: '—' }}</p>
                                        <p class="service-line"><strong>Plaats:</strong> {{ (string) ($entry['city'] ?? '—') ?: '—' }}</p>
                                        <p class="service-line"><strong>010 nummer:</strong> {{ (string) ($entry['phone_010'] ?? '—') ?: '—' }}</p>
                                        <p class="service-line"><strong>Site:</strong> {{ (string) ($entry['website'] ?? '—') ?: '—' }}</p>
                                        <p class="service-line"><strong>Extra informatie:</strong> {{ (string) ($entry['extra_info'] ?? '—') ?: '—' }}</p>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @elseif($isVaarschema)
                        <div class="service-grid">
                            <div class="vaarschema-info">
                                <p><strong>Website getij</strong></p>
                                <p>
                                    <a class="vaarschema-link" href="https://waterinfo.rws.nl/publiek/astronomische-getij/heinenoord.goidschalxoord/details">
                                        https://waterinfo.rws.nl/publiek/astronomische-getij/heinenoord.goidschalxoord/details
                                    </a>
                                </p>
                                <p style="margin-top:4px;">We kunnen met 60 NAP net wel naar binnen in de Koedood. Voor de veiligheid 75 NAP aanhouden.</p>
                            </div>
                            <table class="planning-table">
                                <thead>
                                    <tr>
                                        <th>Datum</th>
                                        <th>Van</th>
                                        <th>Naar</th>
                                        <th>Wegvaren</th>
                                        <th>Aankomen</th>
                                        <th>Speling (minuten)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach((array) ($vaarschemaRows ?? []) as $row)
                                        @if(trim((string) ($row['date'] ?? '')) !== '' || trim((string) ($row['from'] ?? '')) !== '' || trim((string) ($row['to'] ?? '')) !== '' || trim((string) ($row['depart_at'] ?? '')) !== '' || trim((string) ($row['arrive_at'] ?? '')) !== '' || trim((string) ($row['tide_margin_minutes'] ?? '')) !== '')
                                            <tr>
                                                <td>{{ (string) ($row['date'] ?? '') }}</td>
                                                <td>{{ (string) ($row['from'] ?? '') }}</td>
                                                <td>{{ (string) ($row['to'] ?? '') }}</td>
                                                <td>{{ (string) ($row['depart_at'] ?? '') }}</td>
                                                <td>{{ (string) ($row['arrive_at'] ?? '') }}</td>
                                                <td>{{ (string) ($row['tide_margin_minutes'] ?? '') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
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
