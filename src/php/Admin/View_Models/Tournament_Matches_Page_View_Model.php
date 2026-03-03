<?php
/**
 * Tournament matches page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Tournament_Matches_Page_View_Model {

    /**
     * @param array<int,object> $matches
     * @param array<int,object> $teams
     */
    public function __construct(
        public object $league,
        public object $tournament,
        public object $competition,
        public string $season,
        public string $form_title,
        public string $submit_title,
        public array $matches,
        public bool $edit,
        public bool $bulk,
        public bool $is_finals,
        public string $mode,
        public array $teams,
        public bool $single_cup_game,
        public int $max_matches,
        public string $final_key,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'league'          => $this->league,
            'tournament'      => $this->tournament,
            'competition'     => $this->competition,
            'season'          => $this->season,
            'form_title'      => $this->form_title,
            'submit_title'    => $this->submit_title,
            'matches'         => $this->matches,
            'edit'            => $this->edit,
            'bulk'            => $this->bulk,
            'is_finals'       => $this->is_finals,
            'mode'            => $this->mode,
            'teams'           => $this->teams,
            'single_cup_game' => $this->single_cup_game,
            'max_matches'     => $this->max_matches,
            'final_key'       => $this->final_key,
        );
    }
}
