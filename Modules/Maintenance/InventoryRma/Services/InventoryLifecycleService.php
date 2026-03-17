<?php
namespace Modules\Maintenance\InventoryRma\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Maintenance\InventoryRma\Models\RmaRequest;
class InventoryLifecycleService {
    public function deploy(string $itemId, string $customerId, string $contractId, string $technicianId): void {
        DB::transaction(function () use ($itemId, $customerId, $contractId, $technicianId) {
            $item = DB::table('inventory_items')->where('id',$itemId)->lockForUpdate()->first();
            if (!$item) throw new \RuntimeException("Item {$itemId} not found");
            DB::table('inventory_items')->where('id',$itemId)->update(['lifecycle_status'=>'deployed','location_type'=>'customer','customer_id'=>$customerId,'contract_id'=>$contractId,'deployed_at'=>now()]);
            DB::table('inventory_movements')->insert(['item_id'=>$itemId,'from_status'=>$item->lifecycle_status ?? 'in_stock','to_status'=>'deployed','from_location_type'=>$item->location_type ?? 'warehouse','to_location_type'=>'customer','performed_by'=>$technicianId,'reason'=>'Deployment to customer','created_at'=>now()]);
            Log::info("InventoryLifecycle: item {$itemId} deployed to customer {$customerId}");
        });
    }
    public function openRma(string $itemId, string $reason, string $description, ?string $supplierId = null): RmaRequest {
        return DB::transaction(function () use ($itemId, $reason, $description, $supplierId) {
            $item = DB::table('inventory_items')->where('id',$itemId)->lockForUpdate()->first();
            if (!$item) throw new \RuntimeException("Item {$itemId} not found");
            DB::table('inventory_items')->where('id',$itemId)->update(['lifecycle_status'=>'rma_pending','rma_opened_at'=>now(),'rma_reason'=>$reason]);
            $rma = RmaRequest::create(['item_id'=>$itemId,'supplier_id'=>$supplierId,'reason'=>$reason,'description'=>$description]);
            DB::table('inventory_movements')->insert(['item_id'=>$itemId,'from_status'=>$item->lifecycle_status ?? 'deployed','to_status'=>'rma_pending','from_location_type'=>$item->location_type ?? 'customer','to_location_type'=>'supplier','performed_by'=>auth()->id() ?? $item->customer_id,'reason'=>"RMA: {$reason}",'created_at'=>now()]);
            return $rma;
        });
    }
    public function resolveRma(RmaRequest $rma, string $resolution, ?string $replacementItemId = null): void {
        DB::transaction(function () use ($rma, $resolution, $replacementItemId) {
            $rma->update(['resolution'=>$resolution,'resolved_at'=>now(),'replacement_item_id'=>$replacementItemId]);
            $finalStatus = match($resolution) { 'replaced','repaired' => 'rma_approved', 'credit' => 'decommissioned', 'rejected' => 'in_stock', default => 'decommissioned' };
            DB::table('inventory_items')->where('id',$rma->item_id)->update(['lifecycle_status'=>$finalStatus]);
            if ($replacementItemId) {
                $originalItem = DB::table('inventory_items')->find($rma->item_id);
                if ($originalItem) {
                    $this->deploy($replacementItemId, $originalItem->customer_id, $originalItem->contract_id, auth()->id() ?? $rma->item_id);
                }
            }
        });
    }
}
