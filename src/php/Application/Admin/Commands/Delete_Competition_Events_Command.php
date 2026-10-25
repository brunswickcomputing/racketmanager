<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Delete_Competition_Events_Command {
    public function __construct(
        public array $event,
    ) {
        Command_Validation::ids( $this->event, 'event' );
    }
}
