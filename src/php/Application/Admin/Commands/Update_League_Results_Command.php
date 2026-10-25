<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Update_League_Results_Command {
    public function __construct(
        public string $season,
        public array $matches,
        public array $home_points,
        public array $away_points,
        public array $custom
    ) {
        Command_Validation::ids( $this->matches, 'matches' );
        Command_Validation::column( $this->home_points, 'home_points' );
        Command_Validation::column( $this->away_points, 'away_points' );
        Command_Validation::tree( $this->custom, 'custom' );
    }
}
