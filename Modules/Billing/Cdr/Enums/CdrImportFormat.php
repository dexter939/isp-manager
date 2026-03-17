<?php
namespace Modules\Billing\Cdr\Enums;
enum CdrImportFormat: string {
    case Asterisk = 'asterisk';
    case Yeastar  = 'yeastar';
    case Generic  = 'generic';
}
