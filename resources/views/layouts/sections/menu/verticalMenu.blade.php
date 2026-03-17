<aside class="layout-menu">
  <a href="{{ route('dashboard') }}" class="menu-logo">
    <i class="ri-wifi-line"></i>
    <span>{{ config('variables.templateName', 'ISP Manager') }}</span>
  </a>

  <ul class="list-unstyled mb-0 py-2">

    <li class="menu-item">
      <a href="{{ route('dashboard') }}" class="menu-link">
        <i class="ri-home-smile-line"></i><span>Dashboard</span>
      </a>
    </li>

    {{-- CRM --}}
    <li class="menu-header">CRM</li>

    <li class="menu-item">
      <a href="{{ route('customers.index') }}" class="menu-link">
        <i class="ri-user-3-line"></i><span>Clienti</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="{{ route('contracts.index') }}" class="menu-link">
        <i class="ri-file-text-line"></i><span>Contratti</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="{{ route('service-plans.index') }}" class="menu-link">
        <i class="ri-price-tag-3-line"></i><span>Piani di servizio</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="{{ route('provisioning.index') }}" class="menu-link">
        <i class="ri-signal-tower-line"></i><span>Provisioning</span>
      </a>
    </li>

    {{-- Fatturazione --}}
    <li class="menu-header">Fatturazione</li>

    <li class="menu-item has-submenu">
      <a href="#menu-billing" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-billing">
        <i class="ri-bill-line"></i><span>Fatturazione</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-billing">
        <li class="menu-item">
          <a href="{{ route('billing.invoices.index') }}" class="menu-link">Fatture</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.payments.index') }}" class="menu-link">Pagamenti</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.proforma.index') }}" class="menu-link">Proforma</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.bundles.index') }}" class="menu-link">Bundle</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.payment-matching.index') }}" class="menu-link">Regole matching</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.dunning.index') }}" class="menu-link">Solleciti</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.sdi.index') }}" class="menu-link">Fatturazione elettronica</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.sepa.index') }}" class="menu-link">SEPA SDD</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.run.index') }}" class="menu-link">Genera fatture</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.prepaid.wallets.index') }}" class="menu-link">Portafogli prepaid</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('billing.prepaid.products.index') }}" class="menu-link">Prodotti ricarica</a>
        </li>
      </ul>
    </li>

    {{-- Rete & Infrastruttura --}}
    <li class="menu-header">Rete & Infrastruttura</li>

    <li class="menu-item">
      <a href="{{ route('coverage.index') }}" class="menu-link">
        <i class="ri-map-pin-2-line"></i><span>Copertura</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="{{ route('network.radius') }}" class="menu-link">
        <i class="ri-router-line"></i><span>RADIUS / PPPoE</span>
      </a>
    </li>

    <li class="menu-item has-submenu">
      <a href="#menu-infra" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-infra">
        <i class="ri-git-branch-line"></i><span>Infrastruttura</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-infra">
        <li class="menu-item">
          <a href="{{ route('network.sites.index') }}" class="menu-link">Siti di rete</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('network.topology.index') }}" class="menu-link">Topologia</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('network.fair-usage.index') }}" class="menu-link">Fair Usage / FUP</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('coverage.elevation.index') }}" class="menu-link">Elevazione WISP</a>
        </li>
      </ul>
    </li>

    {{-- Monitoraggio --}}
    <li class="menu-item has-submenu">
      <a href="#menu-monitoring" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-monitoring">
        <i class="ri-pulse-line"></i><span>Monitoraggio</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-monitoring">
        <li class="menu-item">
          <a href="{{ route('monitoring.index') }}" class="menu-link">Dashboard</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('monitoring.alerts') }}" class="menu-link">Allarmi</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('monitoring.cpe.index') }}" class="menu-link">CPE / ACS</a>
        </li>
      </ul>
    </li>

    {{-- Assistenza & Field --}}
    <li class="menu-header">Assistenza & Field</li>

    <li class="menu-item has-submenu">
      <a href="#menu-tickets" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-tickets">
        <i class="ri-customer-service-2-line"></i><span>Ticket</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-tickets">
        <li class="menu-item">
          <a href="{{ route('tickets.index') }}" class="menu-link">Tutti i ticket</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('tickets.sla') }}" class="menu-link">
            <i class="ri-alarm-warning-line me-1 text-warning"></i>SLA Dashboard
          </a>
        </li>
        <li class="menu-item">
          <a href="{{ route('tickets.create') }}" class="menu-link">Nuovo ticket</a>
        </li>
      </ul>
    </li>

    <li class="menu-item has-submenu">
      <a href="#menu-field" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-field">
        <i class="ri-tools-line"></i><span>Field Service</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-field">
        <li class="menu-item">
          <a href="{{ route('maintenance.dispatcher.index') }}" class="menu-link">Dispatcher</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('maintenance.oncall.index') }}" class="menu-link">Reperibilità</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('maintenance.route-optimizer.index') }}" class="menu-link">Ottimizzatore rotte</a>
        </li>
      </ul>
    </li>

    <li class="menu-item has-submenu">
      <a href="#menu-inventory" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-inventory">
        <i class="ri-archive-line"></i><span>Magazzino</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-inventory">
        <li class="menu-item">
          <a href="{{ route('inventory.index') }}" class="menu-link">Inventario</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('maintenance.inventory-rma.index') }}" class="menu-link">RMA</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('maintenance.purchase-orders.index') }}" class="menu-link">Ordini acquisto</a>
        </li>
      </ul>
    </li>

    {{-- Reporting --}}
    <li class="menu-header">Reporting</li>

    <li class="menu-item has-submenu">
      <a href="#menu-reporting" class="menu-link menu-toggle collapsed" data-bs-toggle="collapse"
         aria-expanded="false" aria-controls="menu-reporting">
        <i class="ri-bar-chart-2-line"></i><span>Analytics</span>
        <i class="ri-arrow-right-s-line menu-chevron ms-auto"></i>
      </a>
      <ul class="menu-sub collapse list-unstyled" id="menu-reporting">
        <li class="menu-item">
          <a href="{{ route('reporting.index') }}" class="menu-link">Overview</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('reporting.revenue') }}" class="menu-link">Revenue</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('reporting.contracts') }}" class="menu-link">Contratti</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('reporting.tickets') }}" class="menu-link">Ticket & SLA</a>
        </li>
        <li class="menu-item">
          <a href="{{ route('reporting.agents') }}" class="menu-link">Provvigioni agenti</a>
        </li>
      </ul>
    </li>

    {{-- Admin --}}
    <li class="menu-header">Amministrazione</li>

    <li class="menu-item">
      <a href="{{ route('api-docs.index') }}" class="menu-link">
        <i class="ri-code-s-slash-line"></i><span>API Docs</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="{{ route('settings.show') }}" class="menu-link">
        <i class="ri-settings-4-line"></i><span>Impostazioni</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="{{ route('email-templates.index') }}" class="menu-link">
        <i class="ri-mail-settings-line"></i><span>Template Email</span>
      </a>
    </li>

    @can('manage-tenants')
    <li class="menu-item">
      <a href="{{ route('admin.users.index') }}" class="menu-link">
        <i class="ri-team-line"></i><span>Utenti</span>
      </a>
    </li>
    <li class="menu-item">
      <a href="{{ route('admin.agents.index') }}" class="menu-link">
        <i class="ri-shake-hands-line"></i><span>Agenti</span>
      </a>
    </li>
    @endcan

    @if(auth()->check() && auth()->user()->is_super_admin)
    <li class="menu-item">
      <a href="{{ route('superadmin.tenants.index') }}" class="menu-link">
        <i class="ri-building-4-line"></i><span>Gestione Tenant</span>
      </a>
    </li>
    @endif

  </ul>
</aside>
