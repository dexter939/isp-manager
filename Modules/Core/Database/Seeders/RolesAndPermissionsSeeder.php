<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Crea ruoli e permessi base per IspManager.
     * Ruoli: super_admin, admin, agent, technician, billing, customer, carrier_webhook
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'coverage.view', 'coverage.import',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete', 'contracts.sign',
            'orders.view', 'orders.create', 'orders.send', 'orders.cancel', 'orders.reschedule',
            'vlan.view', 'vlan.manage',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'invoices.send', 'invoices.send_sdi', 'invoices.export',
            'payments.view', 'payments.process',
            'sepa.view', 'sepa.export',
            'radius.view', 'radius.coa', 'radius.logs', 'radius.retention.export',
            'walled_garden.view', 'walled_garden.manage',
            'monitoring.view', 'monitoring.cpe_reboot', 'monitoring.snmp', 'monitoring.linetest',
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
            'ai.ticket_writer', 'ai.marketing',
            'maintenance.view', 'maintenance.edit',
            'inventory.view', 'inventory.edit',
            'dunning.view', 'dunning.manage',
            'admin.users', 'admin.roles', 'admin.config', 'admin.quota',
            'admin.audit', 'admin.reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->syncPermissions([
            'coverage.view', 'coverage.import',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete', 'contracts.sign',
            'orders.view', 'orders.create', 'orders.send', 'orders.cancel', 'orders.reschedule',
            'vlan.view', 'vlan.manage',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'invoices.send', 'invoices.send_sdi', 'invoices.export',
            'payments.view', 'payments.process', 'sepa.view', 'sepa.export',
            'radius.view', 'radius.coa', 'radius.logs',
            'walled_garden.view', 'walled_garden.manage',
            'monitoring.view', 'monitoring.cpe_reboot', 'monitoring.snmp', 'monitoring.linetest',
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
            'ai.ticket_writer', 'ai.marketing',
            'maintenance.view', 'maintenance.edit', 'inventory.view', 'inventory.edit',
            'dunning.view', 'dunning.manage',
            'admin.users', 'admin.roles', 'admin.quota', 'admin.audit', 'admin.reports',
        ]);

        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'sanctum']);
        $agent->syncPermissions([
            'coverage.view',
            'customers.view', 'customers.create', 'customers.edit',
            'contracts.view', 'contracts.create', 'contracts.sign',
            'invoices.view', 'ai.marketing',
        ]);

        $technician = Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'sanctum']);
        $technician->syncPermissions([
            'coverage.view', 'customers.view', 'contracts.view',
            'orders.view', 'orders.reschedule',
            'radius.view', 'radius.coa', 'radius.logs', 'walled_garden.view',
            'monitoring.view', 'monitoring.cpe_reboot', 'monitoring.snmp', 'monitoring.linetest',
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
            'maintenance.view', 'maintenance.edit', 'inventory.view', 'inventory.edit',
            'ai.ticket_writer',
        ]);

        $billing = Role::firstOrCreate(['name' => 'billing', 'guard_name' => 'sanctum']);
        $billing->syncPermissions([
            'customers.view', 'contracts.view',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'invoices.send', 'invoices.send_sdi', 'invoices.export',
            'payments.view', 'payments.process', 'sepa.view', 'sepa.export',
            'dunning.view', 'dunning.manage', 'admin.reports',
        ]);

        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'sanctum']);
        $customer->syncPermissions(['invoices.view', 'tickets.view', 'tickets.create']);

        // carrier_webhook: nessun permesso applicativo, middleware dedicato
        Role::firstOrCreate(['name' => 'carrier_webhook', 'guard_name' => 'sanctum']);

        $this->command->info('Ruoli IspManager: super_admin, admin, agent, technician, billing, customer, carrier_webhook');
    }
}
