<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Delete_League_Matches_Command {
    public function __construct(
        public array $match
    ) {
        Command_Validation::ids( $this->match, 'match' );
    }
}
