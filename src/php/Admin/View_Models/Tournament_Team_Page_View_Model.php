<?php
/**
 * Tournament team page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

readonly final class Tournament_Team_Page_View_Model {

    /**
     * @param object $team
     * @param object|null $league
     * @param object|null $tournament
     * @param array $clubs
     * @param string $form_title
     * @param string $form_action
     * @param string $file
     * @param string $season
     * @param array $match_days
     */
    public function __construct(
        public object $team,
        public ?object $league,
        public ?object $tournament,
        public array $clubs,
        public string $form_title,
        public string $form_action,
        public string $file,
        public string $season = '',
        public array $match_days = array(),
    ) {
    }

    /**
     * Export variables expected by templates/admin/includes/teams/team.php or player-team.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'team'        => $this->team,
            'league'      => $this->league,
            'tournament'  => $this->tournament,
            'clubs'       => $this->clubs,
            'form_title'  => $this->form_title,
            'form_action' => $this->form_action,
            'season'      => $this->season,
            'match_days'  => $this->match_days,
            'edit'        => true, // This controller is only for editing existing teams in this context
            'club_id'     => isset( $this->team->club_id ) ? $this->team->club_id : '',
            'league_id'   => isset( $this->league->id ) ? $this->league->id : '',
        );
    }
}
