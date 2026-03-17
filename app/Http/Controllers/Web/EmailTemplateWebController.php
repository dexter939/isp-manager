<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantTemplateMail;

class EmailTemplateWebController extends Controller
{
    public function __construct(private readonly EmailTemplateService $emailService) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $slugMeta = $this->emailService->allSlugs();

        // Load tenant-specific overrides
        $tenantTemplates = DB::table('email_templates')
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('slug');

        // Load global defaults
        $defaultTemplates = DB::table('email_templates')
            ->whereNull('tenant_id')
            ->get()
            ->keyBy('slug');

        $templates = collect($slugMeta)->map(function ($meta, $slug) use ($tenantTemplates, $defaultTemplates) {
            $tpl = $tenantTemplates[$slug] ?? $defaultTemplates[$slug] ?? null;
            return (object) [
                'slug'        => $slug,
                'name'        => $tpl?->name ?? $meta['label'],
                'subject'     => $tpl?->subject ?? '—',
                'is_active'   => (bool) ($tpl?->is_active ?? true),
                'is_custom'   => isset($tenantTemplates[$slug]),
                'updated_at'  => $tpl?->updated_at,
                'icon'        => $meta['icon'],
                'color'       => $meta['color'],
                'label'       => $meta['label'],
            ];
        });

        return view('email-templates.index', compact('templates'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(string $slug)
    {
        $tenantId = auth()->user()->tenant_id;
        $slugMeta = $this->emailService->allSlugs();

        if (!isset($slugMeta[$slug])) {
            abort(404);
        }

        // Try tenant-specific, then global default
        $template = DB::table('email_templates')
            ->where('slug', $slug)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id IS NULL ASC')
            ->first();

        $meta = $slugMeta[$slug];

        return view('email-templates.edit', compact('template', 'slug', 'meta'));
    }

    public function update(Request $request, string $slug)
    {
        $tenantId = auth()->user()->tenant_id;
        $slugMeta = $this->emailService->allSlugs();

        if (!isset($slugMeta[$slug])) {
            abort(404);
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'subject'   => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'required|string',
        ]);

        $data = [
            'name'       => $request->input('name'),
            'subject'    => $request->input('subject'),
            'body_html'  => $request->input('body_html'),
            'body_text'  => $request->input('body_text'),
            'is_active'  => $request->boolean('is_active'),
            'updated_at' => now(),
        ];

        $exists = DB::table('email_templates')
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            DB::table('email_templates')
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->update($data);
        } else {
            // Global default exists — create tenant-specific override
            $global = DB::table('email_templates')->whereNull('tenant_id')->where('slug', $slug)->first();
            DB::table('email_templates')->insert(array_merge($data, [
                'tenant_id'  => $tenantId,
                'slug'       => $slug,
                'variables'  => $global?->variables ?? '[]',
                'created_at' => now(),
            ]));
        }

        return redirect()->route('email-templates.index')
            ->with('success', "Template «{$slug}» salvato.");
    }

    // ── Reset to default ──────────────────────────────────────────────────────

    public function reset(string $slug)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('email_templates')
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->delete();

        return redirect()->route('email-templates.index')
            ->with('success', "Template «{$slug}» ripristinato al default.");
    }

    // ── Toggle active ─────────────────────────────────────────────────────────

    public function toggle(string $slug)
    {
        $tenantId = auth()->user()->tenant_id;

        $tpl = DB::table('email_templates')
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();

        if ($tpl) {
            DB::table('email_templates')
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->update(['is_active' => !$tpl->is_active, 'updated_at' => now()]);
        } else {
            // Clone default with opposite active
            $global = DB::table('email_templates')->whereNull('tenant_id')->where('slug', $slug)->first();
            if ($global) {
                DB::table('email_templates')->insert([
                    'tenant_id'  => $tenantId,
                    'slug'       => $slug,
                    'name'       => $global->name,
                    'subject'    => $global->subject,
                    'body_html'  => $global->body_html,
                    'body_text'  => $global->body_text,
                    'variables'  => $global->variables,
                    'is_active'  => false, // disabling
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Stato template aggiornato.');
    }

    // ── Preview ───────────────────────────────────────────────────────────────

    public function preview(Request $request, string $slug)
    {
        $tenantId = auth()->user()->tenant_id;

        $rendered = $this->emailService->render($slug, $tenantId, $this->previewVariables($slug));

        if (!$rendered) {
            abort(404, 'Template non trovato.');
        }

        return response($rendered['body_html'])->header('Content-Type', 'text/html');
    }

    // ── Send test ─────────────────────────────────────────────────────────────

    public function sendTest(Request $request, string $slug)
    {
        $request->validate(['test_email' => 'required|email']);

        $tenantId = auth()->user()->tenant_id;

        $rendered = $this->emailService->render($slug, $tenantId, $this->previewVariables($slug));

        if (!$rendered) {
            return back()->with('error', 'Template non trovato o disabilitato.');
        }

        $settings  = DB::table('tenants')->where('id', $tenantId)->value('settings');
        $settings  = is_string($settings) ? json_decode($settings, true) : ($settings ?? []);
        $fromEmail = $settings['notifications']['email_from'] ?? config('mail.from.address');
        $fromName  = $settings['notifications']['email_from_name'] ?? config('mail.from.name');

        Mail::to($request->input('test_email'))
            ->send(new TenantTemplateMail($rendered, $fromEmail, $fromName));

        return back()->with('success', "Email di test inviata a {$request->input('test_email')}.");
    }

    // ── Sample variables for preview / test ──────────────────────────────────

    private function previewVariables(string $slug): array
    {
        $base = [
            'customer_name'  => 'Mario Rossi',
            'tenant_name'    => 'Acme Broadband s.r.l.',
            'tenant_email'   => 'info@acme.it',
            'current_date'   => now()->format('d/m/Y'),
        ];

        return array_merge($base, match($slug) {
            'invoice_generated', 'invoice_overdue' => [
                'invoice_number'   => 'FT-2026-0042',
                'invoice_amount'   => '€ 29,90',
                'invoice_due_date' => now()->addDays(30)->format('d/m/Y'),
                'invoice_period'   => 'Marzo 2026',
            ],
            'payment_received' => [
                'payment_amount'  => '€ 29,90',
                'payment_date'    => now()->format('d/m/Y'),
                'payment_method'  => 'Bonifico bancario',
                'invoice_number'  => 'FT-2026-0042',
            ],
            'contract_signed', 'contract_suspended' => [
                'contract_number'   => 'CT-2026-0015',
                'plan_name'         => 'Fibra 1 Gbps FTTH',
                'activation_date'   => now()->format('d/m/Y'),
                'suspension_reason' => 'Mancato pagamento fattura FT-2026-0042',
            ],
            'ticket_opened' => [
                'ticket_number'    => 'TK-20260316-0001',
                'ticket_title'     => 'Interruzione connessione',
                'ticket_priority'  => 'Alta',
                'ticket_opened_at' => now()->format('d/m/Y H:i'),
            ],
            'ticket_resolved' => [
                'ticket_number'           => 'TK-20260316-0001',
                'ticket_title'            => 'Interruzione connessione',
                'ticket_resolved_at'      => now()->format('d/m/Y H:i'),
                'ticket_resolution_notes' => 'Problema risolto lato OLT. Servizio ripristinato.',
            ],
            'portal_welcome' => [
                'portal_email'         => 'mario.rossi@example.com',
                'portal_temp_password' => '********',
                'portal_url'           => url('/portal'),
            ],
            default => [],
        });
    }
}
