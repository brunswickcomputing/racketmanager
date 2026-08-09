<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\DTOs;

/**
 * The read model for the fixture header fragment.
 */
class Fixture_Header_Read_Model {
    public function __construct(
        public int $id,
        public string $event_name,
        public string $event_url,
        public string $league_title,
        public string $league_url,
        public string $season,
        public ?string $round_name,
        public ?int $match_day,
        public ?int $leg,
        public string $fixture_date,
        public string $formatted_date,
        public ?string $original_date_formatted,
        public string $home_team_name,
        public string $home_team_url,
        public bool $home_team_withdrawn,
        public string $away_team_name,
        public string $away_team_url,
        public bool $away_team_withdrawn,
        public bool $is_pending,
        public string $score_class,
        public string $home_points,
        public string $away_points,
        public string $match_time,
        public ?string $status_message,
        public ?string $status_description,
        public bool $show_menu,
        public bool $allow_amend_score,
        public bool $allow_schedule_match,
        public bool $allow_switch_match,
        public bool $allow_reset_match_result,
        public string $amend_score_label,
        public string $match_link,
        public string $rubbers_score,
        public string $sets_score,
        public string $games_score,
        public bool $edit_mode,
        public bool $user_can_update,
        public bool $match_approval_mode,
        public bool $is_shortcode = false
    ) {}
}
