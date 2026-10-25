<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Delete_Club_Players_Command {
    public function __construct(
        public array $clubPlayer,
        public int $user_id,
    ) {
        Command_Validation::ids( $this->clubPlayer, 'clubPlayer' );
    }
}
