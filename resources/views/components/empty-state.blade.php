@props(['message' => 'Nessun risultato trovato', 'icon' => 'ri-inbox-line', 'colspan' => 1])

<tr>
  <td colspan="{{ $colspan }}" class="text-center py-5">
    <i class="{{ $icon }} fs-2 text-muted d-block mb-2"></i>
    <span class="text-muted">{{ $message }}</span>
  </td>
</tr>
