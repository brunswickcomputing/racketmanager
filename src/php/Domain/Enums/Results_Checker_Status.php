<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums;

/**
 * Enum for Result Checker statuses
 */
enum Results_Checker_Status: int {
    case OUTSTANDING = 0;
    case APPROVED    = 1;
    case HANDLED     = 2;
}
