<?php
return [
    'default_commission_rate' => (float) env('AGENT_DEFAULT_COMMISSION_RATE', 10.00),
    'liquidation_day'         => (int) env('AGENT_LIQUIDATION_DAY', 1),
    'code_prefix'             => 'AGT',
];
