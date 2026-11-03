<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single match row for templates/includes/matches-teams-match.php.
 */
final readonly class Team_Match_Row_View_Model {
    /**
     * @param int|string|null $id id.
     * @param bool            $is_selected is selected.
     * @param bool            $show_header show header.
     * @param string          $league_title league title.
     * @param string|null     $match_link match link.
     * @param string|null     $final_name final name.
     * @param int|null        $match_day match day.
     * @param string|null     $match_day_text match day text.
     * @param int|null        $leg leg.
     * @param string|null     $leg_text leg text.
     * @param bool            $show_date show date.
     * @param string|null     $date date.
     * @param string|null     $formatted_date formatted date.
     * @param string|null     $status_message status message.
     * @param bool            $is_highlighted is highlighted.
     * @param string|null     $status_class status class.
     * @param string|null     $status_text status text.
     * @param bool            $is_pending is pending.
     * @param string|null     $score_class score class.
     * @param string|null     $score_text score text.
     * @param string          $home_name home name.
     * @param bool            $home_is_withdrawn home is withdrawn.
     * @param string|null     $home_withdrawn_title home withdrawn title.
     * @param string|null     $home_points home points.
     * @param string          $away_name away name.
     * @param bool            $away_is_withdrawn away is withdrawn.
     * @param string|null     $away_withdrawn_title away withdrawn title.
     * @param string|null     $away_points away points.
     * @param bool            $user_can_update user can update.
     * @param string|null     $update_link update link.
     * @param string          $pencil_icon_url pencil icon url.
     * @param bool            $is_tournament is tournament.
     * @param object|null     $dto dto.
     * @param mixed           $match match.
     */
    public function __construct(
        public int|string|null $id = null,
        public bool $is_selected = false,
        public bool $show_header = true,
        public string $league_title = '',
        public ?string $match_link = null,
        public ?string $final_name = null,
        public ?int $match_day = null,
        public ?string $match_day_text = null,
        public ?int $leg = null,
        public ?string $leg_text = null,
        public bool $show_date = true,
        public ?string $date = null,
        public ?string $formatted_date = null,
        public ?string $status_message = null,
        public bool $is_highlighted = false,
        public ?string $status_class = null,
        public ?string $status_text = null,
        public bool $is_pending = false,
        public ?string $score_class = null,
        public ?string $score_text = null,
        public string $home_name = '',
        public bool $home_is_withdrawn = false,
        public ?string $home_withdrawn_title = null,
        public ?string $home_points = null,
        public string $away_name = '',
        public bool $away_is_withdrawn = false,
        public ?string $away_withdrawn_title = null,
        public ?string $away_points = null,
        public bool $user_can_update = false,
        public ?string $update_link = null,
        public string $pencil_icon_url = '',
        public bool $is_tournament = false,
        public ?object $dto = null,
        public mixed $match = null
    ) {}
}
