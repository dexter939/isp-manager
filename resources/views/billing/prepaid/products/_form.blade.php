<div class="mb-3">
  <label class="form-label">Nome prodotto <span class="text-danger">*</span></label>
  <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
         value="{{ old('name') }}" placeholder="es. Ricarica €10" required maxlength="120">
  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3">
  <div class="col-6">
    <label class="form-label">Importo (centesimi) <span class="text-danger">*</span></label>
    <div class="input-group">
      <input type="number" name="amount_amount" class="form-control @error('amount_amount') is-invalid @enderror"
             value="{{ old('amount_amount') }}" min="100" placeholder="1000" required>
      <span class="input-group-text">¢</span>
    </div>
    <div class="form-text">€10 = 1000 centesimi</div>
    @error('amount_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-6">
    <label class="form-label">Bonus (centesimi)</label>
    <div class="input-group">
      <input type="number" name="bonus_amount" class="form-control @error('bonus_amount') is-invalid @enderror"
             value="{{ old('bonus_amount', 0) }}" min="0" placeholder="0">
      <span class="input-group-text">¢</span>
    </div>
    <div class="form-text">Credito extra gratuito</div>
    @error('bonus_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-6">
    <label class="form-label">Validità (giorni)</label>
    <input type="number" name="validity_days" class="form-control @error('validity_days') is-invalid @enderror"
           value="{{ old('validity_days') }}" min="1" placeholder="Lascia vuoto = illimitata">
    @error('validity_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-6">
    <label class="form-label">Ordinamento</label>
    <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
           value="{{ old('sort_order', 0) }}" min="0">
    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
</div>
