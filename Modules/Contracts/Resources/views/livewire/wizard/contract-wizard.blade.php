<div class="max-w-4xl mx-auto p-6">

    {{-- Progress bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            @foreach([1=>'Cliente', 2=>'Indirizzo', 3=>'Piano', 4=>'Pagamento', 5=>'Riepilogo', 6=>'Firma'] as $n => $label)
                <div class="flex flex-col items-center">
                    <button
                        wire:click="goToStep({{ $n }})"
                        @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors',
                            'bg-blue-600 text-white' => $step === $n,
                            'bg-green-500 text-white cursor-pointer' => $step > $n,
                            'bg-gray-200 text-gray-500 cursor-default' => $step < $n,
                        ])
                        @disabled($n >= $step)
                    >
                        @if($step > $n) ✓ @else {{ $n }} @endif
                    </button>
                    <span class="text-xs mt-1 {{ $step === $n ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
                @if($n < 6) <div class="flex-1 h-1 {{ $step > $n ? 'bg-green-500' : 'bg-gray-200' }} mx-2"></div> @endif
            @endforeach
        </div>
    </div>

    {{-- Messaggio errore --}}
    @if($errorMessage)
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- ======================== STEP 1: CLIENTE ======================== --}}
    @if($step === 1)
    <div>
        <h2 class="text-xl font-semibold mb-4">Dati Cliente</h2>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Tipo Cliente</label>
            <select wire:model.live="customerType" class="w-full border rounded px-3 py-2">
                <option value="privato">Persona Fisica</option>
                <option value="azienda">Persona Giuridica / Azienda</option>
            </select>
        </div>

        @if($customerType === 'privato')
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Nome *</label>
                    <input wire:model="nome" type="text" class="w-full border rounded px-3 py-2" @error('nome') border-red-500 @enderror>
                    @error('nome') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cognome *</label>
                    <input wire:model="cognome" type="text" class="w-full border rounded px-3 py-2">
                    @error('cognome') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Codice Fiscale</label>
                <input wire:model="codiceFiscale" type="text" maxlength="16" placeholder="RSSMRA80A01H501T" class="w-full border rounded px-3 py-2 uppercase">
            </div>
        @else
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Ragione Sociale *</label>
                <input wire:model="ragioneSociale" type="text" class="w-full border rounded px-3 py-2">
                @error('ragioneSociale') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Partita IVA *</label>
                    <input wire:model="piva" type="text" maxlength="11" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Codice Fiscale</label>
                    <input wire:model="codiceFiscale" type="text" maxlength="16" class="w-full border rounded px-3 py-2 uppercase">
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Email *</label>
                <input wire:model="email" type="email" class="w-full border rounded px-3 py-2">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">PEC</label>
                <input wire:model="pec" type="email" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Cellulare * <span class="text-xs text-gray-500">(usato per OTP)</span></label>
                <input wire:model="cellulare" type="tel" placeholder="+39 333 1234567" class="w-full border rounded px-3 py-2">
                @error('cellulare') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Telefono fisso</label>
                <input wire:model="telefono" type="tel" class="w-full border rounded px-3 py-2">
            </div>
        </div>
    </div>
    @endif

    {{-- ======================== STEP 2: INDIRIZZO ======================== --}}
    @if($step === 2)
    <div>
        <h2 class="text-xl font-semibold mb-4">Indirizzo di Installazione</h2>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Via / Piazza *</label>
                <input wire:model="via" type="text" class="w-full border rounded px-3 py-2">
                @error('via') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Civico *</label>
                <input wire:model="civico" type="text" class="w-full border rounded px-3 py-2">
                @error('civico') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Scala</label>
                <input wire:model="scala" type="text" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Piano</label>
                <input wire:model="piano" type="text" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Interno</label>
                <input wire:model="interno" type="text" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">CAP *</label>
                <input wire:model="cap" type="text" maxlength="5" class="w-full border rounded px-3 py-2">
                @error('cap') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Comune *</label>
                <input wire:model="comune" type="text" class="w-full border rounded px-3 py-2">
                @error('comune') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Provincia *</label>
                <input wire:model="provincia" type="text" maxlength="2" placeholder="MI" class="w-full border rounded px-3 py-2 uppercase">
                @error('provincia') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    @endif

    {{-- ======================== STEP 3: PIANO ======================== --}}
    @if($step === 3)
    <div>
        <h2 class="text-xl font-semibold mb-4">Selezione Piano</h2>
        <div class="grid grid-cols-1 gap-3">
            @foreach($this->availablePlans as $plan)
                <label class="cursor-pointer">
                    <input type="radio" wire:model="servicePlanId" value="{{ $plan->id }}" class="sr-only">
                    <div @class([
                        'border-2 rounded-lg p-4 transition-colors',
                        'border-blue-600 bg-blue-50' => $servicePlanId == $plan->id,
                        'border-gray-200 hover:border-blue-300' => $servicePlanId != $plan->id,
                    ])>
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg">{{ $plan->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $plan->carrier->label() }} — {{ $plan->technology }}</p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $plan->bandwidth_dl }} Mbps ↓ / {{ $plan->bandwidth_ul }} Mbps ↑
                                </p>
                                @if($plan->description)
                                    <p class="text-xs text-gray-500 mt-1">{{ $plan->description }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-blue-600">€ {{ number_format($plan->price_monthly, 2, ',', '.') }}</div>
                                <div class="text-xs text-gray-500">/mese + IVA</div>
                                @if($plan->activation_fee > 0)
                                    <div class="text-xs text-gray-500 mt-1">Attivazione: € {{ number_format($plan->activation_fee, 2, ',', '.') }}</div>
                                @endif
                                <div class="text-xs text-gray-500">Min {{ $plan->min_contract_months }} mesi</div>
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
        @error('servicePlanId') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
    </div>
    @endif

    {{-- ======================== STEP 4: PAGAMENTO ======================== --}}
    @if($step === 4)
    <div>
        <h2 class="text-xl font-semibold mb-4">Pagamento e Fatturazione</h2>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Metodo di Pagamento *</label>
            <select wire:model.live="paymentMethod" class="w-full border rounded px-3 py-2">
                @foreach($this->paymentMethods as $method)
                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                @endforeach
            </select>
        </div>

        @if($paymentMethod === 'sdd')
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">IBAN *</label>
                <input wire:model="iban" type="text" maxlength="34" placeholder="IT60X0542811101000000123456" class="w-full border rounded px-3 py-2 font-mono uppercase">
                @error('iban') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Ciclo di fatturazione *</label>
                <select wire:model="billingCycle" class="w-full border rounded px-3 py-2">
                    @foreach($this->billingCycles as $cycle)
                        <option value="{{ $cycle->value }}">{{ $cycle->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Giorno fatturazione * <span class="text-xs text-gray-500">(1-28)</span></label>
                <input wire:model="billingDay" type="number" min="1" max="28" class="w-full border rounded px-3 py-2">
                @error('billingDay') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    @endif

    {{-- ======================== STEP 5: RIEPILOGO ======================== --}}
    @if($step === 5)
    <div>
        <h2 class="text-xl font-semibold mb-4">Riepilogo Contratto</h2>
        <div class="bg-gray-50 rounded-lg p-4 mb-4 space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Cliente</h3>
                <p class="font-medium">
                    @if($customerType === 'privato') {{ $nome }} {{ $cognome }}
                    @else {{ $ragioneSociale }} @endif
                </p>
                <p class="text-sm text-gray-600">{{ $email }} | {{ $cellulare }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Indirizzo Installazione</h3>
                <p class="text-sm">{{ $via }} {{ $civico }}{{ $scala ? ', Sc. '.$scala : '' }}{{ $piano ? ', P. '.$piano : '' }}, {{ $cap }} {{ $comune }} ({{ $provincia }})</p>
            </div>
            @if($this->selectedPlan)
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Piano</h3>
                <p class="font-medium">{{ $this->selectedPlan->name }} — {{ $this->selectedPlan->carrier->label() }}</p>
                <p class="text-sm text-gray-600">{{ $this->selectedPlan->bandwidth_dl }} Mbps ↓ / {{ $this->selectedPlan->bandwidth_ul }} Mbps ↑</p>
            </div>
            <div class="border-t pt-3">
                <div class="flex justify-between text-sm"><span>Canone mensile (IVA esclusa)</span><span>€ {{ number_format($this->selectedPlan->price_monthly, 2, ',', '.') }}</span></div>
                @if($this->selectedPlan->activation_fee > 0)
                <div class="flex justify-between text-sm"><span>Costo attivazione</span><span>€ {{ number_format($this->selectedPlan->activation_fee, 2, ',', '.') }}</span></div>
                @endif
                <div class="flex justify-between font-bold text-blue-600 text-lg border-t mt-2 pt-2">
                    <span>Canone mensile + IVA 22%</span>
                    <span>€ {{ number_format($this->monthlyTotal, 2, ',', '.') }}</span>
                </div>
            </div>
            @endif
        </div>
        <div class="flex gap-3">
            <button wire:click="prepareContract" wire:loading.attr="disabled"
                    class="flex-1 bg-blue-600 text-white py-2 px-4 rounded font-semibold hover:bg-blue-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="prepareContract">Genera PDF e procedi alla Firma</span>
                <span wire:loading wire:target="prepareContract">Generazione in corso...</span>
            </button>
        </div>
        @if($contractId)
            <p class="mt-3 text-green-600 text-sm">✓ PDF generato. Clicca "Avanti" per la firma.</p>
        @endif
    </div>
    @endif

    {{-- ======================== STEP 6: FIRMA FEA ======================== --}}
    @if($step === 6)
    <div>
        @if($signatureSuccess)
            <div class="text-center py-8">
                <div class="text-5xl mb-4">✅</div>
                <h2 class="text-2xl font-bold text-green-600 mb-2">Contratto Firmato!</h2>
                <p class="text-gray-600">Il contratto è stato firmato con successo via FEA (Firma Elettronica Avanzata).</p>
                <p class="text-sm text-gray-500 mt-2">Una copia del documento firmato è stata salvata nel tuo archivio.</p>
                <a href="{{ route('contracts.index') }}" class="mt-6 inline-block bg-blue-600 text-white py-2 px-6 rounded">
                    Vai ai Contratti
                </a>
            </div>
        @else
            <h2 class="text-xl font-semibold mb-4">Firma Elettronica Avanzata</h2>
            <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4 text-sm">
                <strong>Come funziona la firma FEA?</strong><br>
                Invieremo un codice OTP a 6 cifre al cellulare del cliente ({{ $cellulare }}).
                Il cliente dovrà inserirlo per firmare il contratto digitalmente ai sensi dell'art. 26 eIDAS.
            </div>

            @if(!$otpSentTo)
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Canale invio OTP</label>
                    <select wire:model="otpChannel" class="w-full border rounded px-3 py-2">
                        <option value="sms">SMS</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>
                <button wire:click="sendOtp" wire:loading.attr="disabled"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded font-semibold hover:bg-blue-700">
                    <span wire:loading.remove wire:target="sendOtp">Invia Codice OTP</span>
                    <span wire:loading wire:target="sendOtp">Invio in corso...</span>
                </button>
            @else
                <p class="text-sm text-green-600 mb-4">✓ OTP inviato a {{ $otpSentTo }}</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Codice OTP *</label>
                    <input wire:model="otp" type="text" maxlength="6" placeholder="000000"
                           class="w-full border rounded px-3 py-2 text-center text-3xl font-mono tracking-widest">
                    @error('otp') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="sendOtp" class="flex-1 border border-gray-300 py-2 px-4 rounded text-sm">
                        Ri-invia OTP
                    </button>
                    <button wire:click="verifyOtp" wire:loading.attr="disabled"
                            class="flex-1 bg-green-600 text-white py-2 px-4 rounded font-semibold hover:bg-green-700">
                        <span wire:loading.remove wire:target="verifyOtp">Firma il Contratto</span>
                        <span wire:loading wire:target="verifyOtp">Verifica in corso...</span>
                    </button>
                </div>
            @endif
        @endif
    </div>
    @endif

    {{-- Navigazione step --}}
    @if(!$signatureSuccess)
    <div class="flex justify-between mt-8">
        @if($step > 1)
            <button wire:click="previousStep" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">
                ← Indietro
            </button>
        @else
            <div></div>
        @endif

        @if($step < 5)
            <button wire:click="nextStep" class="px-6 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700">
                Avanti →
            </button>
        @elseif($step === 5 && $contractId)
            <button wire:click="nextStep" class="px-6 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700">
                Procedi alla Firma →
            </button>
        @endif
    </div>
    @endif

</div>
