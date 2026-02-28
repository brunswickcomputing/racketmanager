<?php
/**
 * Tournament setup admin page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Services\Validator\Validator_Tournament;

readonly final class Tournament_Setup_Page_View_Model {

    /**
     * @param object $tournament
     * @param string $season
     * @param array $match_dates
     * @param int|null $match_count
     * @param object|null $league
     * @param Validator_Tournament $validator
     */
    public function __construct(
        public object $tournament,
        public string $season,
        public array $match_dates,
        public ?int $match_count,
        public ?object $league,
        public Validator_Tournament $validator,
    ) {
    }

    /**
     * Export variables expected by templates/admin/tournament/setup.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'tournament'  => $this->tournament,
            'season'      => $this->season,
            'match_dates' => $this->match_dates,
            'match_count' => $this->match_count,
            'league'      => $this->league,
            'validator'   => $this->validator,
        );
    }
}
