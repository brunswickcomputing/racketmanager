<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View Model for Result Checker presentation
 */
readonly class Results_Checker_View_Model {
    /**
     * @param int $id ID.
     * @param string $formatted_date Formatted date.
     * @param string $match_link Match link.
     * @param string $match_title Match title.
     * @param string $team_title Team title.
     * @param string $player_link Player link.
     * @param string $player_name Player name.
     * @param string $description Description.
     * @param string $status_desc Status description.
     * @param string $tooltip Tooltip information.
     * @param bool $show_status Whether to show status.
     */
    public function __construct(
        public int $id, public string $formatted_date, public string $match_link, public string $match_title, public string $team_title, public string $player_link, public string $player_name, public string $description, public string $status_desc, public string $tooltip, public bool $show_status,
    ) {
    }
}
