<?php
declare( strict_types=1 );

/**
 * Order of Play Match View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

namespace Racketmanager\Presentation\View_Models;

final class Order_Of_Play_Match_View_Model {

    /**
     * Backward-compatible property hook for legacy callers reading $vm->league_title.
     */
    public string $league_title {
        final get => $this->league_name;
    }

    public function __construct(
        public readonly int $id,
        public readonly string $league_name,
        public readonly string $home_match_title,
        public readonly string $away_match_title,
        public readonly bool $is_home_pending = false,
        public readonly bool $is_away_pending = false,
        public readonly ?string $time = null,
        public readonly ?string $location = null,
        public readonly int|string|null $winner = null,
        public readonly int|string|null $home_team = null,
        public readonly int|string|null $away_team = null,
    ) {
    }
}
