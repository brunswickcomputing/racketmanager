<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single match row for templates/includes/matches-teams-match.php.
 */
final class Team_Match_Row_View_Model {

    public readonly string $league_name;

    /**
     * Backward-compatible property hook for legacy callers reading $row->league_title.
     */
    public string $league_title {
        final get => $this->league_name;
    }

    /**
     * @param int|string|null $id id.
     * @param bool            $is_selected is selected.
     * @param bool            $show_header show header.
     * @param string          $league_name league name.
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
     * @param string|null     $league_title Legacy alias for $league_name.
     */
    public function __construct(
        public readonly int|string|null $id = null,
        public readonly bool $is_selected = false,
        public readonly bool $show_header = true,
        string $league_name = '',
        public readonly ?string $match_link = null,
        public readonly ?string $final_name = null,
        public readonly ?int $match_day = null,
        public readonly ?string $match_day_text = null,
        public readonly ?int $leg = null,
        public readonly ?string $leg_text = null,
        public readonly bool $show_date = true,
        public readonly ?string $date = null,
        public readonly ?string $formatted_date = null,
        public readonly ?string $status_message = null,
        public readonly bool $is_highlighted = false,
        public readonly ?string $status_class = null,
        public readonly ?string $status_text = null,
        public readonly bool $is_pending = false,
        public readonly ?string $score_class = null,
        public readonly ?string $score_text = null,
        public readonly string $home_name = '',
        public readonly bool $home_is_withdrawn = false,
        public readonly ?string $home_withdrawn_title = null,
        public readonly ?string $home_points = null,
        public readonly string $away_name = '',
        public readonly bool $away_is_withdrawn = false,
        public readonly ?string $away_withdrawn_title = null,
        public readonly ?string $away_points = null,
        public readonly bool $user_can_update = false,
        public readonly ?string $update_link = null,
        public readonly string $pencil_icon_url = '',
        public readonly bool $is_tournament = false,
        public readonly ?object $dto = null,
        public readonly mixed $match = null,
        ?string $league_title = null,
    ) {
        $this->league_name = '' !== $league_name ? $league_name : (string) ( $league_title ?? '' );
    }
}
