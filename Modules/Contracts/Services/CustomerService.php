<?php

declare(strict_types=1);

namespace Modules\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Contracts\Enums\CustomerStatus;
use Modules\Contracts\Enums\CustomerType;
use Modules\Contracts\Models\Customer;

class CustomerService
{
    /**
     * Crea un nuovo cliente con validazione CF/PIVA.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $tenantId): Customer
    {
        $data['tenant_id'] = $tenantId;
        $data['status']    = CustomerStatus::Prospect->value;

        $this->validateFiscalData($data);

        return Customer::create($data);
    }

    /**
     * Aggiorna i dati di un cliente.
     *
     * @param array<string, mixed> $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $this->validateFiscalData(array_merge($customer->toArray(), $data));
        $customer->update($data);

        return $customer->fresh();
    }

    /**
     * Ricerca clienti con paginazione.
     *
     * @return LengthAwarePaginator<Customer>
     */
    public function search(int $tenantId, ?string $query = null, ?CustomerStatus $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $q = Customer::where('tenant_id', $tenantId)
            ->with(['contracts' => fn($q) => $q->active()])
            ->orderBy('created_at', 'desc');

        if ($query) {
            $q->search($query);
        }

        if ($status) {
            $q->where('status', $status->value);
        }

        return $q->paginate($perPage);
    }

    /**
     * Attiva il cliente (da prospect → active).
     * Chiamato quando il primo contratto viene firmato.
     */
    public function activate(Customer $customer): void
    {
        if ($customer->status === CustomerStatus::Prospect) {
            $customer->update(['status' => CustomerStatus::Active->value]);
        }
    }

    /**
     * Sospende il cliente (morosità grave).
     */
    public function suspend(Customer $customer): void
    {
        $customer->update(['status' => CustomerStatus::Suspended->value]);
    }

    /**
     * Valida CF e PIVA con algoritmo di controllo italiano.
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    private function validateFiscalData(array $data): void
    {
        $type = CustomerType::from($data['type'] ?? 'privato');

        if (!empty($data['codice_fiscale'])) {
            if (!$this->isValidCodiceFiscale($data['codice_fiscale'])) {
                throw new \InvalidArgumentException('Codice fiscale non valido: ' . substr($data['codice_fiscale'], 0, 3) . '...');
            }
        }

        if ($type === CustomerType::Azienda && !empty($data['piva'])) {
            if (!$this->isValidPartitaIva($data['piva'])) {
                throw new \InvalidArgumentException('Partita IVA non valida.');
            }
        }
    }

    /**
     * Algoritmo di controllo Codice Fiscale italiano.
     * Supporta sia persone fisiche (16 char alfanumerico) che giuridiche (11 digit).
     */
    private function isValidCodiceFiscale(string $cf): bool
    {
        $cf = strtoupper(trim($cf));

        // CF numerico (persone giuridiche) = stessa struttura P.IVA
        if (ctype_digit($cf)) {
            return $this->isValidPartitaIva($cf);
        }

        if (strlen($cf) !== 16) {
            return false;
        }

        $odd  = [1,0,5,7,9,13,15,17,19,21,2,4,18,20,11,3,6,8,12,14,16,10,22,25,24,23];
        $even = [0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,25];

        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $char = ord($cf[$i]);
            $val  = ($char >= 48 && $char <= 57) ? $char - 48 : $char - 55;
            $sum += ($i % 2 === 0) ? $odd[$val] : $even[$val];
        }

        return (ord($cf[15]) - 65) === ($sum % 26);
    }

    /**
     * Algoritmo di controllo Partita IVA italiana (Luhn mod 11).
     */
    private function isValidPartitaIva(string $piva): bool
    {
        $piva = preg_replace('/\s/', '', $piva);

        if (strlen($piva) !== 11 || !ctype_digit($piva)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i <= 9; $i++) {
            $n = (int) $piva[$i];
            if ($i % 2 === 0) {
                $sum += $n;
            } else {
                $t = $n * 2;
                $sum += ($t > 9) ? $t - 9 : $t;
            }
        }

        return ((10 - ($sum % 10)) % 10) === (int) $piva[10];
    }
}
