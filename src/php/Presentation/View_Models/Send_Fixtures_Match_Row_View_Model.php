<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single match row for templates/email/send-fixtures.php.
 */
final readonly class Send_Fixtures_Match_Row_View_Model {
    /**
     * Initialises the match display data.
     *
     * @param string          $round_display Display text for the round.
     * @param string          $date_display Display text for the date.
     * @param string          $day_display Display text for the day.
     * @param string          $time_display Display text for the time.
     * @param string          $home_team_title Display title for the home team.
     * @param string          $away_team_title Display title for the away team.
     * @param bool            $is_home Whether the current context represents the home team.
     * @param bool            $is_away Whether the current context represents the away team.
     * @param int|null        $id Match identifier.
     * @param int|null        $match_day Match day identifier or number.
     * @param string|null     $date Match date.
     * @param string|null     $start_time Match start time.
     * @param int|string|null $home_team Home team identifier or value.
     * @param int|string|null $away_team Away team identifier or value.
     * @param array|null      $teams Related teams data.
     *
     * @return void
     */
    public function __construct(
        public string $round_display = '',
        public string $date_display = '',
        public string $day_display = '',
        public string $time_display = '',
        public string $home_team_title = '',
        public string $away_team_title = '',
        public bool $is_home = false,
        public bool $is_away = false,
        public ?int $id = null,
        public ?int $match_day = null,
        public ?string $date = null,
        public ?string $start_time = null,
        public int|string|null $home_team = null,
        public int|string|null $away_team = null,
        public ?array $teams = null,
    ) {}
}
