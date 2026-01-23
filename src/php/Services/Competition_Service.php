<?php
/**
 * Competition_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Competition;
use Racketmanager\Domain\DTO\Competition_Overview_DTO;
use Racketmanager\Domain\Event;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Clubs_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\Duplicate_Competition_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Config;
use Racketmanager\Services\Validator\Validator_Plan;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use WP_Error;
use function Racketmanager\get_match;

/**
 * Class to implement the Competition Management Service
 */
class Competition_Service {
    private Competition_Repository $competition_repository;
    private Event_Repository $event_repository;
    private League_Team_Repository $league_team_repository;
    private League_Repository $league_repository;
    private RacketManager $racketmanager;
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;
    private Player_Service $player_service;

    /**
     * Constructor
     *
     * @param RacketManager $plugin_instance
     * @param Competition_Repository $competition_repository
     * @param Club_Repository $club_repository
     * @param Event_Repository $event_repository
     * @param League_Repository $league_repository
     * @param League_Team_Repository $league_team_repository
     * @param Team_Repository $team_repository
     * @param Player_Service $player_service
     */
    public function __construct( RacketManager $plugin_instance, Competition_Repository $competition_repository, Club_Repository $club_repository, Event_Repository $event_repository, League_Repository $league_repository , League_Team_Repository $league_team_repository, Team_Repository $team_repository, Player_Service $player_service ) {
        $this->racketmanager          = $plugin_instance;
        $this->competition_repository = $competition_repository;
        $this->club_repository        = $club_repository;
        $this->event_repository       = $event_repository;
        $this->league_team_repository = $league_team_repository;
        $this->league_repository      = $league_repository;
        $this->team_repository        = $team_repository;
        $this->player_service         = $player_service;
    }

    public function get_event_by_id( null|string|int $event_id ): Event {
        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Competition_Not_Found_Exception( sprintf( __( 'Event %s not found', 'racketmanager' ), $event_id ) );
        }
        return $event;
    }

    public function get_leagues_for_event( ?int $event_id, ?int $season = null ): array {
        try {
            $event = $this->get_event_by_id( $event_id );
        } catch ( Event_Not_Found_Exception $e ) {
            throw new Event_Not_Found_Exception( $e );
        }
        return $this->league_repository->get_by_event_id( $event->get_id(), $season );
    }

    public function get_clubs_for_event( ?int $event_id, ?int $season = null ): array {
        try {
            $event = $this->get_event_by_id( $event_id );
        } catch ( Event_Not_Found_Exception $e ) {
            throw new Event_Not_Found_Exception( $e );
        }
        return $this->league_team_repository->get_clubs_by_event_id( $event->get_id(), $season );
    }

    public function get_teams_for_event( ?int $event_id, ?int $season = null ): array {
        try {
            $event = $this->get_event_by_id( $event_id );
        } catch ( Event_Not_Found_Exception $e ) {
            throw new Event_Not_Found_Exception( $e );
        }
        return $this->league_team_repository->get_by_event_id( $event->get_id(), $season );
    }

    public function get_by_id( null|string|int $competition_id ): Competition {
        $competition = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( sprintf( __( 'Competition %s not found', 'racketmanager' ), $competition_id ) );
        }
        return $competition;
    }

    public function get_all(): array {
        return $this->competition_repository->find_all();
    }

    public function get_by_criteria( array $criteria ): array {
        return $this->competition_repository->find_by( $criteria );
    }

    public function get_events_for_competition( ?int $competition_id, ?int $season = null ): array {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }
        $events = $this->event_repository->find_by_competition_id( $competition->get_id() );
        foreach ( $events as $i => $event ) {
            $event = $this->get_event_by_id( $event->id );
            if ( $season && empty( $event->get_season_by_name( $season ) ) ) {
                unset( $events[ $i ] );
            } else {
                $events[ $i ] = $event;
            }
        }
        return $events;
    }

    public function get_events_with_details_for_competition($competition_id, $season, $min_fixtures = 1): array {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }

        return $this->event_repository->find_events_by_competition_with_counts($competition->get_id(), $season, $min_fixtures);
    }

    public function get_leagues(): array {
        return $this->competition_repository->find_by( array( 'type' => 'league' ) );
    }

    public function get_tournaments(): array {
        return $this->competition_repository->find_by( array( 'type' => 'tournament' ) );
    }

    public function find_competitions_with_summary( ?string $age_group, ?string $type ): array {
        return $this->competition_repository->find_competitions_with_summary( $age_group, $type );
    }

    public function get_competition_overview($competition_id, $season, $min_fixtures = 1): ?Competition_Overview_DTO {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }

        return $this->competition_repository->get_competition_overview($competition->get_id(), $season, $min_fixtures);
    }

    public function get_teams_for_competition($competition_id, $season, $min_fixtures = 1): array {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }

        return $this->team_repository->find_teams_by_competition_with_details($competition->get_id(), $season, $min_fixtures);
    }

    public function get_clubs_for_competition( ?int $competition_id, ?int $season = null ): array {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }
        return $this->league_team_repository->get_clubs_by_competition_id( $competition->get_id(), $season );
    }

    public function get_club_details_for_competition( ?int $competition_id, ?int $season = null ): array {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }
        return $this->club_repository->find_clubs_by_competition_and_season( $competition->get_id(), $season );
    }

    public function get_winners_for_competition( ?int $competition_id, ?int $season ): array {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }
        try {
            $this->is_season_valid_for_competition( $competition, $season );
        } catch ( Season_Not_Found_Exception $e ) {
            throw new Season_Not_Found_Exception( $e );
        }
        if ( $competition->is_league ) {
            $winners = $this->competition_repository->get_league_winners( $competition_id, $season );
        } else {
            $winners = $this->competition_repository->get_championship_winners( $competition_id, $season );
        }
        $return = array();
        foreach ( $winners as $winner ) {
            if ( ! $competition->is_league ) {
                $match = get_match( $winner->id );
            }
            if ( $competition->is_player_entry ) {
                if ( $winner->winner_id === $winner->home_team ) {
                    $winner_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
                } else {
                    $winner_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
                }
                if ( $winner->loser_id === $winner->home_team ) {
                    $loser_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
                } else {
                    $loser_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
                }
                $winner->winner_club = $winner_club;
                $winner->loser_club  = $loser_club;
            }
            $winner->league           = $winner->title;
            $winner->competition_name = $competition->name;
            $winner->competition_type = $competition->type;
            $winner->season           = $competition->current_season['name'];
            $winner->is_team_entry    = $competition->is_team_entry;
            $key = strtoupper( $winner->type );
            if ( false === array_key_exists( $key, $return ) ) {
                $return[ $key ] = array();
            }
            // now just add the row data.
            $return[ $key ][] = $winner;
        }
        return $return;
    }

    public function create( ?string $name, ?string $type, ?string $age_group ): Competition {
        $competition_check = $this->competition_repository->find_by( [ 'name' => $name ] );
        if ( $competition_check ) {
            throw new Duplicate_Competition_Exception( __( 'Competition already exists', 'racketmanager' ) );
        }
        // One-liner creation using the Factory Pattern
        $competition = Competition::create( $name, $type, $age_group );
        $this->competition_repository->save( $competition );
        return $competition;
    }

    public function amend_details( int $competition_id, stdClass $config ): int|WP_Error {
        $competition = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( sprintf( __( 'Competition %s not found', 'racketmanager' ), $competition_id ) );
        }
        $competition_valid = $this->validate_config( $config, $competition->is_team_entry );
        if ( is_wp_error( $competition_valid ) ) {
            return $competition_valid;
        }
        $competition->set_name( $config->name );
        $competition->set_age_group( $config->age_group );
        $settings = $this->set_config( $config, $competition->is_team_entry );
        $competition->set_settings( $settings );
        $result = $this->competition_repository->save( $competition );
        if ( false === $result ) {
            throw new Database_Operation_Exception( __( 'Failed to update competition', 'racketmanager' ) );
        }
        return ( int ) $result; // Returns 1 if updated, 0 if no change
    }

    private function is_season_valid_for_competition( Competition $competition, int $season ): array {
        $current_season = $competition->get_season_by_name( $season );
        if ( ! $current_season ) {
            throw new Season_Not_Found_Exception( sprintf( __( 'Season %s not found', 'racketmanager' ), $season ) );
        }
        return $current_season;
    }

    /**
     * Set plan configuration
     *
     * @param int|null $competition_id
     * @param int|null $season
     * @param string|null $start_time
     * @param int|null $num_courts
     * @param string|null $time_increment
     *
     * @return int|WP_Error
     */
    public function set_plan_config( ?int $competition_id, ?int $season, ?string $start_time, ?int $num_courts, ?string $time_increment ): int| WP_Error {
        $competition = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( sprintf( __( 'Competition %s not found', 'racketmanager' ), $competition_id ) );
        }
        try {
            $current_season = $this->is_season_valid_for_competition( $competition, $season );
        } catch ( Season_Not_Found_Exception $e ) {
            throw new Season_Not_Found_Exception( $e );
        }
        $seasons   = $competition->get_seasons();
        $validator = new Validator_Plan();
        $validator = $validator->start_time( $start_time );
        $validator = $validator->num_courts_available( $num_courts );
        $validator = $validator->time_increment( $time_increment );
        if ( ! empty( $validator->error ) ) {
            return $validator->err;
        }
        $current_season['starttime']      = $start_time;
        $current_season['num_courts']     = $num_courts;
        $current_season['time_increment'] = $time_increment;
        $seasons[ $season ]               = $current_season;
        $competition->set_seasons( $seasons );
        $result = $this->competition_repository->save( $competition );
        if ( false === $result ) {
            throw new Database_Operation_Exception( __( 'Failed to update competition', 'racketmanager' ) );
        }
        return ( int ) $result;
    }

    /**
     * Save plan
     *
     * @param int|null $competition_id competition id.
     * @param int|null $season season.
     * @param array $courts number of courts available.
     * @param array $start_times start times of matches.
     * @param array $matches matches.
     * @param array $match_times match times.
     *
     * @return int
     */
    public function save_plan( ?int $competition_id, ?int $season, array $courts, array $start_times, array $matches, array $match_times ): int {
        $competition = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }
        try {
            $current_season = $this->is_season_valid_for_competition( $competition, $season );
        } catch ( Season_Not_Found_Exception $e ) {
            throw new Season_Not_Found_Exception( $e );
        }
        $seasons       = $competition->get_seasons();
        $order_of_play = array();
        $num_courts    = count( $courts );
        for ( $i = 0; $i < $num_courts; $i++ ) {
            $order_of_play[ $i ]['court']      = $courts[ $i ];
            $order_of_play[ $i ]['start_time'] = $start_times[ $i ];
            $order_of_play[ $i ]['matches']    = $matches[ $i ];
            $num_matches                       = count( $matches[ $i ] );
            for ( $m = 0; $m < $num_matches; $m++ ) {
                $match_id = trim( $matches[ $i ][ $m ] );
                if ( ! empty( $match_id ) ) {
                    $time  = strtotime( $start_times[ $i ] ) + $match_times[ $i ][ $m ];
                    $match = get_match( $match_id );
                    if ( $match ) {
                        $month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
                        $day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
                        $date     = $match->year . '-' . $month . '-' . $day . ' ' . gmdate( 'H:i', $time );
                        $location = $courts[ $i ];
                        $match->set_match_date_in_db( $date );
                        $match->set_location( $location );
                    }
                }
            }
        }
        $current_season['orderofplay'] = $order_of_play;
        $seasons[ $season ]            = $current_season;
        $competition->set_seasons( $seasons );
        $result = $this->competition_repository->save( $competition );
        if ( false === $result ) {
            throw new Database_Operation_Exception( __( 'Failed to update competition', 'racketmanager' ) );
        }
        return ( int ) $result;
    }

    /**
     * Reset the plan
     *
     * @param int $competition_id
     * @param int $season
     * @param array $matches
     *
     * @return int
     */
    public function reset_plan( int $competition_id, int $season, array $matches ): int {
        $competition = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }
        try {
            $current_season = $this->is_season_valid_for_competition( $competition, $season );
        } catch ( Season_Not_Found_Exception $e ) {
            throw new Season_Not_Found_Exception( $e );
        }
        $seasons   = $competition->get_seasons();
        $updates   = false;
        $result    = false;
        if ( $matches ) {
            foreach ( $matches as $match_id ) {
                $match = get_match( intval( $match_id ) );
                if ( $match ) {
                    $month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
                    $day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
                    $date     = $match->year . '-' . $month . '-' . $day . ' 00:00';
                    $location = '';
                    $match->set_match_date_in_db( $date );
                    $match->set_location( $location );
                    $updates = true;
                }
            }
        }
        if ( $updates ) {
            $current_season['orderofplay'] = array();
            $seasons[ $season ]            = $current_season;
            $competition->set_seasons( $seasons );
            $result = $this->competition_repository->save( $competition );
            if ( false === $result ) {
                throw new Database_Operation_Exception( __( 'Failed to update competition', 'racketmanager' ) );
            }
        }
        return ( int ) $result;
    }

    public function remove( $competition_id ): void {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception ) {
            return;
        }
        $events = $this->event_repository->find_by_competition_id( $competition->get_id() );
        foreach ( $events as $event ) {
            $this->event_repository->delete( $event->id );
        }
        $this->competition_repository->delete( $competition_id );
    }

    /**
     * Validate competition config
     *
     * @param stdClass $config
     * @param bool $is_team_entry
     *
     * @return true|WP_Error
     */
    private function validate_config( stdClass $config, bool $is_team_entry = false ): true|WP_Error {
        $validator = new Validator_Config();
        $validator = $validator->name( $config->name );
        $validator = $validator->sport( $config->sport );
        $validator = $validator->competition_type( $config->type );
        $validator = $validator->entry_type( $config->entry_type );
        $validator = $validator->age_group( $config->age_group );
        $validator = $validator->grade( $config->grade );
        if ( 'league' === $config->type ) {
            $validator = $validator->max_teams( $config->max_teams );
            $validator = $validator->teams_per_club( $config->teams_per_club );
            $validator = $validator->teams_prom_relg( $config->teams_prom_relg, $config->teams_per_club );
            $validator = $validator->lowest_promotion( $config->lowest_promotion );
        } elseif ( 'tournament' === $config->type ) {
            $validator = $validator->num_entries( $config->num_entries );
        }
        $validator = $validator->team_ranking( $config->team_ranking );
        $validator = $validator->point_rule( $config->point_rule );
        $validator = $validator->scoring( $config->scoring );
        $validator = $validator->num_sets( $config->num_sets );
        if ( $is_team_entry ) {
            $validator = $validator->num_rubbers( $config->num_rubbers );
        }
        $validator = $validator->match_date_option( $config->fixed_match_dates);
        $validator = $validator->fixture_type( $config->home_away );
        $validator = $validator->round_length( $config->round_length );
        $validator = $validator->default_match_start_time( $config->default_match_start_time );
        if ( 'tournament' !== $config->type ) {
            $validator = $validator->match_day_restriction( $config->match_day_restriction, $config->match_days_allowed, $config->start_time );
        }
        $validator = $validator->point_format( $config->point_format );
        $validator = $validator->point_2_format( $config->point_2_format );
        $validator = $validator->num_matches_per_page( $config->num_matches_per_page );
        if ( ! empty( $validator->error ) ) {
            return $validator->err;
        } else {
            return true;
        }
    }

    /**
     * Set competition config
     *
     * @param stdClass $config
     * @param bool $is_team_entry
     *
     * @return array
     */
    private function set_config( stdClass $config, bool $is_team_entry = false ): array {
        $settings = array();
        $settings['sport'] = $config->sport;
        $settings['type'] = $config->type;
        switch ( $config->type ) {
            case 'league':
                $config->mode = 'default';
                break;
            case 'cup':
                $config->mode       = 'championship';
                $config->entry_type = 'team';
                break;
            case 'tournament':
                $config->mode       = 'championship';
                $config->entry_type = 'player';
                break;
            default:
                break;
        }
        $settings['entry_type'] = $config->entry_type;
        $settings['mode'] = $config->mode;
        $settings['competition_code'] = $config->competition_code;
        $settings['grade'] = $config->grade;
        if ( 'league' === $config->type ) {
            $settings['max_teams'] = $config->max_teams;
            $settings['teams_per_club'] = $config->teams_per_club;
            $settings['teams_prom_relg'] = $config->teams_prom_relg;
            $settings['lowest_promotion'] = $config->lowest_promotion;
        } elseif ( 'tournament' === $config->type ) {
            $settings['num_entries'] = $config->num_entries;
        }
        $settings['team_ranking'] = $config->team_ranking;
        $settings['point_rule'] = $config->point_rule;
        $settings['scoring'] = $config->scoring;
        $settings['num_sets'] = $config->num_sets;
        if ( $is_team_entry ) {
            $settings['num_rubbers'] = $config->num_rubbers;
            $settings['reverse_rubbers'] = $config->reverse_rubbers;
        }
        $settings['fixed_match_dates'] = $config->fixed_match_dates;
        $settings['home_away'] = $config->home_away;
        $settings['round_length'] = $config->round_length;
        if ( 'league' === $config->type || 'cup' === $config->type ) {
            $settings['home_away_diff'] = $config->home_away_diff;
        }
        if ( 'league' === $config->type ) {
            $settings['filler_weeks'] = $config->filler_weeks;
        }
        if ( 'tournament' !== $config->type ) {
            $match_days = Util_Lookup::get_match_days();
            foreach ( $match_days as $match_day => $value ) {
                $config->match_days_allowed[ $match_day ] = isset( $config->match_days_allowed[ $match_day ] ) ? 1 : 0;
            }
            $settings['match_days_allowed'] = $config->match_days_allowed;
            $settings['match_day_restriction'] = $config->match_day_restriction;
            $settings['match_day_weekends'] = $config->match_day_weekends;
            $settings['default_match_start_time'] = $config->default_match_start_time;
            $settings['start_time'] = $config->start_time;
        }
        $settings['point_format'] = $config->point_format;
        $settings['point_2_format'] = $config->point_2_format;
        $settings['num_matches_per_page'] = $config->num_matches_per_page;
        $standing_display_options       = Util_Lookup::get_standings_display_options();
        foreach ( $standing_display_options as $display_option => $value ) {
            $config->standings[ $display_option ] = isset( $config->standings[ $display_option ] ) ? 1 : 0;
        }
        $settings['standings'] = $config->standings;
        $rules_options = $this->get_rules_options( $config->type );
        foreach ( $rules_options as $rules_option => $value ) {
            $config->rules[ $rules_option ] = isset( $config->rules[ $rules_option ] ) ? 1 : 0;
        }
        $settings['rules'] = $config->rules;
        if ( 'league' === $config->type ) {
            $settings['num_courts_available'] = $config->num_courts_available;
        }
        return $settings;
    }

    /**
     * Get rules options
     *
     * @return array of rules options.
     */
    public function get_rules_options( string $type ): array {
        $rules_options    = $this->racketmanager->get_options( 'checks' );
        $result_options   = $this->racketmanager->get_options( $type );
        if ( isset( $result_options['resultTimeout'] ) ) {
            $rules_options['resultTimeout'] = $result_options['resultTimeout'];
        }
        if ( isset( $result_options['confirmationTimeout'] ) ) {
            $rules_options['confirmationTimeout'] = $result_options['confirmationTimeout'];
        }
        return $rules_options;
    }

    public function set_court_availability( int $competition_id, int $club_id, int $num_courts_available ): void {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }
        if ( empty( $competition->settings['num_courts_available'] ) ) {
            $competition->settings['num_courts_available'] = array();
        }
        $competition->set_num_courts_available( $club_id, $num_courts_available );
        $this->competition_repository->save( $competition );

    }

    /**
     * Delete seasons for a competition
     *
     * @param ?int $competition_id
     * @param array $seasons
     *
     * @return void
     */
    public function delete_seasons( ?int $competition_id, array $seasons ): void {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e );
        }
        $deleted = false;
        foreach ( $seasons as $season ) {
            $season_found = $competition->get_season_by_name( $season );
            if ( $season_found ) {
                $deleted = true;
                $seasons = $competition->seasons;
                foreach ( $competition->get_events() as $event ) {
                    $event->delete_season( $season );
                }
                unset( $seasons[ $season ] );
                $schedule_args[] = intval( $competition->id );
                $schedule_args[] = intval( $season );
                $schedule_name   = 'rm_notify_team_entry_open';
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
                $schedule_name = 'rm_notify_team_entry_reminder';
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
                $schedule_name = 'rm_calculate_team_ratings';
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
            }
        }
        if ( $deleted ) {
            $competition->set_seasons( $seasons );
            $this->competition_repository->save( $competition );
        } else {
            throw new Competition_Not_Updated_Exception( __( 'No seasons deleted', 'racketmanager' ) );
        }
    }

}
