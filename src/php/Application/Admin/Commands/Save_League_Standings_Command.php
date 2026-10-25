<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Save_League_Standings_Command {
    public function __construct(
        public ?array $points_plus,
        public ?array $points_minus,
        public ?array $add_points,
        public ?array $num_done_matches,
        public ?array $num_won_matches,
        public ?array $num_draw_matches,
        public ?array $num_lost_matches,
        public ?array $team_id,
        public ?array $custom
    ) {
        Command_Validation::column( $this->points_plus, 'points_plus' );
        Command_Validation::column( $this->points_minus, 'points_minus' );
        Command_Validation::column( $this->add_points, 'add_points' );
        Command_Validation::column( $this->num_done_matches, 'num_done_matches' );
        Command_Validation::column( $this->num_won_matches, 'num_won_matches' );
        Command_Validation::column( $this->num_draw_matches, 'num_draw_matches' );
        Command_Validation::column( $this->num_lost_matches, 'num_lost_matches' );
        Command_Validation::column( $this->team_id, 'team_id' );
        Command_Validation::tree( $this->custom, 'custom' );
    }
}
