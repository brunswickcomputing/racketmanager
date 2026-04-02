<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Validator;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Results_Checker;
use Racketmanager\Domain\DTO\Player\Validation_Context_DTO;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Util\Util;
use DateTime;
use DateMalformedStringException;

/**
 * Service for validating player eligibility and handling dummy player assignments.
 */
class Player_Validation_Service {
    private Registration_Service $registration_service;
    private Results_Checker_Repository_Interface $results_checker_repository;
    private Fixture_Repository_Interface $fixture_repository;

    public function __construct(
        Registration_Service $registration_service,
        Results_Checker_Repository_Interface $results_checker_repository,
        ?Fixture_Repository_Interface $fixture_repository = null
    ) {
        $this->registration_service       = $registration_service;
        $this->results_checker_repository = $results_checker_repository;
        $this->fixture_repository         = $fixture_repository ?? new Fixture_Repository();
    }

    /**
     * Map dummy players based on match type and status.
     *
     * @param string $match_type e.g., 'MD', 'WD', 'XD'
     * @param string $status e.g. 'share', 'walkover_player1'
     * @param array $players Current player assignments
     * @param array $dummy_players Preloaded dummy players by team and gender
     *
     * @return array Updated player assignments
     */
    public function apply_dummy_players( string $match_type, string $status, array $players, array $dummy_players ): array {
        return match ( $status ) {
            'share' => $this->apply_share_dummy_players( $match_type, $players, $dummy_players ),
            'walkover_player1' => $this->apply_walkover_dummy_players( 'home', $match_type, $players, $dummy_players ),
            'walkover_player2' => $this->apply_walkover_dummy_players( 'away', $match_type, $players, $dummy_players ),
            default => $players,
        };
    }

    /**
     * Apply share dummy players.
     */
    private function apply_share_dummy_players( string $match_type, array $players, array $dummy_players ): array {
        $genders = $this->get_genders_for_match_type( $match_type );

        $players['home']['1'] = $dummy_players['home']['share'][ $genders[0] ]->roster_id ?? 0;
        $players['home']['2'] = $dummy_players['home']['share'][ $genders[1] ]->roster_id ?? $players['home']['1'];
        $players['away']['1'] = $dummy_players['away']['share'][ $genders[0] ]->roster_id ?? 0;
        $players['away']['2'] = $dummy_players['away']['share'][ $genders[1] ]->roster_id ?? $players['away']['1'];

        return $players;
    }

    /**
     * Get genders for this match type.
     */
    private function get_genders_for_match_type( string $match_type ): array {
        return match ( $match_type ) {
            'MD', 'BD' => [ 'male', 'male' ],
            'WD', 'GD' => [ 'female', 'female' ],
            'XD' => [ 'male', 'female' ],
            default => [ 'unknown', 'unknown' ],
        };
    }

    /**
     * Apply walkover dummy players for a given winning team.
     */
    private function apply_walkover_dummy_players( string $winner, string $match_type, array $players, array $dummy_players ): array {
        $loser   = 'home' === $winner ? 'away' : 'home';
        $genders = $this->get_genders_for_match_type( $match_type );

        // Winner gets walkover dummies if empty
        if ( empty( $players[ $winner ]['1'] ) ) {
            $players[ $winner ]['1'] = $dummy_players[ $winner ]['walkover'][ $genders[0] ]->roster_id ?? 0;
        }
        if ( empty( $players[ $winner ]['2'] ) ) {
            $players[ $winner ]['2'] = $dummy_players[ $winner ]['walkover'][ $genders[1] ]->roster_id ?? $players[ $winner ]['1'];
        }

        // Loser gets noplayer dummies
        $players[ $loser ]['1'] = $dummy_players[ $loser ]['noplayer'][ $genders[0] ]->roster_id ?? 0;
        $players[ $loser ]['2'] = $dummy_players[ $loser ]['noplayer'][ $genders[1] ]->roster_id ?? $players[ $loser ]['1'];

        return $players;
    }

    /**
     * Run all player and result checks for a fixture.
     *
     * @param Fixture $fixture
     * @param League $league
     * @param array $rubbers Array of Rubber objects or rubber update result arrays
     * @param array $options Configuration options (passed to avoid global dependency)
     *
     * @return void
     */
    public function run_fixture_checks( Fixture $fixture, League $league, array $rubbers, array $options = [] ): void {
        $this->results_checker_repository->delete_by_fixture_id( (int) $fixture->get_id() );

        $check_options = $options['checks'] ?? [];
        $prev_wtns     = [];

        foreach ( $rubbers as &$rubber ) {
            $rubber_players = $this->ensure_rubber_players_loaded( $rubber );

            if ( empty( $rubber_players ) ) {
                continue;
            }

            $check_results = $this->run_rubber_player_checks( $fixture, $league, $rubber, $options );

            if ( ! empty( $league->event->competition->rules['wtn_check'] ) && ! empty( $check_options['wtn_check'] ) ) {
                $this->check_wtn_order( $fixture, $rubber, $rubber_players, $check_results['wtns'], $prev_wtns );

                foreach ( $check_results['wtns'] as $opponent => $wtn ) {
                    $prev_wtns[ $opponent ] = $wtn;
                }
            }
        }

        $this->run_result_timeout_check( $fixture, $league, $options );
    }

    /**
     * Ensure players are fully loaded for a rubber.
     */
    private function ensure_rubber_players_loaded( object|array &$rubber ): array {
        if ( is_array( $rubber ) ) {
            $this->reload_rubber_array_players( $rubber );

            return $rubber['players'];
        }

        if ( empty( $rubber->players ) ) {
            $rubber->get_players();
        }

        return $rubber->players;
    }

    /**
     * Reload players for a rubber passed as an array.
     */
    private function reload_rubber_array_players( array &$rubber ): void {
        foreach ( [ 'home', 'away' ] as $opponent ) {
            if ( ! isset( $rubber['players'][ $opponent ] ) ) {
                continue;
            }
            foreach ( $rubber['players'][ $opponent ] as $player_ref => $player ) {
                $rubber['players'][ $opponent ][ $player_ref ] = $this->ensure_player_object( $player );
            }
        }
    }

    /**
     * Ensure a player is a fully loaded object.
     */
    private function ensure_player_object( mixed $player ): mixed {
        if ( is_numeric( $player ) ) {
            return $this->registration_service->get_registration( (int) $player );
        }

        if ( is_object( $player ) && ! isset( $player->wtn ) && isset( $player->id ) ) {
            return $this->registration_service->get_registration( (int) $player->id );
        }

        return $player;
    }

    /**
     * Run individual player checks for a rubber.
     *
     * Migrated from Rubber::check_players()
     *
     * @param Fixture $fixture
     * @param League $league
     * @param object|array $rubber
     * @param array $options Configuration options
     *
     * @return array
     */
    private function run_rubber_player_checks( Fixture $fixture, League $league, object|array $rubber, array $options = [] ): array {
        $player_wtns = [ 'home' => 0.0, 'away' => 0.0 ];
        $opponents   = [ 'home', 'away' ];

        $competition_season = $league->event->competition->get_season_by_name( $fixture->get_season() );
        $event_season       = $league->event->get_season_by_name( $fixture->get_season() );

        foreach ( $opponents as $opponent ) {
            $player_wtns[ $opponent ] = $this->run_opponent_player_checks( $fixture, $league, $rubber, $opponent, $competition_season, $event_season, $options );
        }

        return [ 'wtns' => $player_wtns ];
    }

    /**
     * Run checks for all players of an opponent.
     */
    private function run_opponent_player_checks( Fixture $fixture, League $league, object|array $rubber, string $opponent, ?array $competition_season, ?array $event_season, array $options ): float {
        $team_id   = 'home' === $opponent ? (int) $fixture->get_home_team() : (int) $fixture->get_away_team();
        $players   = is_array( $rubber ) ? ( $rubber['players'][ $opponent ] ?? [] ) : ( $rubber->players[ $opponent ] ?? [] );
        $rubber_id = (int) ( is_array( $rubber ) ? ( $rubber['id'] ?? 0 ) : $rubber->get_id() );
        $wtn_sum   = 0.0;

        $context = new Validation_Context_DTO( $fixture, $league, $competition_season, $event_season, $options, $team_id, $rubber_id );

        foreach ( $players as $player ) {
            if ( $player ) {
                $wtn_sum += $this->run_single_player_checks( $player, $context );
            }
        }

        return $wtn_sum;
    }

    /**
     * Run all checks for a single player.
     */
    private function run_single_player_checks( object $player, Validation_Context_DTO $context ): float {
        if ( $this->check_system_record( $player, $context ) ) {
            return 0.0;
        }

        $this->check_locked_player( $player, $context );
        $this->check_lead_time( $player, $context );
        $this->check_age_limit( $player, $context );
        $this->check_btm_number( $player, $context );
        $this->check_match_day_play( $player, $context );

        $type = substr( $context->league->event->get_type(), 1, 1 );

        return isset( $player->wtn[ $type ] ) ? floatval( $player->wtn[ $type ] ) : 40.9;
    }

    /**
     * Check if a player is a system record (e.g. unregistered player dummy).
     */
    private function check_system_record( object $player, Validation_Context_DTO $context ): bool {
        if ( empty( $player->system_record ) ) {
            return false;
        }

        $player_options = $context->options['player'] ?? [];
        $gender         = match ( $player->gender ) {
            'M' => 'male',
            'F' => 'female',
            default => 'unknown',
        };

        if ( isset( $player_options['unregistered'][ $gender ] ) && intval( $player->id ) === intval( $player_options['unregistered'][ $gender ] ) ) {
            $error = __( 'Unregistered player', 'racketmanager' );
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $error, $context->rubber_id );
        }

        return true;
    }

    /**
     * Add a player result check entry.
     */
    private function add_player_result_check( Fixture $fixture, int $team_id, int $player_id, string $error, int $rubber_id ): void {
        $check              = new Results_Checker();
        $check->match_id    = (int) $fixture->get_id();
        $check->league_id   = (int) $fixture->get_league_id();
        $check->team_id     = $team_id;
        $check->player_id   = $player_id;
        $check->rubber_id   = $rubber_id;
        $check->description = $error;

        $this->results_checker_repository->save( $check );
    }

    /**
     * Check if a player is locked.
     */
    private function check_locked_player( object $player, Validation_Context_DTO $context ): void {
        if ( ! empty( $player->locked ) ) {
            $error = __( 'locked', 'racketmanager' );
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $error, $context->rubber_id );
        }
    }

    /**
     * Check roster lead time.
     */
    private function check_lead_time( object $player, Validation_Context_DTO $context ): void {
        $check_options = $context->options['checks'] ?? [];
        if ( ! $this->should_check_lead_time( $context->league, $player, $check_options ) ) {
            return;
        }
        $this->validate_lead_time_interval( $player, (int) $check_options['rosterLeadTime'], $context );
    }

    /**
     * Determine if a lead time check should be performed.
     */
    private function should_check_lead_time( League $league, object $player, array $check_options ): bool {
        return ! empty( $league->event->competition->rules['leadTimecheck'] )
               && ! empty( $check_options['leadTimecheck'] )
               && isset( $check_options['rosterLeadTime'], $player->approval_date );
    }

    /**
     * Validate the lead time interval between roster approval and match date.
     */
    private function validate_lead_time_interval( object $player, int $required_hours, Validation_Context_DTO $context ): void {
        try {
            $match_date  = new DateTime( $context->fixture->get_date() );
            $roster_date = new DateTime( $player->approval_date );
            $date_diff   = $roster_date->diff( $match_date );
            $interval    = ( $date_diff->days * 24 ) + $date_diff->h;

            if ( $interval < $required_hours ) {
                $error = sprintf( __( 'registered with club only %d hours before fixture', 'racketmanager' ), $interval );
                $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $error, $context->rubber_id );
            } elseif ( $date_diff->invert ) {
                $error = sprintf( __( 'registered with club %d hours after fixture', 'racketmanager' ), $interval );
                $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $error, $context->rubber_id );
            }
        } catch ( DateMalformedStringException $e ) {
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $e->getMessage(), $context->rubber_id );
        }
    }

    /**
     * Check age limit.
     */
    private function check_age_limit( object $player, Validation_Context_DTO $context ): void {
        $check_options = $context->options['checks'] ?? [];
        if ( ! $this->should_check_age_limit( $context->league, $check_options ) ) {
            return;
        }

        if ( empty( $player->age ) ) {
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, __( 'no age provided', 'racketmanager' ), $context->rubber_id );

            return;
        }

        $player_age = $this->calculate_effective_player_age( $player, $context->competition_season, $context->event_season );
        $age_check  = Util::check_age_within_limit( $player_age, (int) $context->league->event->age_limit, (string) $player->gender, (int) $context->league->event->age_offset );

        if ( ! $age_check->valid ) {
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $age_check->msg, $context->rubber_id );
        }
    }

    /**
     * Determine if an age limit check should be performed.
     */
    private function should_check_age_limit( League $league, array $check_options ): bool {
        return ! empty( $league->event->competition->rules['ageLimitCheck'] )
               && ! empty( $check_options['ageLimitCheck'] )
               && ! empty( $league->event->age_limit )
               && 'open' !== $league->event->age_limit;
    }

    /**
     * Calculate player's age based on season end date if available, otherwise use current age.
     */
    private function calculate_effective_player_age( object $player, ?array $competition_season, ?array $event_season ): int {
        $date_end = $competition_season['date_end'] ?? ( ! empty( $event_season['match_dates'] ) ? end( $event_season['match_dates'] ) : null );

        if ( $date_end && isset( $player->year_of_birth ) ) {
            return intval( substr( $date_end, 0, 4 ) ) - intval( $player->year_of_birth );
        }

        return (int) ( $player->age ?? 0 );
    }

    /**
     * Check BTM number.
     */
    private function check_btm_number( object $player, Validation_Context_DTO $context ): void {
        $register_options = $context->options['rosters'] ?? [];
        if ( isset( $register_options['btm'] ) && empty( $player->btm ) ) {
            $error = __( 'LTA tennis number missing', 'racketmanager' );
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $error, $context->rubber_id );
        }
    }

    /**
     * Check if a player played in another match on the same day.
     */
    private function check_match_day_play( object $player, Validation_Context_DTO $context ): void {
        if ( ! $context->fixture->get_match_day() ) {
            return;
        }

        $count = $this->fixture_repository->count_player_matches_on_same_day(
            $context->fixture->get_season(),
            (int) $context->fixture->get_match_day(),
            (int) $context->league->get_id(),
            (int) $player->registration_id
        );

        if ( $count > 0 ) {
            $error = __( 'played in another match on the same match day', 'racketmanager' );
            $this->add_player_result_check( $context->fixture, $context->team_id, (int) $player->id, $error, $context->rubber_id );
        }
    }

    /**
     * Check WTN order for a rubber compared to previous rubbers.
     */
    private function check_wtn_order( Fixture $fixture, object|array $rubber, array $rubber_players, array $wtns, array $prev_wtns ): void {
        if ( empty( $prev_wtns ) ) {
            return;
        }

        $rubber_number = is_array( $rubber ) ? ( $rubber['rubber_number'] ?? 0 ) : $rubber->get_rubber_number();

        foreach ( $wtns as $opponent => $wtn ) {
            if ( isset( $prev_wtns[ $opponent ] ) && $wtn < $prev_wtns[ $opponent ] ) {
                $this->add_wtn_order_violations( $fixture, $opponent, $rubber, $rubber_players, $rubber_number, $wtn, $prev_wtns[ $opponent ] );
            }
        }
    }

    /**
     * Add WTN order violations for a specific team.
     */
    private function add_wtn_order_violations( Fixture $fixture, string $opponent, object|array $rubber, array $rubber_players, int $rubber_number, float $wtn, float $prev_wtn ): void {
        $team_id = 'home' === $opponent ? $fixture->get_home_team() : $fixture->get_away_team();
        $message = sprintf(
            __( 'Players out of order. Rubber %1$d has wtn %2$.1f - previous rubber has wtn %3$.1f', 'racketmanager' ),
            $rubber_number,
            $wtn,
            $prev_wtn
        );

        $players = $rubber_players[ $opponent ] ?? [];
        foreach ( $players as $player ) {
            if ( $player ) {
                $rubber_id = is_array( $rubber ) ? ( $rubber['id'] ?? 0 ) : $rubber->get_id();
                $this->add_player_result_check( $fixture, (int) $team_id, (int) $player->id, $message, (int) $rubber_id );
            }
        }
    }

    /**
     * Run result timeout check.
     *
     * @param Fixture $fixture
     * @param League $league
     * @param array $options Configuration options
     *
     * @return void
     */
    private function run_result_timeout_check( Fixture $fixture, League $league, array $options = [] ): void {
        if ( empty( $league->event->competition->rules['resultTimeout'] ) ) {
            return;
        }

        $result_timeout = $this->get_result_timeout( $league, $options );

        if ( $result_timeout && $fixture->get_date_result_entered() ) {
            $this->validate_result_timeout( $fixture, $result_timeout );
        }
    }

    /**
     * Get result timeout value from options.
     */
    private function get_result_timeout( League $league, array $options ): ?int {
        $competition_options = $options[ $league->event->competition->type ] ?? [];
        $timeout             = $competition_options['resultTimeout'] ?? null;

        return $timeout !== null ? (int) $timeout : null;
    }

    /**
     * Validate if a result was entered within the allowed time.
     */
    private function validate_result_timeout( Fixture $fixture, int $result_timeout_hours ): void {
        try {
            $date_entered = new DateTime( $fixture->get_date_result_entered() );
            $match_date   = new DateTime( $fixture->get_date() );
            $diff         = $date_entered->diff( $match_date );

            if ( $diff->invert ) {
                $minutes = ( $diff->days * 24 * 60 ) + ( $diff->h * 60 ) + $diff->i;
                if ( $minutes > ( $result_timeout_hours * 60 ) ) {
                    $hours  = $minutes / 60;
                    $reason = sprintf( __( 'Result entered %d hours after match', 'racketmanager' ), $hours );
                    $this->add_match_result_check( $fixture, (int) $fixture->get_home_team(), $reason );
                }
            }
        } catch ( DateMalformedStringException $e ) {
            $this->add_match_result_check( $fixture, (int) $fixture->get_home_team(), $e->getMessage() );
        }
    }

    /**
     * Add a match result check entry.
     */
    private function add_match_result_check( Fixture $fixture, int $team_id, string $error ): void {
        $check              = new Results_Checker();
        $check->match_id    = (int) $fixture->get_id();
        $check->league_id   = (int) $fixture->get_league_id();
        $check->team_id     = $team_id;
        $check->description = $error;

        $this->results_checker_repository->save( $check );
    }

}
