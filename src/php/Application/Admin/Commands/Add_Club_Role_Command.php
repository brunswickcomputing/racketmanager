<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Add_Club_Role_Command {
    public function __construct(
        public ?int $club_id,
        public ?int $role_id,
        public ?int $user_id,
    ) {
    }
}
