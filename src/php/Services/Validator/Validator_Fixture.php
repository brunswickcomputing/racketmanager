<?php
/**
 * Match Validation API: Match validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Services\Validator;

use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use function Racketmanager\get_match;
use function Racketmanager\get_rubber;

/**
 * Class to implement the Match Validator object
 */
final class Validator_Fixture extends Validator {
    /**
     * @var float|int|mixed|string
     */
    public mixed $home_points;
    /**
     * @var float|int|mixed|string
     */
    public mixed $away_points;
    public array $sets;
    public array $stats;
    public array $points;
    public array $rubbers = array();
    private array $players_involved = array();

    /**
     * Validate fixture
     *
     * @param ?int $fixture_id fixture id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function fixture( ?int $fixture_id, string $error_field = 'match' ): object {
        if ( empty( $fixture_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Match id not supplied', 'racketmanager' );
        }
        return $this;
    }

    /**
     * Validate match
     *
     * @param ?int $match_id match id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function match( ?int $match_id, string $error_field = 'match' ): object {
        if ( empty( $match_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Match id not supplied', 'racketmanager' );
        } else {
            $match = get_match( $match_id );
            if ( ! $match ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Match not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate new match date
     *
     * @param ?string $schedule_date new match_date.
     * @param string $match_date current match date.
     * @return object $validation updated validation object.
     */
    public function scheduled_date( ?string $schedule_date, string $match_date ): object {
        if ( empty( $schedule_date ) ) {
            $this->error      = true;
            $this->err_flds[] = 'schedule-date';
            $this->err_msgs[] = __( 'New date not set', 'racketmanager' );
        } else {
            if ( strlen( $schedule_date ) === 10 ) {
                $schedule_date = substr( $schedule_date, 0, 10 );
                $match_date    = substr( $match_date, 0, 10 );
            } else {
                $schedule_date = substr( $schedule_date, 0, 10 ) . ' ' . substr( $schedule_date, 11, 5 );
            }
            if ( $schedule_date === $match_date ) {
                $this->error      = true;
                $this->err_flds[] = 'schedule-date';
                $this->err_msgs[] = __( 'Date not changed', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate match status
     *
     * @param ?string $match_status match status.
     * @param string $error_field error field.
     * @param bool   $required is null value invalid.
     * @return object $validation updated validation object.
     */
    public function match_status( ?string $match_status, string $error_field = 'match', bool $required = false ): object {
        if ( empty( $match_status ) ) {
            if ( $required ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'No match status selected', 'racketmanager' );
            }
        } else {
            $match_status_values = explode( '_', $match_status );
            $status_value        = $match_status_values[0];
            $player_ref          = $match_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $this->error      = true;
                        $this->err_flds[] = $error_field;
                        $this->err_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                    }
                    break;
                case 'none':
                case 'abandoned':
                case 'cancelled':
                case 'share':
                    break;
                default:
                    $this->error      = true;
                    $this->err_flds[] = $error_field;
                    $this->err_msgs[] = __( 'Match status not valid', 'racketmanager' );
                    break;
            }
        }
        return $this;
    }
    /**
     * Validate rubber
     *
     * @param ?int $rubber_id rubber id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function rubber( ?int $rubber_id, string $error_field = 'match' ): object {
        if ( empty( $rubber_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Rubber id not supplied', 'racketmanager' );
        } else {
            $rubber = get_rubber( $rubber_id );
            if ( ! $rubber ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Rubber not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate rubber number
     *
     * @param ?int $rubber_number rubber number.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function rubber_number( ?int $rubber_number, string $error_field = 'match' ): object {
        if ( empty( $rubber_number ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Rubber number not supplied', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate score status
     *
     * @param ?string $score_status score status.
     * @param string  $error_field error field.
     * @param bool    $required is value required.
     * @return object $validation updated validation object.
     */
    public function score_status( ?string $score_status, string $error_field = 'match', bool $required = false ): object {
        if ( empty( $score_status ) ) {
            if ( $required ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'No match status selected', 'racketmanager' );
            }
        } else {
            $score_status_values = explode( '_', $score_status );
            $status_value        = $score_status_values[0];
            $player_ref = $score_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $this->error      = true;
                        $this->err_flds[] = 'score_status';
                        $this->err_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                    }
                    break;
                case 'share':
                case 'none':
                case 'invalid':
                case 'abandoned':
                    break;
                default:
                    $this->error      = true;
                    $this->err_flds[] = 'score_status';
                    $this->err_msgs[] = __( 'Score status not valid', 'racketmanager' );
                    break;
            }
        }
        return $this;
    }

    /**
     * Validate match score
     *
     * @param object $match match object.
     * @param array|null $sets sets.
     * @param string|null $match_status match status.
     * @param string      $set_prefix_start
     * @param int|null $rubber_number
     *
     * @return object $validation updated validation object.
     */
    public function match_score( object $match, ?array $sets, ?string $match_status, string $set_prefix_start, ?int $rubber_number = null ): object {
        $score_validator = new Score_Validation_Service();
        $context         = Scoring_Context::from_legacy_match( $match );
        $score_validator->validate( $context, $sets, $match_status, $set_prefix_start, $rubber_number );

        if ( $score_validator->get_error() ) {
            $this->error    = true;
            $this->err_flds = array_merge( $this->err_flds, $score_validator->get_err_flds() );
            $this->err_msgs = array_merge( $this->err_msgs, $score_validator->get_err_msgs() );
        }

        $this->home_points = $score_validator->get_home_points();
        $this->away_points = $score_validator->get_away_points();
        $this->sets        = $score_validator->get_sets();
        $this->stats       = $score_validator->get_stats();
        $this->points      = $score_validator->get_points();

        return $this;
    }

    /**
     * Validate team match action
     *
     * @param string|null $action
     *
     * @return object
     */
    public function result_action( ?string $action ): object {
        if ( empty( $action) ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Action is not set', 'racketmanager' );
            $this->err_flds[] = 'action';
        } elseif ( 'results' !== $action && 'confirm' !== $action ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Invalid action', 'racketmanager' );
            $this->err_flds[] = 'action';
        }
        return $this;
    }

    /**
     * Function to validate a result confirmation action.
     *
     * @param string|null $result_confirm
     * @param string|null $comments
     *
     * @return object
     */
    public function result_confirm( ?string $result_confirm, ?string $comments ): object {
        if ( empty( $result_confirm ) ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Either confirm or challenge result', 'racketmanager' );
            $this->err_flds[] = 'resultConfirm';
            $this->err_flds[] = 'resultChallenge';
            $this->status     = 400;
        } elseif ( 'C' === $result_confirm ) {
            if ( empty( $comments ) ) {
                $this->error      = true;
                $this->err_msgs[] = __( 'You must enter a reason for challenging the result', 'racketmanager' );
                $this->err_flds[] = 'resultConfirmComments';
                $this->status     = 400;
            }
        } elseif ( 'A' !== $result_confirm ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Invalid option selected', 'racketmanager' );
            $this->err_flds[] = 'resultConfirm';
            $this->err_flds[] = 'resultChallenge';
            $this->status     = 400;
        }
        return $this;
    }

    /**
     * Function to check if a user is in a team
     *
     * @param object $is_update_allowed
     * @param array $match_players
     *
     * @return object
     */
    public function can_player_enter_result( object $is_update_allowed, array $match_players ): object {
        if ( ! $is_update_allowed->user_can_update ) {
            $this->error = true;
            $this->msg   = __( 'Result entry not permitted', 'racketmanager' );
            return $this;
        }

        if ( 'player' !== $is_update_allowed->user_type ) {
            return $this;
        }

        try {
            $registrations = $this->registration_service->get_clubs_for_player( get_current_user_id() );
            if ( ! $this->is_player_in_match( $registrations, $match_players ) ) {
                $this->error = true;
                $this->msg   = __( 'Player cannot submit results', 'racketmanager' );
            }
        } catch ( Player_Not_Found_Exception ) {
            $this->error = true;
            $this->msg   = __( 'Player not found', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Check if any of the player's registrations match a player in the match.
     *
     * @param array $registrations
     * @param array $match_players
     *
     * @return bool
     */
    private function is_player_in_match( array $registrations, array $match_players ): bool {
        foreach ( $registrations as $registration ) {
            $registration_id = $registration->registration_id;
            foreach ( $match_players as $teams ) {
                foreach ( $teams as $players ) {
                    foreach ( $players as $player ) {
                        if ( intval( $player ) === $registration_id ) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Function to check players involved in a match
     *
     * @param array $players
     * @param array $player_numbers
     * @param int $rubber
     * @param bool $playoff
     * @param bool $reverse_rubber
     *
     * @return object
     */
    public function players_involved( array $players, array $player_numbers, int $rubber, bool $playoff, bool $reverse_rubber ): object {
        $opponents = array( 'home', 'away' );
        foreach ( $opponents as $opponent ) {
            $team_players = $players[ $opponent ] ?? array();
            $this->validate_team_players( $team_players, $player_numbers, $rubber, $opponent, $playoff, $reverse_rubber );
        }
        return $this;
    }

    /**
     * Validate players for a specific team (home or away).
     *
     * @param array  $team_players
     * @param array  $player_numbers
     * @param int    $rubber
     * @param string $opponent
     * @param bool   $playoff
     * @param bool   $reverse_rubber
     */
    private function validate_team_players( array $team_players, array $player_numbers, int $rubber, string $opponent, bool $playoff, bool $reverse_rubber ): void {
        foreach ( $player_numbers as $player_number ) {
            if ( empty( $team_players[ $player_number ] ) ) {
                $this->add_player_error( 'Player not selected', $rubber, $opponent, $player_number );
                continue;
            }

            $player_ref  = $team_players[ $player_number ];
            $club_player = $this->registration_service->get_registration( $player_ref );
            if ( ! $club_player->system_record ) {
                $this->validate_player_eligibility( $player_ref, $rubber, $opponent, $player_number, $playoff, $reverse_rubber );
            }
        }
    }

    /**
     * Validate player eligibility based on match context.
     *
     * @param int|string $player_ref
     * @param int        $rubber
     * @param string     $opponent
     * @param int        $player_number
     * @param bool       $playoff
     * @param bool       $reverse_rubber
     */
    private function validate_player_eligibility( int|string $player_ref, int $rubber, string $opponent, int $player_number, bool $playoff, bool $reverse_rubber ): void {
        $player_found = in_array( $player_ref, $this->players_involved, true );

        if ( ! $player_found ) {
            $this->handle_new_player( $player_ref, $rubber, $opponent, $player_number, $playoff, $reverse_rubber );
        } elseif ( ! $playoff && ! $reverse_rubber ) {
            $this->add_player_error( 'Player already selected', $rubber, $opponent, $player_number );
        }
    }

    /**
     * Handle a player who hasn't been tracked yet in the current match.
     *
     * @param int|string $player_ref
     * @param int        $rubber
     * @param string     $opponent
     * @param int        $player_number
     * @param bool       $playoff
     * @param bool       $reverse_rubber
     */
    private function handle_new_player( int|string $player_ref, int $rubber, string $opponent, int $player_number, bool $playoff, bool $reverse_rubber ): void {
        if ( $playoff ) {
            $this->add_player_error( 'Player for playoff must have played', $rubber, $opponent, $player_number );
        } elseif ( $reverse_rubber ) {
            $this->add_player_error( 'Player for reverse rubber must have played', $rubber, $opponent, $player_number );
        } else {
            $this->players_involved[] = $player_ref;
        }
    }

    /**
     * Helper to add a player-related validation error.
     *
     * @param string $message
     * @param int    $rubber
     * @param string $opponent
     * @param int    $player_number
     */
    private function add_player_error( string $message, int $rubber, string $opponent, int $player_number ): void {
        $this->error      = true;
        $this->err_flds[] = 'players_' . $rubber . '_' . $opponent . '_' . $player_number;
        $this->err_msgs[] = __( $message, 'racketmanager' );
    }
}
