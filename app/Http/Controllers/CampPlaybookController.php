<?php

namespace App\Http\Controllers;

use App\Models\CampPlaybook;
use App\Models\User;
use App\Models\UserSectionRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampPlaybookController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CampPlaybooks/Index', [
            'items' => CampPlaybook::query()
                ->latest('camp_year')
                ->latest('id')
                ->get()
                ->map(function (CampPlaybook $item): array {
                    $sections = $this->normalizePlaybookSections(
                        (array) data_get($item->meta, 'sections', []),
                        (string) ($item->content ?? '')
                    );

                    return [
                        'id' => (int) $item->id,
                        'camp_year' => (int) $item->camp_year,
                        'title' => (string) $item->title,
                        'content' => $this->flattenSectionsToContent($sections),
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $copyId = (int) $request->query('copy', 0);
        $copyItem = null;
        if ($copyId > 0) {
            $source = CampPlaybook::query()->find($copyId);
            if ($source && (string) $source->section === (string) session('active_section', 'dolfijnen')) {
                $sections = $this->normalizePlaybookSections(
                    (array) data_get($source->meta, 'sections', []),
                    (string) ($source->content ?? '')
                );

                $copyItem = [
                    'camp_year' => (int) $source->camp_year,
                    'title' => (string) $source->title,
                    'camp_location' => $this->normalizeCampLocation((string) data_get($source->meta, 'camp_location', 'fram')),
                    'camp_place' => (string) data_get($source->meta, 'camp_place', ''),
                    'camp_dates' => (string) data_get($source->meta, 'camp_dates', ''),
                    'emergency_contacts' => $this->normalizeEmergencyContacts((array) data_get($source->meta, 'emergency_contacts', [])),
                    'day_plans' => $this->normalizeDayPlans((array) data_get($source->meta, 'day_plans', [])),
                    'vaarschema_rows' => $this->normalizeVaarschemaRows((array) data_get($source->meta, 'vaarschema_rows', [])),
                    'playbook_sections' => $sections,
                ];
            }
        }

        return Inertia::render('CampPlaybooks/Show', [
            'mode' => 'create',
            'item' => null,
            'copyItem' => $copyItem,
            'leaderTeam' => $this->leaderTeamOptions(),
            'defaultSections' => $this->defaultPlaybookSections((string) session('active_section', 'dolfijnen')),
            'defaultDayPlans' => $this->defaultDayPlans(),
            'defaultVaarschemaRows' => $this->defaultVaarschemaRows(),
        ]);
    }

    public function show(CampPlaybook $campPlaybook): Response
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        return Inertia::render('CampPlaybooks/Show', [
            'mode' => 'edit',
            'item' => [
                'id' => (int) $campPlaybook->id,
                'camp_year' => (int) $campPlaybook->camp_year,
                'title' => (string) $campPlaybook->title,
                'camp_location' => $this->normalizeCampLocation((string) data_get($campPlaybook->meta, 'camp_location', 'fram')),
                'camp_place' => (string) data_get($campPlaybook->meta, 'camp_place', ''),
                'camp_dates' => (string) data_get($campPlaybook->meta, 'camp_dates', ''),
                'emergency_contacts' => $this->normalizeEmergencyContacts((array) data_get($campPlaybook->meta, 'emergency_contacts', [])),
                'day_plans' => $this->normalizeDayPlans((array) data_get($campPlaybook->meta, 'day_plans', [])),
                'vaarschema_rows' => $this->normalizeVaarschemaRows((array) data_get($campPlaybook->meta, 'vaarschema_rows', [])),
                'playbook_sections' => $this->normalizePlaybookSections(
                    (array) data_get($campPlaybook->meta, 'sections', []),
                    (string) ($campPlaybook->content ?? '')
                ),
            ],
            'copyItem' => null,
            'leaderTeam' => $this->leaderTeamOptions(),
            'defaultSections' => $this->defaultPlaybookSections((string) session('active_section', 'dolfijnen')),
            'defaultDayPlans' => $this->defaultDayPlans(),
            'defaultVaarschemaRows' => $this->defaultVaarschemaRows(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'camp_location' => ['nullable', 'string', 'in:clubhuis,fram'],
            'camp_place' => ['nullable', 'string', 'max:255'],
            'camp_dates' => ['nullable', 'string', 'max:255'],
            'emergency_contacts' => ['nullable', 'array'],
            'day_plans' => ['nullable', 'array'],
            'vaarschema_rows' => ['nullable', 'array'],
            'playbook_sections' => ['nullable', 'array'],
        ]);

        $userId = $request->user()?->id;
        $sections = $this->normalizePlaybookSections((array) ($data['playbook_sections'] ?? []), (string) ($data['content'] ?? ''));
        $content = $this->flattenSectionsToContent($sections);
        $dayPlans = $this->normalizeDayPlans((array) ($data['day_plans'] ?? []));
        $vaarschemaRows = $this->normalizeVaarschemaRows((array) ($data['vaarschema_rows'] ?? []));
        CampPlaybook::create([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => $content,
            'meta' => [
                'sections' => $sections,
                'camp_location' => $this->normalizeCampLocation((string) ($data['camp_location'] ?? 'fram')),
                'camp_place' => trim((string) ($data['camp_place'] ?? '')),
                'camp_dates' => trim((string) ($data['camp_dates'] ?? '')),
                'emergency_contacts' => $this->normalizeEmergencyContacts((array) ($data['emergency_contacts'] ?? [])),
                'day_plans' => $dayPlans,
                'vaarschema_rows' => $vaarschemaRows,
            ],
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function update(Request $request, CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'camp_location' => ['nullable', 'string', 'in:clubhuis,fram'],
            'camp_place' => ['nullable', 'string', 'max:255'],
            'camp_dates' => ['nullable', 'string', 'max:255'],
            'emergency_contacts' => ['nullable', 'array'],
            'day_plans' => ['nullable', 'array'],
            'vaarschema_rows' => ['nullable', 'array'],
            'playbook_sections' => ['nullable', 'array'],
        ]);

        $sections = $this->normalizePlaybookSections((array) ($data['playbook_sections'] ?? []), (string) ($data['content'] ?? ''));
        $content = $this->flattenSectionsToContent($sections);
        $meta = (array) ($campPlaybook->meta ?? []);
        $meta['sections'] = $sections;
        $meta['camp_location'] = $this->normalizeCampLocation((string) ($data['camp_location'] ?? 'fram'));
        $meta['camp_place'] = trim((string) ($data['camp_place'] ?? ''));
        $meta['camp_dates'] = trim((string) ($data['camp_dates'] ?? ''));
        $meta['emergency_contacts'] = $this->normalizeEmergencyContacts((array) ($data['emergency_contacts'] ?? []));
        $meta['day_plans'] = $this->normalizeDayPlans((array) ($data['day_plans'] ?? []));
        $meta['vaarschema_rows'] = $this->normalizeVaarschemaRows((array) ($data['vaarschema_rows'] ?? []));

        $campPlaybook->update([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => $content,
            'meta' => $meta,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function destroy(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);
        $campPlaybook->delete();

        return to_route('camp-playbooks.index');
    }

    public function copy(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        $userId = request()->user()?->id;
        $sections = $this->normalizePlaybookSections(
            (array) data_get($campPlaybook->meta, 'sections', []),
            (string) ($campPlaybook->content ?? '')
        );
        CampPlaybook::create([
            'section' => (string) $campPlaybook->section,
            'camp_year' => (int) $campPlaybook->camp_year,
            'title' => (string) $campPlaybook->title.' (kopie)',
            'content' => $this->flattenSectionsToContent($sections),
            'meta' => [
                'sections' => $sections,
                'camp_location' => $this->normalizeCampLocation((string) data_get($campPlaybook->meta, 'camp_location', 'fram')),
                'camp_place' => (string) data_get($campPlaybook->meta, 'camp_place', ''),
                'camp_dates' => (string) data_get($campPlaybook->meta, 'camp_dates', ''),
                'emergency_contacts' => $this->normalizeEmergencyContacts((array) data_get($campPlaybook->meta, 'emergency_contacts', [])),
                'day_plans' => $this->normalizeDayPlans((array) data_get($campPlaybook->meta, 'day_plans', [])),
                'vaarschema_rows' => $this->normalizeVaarschemaRows((array) data_get($campPlaybook->meta, 'vaarschema_rows', [])),
            ],
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function downloadPdf(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);
        $sections = $this->normalizePlaybookSections(
            (array) data_get($campPlaybook->meta, 'sections', []),
            (string) ($campPlaybook->content ?? '')
        );

        $pdf = Pdf::loadView('pdf.camp-playbook', [
            'playbook' => $campPlaybook,
            'sections' => $sections,
            'emergencyContacts' => $this->normalizeEmergencyContacts((array) data_get($campPlaybook->meta, 'emergency_contacts', [])),
            'dayPlans' => $this->normalizeDayPlans((array) data_get($campPlaybook->meta, 'day_plans', [])),
            'vaarschemaRows' => $this->normalizeVaarschemaRows((array) data_get($campPlaybook->meta, 'vaarschema_rows', [])),
            'leaderTeamMap' => $this->leaderTeamMapById(),
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4');

        $filename = sprintf('draaiboek-%d-%s.pdf', (int) $campPlaybook->id, now()->format('Ymd-His'));

        return $pdf->download($filename);
    }

    private function logoDataUri(): string
    {
        $path = public_path('images/logo.png');
        if (! is_file($path)) {
            return '';
        }

        $binary = file_get_contents($path);
        if ($binary === false) {
            return '';
        }

        $mime = function_exists('mime_content_type')
            ? (mime_content_type($path) ?: 'image/png')
            : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * @param  array<int,mixed>  $rawSections
     * @return array<int,array{title:string,content:string}>
     */
    private function normalizePlaybookSections(array $rawSections, string $fallbackContent = ''): array
    {
        $normalized = collect($rawSections)
            ->map(function ($section): array {
                return [
                    'title' => trim((string) data_get($section, 'title', '')),
                    'content' => trim((string) data_get($section, 'content', '')),
                ];
            })
            ->filter(fn (array $section): bool => $section['title'] !== '' || $section['content'] !== '')
            ->values()
            ->all();

        if ($normalized !== []) {
            return $normalized;
        }

        $defaults = $this->defaultPlaybookSections((string) session('active_section', 'dolfijnen'));
        $content = trim($fallbackContent);
        if ($content !== '') {
            $defaults[0]['content'] = $content;
        }

        return $defaults;
    }

    /**
     * @return array<int,array{title:string,content:string}>
     */
    private function defaultPlaybookSections(string $activeSection): array
    {
        $sections = [
            ['title' => 'Algemeen', 'content' => ''],
            ['title' => 'Monsterrol', 'content' => ''],
            ['title' => 'Taakverdeling', 'content' => ''],
            ['title' => 'Taak uitleg', 'content' => ''],
            ['title' => 'Algemene afspraken', 'content' => ''],
            ['title' => 'Speltak afspraken', 'content' => ''],
            ['title' => 'Corveerooster', 'content' => ''],
        ];

        if ($activeSection === 'dolfijnen') {
            $sections[] = ['title' => 'Vinindeling', 'content' => ''];
        } elseif ($activeSection === 'zeeverkenners') {
            $sections[] = ['title' => 'Bakindeling', 'content' => ''];
        }

        $sections = [
            ...$sections,
            ['title' => 'Vaarschema', 'content' => ''],
            ['title' => 'Planning per dag', 'content' => ''],
            ['title' => 'Hulpdiensten', 'content' => ''],
        ];

        return $sections;
    }

    /**
     * @param  array<int,array{title:string,content:string}>  $sections
     */
    private function flattenSectionsToContent(array $sections): string
    {
        return collect($sections)
            ->map(function (array $section): string {
                $title = trim((string) ($section['title'] ?? ''));
                $content = trim((string) ($section['content'] ?? ''));
                if ($title === '' && $content === '') {
                    return '';
                }

                if ($title === '') {
                    return $content;
                }

                if ($content === '') {
                    return $title;
                }

                return $title.":\n".$content;
            })
            ->filter(fn (string $chunk): bool => trim($chunk) !== '')
            ->implode("\n\n");
    }

    private function normalizeCampLocation(string $campLocation): string
    {
        return in_array($campLocation, ['clubhuis', 'fram'], true) ? $campLocation : 'fram';
    }

    /**
     * @return array{
     *   huisartsen:array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string},
     *   ziekenhuizen:array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string},
     *   tandartsen:array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}
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
            'huisartsen' => $empty,
            'ziekenhuizen' => $empty,
            'tandartsen' => $empty,
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array{
     *   huisartsen:array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string},
     *   ziekenhuizen:array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string},
     *   tandartsen:array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}
     * }
     */
    private function normalizeEmergencyContacts(array $raw): array
    {
        $defaults = $this->defaultEmergencyContacts();
        foreach (array_keys($defaults) as $category) {
            $entry = is_array($raw[$category] ?? null) ? $raw[$category] : [];
            $defaults[$category] = [
                'name' => trim((string) ($entry['name'] ?? '')),
                'address' => trim((string) ($entry['address'] ?? '')),
                'postal_code' => trim((string) ($entry['postal_code'] ?? '')),
                'city' => trim((string) ($entry['city'] ?? '')),
                'phone_010' => trim((string) ($entry['phone_010'] ?? '')),
                'website' => trim((string) ($entry['website'] ?? '')),
                'extra_info' => trim((string) ($entry['extra_info'] ?? '')),
            ];
        }

        return $defaults;
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{day_label:string,daywatch_ids:array<int,int>,planning_rows:array<int,array{time:string,program:string,game:string,needs:string}>,game_explanation:string}>
     */
    private function normalizeDayPlans(array $raw): array
    {
        $normalized = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $rows = collect((array) ($entry['planning_rows'] ?? []))
                    ->filter(fn ($row): bool => is_array($row))
                    ->map(function (array $row): array {
                        return [
                            'time' => trim((string) ($row['time'] ?? '')),
                            'program' => trim((string) ($row['program'] ?? '')),
                            'game' => trim((string) ($row['game'] ?? '')),
                            'needs' => trim((string) ($row['needs'] ?? '')),
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['time'] !== '' || $row['program'] !== '' || $row['game'] !== '' || $row['needs'] !== '')
                    ->values()
                    ->all();

                $daywatchIds = collect((array) ($entry['daywatch_ids'] ?? []))
                    ->map(fn ($id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();

                return [
                    'day_label' => trim((string) ($entry['day_label'] ?? '')),
                    'daywatch_ids' => $daywatchIds,
                    'planning_rows' => $rows !== [] ? $rows : [['time' => '', 'program' => '', 'game' => '', 'needs' => '']],
                    'game_explanation' => trim((string) ($entry['game_explanation'] ?? '')),
                ];
            })
            ->filter(fn (array $day): bool => $day['day_label'] !== '' || $day['game_explanation'] !== '' || $day['planning_rows'] !== [['time' => '', 'program' => '', 'game' => '', 'needs' => '']] || $day['daywatch_ids'] !== [])
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : $this->defaultDayPlans();
    }

    /**
     * @return array<int,array{day_label:string,daywatch_ids:array<int,int>,planning_rows:array<int,array{time:string,program:string,game:string,needs:string}>,game_explanation:string}>
     */
    private function defaultDayPlans(): array
    {
        return [[
            'day_label' => 'Dag 1',
            'daywatch_ids' => [],
            'planning_rows' => [['time' => '', 'program' => '', 'game' => '', 'needs' => '']],
            'game_explanation' => '',
        ]];
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    private function leaderTeamOptions(): array
    {
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);

        return User::query()
            ->whereNotNull('first_name')
            ->whereHas('sectionRoles', function ($query) use ($activeSection): void {
                $query->where('section', $activeSection)
                    ->whereIn('role', [UserSectionRole::ROLE_TEAMLEIDER, UserSectionRole::ROLE_LEIDING]);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $leader): array => [
                'id' => (int) $leader->id,
                'name' => trim(((string) $leader->first_name).' '.((string) ($leader->last_name ?? ''))),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int,string>
     */
    private function leaderTeamMapById(): array
    {
        return collect($this->leaderTeamOptions())
            ->mapWithKeys(fn (array $leader): array => [(int) ($leader['id'] ?? 0) => (string) ($leader['name'] ?? 'Onbekend')])
            ->all();
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{date:string,from:string,to:string,depart_at:string,arrive_at:string,tide_margin_minutes:string}>
     */
    private function normalizeVaarschemaRows(array $raw): array
    {
        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                return [
                    'date' => trim((string) ($entry['date'] ?? '')),
                    'from' => trim((string) ($entry['from'] ?? '')),
                    'to' => trim((string) ($entry['to'] ?? '')),
                    'depart_at' => trim((string) ($entry['depart_at'] ?? '')),
                    'arrive_at' => trim((string) ($entry['arrive_at'] ?? '')),
                    'tide_margin_minutes' => trim((string) ($entry['tide_margin_minutes'] ?? '')),
                ];
            })
            ->filter(fn (array $row): bool => $row['date'] !== '' || $row['from'] !== '' || $row['to'] !== '' || $row['depart_at'] !== '' || $row['arrive_at'] !== '' || $row['tide_margin_minutes'] !== '')
            ->values()
            ->all();

        return $rows !== [] ? $rows : $this->defaultVaarschemaRows();
    }

    /**
     * @return array<int,array{date:string,from:string,to:string,depart_at:string,arrive_at:string,tide_margin_minutes:string}>
     */
    private function defaultVaarschemaRows(): array
    {
        return [[
            'date' => '',
            'from' => '',
            'to' => '',
            'depart_at' => '',
            'arrive_at' => '',
            'tide_margin_minutes' => '',
        ]];
    }
}
