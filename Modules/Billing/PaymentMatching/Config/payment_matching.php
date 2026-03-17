<?php
return [
    'system_rules' => [
        ['name' => 'Simbolo variabile = numero fattura', 'criteria' => [['field'=>'variable_symbol','operator'=>'equals','match_against'=>'invoice_number','value'=>null]], 'action' => 'match_oldest'],
        ['name' => 'Simbolo variabile = numero cliente', 'criteria' => [['field'=>'variable_symbol','operator'=>'equals','match_against'=>'customer_number','value'=>null]], 'action' => 'match_oldest'],
        ['name' => 'IBAN pagante = IBAN cliente', 'criteria' => [['field'=>'iban','operator'=>'equals','match_against'=>'assigned_variable_symbol','value'=>null]], 'action' => 'match_oldest'],
        ['name' => 'Importo esatto = fattura più vecchia', 'criteria' => [['field'=>'amount','operator'=>'equals','match_against'=>'invoice_number','value'=>null]], 'action' => 'match_oldest'],
    ],
];
