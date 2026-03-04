<?php
/**
 * Tournament Plan Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Services\Validator\Validator_Tournament;

final readonly class Tournament_Plan_Page_View_Model {

    /**
     * @param object $tournament
     * @param array $final_matches
     * @param string $tab
     * @param array $order_of_play
     * @param Validator_Tournament|null $validator Legacy/BC only
     */
    public function __construct(
        public object $tournament,
        public array $final_matches,
        public string $tab,
        public array $order_of_play,
        public Error_Bag $errors,
        public ?Validator_Tournament $validator = null,
    ) {
    }

    /**
     * Export variables expected by templates/admin/tournament/plan.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'tournament'    => $this->tournament,
            'final_matches' => $this->final_matches,
            'tab'           => $this->tab,
            'order_of_play' => $this->order_of_play,
            'errors'        => $this->errors,
            'validator'     => $this->validator,
        );
    }
}
