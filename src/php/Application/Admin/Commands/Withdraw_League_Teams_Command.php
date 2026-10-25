<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Withdraw_League_Teams_Command {
    public function __construct(
        public ?string $season,
        public array $team
    ) {
        Command_Validation::ids( $this->team, 'team' );
    }
}
