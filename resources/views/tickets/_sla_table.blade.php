<div class="table-responsive">
  <table class="table table-sm table-hover mb-0">
    <thead class="table-light">
      <tr>
        <th>Ticket</th>
        <th>Titolo</th>
        <th>Cliente</th>
        <th>Priorità</th>
        <th>Assegnato</th>
        <th>
          @if($mode === 'overdue') Scaduto da
          @elseif($mode === 'at_risk') Scade tra
          @else Aperto il
          @endif
        </th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $row)
        @php
          $priorityColor = match($row->priority) {
            'critical','high' => 'danger', 'medium' => 'warning', default => 'secondary'
          };
        @endphp
        <tr>
          <td class="font-monospace small fw-semibold">{{ $row->ticket_number }}</td>
          <td class="small">{{ \Illuminate\Support\Str::limit($row->title, 45) }}</td>
          <td class="small text-muted">{{ $row->customer_full_name ?? '—' }}</td>
          <td>
            <span class="badge bg-{{ $priorityColor }}">{{ ucfirst($row->priority) }}</span>
          </td>
          <td class="small text-muted">{{ $row->assigned_name ?? '—' }}</td>
          <td class="small fw-semibold">
            @if($mode === 'overdue')
              <span class="text-danger">{{ round($row->hours_overdue, 1) }}h fa</span>
            @elseif($mode === 'at_risk')
              <span class="text-warning">{{ round($row->hours_remaining, 1) }}h</span>
            @else
              {{ \Carbon\Carbon::parse($row->opened_at)->format('d/m H:i') }}
            @endif
          </td>
          <td class="text-end">
            <a href="{{ route('tickets.show', $row->id) }}" class="btn btn-sm btn-outline-primary">
              <i class="ri-arrow-right-line"></i>
            </a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
