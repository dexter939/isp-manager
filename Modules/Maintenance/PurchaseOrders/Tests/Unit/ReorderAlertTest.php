<?php

namespace Modules\Maintenance\PurchaseOrders\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Maintenance\PurchaseOrders\Enums\PurchaseOrderStatus;

class ReorderAlertTest extends TestCase
{
    public function test_purchase_order_status_draft_value(): void
    {
        $this->assertEquals('draft', PurchaseOrderStatus::Draft->value);
    }

    public function test_purchase_order_status_received_value(): void
    {
        $this->assertEquals('received', PurchaseOrderStatus::Received->value);
    }
}
