<?php

namespace Modules\Maintenance\PurchaseOrders\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Maintenance\PurchaseOrders\Models\PurchaseOrder;
use Modules\Maintenance\PurchaseOrders\Models\PurchaseOrderItem;
use Modules\Maintenance\PurchaseOrders\Models\ReorderRule;
use Modules\Maintenance\PurchaseOrders\Enums\PurchaseOrderStatus;

class PurchaseOrderService
{
    /**
     * Check all reorder rules and auto-create POs where stock < min_stock_quantity.
     */
    public function checkReorderAlerts(): array
    {
        $created = [];

        $rules = ReorderRule::where('auto_order', true)->with('supplier')->get();

        foreach ($rules as $rule) {
            $currentStock = DB::table('inventory_items')
                ->where('inventory_model_id', $rule->inventory_model_id)
                ->where('status', 'available')
                ->count();

            if ($currentStock < $rule->min_stock_quantity) {
                $po = $this->createFromReorderRule($rule, $currentStock);
                $created[] = $po->id;

                Log::info("Auto-created PO {$po->id} for model {$rule->inventory_model_id} — stock={$currentStock} < min={$rule->min_stock_quantity}");
            }
        }

        return $created;
    }

    public function createFromReorderRule(ReorderRule $rule, int $currentStock): PurchaseOrder
    {
        return DB::transaction(function () use ($rule, $currentStock) {
            $po = PurchaseOrder::create([
                'supplier_id' => $rule->supplier_id,
                'status'      => PurchaseOrderStatus::Draft,
                'notes'       => "Auto-generated: stock={$currentStock}, min={$rule->min_stock_quantity}",
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id'  => $po->id,
                'inventory_model_id' => $rule->inventory_model_id,
                'quantity_ordered'   => $rule->reorder_quantity,
                'quantity_received'  => 0,
            ]);

            return $po;
        });
    }

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $po = PurchaseOrder::create($data);

            foreach ($items as $item) {
                $item['purchase_order_id'] = $po->id;
                PurchaseOrderItem::create($item);
            }

            return $po->load('items');
        });
    }

    public function send(PurchaseOrder $po): PurchaseOrder
    {
        $po->update([
            'status'  => PurchaseOrderStatus::Sent,
            'sent_at' => now(),
        ]);
        return $po;
    }

    /**
     * Record received quantities. Mark PO as received when all items are fully received.
     */
    public function receive(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $itemId => $qty) {
                $item = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('id', $itemId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $item->increment('quantity_received', $qty);
            }

            $po->refresh();
            $allReceived = $po->items->every(fn($i) => $i->quantity_received >= $i->quantity_ordered);

            $po->update([
                'status'      => $allReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived,
                'received_at' => $allReceived ? now() : null,
            ]);

            return $po;
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => PurchaseOrderStatus::Cancelled]);
        return $po;
    }
}
