<?php

namespace Modules\Maintenance\InventoryRma\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Maintenance\InventoryRma\Enums\ItemStatus;

class InventoryLifecycleTest extends TestCase
{
    public function test_item_status_deployed_value(): void
    {
        $this->assertEquals('deployed', ItemStatus::Deployed->value);
    }

    public function test_item_status_in_rma_value(): void
    {
        $this->assertEquals('in_rma', ItemStatus::InRma->value);
    }
}
