<?php

namespace Tests\Feature;

use App\Models\FinanceDeclaration;
use App\Models\FinancePot;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FinanceFlowTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $section, string $role): User
    {
        $user = User::factory()->create();
        UserSectionRole::query()->create([
            'user_id' => $user->id,
            'section' => $section,
            'role' => $role,
        ]);

        return $user;
    }

    public function test_user_can_submit_finance_declaration(): void
    {
        $user = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);
        $pot = FinancePot::query()->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Kamp pot',
            'starting_amount' => 2000,
            'current_amount' => 2000,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('finance.declarations.store'), [
                'pot_id' => $pot->id,
                'amount' => '25.50',
                'iban' => 'NL91ABNA0417164300',
                'account_name' => 'Test User',
                'description_total' => 'Boodschappen opkomst',
                'description_lines' => 'Brood 5.50, Fruit 20.00',
                'declared_at' => '2026-04-09',
                'receipt_file' => UploadedFile::fake()->image('bonnetje-20260409-25,50.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('finance_declarations', [
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'created_by_user_id' => $user->id,
            'pot_id' => $pot->id,
            'status' => FinanceDeclaration::STATUS_SUBMITTED,
        ]);
    }

    public function test_penningmeester_can_approve_and_balance_is_decreased(): void
    {
        $bestuur = $this->userWithRole(UserSectionRole::SECTION_BESTUUR, UserSectionRole::ROLE_PENNINGMEESTER);
        $pot = FinancePot::query()->create([
            'section' => UserSectionRole::SECTION_BESTUUR,
            'name' => 'Algemeen',
            'starting_amount' => 1000,
            'current_amount' => 1000,
            'active' => true,
        ]);
        $declaration = FinanceDeclaration::query()->create([
            'section' => UserSectionRole::SECTION_BESTUUR,
            'created_by_user_id' => $bestuur->id,
            'pot_id' => $pot->id,
            'status' => FinanceDeclaration::STATUS_SUBMITTED,
            'amount' => 125.25,
            'iban' => 'NL91ABNA0417164300',
            'account_name' => 'Bestuur User',
            'description_total' => 'Materiaal',
            'description_lines' => 'Diverse materialen',
            'declared_at' => '2026-04-09',
        ]);

        $this->actingAs($bestuur)
            ->withSession(['active_section' => UserSectionRole::SECTION_BESTUUR])
            ->patch(route('finance.declarations.approve', $declaration))
            ->assertRedirect();

        $this->assertDatabaseHas('finance_declarations', [
            'id' => $declaration->id,
            'status' => FinanceDeclaration::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('finance_ledger_entries', [
            'declaration_id' => $declaration->id,
            'pot_id' => $pot->id,
            'type' => 'debit',
        ]);
        $this->assertSame('874.75', (string) $pot->fresh()->current_amount);
    }

    public function test_bestuurslid_cannot_review_declaration_without_penningmeester_role(): void
    {
        $bestuur = $this->userWithRole(UserSectionRole::SECTION_BESTUUR, UserSectionRole::ROLE_BESTUURSLID);
        $pot = FinancePot::query()->create([
            'section' => UserSectionRole::SECTION_BESTUUR,
            'name' => 'Algemeen',
            'starting_amount' => 1000,
            'current_amount' => 1000,
            'active' => true,
        ]);
        $declaration = FinanceDeclaration::query()->create([
            'section' => UserSectionRole::SECTION_BESTUUR,
            'created_by_user_id' => $bestuur->id,
            'pot_id' => $pot->id,
            'status' => FinanceDeclaration::STATUS_SUBMITTED,
            'amount' => 20,
            'iban' => 'NL91ABNA0417164300',
            'account_name' => 'Bestuur User',
            'description_total' => 'Materiaal',
            'description_lines' => 'Diverse materialen',
            'declared_at' => '2026-04-09',
        ]);

        $this->actingAs($bestuur)
            ->withSession(['active_section' => UserSectionRole::SECTION_BESTUUR])
            ->patch(route('finance.declarations.approve', $declaration))
            ->assertForbidden();
    }

    public function test_admin_can_create_finance_pot_outside_bestuur_section(): void
    {
        $admin = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('finance.pots.store'), [
                'name' => 'Testpot',
                'starting_amount' => '100.00',
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('finance_pots', [
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Testpot',
        ]);
    }

    public function test_non_admin_and_non_bestuur_cannot_create_finance_pot(): void
    {
        $teamleider = $this->userWithRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_TEAMLEIDER);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('finance.pots.store'), [
                'name' => 'Verboden pot',
                'starting_amount' => '50.00',
                'active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('finance_pots', [
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Verboden pot',
        ]);
    }
}
