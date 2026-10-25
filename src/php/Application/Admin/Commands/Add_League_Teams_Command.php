<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Add_League_Teams_Command {
    public function __construct(
        public ?int $league_id,
        public ?int $season,
        public array $team,
        public bool $has_event
    ) {
        Command_Validation::ids( $this->team, 'team' );
    }
}
