<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Delete_Club_Teams_Command {
    public function __construct(
        public array $team,
    ) {
        Command_Validation::ids( $this->team, 'team' );
    }
}
