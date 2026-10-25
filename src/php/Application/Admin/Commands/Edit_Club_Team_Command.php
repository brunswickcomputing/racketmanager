<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Edit_Club_Team_Command {
    public function __construct(
        public ?string $team_id,
        public int $club_id,
        public string $team_type,
    ) {
    }
}
