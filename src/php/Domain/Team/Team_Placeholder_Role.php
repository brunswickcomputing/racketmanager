<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Team;

enum Team_Placeholder_Role: string {
    case WINNER = 'winner';
    case LOSER  = 'loser';
}
