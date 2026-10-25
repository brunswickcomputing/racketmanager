<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Edit_Competition_Season_Command {
    public function __construct(
        public ?int $competition_id,
        public ?string $season,
        public array $season_data,
        public array $form_data,
    ) {
    }
}
