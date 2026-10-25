<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Delete_Constitution_Teams_Command {
    public function __construct(
        public string $latest_season,
        public array $table,
        public array $team_id,
        public array $league_id
    ) {
        Command_Validation::column( $this->table, 'table' );
        Command_Validation::column( $this->team_id, 'team_id' );
        Command_Validation::column( $this->league_id, 'league_id' );
    }
}
