<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->defaults();

        foreach ($templates as $tpl) {
            DB::table('email_templates')->updateOrInsert(
                ['tenant_id' => null, 'slug' => $tpl['slug']],
                array_merge($tpl, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        $this->command->info('Email templates seeded: ' . count($templates));
    }

    private function defaults(): array
    {
        return [

            // ── Fattura emessa ────────────────────────────────────────────────
            [
                'slug'      => 'invoice_generated',
                'name'      => 'Nuova fattura emessa',
                'subject'   => 'Fattura {{invoice_number}} — {{tenant_name}}',
                'body_html' => $this->wrap('Nuova fattura', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>ti informiamo che è disponibile la tua fattura:</p>
                    <table width="100%" style="border-collapse:collapse;margin:20px 0">
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Numero fattura</td>
                        <td style="padding:8px 12px">{{invoice_number}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Periodo</td>
                        <td style="padding:8px 12px">{{invoice_period}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Importo</td>
                        <td style="padding:8px 12px;font-size:18px;font-weight:bold;color:#696cff">{{invoice_amount}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Scadenza</td>
                        <td style="padding:8px 12px">{{invoice_due_date}}</td>
                      </tr>
                    </table>
                    <p>Puoi visualizzare e scaricare la fattura accedendo al portale clienti.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nFattura {{invoice_number}} — Importo: {{invoice_amount}} — Scadenza: {{invoice_due_date}}\n\nAccedi al portale per visualizzarla.",
                'variables' => json_encode(['customer_name','invoice_number','invoice_amount','invoice_due_date','invoice_period','tenant_name']),
                'is_active' => true,
            ],

            // ── Fattura scaduta ───────────────────────────────────────────────
            [
                'slug'      => 'invoice_overdue',
                'name'      => 'Fattura scaduta — sollecito',
                'subject'   => 'Sollecito — fattura {{invoice_number}} scaduta',
                'body_html' => $this->wrap('Sollecito di pagamento', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>ti ricordiamo che la seguente fattura risulta <strong style="color:#ff3e1d">non pagata</strong> e scaduta:</p>
                    <table width="100%" style="border-collapse:collapse;margin:20px 0;border:2px solid #ff3e1d">
                      <tr>
                        <td style="padding:8px 12px;background:#fff5f5;font-weight:bold">Numero fattura</td>
                        <td style="padding:8px 12px">{{invoice_number}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#fff5f5;font-weight:bold">Importo dovuto</td>
                        <td style="padding:8px 12px;font-size:18px;font-weight:bold;color:#ff3e1d">{{invoice_amount}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#fff5f5;font-weight:bold">Scaduta il</td>
                        <td style="padding:8px 12px;color:#ff3e1d">{{invoice_due_date}}</td>
                      </tr>
                    </table>
                    <p>Ti invitiamo a regolarizzare il pagamento quanto prima per evitare la sospensione del servizio.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nFATTURA SCADUTA: {{invoice_number}} — Importo: {{invoice_amount}} — Scaduta il: {{invoice_due_date}}\n\nProcedi al pagamento per evitare la sospensione del servizio.",
                'variables' => json_encode(['customer_name','invoice_number','invoice_amount','invoice_due_date','tenant_name']),
                'is_active' => true,
            ],

            // ── Pagamento ricevuto ─────────────────────────────────────────────
            [
                'slug'      => 'payment_received',
                'name'      => 'Conferma pagamento ricevuto',
                'subject'   => 'Pagamento ricevuto — {{payment_amount}}',
                'body_html' => $this->wrap('Pagamento ricevuto', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>abbiamo ricevuto il tuo pagamento. Grazie!</p>
                    <table width="100%" style="border-collapse:collapse;margin:20px 0">
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Importo</td>
                        <td style="padding:8px 12px;font-size:18px;font-weight:bold;color:#71dd37">{{payment_amount}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Data</td>
                        <td style="padding:8px 12px">{{payment_date}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Metodo</td>
                        <td style="padding:8px 12px">{{payment_method}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Fattura</td>
                        <td style="padding:8px 12px">{{invoice_number}}</td>
                      </tr>
                    </table>
                    <p>Il tuo servizio è attivo e non sarà interrotto.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nPagamento ricevuto: {{payment_amount}} il {{payment_date}} ({{payment_method}}) — Fattura {{invoice_number}}.",
                'variables' => json_encode(['customer_name','payment_amount','payment_date','payment_method','invoice_number','tenant_name']),
                'is_active' => true,
            ],

            // ── Contratto attivato ────────────────────────────────────────────
            [
                'slug'      => 'contract_signed',
                'name'      => 'Contratto attivato',
                'subject'   => 'Il tuo contratto {{contract_number}} è attivo!',
                'body_html' => $this->wrap('Benvenuto!', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>siamo lieti di comunicarti che il tuo contratto è stato attivato con successo. Benvenuto tra i nostri clienti!</p>
                    <table width="100%" style="border-collapse:collapse;margin:20px 0">
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Numero contratto</td>
                        <td style="padding:8px 12px;font-family:monospace">{{contract_number}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Piano</td>
                        <td style="padding:8px 12px;font-weight:bold">{{plan_name}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Data attivazione</td>
                        <td style="padding:8px 12px">{{activation_date}}</td>
                      </tr>
                    </table>
                    <p>Accedi al portale clienti per monitorare il tuo servizio e le tue fatture.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nContratto {{contract_number}} attivato!\nPiano: {{plan_name}} — Attivazione: {{activation_date}}",
                'variables' => json_encode(['customer_name','contract_number','plan_name','activation_date','tenant_name']),
                'is_active' => true,
            ],

            // ── Contratto sospeso ─────────────────────────────────────────────
            [
                'slug'      => 'contract_suspended',
                'name'      => 'Contratto sospeso',
                'subject'   => 'Servizio sospeso — contratto {{contract_number}}',
                'body_html' => $this->wrap('Servizio sospeso', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>ti informiamo che il tuo servizio <strong>{{plan_name}}</strong> (contratto {{contract_number}}) è stato temporaneamente sospeso.</p>
                    <p style="color:#ff3e1d;font-weight:bold">Motivo: {{suspension_reason}}</p>
                    <p>Per riattivare il servizio, ti chiediamo di regolarizzare la tua posizione.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nServizio {{plan_name}} (contratto {{contract_number}}) sospeso.\nMotivo: {{suspension_reason}}",
                'variables' => json_encode(['customer_name','contract_number','plan_name','suspension_reason','tenant_name']),
                'is_active' => true,
            ],

            // ── Ticket aperto ─────────────────────────────────────────────────
            [
                'slug'      => 'ticket_opened',
                'name'      => 'Conferma apertura ticket',
                'subject'   => 'Ticket {{ticket_number}} ricevuto — {{tenant_name}}',
                'body_html' => $this->wrap('Richiesta ricevuta', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>abbiamo ricevuto la tua richiesta di assistenza. Il nostro team la prenderà in carico al più presto.</p>
                    <table width="100%" style="border-collapse:collapse;margin:20px 0">
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Numero ticket</td>
                        <td style="padding:8px 12px;font-family:monospace;font-weight:bold">{{ticket_number}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Oggetto</td>
                        <td style="padding:8px 12px">{{ticket_title}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Priorità</td>
                        <td style="padding:8px 12px">{{ticket_priority}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5f5f5;font-weight:bold">Aperto il</td>
                        <td style="padding:8px 12px">{{ticket_opened_at}}</td>
                      </tr>
                    </table>
                    <p>Conserva il numero ticket <strong>{{ticket_number}}</strong> per eventuali comunicazioni.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nTicket {{ticket_number}} ricevuto.\nOggetto: {{ticket_title}}\nPriorità: {{ticket_priority}}\nAperto il: {{ticket_opened_at}}",
                'variables' => json_encode(['customer_name','ticket_number','ticket_title','ticket_priority','ticket_opened_at','tenant_name']),
                'is_active' => true,
            ],

            // ── Ticket risolto ────────────────────────────────────────────────
            [
                'slug'      => 'ticket_resolved',
                'name'      => 'Ticket risolto',
                'subject'   => 'Ticket {{ticket_number}} risolto',
                'body_html' => $this->wrap('Ticket risolto', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>siamo lieti di comunicarti che il tuo ticket è stato risolto.</p>
                    <table width="100%" style="border-collapse:collapse;margin:20px 0">
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Numero ticket</td>
                        <td style="padding:8px 12px;font-family:monospace">{{ticket_number}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Oggetto</td>
                        <td style="padding:8px 12px">{{ticket_title}}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 12px;background:#f5fff5;font-weight:bold">Risolto il</td>
                        <td style="padding:8px 12px">{{ticket_resolved_at}}</td>
                      </tr>
                    </table>
                    @if(isset($resolution_notes))
                    <p><strong>Note di risoluzione:</strong> {{ticket_resolution_notes}}</p>
                    @endif
                    <p>Se il problema dovesse ripresentarsi, non esitare a contattarci.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nTicket {{ticket_number}} risolto il {{ticket_resolved_at}}.\n{{ticket_resolution_notes}}",
                'variables' => json_encode(['customer_name','ticket_number','ticket_title','ticket_resolved_at','ticket_resolution_notes','tenant_name']),
                'is_active' => true,
            ],

            // ── Benvenuto portale ─────────────────────────────────────────────
            [
                'slug'      => 'portal_welcome',
                'name'      => 'Benvenuto nel portale clienti',
                'subject'   => 'Accesso al portale {{tenant_name}}',
                'body_html' => $this->wrap('Benvenuto nel portale', <<<'HTML'
                    <p>Gentile {{customer_name}},</p>
                    <p>il tuo accesso al portale clienti è stato attivato. Da ora puoi:</p>
                    <ul style="line-height:2">
                      <li>📄 Visualizzare e scaricare le tue fatture</li>
                      <li>🎫 Aprire e monitorare i ticket di assistenza</li>
                      <li>📊 Controllare lo stato dei tuoi contratti</li>
                    </ul>
                    <p><strong>Email:</strong> {{portal_email}}<br>
                    <strong>Password temporanea:</strong> {{portal_temp_password}}</p>
                    <p style="color:#888;font-size:12px">Accedendo per la prima volta ti verrà chiesto di cambiare la password.</p>
                    HTML),
                'body_text' => "Gentile {{customer_name}},\n\nAccesso portale attivato.\nEmail: {{portal_email}}\nPassword: {{portal_temp_password}}",
                'variables' => json_encode(['customer_name','portal_email','portal_temp_password','tenant_name','portal_url']),
                'is_active' => true,
            ],
        ];
    }

    private function wrap(string $title, string $content): string
    {
        return <<<HTML
        <div style="background:#f0f0f0;padding:20px 0;font-family:Arial,sans-serif">
          <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)">
            <div style="background:#696cff;padding:24px 32px">
              <h1 style="margin:0;color:#fff;font-size:20px">{{tenant_name}}</h1>
              <p style="margin:4px 0 0;color:rgba(255,255,255,.8);font-size:13px">{$title}</p>
            </div>
            <div style="padding:32px;color:#333;font-size:14px;line-height:1.6">
              {$content}
            </div>
            <div style="background:#f9f9f9;padding:16px 32px;border-top:1px solid #eee;font-size:12px;color:#888">
              <p style="margin:0">{{tenant_name}} · {{tenant_email}}</p>
              <p style="margin:4px 0 0">Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.</p>
            </div>
          </div>
        </div>
        HTML;
    }
}
