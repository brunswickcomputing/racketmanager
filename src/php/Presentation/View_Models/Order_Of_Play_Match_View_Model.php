<?php
declare( strict_types=1 );

/**
 * Order of Play Match View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

namespace Racketmanager\Presentation\View_Models;

final readonly class Order_Of_Play_Match_View_Model {

    public function __construct(
        public int $id,
        public string $league_title,
        public string $home_match_title,
        public string $away_match_title,
        public bool $is_home_pending = false,
        public bool $is_away_pending = false,
        public ?string $time = null,
        public ?string $location = null,
        public int|string|null $winner = null,
        public int|string|null $home_team = null,
        public int|string|null $away_team = null,
    ) {
    }
}
