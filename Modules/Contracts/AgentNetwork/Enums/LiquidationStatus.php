<?php
namespace Modules\Contracts\AgentNetwork\Enums;
enum LiquidationStatus: string {
    case Draft    = 'draft';
    case Approved = 'approved';
    case Paid     = 'paid';
}
