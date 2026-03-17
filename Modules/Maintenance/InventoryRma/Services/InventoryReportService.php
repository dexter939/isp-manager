<?php
namespace Modules\Maintenance\InventoryRma\Services;
use Illuminate\Support\Facades\DB;
class InventoryReportService {
    public function getDefectRateByModel(): array {
        return DB::table('inventory_items as ii')
            ->join('inventory_models as im', 'ii.model_id', '=', 'im.id')
            ->select('im.id','im.brand','im.model',DB::raw('COUNT(ii.id) as total_units'),DB::raw("COUNT(CASE WHEN ii.lifecycle_status IN ('rma_pending','rma_in_transit','rma_approved') THEN 1 END) as rma_count"),DB::raw("ROUND(COUNT(CASE WHEN ii.lifecycle_status IN ('rma_pending','rma_in_transit','rma_approved') THEN 1 END) * 100.0 / NULLIF(COUNT(ii.id), 0), 2) as defect_rate_percent"))
            ->groupBy('im.id','im.brand','im.model')
            ->orderByDesc('defect_rate_percent')
            ->get()->toArray();
    }
    public function getRmaStatusReport(): array {
        $openRmas = DB::table('rma_requests as r')->select('r.supplier_id',DB::raw('COUNT(*) as open_count'),DB::raw('AVG(EXTRACT(EPOCH FROM (NOW() - r.created_at))/86400) as avg_days_open'))->whereNull('r.resolved_at')->groupBy('r.supplier_id')->get();
        $pendingShip = DB::table('rma_requests')->where('reason','!=','credit')->whereNull('shipped_at')->whereNull('resolved_at')->count();
        return ['open_rmas_by_supplier'=>$openRmas,'pending_shipment'=>$pendingShip];
    }
    public function getStockLevels(): array {
        return DB::table('inventory_items as ii')->join('inventory_models as im', 'ii.model_id', '=', 'im.id')->select('im.id','im.brand','im.model',DB::raw("COUNT(CASE WHEN ii.lifecycle_status='in_stock' THEN 1 END) as in_stock"),DB::raw("COUNT(CASE WHEN ii.lifecycle_status='deployed' THEN 1 END) as deployed"),DB::raw('COUNT(*) as total'))->groupBy('im.id','im.brand','im.model')->get()->toArray();
    }
}
