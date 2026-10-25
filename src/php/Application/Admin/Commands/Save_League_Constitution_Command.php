<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Save_League_Constitution_Command {
    public function __construct(
        public bool $js_active,
        public ?string $latest_season,
        public string $constitutionAction,
        public array $table_id,
        public array $team_id,
        public array $league_id,
        public array $rank,
        public array $status,
        public array $profile
    ) {
        Command_Validation::column( $this->table_id, 'table_id' );
        Command_Validation::column( $this->team_id, 'team_id' );
        Command_Validation::column( $this->league_id, 'league_id' );
        Command_Validation::column( $this->rank, 'rank' );
        Command_Validation::column( $this->status, 'status' );
        Command_Validation::column( $this->profile, 'profile' );
    }
}
