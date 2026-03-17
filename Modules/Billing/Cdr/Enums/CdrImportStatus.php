<?php
namespace Modules\Billing\Cdr\Enums;
enum CdrImportStatus: string {
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
}
