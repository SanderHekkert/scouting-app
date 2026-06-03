<?php

namespace Database\Seeders;

use App\Models\TaskCategory;
use App\Models\UserSectionRole;
use Illuminate\Database\Seeder;

class TaskCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $names = TaskCategory::DEFAULT_NAMES;
        foreach (UserSectionRole::ALL_SECTIONS as $section) {
            if ($section === UserSectionRole::SECTION_BEVERS) {
                TaskCategory::withoutGlobalScopes()
                    ->where('section', $section)
                    ->delete();

                continue;
            }
            foreach ($names as $index => $name) {
                TaskCategory::withoutGlobalScopes()->updateOrCreate(
                    ['section' => $section, 'name' => $name],
                    ['position' => $index + 1],
                );
            }
        }
    }
}
