<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Ports;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use stdClass;
use WP_Error;

/**
 * Port interface for Competition lifecycle, query, and administration operations.
 */
interface Competition_Service_Interface {
    /**
     * Delete an event and its leagues.
     *
     * @param Event $event Event entity.
     * @return void
     */
    public function delete_event( Event $event ): void;

    /**
     * Delete an event season and its fixtures/teams.
     *
     * @param Event      $event  Event entity.
     * @param int|string $season Season name/year.
     * @return void
     */
    public function delete_event_season( Event $event, int|string $season ): void;

    /**
     * Get an event by identifier.
     *
     * @param int|string|null $event_id Event ID.
     * @return Event
     */
    public function get_event_by_id( null|string|int $event_id ): Event;

    /**
     * Get leagues for an event.
     *
     * @param int|null $event_id Event ID.
     * @param int|null $season   Season year.
     * @return array
     */
    public function get_leagues_for_event( ?int $event_id, ?int $season = null ): array;

    /**
     * Get clubs for an event.
     *
     * @param int|null $event_id Event ID.
     * @param int|null $season   Season year.
     * @return array
     */
    public function get_clubs_for_event( ?int $event_id, ?int $season = null ): array;

    /**
     * Get teams for an event.
     *
     * @param int|null $event_id Event ID.
     * @param int|null $season   Season year.
     * @param int|null $club_id  Club ID filter.
     * @return array
     */
    public function get_teams_for_event( ?int $event_id, ?int $season = null, ?int $club_id = null ): array;

    /**
     * Get competition by identifier, name, or object.
     *
     * @param object|int|string|null $competition_id Competition ID, name, or object.
     * @param string|null            $search_term    Search term column ('id' or 'name').
     * @return Competition
     */
    public function get_competition( object|int|string|null $competition_id = null, ?string $search_term = 'id' ): Competition;

    /**
     * Get competition by ID.
     *
     * @param int|string|null $competition_id Competition ID.
     * @return Competition
     */
    public function get_by_id( null|string|int $competition_id ): Competition;

    /**
     * Get competition by season.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season year.
     * @return Competition
     */
    public function get_competition_by_season( null|int $competition_id, ?int $season ): Competition;

    /**
     * Get all competitions.
     *
     * @return array
     */
    public function get_all(): array;

    /**
     * Get competitions matching criteria.
     *
     * @param array $criteria Search criteria.
     * @return array
     */
    public function get_by_criteria( array $criteria ): array;

    /**
     * Get events for a competition.
     *
     * @param int|string|null $competition_id Competition ID.
     * @param int|null        $season         Season year.
     * @return array
     */
    public function get_events_for_competition( null|int|string $competition_id, ?int $season = null ): array;

    /**
     * Get all events of a competition entity.
     *
     * @param Competition $competition Competition entity.
     * @return array
     */
    public function get_competition_events( Competition $competition ): array;

    /**
     * Resolve a competition event by identifier or object.
     *
     * @param Competition       $competition      Competition entity.
     * @param int|string|object $event_identifier Event identifier or object.
     * @param string|null       $season           Season name.
     * @return Event|null
     */
    public function resolve_competition_event( Competition $competition, int|string|object $event_identifier, ?string $season = null ): ?Event;

    /**
     * Get entry events for a competition season.
     *
     * @param Competition     $competition Competition entity.
     * @param int|string|null $season      Season identifier.
     * @return array
     */
    public function get_entry_events( Competition $competition, int|string|null $season = null ): array;

    /**
     * Get frontend events for a competition season.
     *
     * @param Competition     $competition Competition entity.
     * @param int|string|null $season      Season identifier.
     * @return array
     */
    public function get_frontend_events( Competition $competition, int|string|null $season = null ): array;

    /**
     * Get events with details for a competition.
     *
     * @param mixed $competition_id Competition ID.
     * @param mixed $season         Season identifier.
     * @param mixed $min_fixtures   Minimum fixtures count.
     * @return array
     */
    public function get_events_with_details_for_competition( mixed $competition_id, mixed $season, mixed $min_fixtures = 1 ): array;

    /**
     * Get league competitions.
     *
     * @return array
     */
    public function get_league_competitions(): array;

    /**
     * Get tournament competitions.
     *
     * @return array
     */
    public function get_tournament_competitions(): array;

    /**
     * Find competitions with summary by age group and type.
     *
     * @param string|null $age_group Age group.
     * @param string|null $type      Competition type.
     * @return array
     */
    public function find_competitions_with_summary( ?string $age_group, ?string $type ): array;

    /**
     * Get competition overview DTO.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @param int      $min_fixtures   Minimum fixtures count.
     * @return Competition_Overview_DTO|null
     */
    public function get_competition_overview( ?int $competition_id, ?int $season, int $min_fixtures = 1 ): ?Competition_Overview_DTO;

    /**
     * Get teams for a competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @param int      $min_fixtures   Minimum fixtures count.
     * @return array
     */
    public function get_teams_for_competition( ?int $competition_id, ?int $season, int $min_fixtures = 1 ): array;

    /**
     * Get clubs for a competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @return array
     */
    public function get_clubs_for_competition( ?int $competition_id, ?int $season = null ): array;

    /**
     * Get club details for a competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @return array
     */
    public function get_club_details_for_competition( ?int $competition_id, ?int $season = null ): array;

    /**
     * Get winners for a competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @return array
     */
    public function get_winners_for_competition( ?int $competition_id, ?int $season ): array;

    /**
     * Create a competition.
     *
     * @param string|null $name      Competition name.
     * @param string|null $type      Competition type.
     * @param string|null $age_group Age group.
     * @return Competition
     */
    public function create( ?string $name, ?string $type, ?string $age_group ): Competition;

    /**
     * Amend competition details.
     *
     * @param int      $competition_id Competition ID.
     * @param stdClass $config         Configuration object.
     * @return int|WP_Error
     */
    public function amend_details( int $competition_id, stdClass $config ): int|WP_Error;

    /**
     * Check if a season is valid for a competition.
     *
     * @param Competition $competition Competition entity.
     * @param int         $season      Season identifier.
     * @return array
     */
    public function is_season_valid_for_competition( Competition $competition, int $season ): array;

    /**
     * Verify club entry for a competition season.
     *
     * @param mixed $competition_id Competition ID.
     * @param mixed $club_id        Club ID.
     * @param mixed $season         Season identifier.
     * @return bool
     */
    public function verify_club_entry( mixed $competition_id, mixed $club_id, mixed $season ): bool;

    /**
     * Get active events for a competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @return array
     */
    public function get_active_events_for_competition( ?int $competition_id, ?int $season = null ): array;

    /**
     * Set planning configuration for a competition season.
     *
     * @param int|null    $competition_id Competition ID.
     * @param int|null    $season         Season identifier.
     * @param string|null $start_time     Start time.
     * @param int|null    $num_courts     Number of courts.
     * @param string|null $time_increment Time increment.
     * @return int|WP_Error
     */
    public function set_plan_config( ?int $competition_id, ?int $season, ?string $start_time, ?int $num_courts, ?string $time_increment ): int|WP_Error;

    /**
     * Save match plan for a competition season.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @param array    $courts         Courts array.
     * @param array    $start_times    Start times array.
     * @param array    $matches        Matches array.
     * @param array    $match_times    Match times array.
     * @return int
     */
    public function save_plan( ?int $competition_id, ?int $season, array $courts, array $start_times, array $matches, array $match_times ): int;

    /**
     * Reset match plan for a competition season.
     *
     * @param int   $competition_id Competition ID.
     * @param int   $season         Season identifier.
     * @param array $matches        Matches array.
     * @return int
     */
    public function reset_plan( int $competition_id, int $season, array $matches ): int;

    /**
     * Remove a competition.
     *
     * @param mixed $competition_id Competition ID.
     * @return void
     */
    public function remove( mixed $competition_id ): void;

    /**
     * Get rules options by competition type.
     *
     * @param string $type Competition type.
     * @return array
     */
    public function get_rules_options( string $type ): array;

    /**
     * Set court availability for a club in a competition.
     *
     * @param int $competition_id       Competition ID.
     * @param int $club_id              Club ID.
     * @param int $num_courts_available Number of courts available.
     * @return void
     */
    public function set_court_availability( int $competition_id, int $club_id, int $num_courts_available ): void;

    /**
     * Delete seasons from a competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param array    $seasons        Seasons array.
     * @return void
     */
    public function delete_seasons( ?int $competition_id, array $seasons ): void;

    /**
     * Calculate team ratings for a competition season.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season identifier.
     * @return void
     */
    public function calculate_team_ratings( ?int $competition_id, ?int $season ): void;

    /**
     * Set season configuration for a tournament competition.
     *
     * @param int|null $competition_id Competition ID.
     * @param stdClass $season         Season configuration object.
     * @return void
     */
    public function set_season_for_tournament_competition( ?int $competition_id, stdClass $season ): void;

    /**
     * Remove season from a competition.
     *
     * @param int|null    $competition_id Competition ID.
     * @param string|null $season         Season identifier.
     * @return void
     */
    public function remove_season_for_competition( ?int $competition_id, ?string $season ): void;

    /**
     * Save a competition entity.
     *
     * @param Competition $competition Competition entity.
     * @return bool|int
     */
    public function save( Competition $competition ): bool|int;

    /**
     * Get events for a competition using query arguments.
     *
     * @param Competition|int|string $competition Competition or ID.
     * @param array                  $args        Query arguments.
     * @return array
     */
    public function get_events_by_query( Competition|int|string $competition, array $args = array() ): array;

    /**
     * Get matches for a competition using query arguments.
     *
     * @param Competition|int|string $competition Competition or ID.
     * @param array                  $args        Query arguments.
     * @return array
     */
    public function get_matches_for_competition( Competition|int|string $competition, array $args = array() ): array;

    /**
     * Get teams for a competition using query arguments.
     *
     * @param Competition|int|string $competition Competition or ID.
     * @param array                  $args        Query arguments.
     * @return array
     */
    public function get_teams_by_query( Competition|int|string $competition, array $args = array() ): array;

    /**
     * Get players for a competition using query arguments.
     *
     * @param Competition|int|string $competition Competition or ID.
     * @param array                  $args        Query arguments.
     * @return array
     */
    public function get_players_for_competition_query( Competition|int|string $competition, array $args = array() ): array;

    /**
     * Replace seasons of a competition.
     *
     * @param Competition  $competition Competition entity.
     * @param array|string $seasons     Seasons data.
     * @return bool
     */
    public function update_seasons( Competition $competition, array|string $seasons ): bool;

    /**
     * Add a season to a competition.
     *
     * @param Competition $competition Competition entity.
     * @param object      $season      Season object.
     * @return bool
     */
    public function add_season( Competition $competition, object $season ): bool;

    /**
     * Update a season of a competition.
     *
     * @param Competition $competition Competition entity.
     * @param array       $season      Season data.
     * @return bool
     */
    public function update_season( Competition $competition, array $season ): bool;

    /**
     * Get contact preview HTML.
     *
     * @param Competition $competition Competition entity.
     * @param string      $season      Season identifier.
     * @param string      $email_title Email title.
     * @param string      $email_intro Email intro.
     * @param array       $email_body  Email body.
     * @param string      $email_close Email closing text.
     * @return string
     */
    public function get_contact_preview( Competition $competition, string $season, string $email_title, string $email_intro, array $email_body, string $email_close ): string;
}
