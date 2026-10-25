<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Edit_Club_Admin_Command {
    public function __construct(
        public ?int $club_id = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $shortcode = null,
        public ?string $address = null,
        public ?string $contactno = null,
        public ?string $website = null,
        public ?int $founded = null,
        public ?string $facilities = null,
        public ?int $match_secretary_id = null,
        public ?string $match_secretary_name = null,
        public ?string $match_secretary_contactno = null,
        public ?string $match_secretary_email = null,
    ) {
    }
}
