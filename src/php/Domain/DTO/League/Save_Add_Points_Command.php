<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\League;

/**
 * Command to adjust/amend additional points for a league entry.
 */
readonly class Save_Add_Points_Command {
    public function __construct(
        public int $table_id,
        public int $points = 0
    ) {}

    public static function from_array( array $data ): self {
        return new self(
            table_id: isset( $data['table_id'] ) ? (int) $data['table_id'] : 0,
            points: isset( $data['points'] ) ? (int) $data['points'] : 0
        );
    }

    public static function from_post( array $post ): self {
        return self::from_array( $post );
    }
}
