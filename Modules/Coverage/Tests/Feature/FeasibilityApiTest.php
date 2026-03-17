<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;

describe('GET /api/v1/coverage/feasibility', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->givePermissionTo('coverage.view');

        // Inserisce dati di test in coverage_fibercop
        DB::table('coverage_fibercop')->insert([
            'codice_ui'           => 'IT00BA0001',
            'comune'              => 'Bari',
            'provincia'           => 'BA',
            'cap'                 => '70100',
            'via'                 => 'Via Roma',
            'civico'              => '10',
            'via_normalizzata'    => 'Via Roma',
            'civico_normalizzato' => '10',
            'tecnologia'          => 'FTTH',
            'velocita_max_dl'     => 1000,
            'velocita_max_ul'     => 300,
            'stato_commerciale'   => 'vendibile',
            'imported_at'         => now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        DB::table('address_registry')->insert([
            'comune'              => 'Bari',
            'provincia'           => 'BA',
            'via_normalizzata'    => 'Via Roma',
            'civico_normalizzato' => '10',
            'coverage_fibercop_id' => DB::table('coverage_fibercop')
                ->where('codice_ui', 'IT00BA0001')->value('id'),
            'has_ftth_fibercop'  => true,
            'last_rebuilt_at'    => now(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    });

    it('ritorna 401 senza autenticazione', function () {
        $this->getJson('/api/v1/coverage/feasibility?via=Via+Roma&civico=10&comune=Bari&provincia=BA')
            ->assertStatus(401);
    });

    it('ritorna copertura FTTH per indirizzo presente', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/coverage/feasibility?via=Via+Roma&civico=10&comune=Bari&provincia=BA')
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) =>
                $json->has('data')
                    ->where('data.hasCoverage', true)
                    ->has('data.technologies', 1)
                    ->where('data.technologies.0.carrier', 'fibercop')
                    ->where('data.technologies.0.technology', 'FTTH')
                    ->where('data.technologies.0.maxSpeedDl', 1000)
                    ->where('data.technologies.0.commercialStatus', 'vendibile')
                    ->etc()
            );
    });

    it('ritorna hasCoverage false per indirizzo non presente', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/coverage/feasibility?via=Via+Inesistente&civico=999&comune=Palermo&provincia=PA')
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) =>
                $json->where('data.hasCoverage', false)
                    ->has('data.technologies', 0)
                    ->etc()
            );
    });

    it('ritorna 422 se manca parametro obbligatorio', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/coverage/feasibility?via=Via+Roma&civico=10&comune=Bari')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['provincia']);
    });

    it('ritorna 422 se provincia non è 2 caratteri', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/coverage/feasibility?via=Via+Roma&civico=10&comune=Bari&provincia=Bari')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['provincia']);
    });

    it('normalizza automaticamente la via nel risultato', function () {
        // Cerca con abbreviazione → deve trovare lo stesso record
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/coverage/feasibility?via=V.+Roma&civico=10&comune=BARI&provincia=BA')
            ->assertStatus(200)
            ->assertJsonPath('data.hasCoverage', true);
    });
});

describe('POST /api/v1/coverage/normalize', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->givePermissionTo('coverage.view');
    });

    it('normalizza un indirizzo completo', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/coverage/normalize', [
                'via'       => 'V.LE DELLA LIBERTÀ',
                'civico'    => '3/A',
                'comune'    => 'NAPOLI',
                'provincia' => 'na',
            ])
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) =>
                $json->where('data.via', 'Viale Della Liberta')
                    ->where('data.civico', '3A')
                    ->where('data.comune', 'Napoli')
                    ->where('data.provincia', 'NA')
            );
    });
});
