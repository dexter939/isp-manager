<?php
namespace Modules\Billing\Proforma\Http\Controllers;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use Modules\Billing\Proforma\Services\ProformaService;
use Illuminate\Support\Facades\DB;
class ProformaController extends ApiController {
    public function __construct(private ProformaService $service) {}
    public function index(): JsonResponse {
        return response()->json($this->service->listPendingProformas());
    }
    public function convert(string $id): JsonResponse {
        $proforma = DB::table('invoices')->where('id', $id)->where('invoice_type','proforma')->firstOrFail();
        $invoice  = $this->service->convertToInvoice($proforma);
        return response()->json($invoice);
    }
}
