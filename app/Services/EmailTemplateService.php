<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantTemplateMail;

class EmailTemplateService
{
    /**
     * Render a template with variable substitution.
     * Tenant-specific template takes priority over global default (tenant_id IS NULL).
     *
     * @return array{subject:string,body_html:string,body_text:string}|null
     */
    public function render(string $slug, int $tenantId, array $variables): ?array
    {
        $template = DB::table('email_templates')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id IS NULL ASC') // tenant-specific first
            ->first();

        if (!$template) {
            return null;
        }

        // Merge common variables
        $vars = array_merge($this->commonVariables($tenantId), $variables);

        return [
            'subject'   => $this->interpolate($template->subject, $vars),
            'body_html' => $this->interpolate($template->body_html, $vars),
            'body_text' => $this->interpolate($template->body_text, $vars),
        ];
    }

    /**
     * Render and send an email via the template system.
     * Silently skips if: template not found, is_active=false, or no recipient email.
     */
    public function send(string $slug, int $tenantId, string $toEmail, string $toName, array $variables): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $rendered = $this->render($slug, $tenantId, $variables);
        if (!$rendered) {
            return;
        }

        $settings = DB::table('tenants')->where('id', $tenantId)->value('settings');
        $settings = is_string($settings) ? json_decode($settings, true) : ($settings ?? []);

        $fromEmail = $settings['notifications']['email_from']      ?? config('mail.from.address');
        $fromName  = $settings['notifications']['email_from_name'] ?? config('mail.from.name');

        Mail::to($toEmail, $toName)
            ->send(new TenantTemplateMail($rendered, $fromEmail, $fromName));
    }

    /**
     * Interpolate {{variable}} placeholders in a string.
     */
    public function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $text);
        }
        return $text;
    }

    /**
     * Variables always available in every template.
     */
    private function commonVariables(int $tenantId): array
    {
        $tenant   = DB::table('tenants')->where('id', $tenantId)->first();
        $settings = is_string($tenant?->settings ?? null)
            ? json_decode($tenant->settings, true)
            : ($tenant?->settings ?? []);

        return [
            'tenant_name'  => $settings['company']['ragione_sociale'] ?? $tenant?->name ?? '',
            'tenant_email' => $settings['company']['email'] ?? $settings['notifications']['email_from'] ?? '',
            'current_date' => now()->format('d/m/Y'),
        ];
    }

    /**
     * Return all known template slugs with metadata.
     */
    public function allSlugs(): array
    {
        return [
            'invoice_generated' => ['label' => 'Nuova fattura emessa',          'icon' => 'ri-file-text-line',         'color' => 'primary'],
            'invoice_overdue'   => ['label' => 'Fattura scaduta — sollecito',   'icon' => 'ri-error-warning-line',     'color' => 'danger'],
            'payment_received'  => ['label' => 'Conferma pagamento ricevuto',   'icon' => 'ri-checkbox-circle-line',   'color' => 'success'],
            'contract_signed'   => ['label' => 'Contratto attivato',            'icon' => 'ri-file-add-line',          'color' => 'success'],
            'contract_suspended'=> ['label' => 'Contratto sospeso',             'icon' => 'ri-pause-circle-line',      'color' => 'warning'],
            'ticket_opened'     => ['label' => 'Apertura ticket',               'icon' => 'ri-customer-service-2-line','color' => 'info'],
            'ticket_resolved'   => ['label' => 'Ticket risolto',                'icon' => 'ri-shield-check-line',      'color' => 'success'],
            'portal_welcome'    => ['label' => 'Benvenuto portale clienti',     'icon' => 'ri-user-smile-line',        'color' => 'primary'],
        ];
    }
}
