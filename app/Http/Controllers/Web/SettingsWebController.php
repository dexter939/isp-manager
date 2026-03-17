<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsWebController extends Controller
{
    public function show()
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        // Merge defaults so the view never gets null for nested keys
        $settings = array_replace_recursive($this->defaults(), $tenant->settings ?? []);

        return view('settings.index', compact('tenant', 'settings'));
    }

    public function update(Request $request)
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        $data = $request->validate([
            // Azienda
            'company.ragione_sociale' => 'nullable|string|max:200',
            'company.piva'            => ['nullable', 'string', 'max:11', 'regex:/^\d{11}$/'],
            'company.cf'              => 'nullable|string|max:16',
            'company.rea'             => 'nullable|string|max:20',
            'company.indirizzo'       => 'nullable|string|max:200',
            'company.cap'             => 'nullable|string|max:10',
            'company.citta'           => 'nullable|string|max:100',
            'company.provincia'       => 'nullable|string|max:2',
            'company.paese'           => 'nullable|string|max:2',
            'company.telefono'        => 'nullable|string|max:20',
            'company.email'           => 'nullable|email|max:150',
            'company.pec'             => 'nullable|email|max:150',
            'company.iban'            => 'nullable|string|max:34',

            // Fatturazione
            'billing.billing_day'    => 'required|integer|min:1|max:28',
            'billing.payment_days'   => 'required|integer|min:0|max:365',
            'billing.iva_rate'       => 'required|integer|min:0|max:100',
            'billing.regime_iva'     => 'required|in:ordinario,forfettario,minimi',
            'billing.invoice_prefix' => 'required|string|max:10',
            'billing.currency'       => 'required|string|size:3',

            // SDI
            'sdi.codice_destinatario' => 'nullable|string|max:7',
            'sdi.pec'                 => 'nullable|email|max:150',

            // Branding
            'branding.display_name'  => 'nullable|string|max:100',
            'branding.logo_url'      => 'nullable|url|max:500',
            'branding.primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],

            // Notifiche
            'notifications.email_from'      => 'nullable|email|max:150',
            'notifications.email_from_name' => 'nullable|string|max:100',

            // SMTP override
            'smtp.enabled'    => 'nullable|boolean',
            'smtp.host'       => 'nullable|string|max:255',
            'smtp.port'       => 'nullable|integer|min:1|max:65535',
            'smtp.encryption' => 'nullable|in:tls,ssl,none',
            'smtp.username'   => 'nullable|string|max:255',
            'smtp.password'   => 'nullable|string|max:255',

            // SLA policy
            'sla.critical_first_response_hours' => 'required|integer|min:1',
            'sla.critical_resolution_hours'     => 'required|integer|min:1',
            'sla.high_first_response_hours'     => 'required|integer|min:1',
            'sla.high_resolution_hours'         => 'required|integer|min:1',
            'sla.medium_first_response_hours'   => 'required|integer|min:1',
            'sla.medium_resolution_hours'       => 'required|integer|min:1',
            'sla.low_first_response_hours'      => 'required|integer|min:1',
            'sla.low_resolution_hours'          => 'required|integer|min:1',
            'sla.business_hours_only'           => 'nullable|boolean',
            'sla.business_hours_start'          => 'nullable|string|max:5',
            'sla.business_hours_end'            => 'nullable|string|max:5',

            // Portali
            'portal.customer_portal_enabled' => 'nullable|boolean',
            'portal.customer_portal_welcome' => 'nullable|string|max:500',
            'portal.agent_portal_enabled'    => 'nullable|boolean',
        ]);

        // Checkbox booleans not submitted when unchecked
        $data['smtp']['enabled']                = $request->boolean('smtp.enabled');
        $data['sla']['business_hours_only']     = $request->boolean('sla.business_hours_only');
        $data['portal']['customer_portal_enabled'] = $request->boolean('portal.customer_portal_enabled');
        $data['portal']['agent_portal_enabled']    = $request->boolean('portal.agent_portal_enabled');

        // Merge with existing settings to preserve keys not in this request
        $existing = $tenant->settings ?? [];
        $merged   = array_replace_recursive($existing, $data);

        $tenant->update(['settings' => $merged]);

        return back()->with('success', 'Impostazioni salvate con successo.');
    }

    // ── Test email ────────────────────────────────────────────────────────────

    public function testEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        $tenant   = Tenant::findOrFail(auth()->user()->tenant_id);
        $settings = array_replace_recursive($this->defaults(), $tenant->settings ?? []);

        $from     = $settings['notifications']['email_from']      ?: config('mail.from.address');
        $fromName = $settings['notifications']['email_from_name'] ?: config('mail.from.name');
        $to       = $request->input('test_email');

        Mail::html(
            "<h2>Test email da ISP Manager</h2><p>Questa è un'email di test inviata da <strong>{$tenant->name}</strong>.</p><p>Mittente configurato: {$from}</p>",
            function ($m) use ($to, $from, $fromName, $tenant) {
                $m->to($to)
                  ->from($from, $fromName)
                  ->subject("[{$tenant->name}] Email di test");
            }
        );

        return back()->with('success', "Email di test inviata a {$to}.")->withFragment('tab-notifications');
    }

    // ── Defaults ─────────────────────────────────────────────────────────────

    private function defaults(): array
    {
        return [
            'company' => [
                'ragione_sociale' => '',
                'piva'            => '',
                'cf'              => '',
                'rea'             => '',
                'indirizzo'       => '',
                'cap'             => '',
                'citta'           => '',
                'provincia'       => '',
                'paese'           => 'IT',
                'telefono'        => '',
                'email'           => '',
                'pec'             => '',
                'iban'            => '',
            ],
            'billing' => [
                'billing_day'    => 1,
                'payment_days'   => 30,
                'iva_rate'       => 22,
                'regime_iva'     => 'ordinario',
                'invoice_prefix' => 'FT',
                'currency'       => 'EUR',
            ],
            'sdi' => [
                'codice_destinatario' => '0000000',
                'pec'                 => '',
            ],
            'branding' => [
                'display_name'  => '',
                'logo_url'      => '',
                'primary_color' => '#696cff',
            ],
            'notifications' => [
                'email_from'      => '',
                'email_from_name' => '',
            ],
            'smtp' => [
                'enabled'    => false,
                'host'       => '',
                'port'       => 587,
                'encryption' => 'tls',
                'username'   => '',
                'password'   => '',
            ],
            'sla' => [
                'critical_first_response_hours' => 2,
                'critical_resolution_hours'     => 8,
                'high_first_response_hours'     => 8,
                'high_resolution_hours'         => 24,
                'medium_first_response_hours'   => 24,
                'medium_resolution_hours'       => 48,
                'low_first_response_hours'      => 48,
                'low_resolution_hours'          => 120,
                'business_hours_only'           => false,
                'business_hours_start'          => '08:00',
                'business_hours_end'            => '18:00',
            ],
            'portal' => [
                'customer_portal_enabled' => true,
                'customer_portal_welcome' => '',
                'agent_portal_enabled'    => true,
            ],
        ];
    }
}
