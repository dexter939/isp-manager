<?php
namespace Modules\Contracts\DuplicateChecker\Services;
use Illuminate\Support\Facades\DB;
class CustomerDuplicateChecker {
    public function checkEmail(string $email, ?string $excludeCustomerId = null): array {
        $query = DB::table('customers')->select('id','name','customer_code')->where('email', $email)->whereNull('deleted_at');
        if ($excludeCustomerId) $query->where('id', '!=', $excludeCustomerId);
        return $query->get()->toArray();
    }
    public function checkPhone(string $phone, ?string $excludeCustomerId = null): array {
        $normalized = $this->normalizePhone($phone);
        if (!$normalized) return [];
        $query = DB::table('customers')->select('id','name','customer_code')->where(function ($q) use ($normalized) {
            $q->where(DB::raw("regexp_replace(phone, '[^0-9]', '', 'g')"), 'like', "%{$normalized}%")
              ->orWhere(DB::raw("regexp_replace(mobile, '[^0-9]', '', 'g')"), 'like', "%{$normalized}%");
        })->whereNull('deleted_at');
        if ($excludeCustomerId) $query->where('id', '!=', $excludeCustomerId);
        return $query->get()->toArray();
    }
    public function normalizePhone(string $phone): string {
        // Remove all non-digits
        $digits = preg_replace('/\D/', '', $phone);
        // Remove Italian country code +39
        if (str_starts_with($digits, '39') && strlen($digits) > 10) {
            $digits = substr($digits, 2);
        }
        // Remove leading 0 for mobile numbers
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = ltrim($digits, '0');
        }
        return $digits;
    }
}
