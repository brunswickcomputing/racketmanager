<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use InvalidArgumentException;
use Racketmanager\Services\Admin\League\Match_Date_Round_Validator;

final readonly class Set_League_Match_Dates_Command {
    /** @var list<string>|null */
    public ?array $match_dates;

    public function __construct(
        public ?int $competition_id,
        public ?int $season,
        ?array $rounds
    ) {
        if ( ! $competition_id || ! $season || null === $rounds ) {
            $this->match_dates = null;
            return;
        }
        Command_Validation::tree( $rounds, 'rounds' );
        $validated = Match_Date_Round_Validator::validate( $rounds );
        if ( ! $validated['valid'] ) {
            throw new InvalidArgumentException( $validated['message'] );
        }
        $this->match_dates = $validated['dates'];
    }
}
