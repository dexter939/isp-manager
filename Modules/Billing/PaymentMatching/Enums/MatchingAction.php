<?php
namespace Modules\Billing\PaymentMatching\Enums;
enum MatchingAction: string {
    case MatchOldest  = 'match_oldest';
    case MatchNewest  = 'match_newest';
    case AddToCredit  = 'add_to_credit';
    case Skip         = 'skip';
}
