<?php

namespace App\Http\Controllers\Concerns;

use App\Models\UserSectionRole;

trait CampPlaybookStructuredData
{
    /**
     * @return array<int,array{role:string,vins:array<int,array{vin_name:string,member_names:array<int,string>}>}>
     */
    private function defaultVinindelingRows(): array
    {
        $defaultHeaders = ['', '', ''];
        $defaultRoles = ['Topper', 'Tipper', 'Vinlid', 'Vinlid', 'Vinlid'];

        return collect($defaultRoles)
            ->map(fn (string $role): array => [
                'role' => $role,
                'vins' => collect($defaultHeaders)
                    ->map(fn (string $header): array => [
                        'vin_name' => $header,
                        'member_names' => [],
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{role:string,vins:array<int,array{vin_name:string,member_names:array<int,string>}>}>
     */
    private function normalizeVinindelingRows(array $raw): array
    {
        $defaultHeaders = ['', '', ''];
        $defaultRoles = ['Topper', 'Tipper', 'Vinlid', 'Vinlid', 'Vinlid'];

        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $vins = collect((array) ($entry['vins'] ?? []))
                    ->filter(fn ($vin): bool => is_array($vin))
                    ->map(function (array $vin): array {
                        $memberNames = collect((array) ($vin['member_names'] ?? $vin['members'] ?? []))
                            ->map(fn ($name): string => trim((string) $name))
                            ->filter(fn (string $name): bool => $name !== '')
                            ->unique(fn (string $name): string => mb_strtolower($name))
                            ->values()
                            ->all();

                        return [
                            'vin_name' => trim((string) ($vin['vin_name'] ?? $vin['name'] ?? '')),
                            'member_names' => $memberNames,
                        ];
                    })
                    ->filter(fn (array $vin): bool => $vin['vin_name'] !== '' || $vin['member_names'] !== [])
                    ->values()
                    ->all();

                if ($vins === []) {
                    $legacyFinNames = collect((array) ($entry['fin_names'] ?? []))
                        ->map(fn ($name): string => trim((string) $name))
                        ->filter(fn (string $name): bool => $name !== '')
                        ->map(fn (string $name): array => ['vin_name' => $name, 'member_names' => []])
                        ->values()
                        ->all();

                    $vins = $legacyFinNames;
                }

                return [
                    'role' => trim((string) ($entry['role'] ?? '')),
                    'vins' => $vins,
                ];
            })
            ->values()
            ->all();

        if ($rows === []) {
            return $this->defaultVinindelingRows();
        }

        $headerCandidates = collect($rows)
            ->flatMap(fn (array $row): array => (array) ($row['vins'] ?? []))
            ->filter(fn ($vin): bool => is_array($vin))
            ->map(fn (array $vin): string => trim((string) ($vin['vin_name'] ?? '')))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();

        $headers = [];
        foreach ($headerCandidates as $name) {
            if (! in_array($name, $headers, true)) {
                $headers[] = $name;
            }
            if (count($headers) >= count($defaultHeaders)) {
                break;
            }
        }
        while (count($headers) < count($defaultHeaders)) {
            $headers[] = $defaultHeaders[count($headers)];
        }

        $normalizedRows = collect($rows)
            ->map(function (array $row, int $index) use ($headers, $defaultRoles): array {
                $rowVins = collect((array) ($row['vins'] ?? []))
                    ->filter(fn ($vin): bool => is_array($vin))
                    ->values();

                $vins = collect($headers)->map(function (string $header, int $headerIndex) use ($rowVins): array {
                    $sourceVin = (array) ($rowVins->get($headerIndex, []));
                    $memberNames = collect((array) ($sourceVin['member_names'] ?? []))
                        ->map(fn ($name): string => trim((string) $name))
                        ->filter(fn (string $name): bool => $name !== '')
                        ->unique(fn (string $name): string => mb_strtolower($name))
                        ->values()
                        ->all();

                    return [
                        'vin_name' => $header,
                        'member_names' => $memberNames,
                    ];
                })->all();

                $fallbackRole = $defaultRoles[$index] ?? 'Vinlid';
                $role = trim((string) ($row['role'] ?? ''));

                return [
                    'role' => $role !== '' ? $role : $fallbackRole,
                    'vins' => $vins,
                ];
            })
            ->values()
            ->all();

        return $normalizedRows !== [] ? $normalizedRows : $this->defaultVinindelingRows();
    }

    /**
     * @return array<int,array{
     *   day:string,
     *   date:string,
     *   daywatch:string,
     *   dienstvin:string,
     *   dekhuis:string,
     *   achteronder_en_dekken:string,
     *   wc_en_klusjes:string
     * }>
     */
    private function defaultCorveeRows(): array
    {
        return [[
            'day' => '',
            'date' => '',
            'daywatch' => '',
            'dienstvin' => '',
            'dekhuis' => '',
            'achteronder_en_dekken' => '',
            'wc_en_klusjes' => '',
        ]];
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{
     *   day:string,
     *   date:string,
     *   daywatch:string,
     *   dienstvin:string,
     *   dekhuis:string,
     *   achteronder_en_dekken:string,
     *   wc_en_klusjes:string
     * }>
     */
    private function normalizeCorveeRows(array $raw): array
    {
        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                return [
                    'day' => trim((string) ($entry['day'] ?? '')),
                    'date' => trim((string) ($entry['date'] ?? '')),
                    'daywatch' => trim((string) ($entry['daywatch'] ?? '')),
                    'dienstvin' => trim((string) ($entry['dienstvin'] ?? '')),
                    'dekhuis' => trim((string) ($entry['dekhuis'] ?? '')),
                    'achteronder_en_dekken' => trim((string) ($entry['achteronder_en_dekken'] ?? '')),
                    'wc_en_klusjes' => trim((string) ($entry['wc_en_klusjes'] ?? '')),
                ];
            })
            ->filter(function (array $row): bool {
                return $row['day'] !== ''
                    || $row['date'] !== ''
                    || $row['daywatch'] !== ''
                    || $row['dienstvin'] !== ''
                    || $row['dekhuis'] !== ''
                    || $row['achteronder_en_dekken'] !== ''
                    || $row['wc_en_klusjes'] !== '';
            })
            ->values()
            ->all();

        return $rows !== [] ? $rows : $this->defaultCorveeRows();
    }

    private function sectionLabel(string $section): string
    {
        return match ($section) {
            UserSectionRole::SECTION_BEVERS => 'Bevers',
            UserSectionRole::SECTION_DOLFIJNEN => 'Dolfijnen',
            UserSectionRole::SECTION_ZEEVERKENNERS => 'Zeeverkenners',
            UserSectionRole::SECTION_WILDE_VAART => 'Wilde Vaart',
            UserSectionRole::SECTION_LOODSEN => 'Loodsen',
            UserSectionRole::SECTION_BESTUUR => 'Bestuur',
            default => ucfirst(str_replace('_', ' ', $section)),
        };
    }

    /**
     * @return array{
     *   crew:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>,
     *   speltak:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>
     * }
     */
    private function defaultMonsterrolRows(): array
    {
        $emptyRow = [
            'first_name' => '',
            'last_name' => '',
            'functie' => '',
            'on_board' => '',
            'off_board' => '',
        ];

        return [
            'crew' => [$emptyRow],
            'speltak' => [$emptyRow],
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array{
     *   crew:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>,
     *   speltak:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>
     * }
     */
    private function normalizeMonsterrolRows(array $raw): array
    {
        $defaults = $this->defaultMonsterrolRows();

        $normalizeRows = function (array $rows): array {
            return collect($rows)
                ->filter(fn ($row): bool => is_array($row))
                ->map(function (array $row): array {
                    return [
                        'first_name' => trim((string) ($row['first_name'] ?? '')),
                        'last_name' => trim((string) ($row['last_name'] ?? '')),
                        'functie' => trim((string) ($row['functie'] ?? '')),
                        'on_board' => trim((string) ($row['on_board'] ?? '')),
                        'off_board' => trim((string) ($row['off_board'] ?? '')),
                    ];
                })
                ->filter(fn (array $row): bool => $row['first_name'] !== '' || $row['last_name'] !== '' || $row['functie'] !== '' || $row['on_board'] !== '' || $row['off_board'] !== '')
                ->values()
                ->all();
        };

        // Backward-compatible mapping:
        // old keys were "staff" and "vaarbemanning". These are merged into the new crew table.
        $crewRows = $normalizeRows([
            ...(array) ($raw['crew'] ?? []),
            ...(array) ($raw['staff'] ?? []),
            ...(array) ($raw['vaarbemanning'] ?? []),
        ]);
        $speltakRows = $normalizeRows((array) ($raw['speltak'] ?? []));

        $defaults['crew'] = $crewRows !== [] ? $crewRows : [[
            'first_name' => '',
            'last_name' => '',
            'functie' => '',
            'on_board' => '',
            'off_board' => '',
        ]];
        $defaults['speltak'] = $speltakRows !== [] ? $speltakRows : [[
            'first_name' => '',
            'last_name' => '',
            'functie' => '',
            'on_board' => '',
            'off_board' => '',
        ]];

        return $defaults;
    }

    /**
     * @return array{
     *   huisartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   ziekenhuizen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   tandartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>
     * }
     */
    private function defaultEmergencyContacts(): array
    {
        $empty = [
            'name' => '',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'phone_010' => '',
            'website' => '',
            'extra_info' => '',
        ];

        return [
            'huisartsen' => [$empty],
            'ziekenhuizen' => [$empty],
            'tandartsen' => [$empty],
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array{
     *   huisartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   ziekenhuizen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   tandartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>
     * }
     */
    private function normalizeEmergencyContacts(array $raw): array
    {
        $defaults = $this->defaultEmergencyContacts();
        $normalizeEntry = function (array $entry): array {
            return [
                'name' => trim((string) ($entry['name'] ?? '')),
                'address' => trim((string) ($entry['address'] ?? '')),
                'postal_code' => trim((string) ($entry['postal_code'] ?? '')),
                'city' => trim((string) ($entry['city'] ?? '')),
                'phone_010' => trim((string) ($entry['phone_010'] ?? '')),
                'website' => trim((string) ($entry['website'] ?? '')),
                'extra_info' => trim((string) ($entry['extra_info'] ?? '')),
            ];
        };

        foreach (array_keys($defaults) as $category) {
            $rawCategory = $raw[$category] ?? [];
            $rows = [];

            if (is_array($rawCategory) && array_is_list($rawCategory)) {
                $rows = collect($rawCategory)
                    ->filter(fn ($entry): bool => is_array($entry))
                    ->map(fn (array $entry): array => $normalizeEntry($entry))
                    ->filter(fn (array $entry): bool => collect($entry)->contains(fn (string $value): bool => $value !== ''))
                    ->values()
                    ->all();
            } elseif (is_array($rawCategory)) {
                // Backward compatibility with old single object shape.
                $single = $normalizeEntry($rawCategory);
                $rows = collect([$single])
                    ->filter(fn (array $entry): bool => collect($entry)->contains(fn (string $value): bool => $value !== ''))
                    ->values()
                    ->all();
            }

            $defaults[$category] = $rows !== [] ? $rows : $this->defaultEmergencyContacts()[$category];
        }

        return $defaults;
    }
}
