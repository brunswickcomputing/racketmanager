<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Rank_League_Teams_Command {
    public function __construct(
        public string $type,
        public array $table_id,
        public ?array $rank,
        public ?array $rating_points,
        public bool $js_active
    ) {
        Command_Validation::ids( $this->table_id, 'table_id' );
        Command_Validation::column( $this->rank, 'rank' );
        Command_Validation::column( $this->rating_points, 'rating_points' );
        Command_Validation::choice( $this->type, [ 'manual', 'random', 'ratings' ] );
        $sort_values = 'ratings' === $this->type ? $this->rating_points : ( 'manual' === $this->type && ! $this->js_active ? $this->rank : null );
        if ( $this->table_id && null !== $sort_values && ( 'ratings' === $this->type || $sort_values ) && count( $sort_values ) !== count( $this->table_id ) ) {
            throw new \InvalidArgumentException( 'Ranking values must match selected teams' );
        }
    }
}
