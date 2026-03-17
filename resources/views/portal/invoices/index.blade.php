@extends('layouts.portal')
@section('title', 'Le mie fatture')

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="ri-bill-line me-2"></i>Le mie fatture</h5>
  </div>

  <div class="card portal-card mb-3">
    <div class="card-body py-2">
      <form class="row g-2 align-items-end">
        <div class="col-auto">
          <select name="status" class="form-select form-select-sm">
            <option value="">Tutti gli stati</option>
            <option value="issued"  @selected(request('status') === 'issued')>Da pagare</option>
            <option value="paid"    @selected(request('status') === 'paid')>Pagate</option>
            <option value="overdue" @selected(request('status') === 'overdue')>Scadute</option>
          </select>
        </div>
        <div class="col-auto">
          <select name="year" class="form-select form-select-sm">
            <option value="">Tutti gli anni</option>
            @foreach($years as $y)
              <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">Filtra</button>
          <a href="{{ route('portal.invoices') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card portal-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Numero</th>
              <th>Periodo</th>
              <th>Emessa il</th>
              <th>Scadenza</th>
              <th class="text-end">Importo</th>
              <th>Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($invoices as $inv)
              <tr>
                <td>
                  <a href="{{ route('portal.invoices.show', $inv->id) }}" class="fw-semibold text-decoration-none">
                    {{ $inv->number }}
                  </a>
                </td>
                <td class="small text-muted">
                  @if($inv->period_from && $inv->period_to)
                    {{ \Carbon\Carbon::parse($inv->period_from)->format('d/m/Y') }} –
                    {{ \Carbon\Carbon::parse($inv->period_to)->format('d/m/Y') }}
                  @else —
                  @endif
                </td>
                <td class="small">{{ \Carbon\Carbon::parse($inv->issue_date)->format('d/m/Y') }}</td>
                <td class="small {{ $inv->status === 'overdue' ? 'text-danger fw-semibold' : 'text-muted' }}">
                  {{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') : '—' }}
                </td>
                <td class="text-end fw-semibold">€ {{ number_format($inv->total / 100, 2, ',', '.') }}</td>
                <td><span class="badge badge-status-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    @if(in_array($inv->status, ['issued', 'overdue']))
                      <a href="{{ route('portal.invoices.pay', $inv->id) }}" class="btn btn-sm btn-danger" title="Paga ora">
                        <i class="ri-secure-payment-line"></i>
                      </a>
                    @endif
                    @if($inv->pdf_path)
                      <a href="{{ route('portal.invoices.pdf', $inv->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="PDF">
                        <i class="ri-file-pdf-line"></i>
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">Nessuna fattura trovata.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($invoices->hasPages())
      <div class="card-footer">{{ $invoices->links() }}</div>
    @endif
  </div>

@endsection
