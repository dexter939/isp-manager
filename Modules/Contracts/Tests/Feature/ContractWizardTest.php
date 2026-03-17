<?php

declare(strict_types=1);

namespace Modules\Contracts\Tests\Feature;

use Livewire\Livewire;
use Modules\Contracts\Http\Livewire\ContractWizard;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\ServicePlan;
use Tests\TestCase;

class ContractWizardTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function wizard_starts_at_step_1(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->assertSet('step', 1)
            ->assertSee('Dati Cliente');
    }

    /** @test */
    public function it_advances_to_step_2_with_valid_step1_data(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('customerType', 'privato')
            ->set('nome', 'Mario')
            ->set('cognome', 'Rossi')
            ->set('email', 'mario@example.com')
            ->set('cellulare', '+39 333 1234567')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertNoErrors();
    }

    /** @test */
    public function it_shows_validation_error_when_step1_incomplete(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('customerType', 'privato')
            ->set('nome', '')
            ->set('email', 'invalid-email')
            ->call('nextStep')
            ->assertSet('step', 1)
            ->assertHasErrors(['nome', 'email']);
    }

    /** @test */
    public function it_requires_ragione_sociale_for_azienda(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('customerType', 'azienda')
            ->set('email', 'info@acme.it')
            ->set('cellulare', '+39 02 1234567')
            ->call('nextStep')
            ->assertHasErrors(['ragioneSociale']);
    }

    /** @test */
    public function it_can_navigate_backward(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('step', 3)
            ->call('previousStep')
            ->assertSet('step', 2);
    }

    /** @test */
    public function step_cannot_go_below_1(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('step', 1)
            ->call('previousStep')
            ->assertSet('step', 1);
    }

    /** @test */
    public function it_shows_plan_selection_at_step_3(): void
    {
        $this->actingAs($this->makeAgent());

        ServicePlan::factory()->count(3)->active()->public()->create([
            'tenant_id' => $this->makeAgent()->tenant_id,
        ]);

        Livewire::test(ContractWizard::class)
            ->set('step', 3)
            ->assertSee('Selezione Piano');
    }

    /** @test */
    public function it_requires_iban_when_payment_method_is_sdd(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('step', 4)
            ->set('paymentMethod', 'sdd')
            ->set('iban', '')
            ->call('nextStep')
            ->assertHasErrors(['iban']);
    }

    /** @test */
    public function it_does_not_require_iban_for_bonifico(): void
    {
        $this->actingAs($this->makeAgent());

        Livewire::test(ContractWizard::class)
            ->set('step', 4)
            ->set('paymentMethod', 'bonifico')
            ->set('billingCycle', 'monthly')
            ->set('billingDay', 1)
            ->call('nextStep')
            ->assertNoErrors(['iban'])
            ->assertSet('step', 5);
    }

    // ---- Helpers ----

    private function makeAgent(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'tenant_id' => 1,
        ])->assignRole('agent');
    }
}
