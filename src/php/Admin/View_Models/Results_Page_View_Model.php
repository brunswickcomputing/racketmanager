<?php
declare( strict_types=1 );
/**
 * Results page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

/**
 * Class Results_Page_View_Model
 *
 * Holds state for the results administration screen.
 */
final readonly class Results_Page_View_Model {

    /**
     * @param string                           $tab                           Active tab slug.
     * @param Admin_Results_Match_View_Model[] $pending_results_matches       Matches with results pending.
     * @param Admin_Results_Match_View_Model[] $awaiting_confirmation_matches Matches awaiting confirmation.
     * @param Admin_Results_Match_View_Model[] $challenged_results_matches    Challenged matches.
     * @param Admin_Results_Match_View_Model[] $approved_results_matches      Approved matches.
     * @param Admin_Results_Match_View_Model[] $postponed_matches             Postponed matches.
     * @param Admin_Results_Match_View_Model[] $tournament_results_matches    Tournament matches.
     * @param string                           $season_select                 Selected season filter.
     * @param int|string                       $competition_select            Selected competition filter.
     * @param int|string                       $event_select                  Selected event filter.
     * @param string                           $results_check_filter          Selected results checker status filter.
     * @param array                            $results_checkers              Presented results checker warnings.
     * @param array                            $competitions                  Available competitions for filter.
     * @param array                            $events                        Available events for filter.
     * @param array                            $seasons                       Available seasons for filter.
     */
    public function __construct(
        public string $tab = 'resultschecker',
        public array $pending_results_matches = array(),
        public array $awaiting_confirmation_matches = array(),
        public array $challenged_results_matches = array(),
        public array $approved_results_matches = array(),
        public array $postponed_matches = array(),
        public array $tournament_results_matches = array(),
        public string $season_select = '',
        public int|string $competition_select = '',
        public int|string $event_select = '',
        public string $results_check_filter = 'outstanding',
        public array $results_checkers = array(),
        public array $competitions = array(),
        public array $events = array(),
        public array $seasons = array(),
    ) {
    }

    /**
     * Convert to template variables array.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'tab'                           => $this->tab,
            'pending_results_matches'       => $this->pending_results_matches,
            'awaiting_confirmation_matches' => $this->awaiting_confirmation_matches,
            'challenged_results_matches'    => $this->challenged_results_matches,
            'approved_results_matches'      => $this->approved_results_matches,
            'postponed_matches'             => $this->postponed_matches,
            'tournament_results_matches'    => $this->tournament_results_matches,
            'season_select'                 => $this->season_select,
            'competition_select'            => $this->competition_select,
            'event_select'                  => $this->event_select,
            'results_check_filter'          => $this->results_check_filter,
            'results_checkers'              => $this->results_checkers,
            'competitions'                  => $this->competitions,
            'events'                        => $this->events,
            'seasons'                       => $this->seasons,
        );
    }
}
