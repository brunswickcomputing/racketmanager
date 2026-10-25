<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Generate_Constitution_Matches_Command {
    public function __construct(
        public ?int $event_id,
        public ?int $latest_season
    ) {

    }
}
