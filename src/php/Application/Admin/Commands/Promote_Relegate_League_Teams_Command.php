<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Promote_Relegate_League_Teams_Command {
    public function __construct(
        public bool $js_active,
        public int $latest_season,
        public ?int $event_id,
        public array $table_id,
        public array $team_id,
        public array $league_id,
        public array $rank,
        public array $old_rank,
        public array $status
    ) {
        Command_Validation::column( $this->table_id, 'table_id' );
        Command_Validation::column( $this->team_id, 'team_id' );
        Command_Validation::column( $this->league_id, 'league_id' );
        Command_Validation::column( $this->rank, 'rank' );
        Command_Validation::column( $this->old_rank, 'old_rank' );
        Command_Validation::column( $this->status, 'status' );
    }
}
