<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Schedule_Club_Player_Ratings_Command {
    public function __construct(
        public ?int $club_id,
    ) {
    }
}
