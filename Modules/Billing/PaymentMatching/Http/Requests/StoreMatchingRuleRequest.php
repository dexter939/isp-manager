<?php
namespace Modules\Billing\PaymentMatching\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMatchingRuleRequest extends FormRequest {
    public function authorize(): bool { return $this->user()?->hasRole(['admin','billing']); }
    public function rules(): array {
        return ['name'=>'required|string|max:255','priority'=>'integer|min:1','criteria'=>'required|array|min:1','criteria.*.field'=>'required|in:variable_symbol,specific_symbol,payment_note,iban,amount','criteria.*.operator'=>'required|in:equals,contains,starts_with,greater_than,less_than','criteria.*.match_against'=>'required|in:invoice_number,customer_number,assigned_variable_symbol,literal','criteria.*.value'=>'nullable|string','action'=>'required|in:match_oldest,match_newest,add_to_credit,skip','action_note'=>'nullable|string|max:500'];
    }
}
