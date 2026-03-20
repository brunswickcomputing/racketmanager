<?php

namespace Racketmanager\Domain\Enums;

enum Fixture_Reset_Status: string {
    case SUCCESS_DIVISION_RESET   = 'division_reset';
    case SUCCESS_KNOCKOUT_RESET   = 'knockout_reset';
    case ERROR_NOT_FOUND          = 'not_found';
}
