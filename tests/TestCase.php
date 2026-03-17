<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a user row and return the Eloquent model.
     * tenants.id and users.id are integer auto-increment.
     */
    protected function makeUser(int $tenantId = 1, string $role = 'user', array $extra = []): User
    {
        // Ensure tenant row exists (integer PK)
        if (! DB::table('tenants')->where('id', $tenantId)->exists()) {
            DB::statement('INSERT INTO tenants (id, name, slug, is_active, settings, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [
                $tenantId,
                'Test Tenant ' . $tenantId,
                'test-tenant-' . $tenantId,
                1,
                '{}',
                now(),
                now(),
            ]);
        }

        $userId = DB::table('users')->insertGetId(array_merge([
            'tenant_id'         => $tenantId,
            'name'              => 'Test User',
            'email'             => 'user-' . Str::random(8) . '@test.local',
            'password'          => bcrypt('password'),
            'role'              => $role,
            'is_active'         => 1,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $extra));

        return User::findOrFail($userId);
    }

    /**
     * Authenticate as a tenant admin and return $this for chaining.
     */
    protected function actingAsAdmin(int $tenantId = 1): static
    {
        return $this->actingAs($this->makeUser(tenantId: $tenantId, role: 'admin'));
    }

    /**
     * Insert a minimal tenant row and return its auto-increment integer ID.
     */
    protected function createTenant(array $attrs = []): int
    {
        return DB::table('tenants')->insertGetId(array_merge([
            'name'       => 'Tenant ' . Str::random(4),
            'slug'       => 'tenant-' . Str::random(8),
            'is_active'  => 1,
            'settings'   => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    /**
     * Insert a minimal customer row and return the raw DB object.
     */
    protected function createCustomer(int $tenantId = 1, array $attrs = []): object
    {
        $id = Str::uuid()->toString();
        DB::table('customers')->insert(array_merge([
            'id'             => $id,
            'tenant_id'      => $tenantId,
            'first_name'     => 'Mario',
            'last_name'      => 'Rossi',
            'email'          => 'customer-' . Str::random(6) . '@test.local',
            'fiscal_code'    => strtoupper(Str::random(16)),
            'balance_amount' => 0,
            'balance_currency'=> 'EUR',
            'created_at'     => now(),
            'updated_at'     => now(),
        ], $attrs));

        return DB::table('customers')->find($id);
    }

    /**
     * Insert a minimal network_site row and return the raw DB object.
     */
    protected function createNetworkSite(int $tenantId = 1, array $attrs = []): object
    {
        $id = Str::uuid()->toString();
        DB::table('network_sites')->insert(array_merge([
            'id'         => $id,
            'tenant_id'  => $tenantId,
            'name'       => 'BTS ' . Str::random(4),
            'type'       => 'mast',
            'latitude'   => 45.4654,
            'longitude'  => 9.1866,
            'status'     => 'active',
            'importance' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));

        return DB::table('network_sites')->find($id);
    }

    /**
     * Insert a minimal field_intervention row and return its ID.
     */
    protected function createFieldIntervention(int $tenantId = 1, array $attrs = []): string
    {
        $id = Str::uuid()->toString();
        DB::table('field_interventions')->insert(array_merge([
            'id'                => $id,
            'tenant_id'         => $tenantId,
            'customer_id'       => Str::uuid()->toString(),
            'intervention_type' => 'installation',
            'status'            => 'scheduled',
            'priority'          => 'normal',
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $attrs));

        return $id;
    }

    /**
     * Insert a minimal supplier row and return its ID.
     */
    protected function createSupplier(int $tenantId = 1, array $attrs = []): string
    {
        $id = Str::uuid()->toString();
        DB::table('suppliers')->insert(array_merge([
            'id'         => $id,
            'tenant_id'  => $tenantId,
            'name'       => 'Supplier ' . Str::random(4),
            'email'      => 'supplier-' . Str::random(6) . '@test.local',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));

        return $id;
    }
}
