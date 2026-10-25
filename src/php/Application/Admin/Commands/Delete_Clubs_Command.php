<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Delete_Clubs_Command {
    public function __construct(
        public array $club,
    ) {
        Command_Validation::ids( $this->club, 'club' );
    }
}
