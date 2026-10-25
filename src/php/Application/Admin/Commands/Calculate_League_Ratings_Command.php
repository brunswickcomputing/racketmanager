<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Calculate_League_Ratings_Command {
    public function __construct(
        public ?int $competition_id,
        public ?int $season
    ) {

    }
}
