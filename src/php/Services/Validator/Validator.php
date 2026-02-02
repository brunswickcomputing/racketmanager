<?php
/**
 * Validator API: player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validator
 */

namespace Racketmanager\Services\Validator;

use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use WP_Error;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_player;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;
use function Racketmanager\is_lta_number_required;

/**
 * Class to implement the Validator object
 */
class Validator {
    /**
     * Error indicator
     *
     * @var boolean
     */
    public bool $error;
    /**
     * Error field
     *
     * @var array
     */
    public array $err_flds;
    /**
     * Error message
     *
     * @var array
     */
    public array $err_msgs;
    /**
     * Status
     *
     * @var int|null
     */
    public ?int $status;
    /**
     * Message
     *
     * @var string|null
     */
    public ?string $msg;
    public WP_Error $err;
    protected Player_Service $player_service;
    protected Registration_Service $registration_service;
    protected Club_Service $club_service;
    protected Competition_Service $competition_service;

    /**
     * Constructor
     */
    public function __construct() {
        global $racketmanager;
        $this->error    = false;
        $this->err_flds = array();
        $this->err_msgs = array();
        $this->status   = null;
        $this->msg      = null;
        $this->err      = new WP_Error();

        $c                          = $racketmanager->container;
        $this->competition_service  = $c->get( 'competition_service' );
        $this->club_service         = $c->get( 'club_service' );
        $this->player_service       = $c->get( 'player_service' );
        $this->registration_service = $c->get( 'registration_service' );
    }

    /**
     * Validate security token
     *
     * @param string $nonce nonce name.
     * @param string $nonce_action nonce action.
     *
     * @return object $validation updated validation object.
     */
    public function check_security_token( string $nonce = 'security', string $nonce_action = 'ajax-nonce' ): object {
        if ( isset( $_REQUEST[ $nonce ] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $nonce ] ) ), $nonce_action ) ) {
                $this->error  = true;
                $this->msg    = __( 'Sorry, the action could not be completed. The link or form you are using has expired or is invalid. Please try again, or go back to the previous page and try again', 'racketmanager' );
                $this->status = 403;
            }
        } else {
            $this->error  = true;
            $this->msg    = __( 'There was a problem with your request. Please try again or refresh the page', 'racketmanager' );
            $this->status = 403;
        }
        return $this;
    }
    /**
     * Validate capability
     *
     * @param string|null $capability nonce name.
     *
     * @return object $validation updated validation object.
     */
    public function capability( ?string $capability ): object {
        if ( empty( $capability ) ) {
            $this->error  = true;
            $this->msg    = __( 'Capability not provided', 'racketmanager' );
            $this->status = 401;
        } elseif ( ! current_user_can( $capability ) ) {
            $this->error  = true;
            $this->msg    = __( 'You do not have permission to perform this task', 'racketmanager' );
            $this->status = 401;
        }
        return $this;
    }
    /**
     * Validate team
     *
     * @param int|null $team_id team id.
     *
     * @return object $validation updated validation object.
     */
    public function team( ?int $team_id ): object {
        if ( empty( $team_id ) ) {
            $error_field   = 'contactno';
            $error_message = __( 'Team id not found', 'racketmanager' );
            $status        = 404;
            $this->set_errors( $error_field, $error_message, $status );
        } else { // TODO: remove get_team
            $team = get_team( $team_id );
            if ( ! $team ) {
                $error_field   = 'contactno';
                $error_message = __( 'Team not found', 'racketmanager' );
                $status        = 404;
                $this->set_errors( $error_field, $error_message, $status );
            }
        }
        return $this;
    }
    /**
     * Validate player
     *
     * @param int $player_id player id.
     *
     * @return object $validation updated validation object.
     */
    public function player( int $player_id ): object {
        if ( empty( $player_id ) ) {
            $this->error      = true;
            $this->err_flds[] = 'contactno';
            $this->err_msgs[] = __( 'Player id required', 'racketmanager' );
        } else {
            $player = get_player( $player_id );
            if ( ! $player ) {
                $this->error      = true;
                $this->err_flds[] = 'contactno';
                $this->err_msgs[] = __( 'Player not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate first name
     *
     * @param string|null $first_name first name.
     *
     * @return object $validation updated validation object.
     */
    public function first_name( ?string $first_name ): object {
        if ( empty( $first_name ) ) {
            $this->error      = true;
            $this->err_flds[] = 'firstname';
            $this->err_msgs[] = __( 'First name is required', 'racketmanager' );
            $this->err->add( 'firstname', __( 'First name is required', 'racketmanager' ) );
        }
        return $this;
    }
    /**
     * Validate surname
     *
     * @param string|null $surname surname.
     *
     * @return object $validation updated validation object.
     */
    public function surname( ?string $surname ): object {
        if ( empty( $surname ) ) {
            $this->error      = true;
            $this->err_flds[] = 'surname';
            $this->err_msgs[] = __( 'Surname is required', 'racketmanager' );
            $this->err->add( 'surname', __( 'Surname is required', 'racketmanager' ) );
        }
        return $this;
    }
    /**
     * Validate gender
     *
     * @param string|null $gender gender.
     *
     * @return object $validation updated validation object.
     */
    public function gender( ?string $gender ): object {
        if ( empty( $gender ) ) {
            $this->error      = true;
            $this->err_flds[] = 'gender';
            $this->err_msgs[] = __( 'Gender is required', 'racketmanager' );
            $this->err->add( 'gender', __( 'Gender is required', 'racketmanager' ) );
        }
        return $this;
    }
    /**
     * Validate telephone
     *
     * @param string|null $telephone telephone number.
     * @param string|null $field_ref field.
     * @param bool        $field_ref_override field ref override.
     *
     * @return object $validation updated validation object.
     */
    public function telephone( ?string $telephone, ?string $field_ref = null, bool $field_ref_override = false ): object {
        $err_field = 'contactno';
        if ( $field_ref_override ) {
            $err_field = $field_ref;
        } elseif ( $field_ref ) {
            $err_field .= '-' . $field_ref;
        }
        if ( empty( $telephone ) ) {
            $this->error      = true;
            $this->err_flds[] = $err_field;
            $this->err_msgs[] = __( 'Telephone number required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate email
     *
     * @param string|null $email email address.
     * @param int|null    $player_id player id.
     * @param bool   $email_required is an email address required?
     * @param string|null $field_ref field.
     * @param bool $field_ref_override field ref override.
     *
     * @return object $validation updated validation object.
     */
    public function email( ?string $email, ?int $player_id, bool $email_required = true, ?string $field_ref = null, bool $field_ref_override = false ): object {
        $err_field = 'contactemail';
        if ( $field_ref_override ) {
            $err_field = $field_ref;
        } elseif ( $field_ref ) {
            $err_field .= '-' . $field_ref;
        }
        if ( empty( $email ) ) {
            if ( $email_required ) {
                $this->error      = true;
                $this->err_flds[] = $err_field;
                $this->err_msgs[] = __( 'Email address is required', 'racketmanager' );
                $this->err->add( $err_field, __( 'Email address is required', 'racketmanager' ) );
            }
        } else {
            $player = get_player( $email, 'email' );
            if ( $player && $player_id !== $player->ID ) {
                $this->error      = true;
                $this->err_flds[] = $err_field;
                $this->err_msgs[] = sprintf( __( 'Email address already used by %s', 'racketmanager' ), $player->display_name );
                $this->err->add( $err_field, sprintf( __( 'Email address already used by %s', 'racketmanager' ), $player->display_name ) );
            }
        }
        return $this;
    }
    /**
     * Validate btm
     *
     * @param int|null $btm lta tennis number.
     * @param int|null $player_id player id.
     *
     * @return object $validation updated validation object.
     */
    public function btm( ?int $btm, ?int $player_id ): object {
        $btm_required = is_lta_number_required();
        if ( empty( $btm ) ) {
            if ( $btm_required ) {
                $this->error      = true;
                $this->err_flds[] = 'btm';
                $this->err_msgs[] = __( 'LTA Tennis Number is required', 'racketmanager' );
                $this->err->add( 'btm', __( 'LTA Tennis Number is required', 'racketmanager' ) );
            }
        } else {
            $player = get_player( $btm, 'btm' );
            if ( $player && $player_id !== $player->ID ) {
                $this->error      = true;
                $this->err_flds[] = 'btm';
                $this->err_msgs[] = sprintf( __( 'LTA Tennis Number already used by %s', 'racketmanager' ), $player->display_name );
                $this->err->add( 'btm', sprintf( __( 'LTA Tennis Number already used by %s', 'racketmanager' ), $player->display_name ) );
            }
        }
        return $this;
    }
    /**
     * Validate season
     *
     * @param string|null $season season.
     *
     * @return object $validation updated validation object.
     */
    public function season( ?string $season ): object {
        if ( empty( $season ) ) {
            $error_field   = 'season';
            $error_message = __( 'Season is required', 'racketmanager' );
            $status        = 400;
            $this->set_errors( $error_field, $error_message, $status );
        }
        return $this;
    }

    /**
     * Validate season for event/competition
     *
     * @param string|null $season season.
     * @param array|null $seasons
     *
     * @return object $validation updated validation object.
     */
    public function season_set( ?string $season, array|null $seasons ): object {
        $error_field = 'season';
        // Validate season value
        if ( empty( $season ) ) {
            $error_message = __( 'Season is required', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( empty( $seasons ) ) {
            $error_message = __( 'No seasons found', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( empty( $seasons[ $season ] ) ) {
            $error_message = __( 'Season not found', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
    /**
     * Validate club
     *
     * @param string|null $club_id club.
     * @param string $error_field error field.
     *
     * @return object $validation updated validation object.
     */
    public function club( ?string $club_id, string $error_field = 'club' ): object {
        if ( empty( $club_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Club not found', 'racketmanager' );
        } else {
            $club = get_club( $club_id );
            if ( ! $club ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Club not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate competition
     *
     * @param int|string|null $competition competition.
     * @param bool $exists check if competition exists.
     *
     * @return object $validation updated validation object.
     */
    public function competition( int|string|null $competition, bool $exists = true ): object {
        $error_field   = 'competition';
        if ( empty( $competition ) ) {
            $error_message = __( 'Competition not specified', 'racketmanager' );
            $status        = 404;
            $this->set_errors( $error_field, $error_message, $status );
        } else {
            if ( is_int( $competition ) ) {
                $competition = $this->competition_service->get_by_id( $competition );
            } elseif ( is_string( $competition ) ) {
                $competition = $this->competition_service->get_by_id( $competition );
            }
            if ( ! $competition ) {
                if ( $exists ) {
                    $error_message = __( 'Competition not found', 'racketmanager' );
                    $status        = 404;
                    $this->set_errors( $error_field, $error_message, $status );
                }
            } else {
                if ( ! $exists ) {
                    $error_message = __( 'Competition already found', 'racketmanager' );
                    $status        = 400;
                    $this->set_errors( $error_field, $error_message, $status );
                }
            }
        }
        return $this;
    }

    /**
     * Compare values
     *
     * @param int|string|null $passed new value.
     * @param int|string|null $original original value.
     * @return object $validation updated validation object.
     */
    public function compare( int|string|null $passed, int|string|null $original, $error_field = 'compare' ): object {
        if ( empty( $passed ) ) {
            $error_message = __( 'New value not found', 'racketmanager' );
            $status        = 400;
            $this->set_errors( $error_field, $error_message, $status );
        } elseif ( empty( $original ) ) {
            $error_message = __( 'Original value not found', 'racketmanager' );
            $status        = 400;
            $this->set_errors( $error_field, $error_message, $status );
        } elseif( $passed !== $original ) {
            $error_message = __( 'Passed values do not match', 'racketmanager' );
            $status        = 400;
            $this->set_errors( $error_field, $error_message, $status );
        }
        return $this;
    }
    /**
     * Validate event
     *
     * @param object|int|null|string $event event.
     *
     * @return object $validation updated validation object.
     */
    public function event( object|int|null|string $event = null ): object {
        if ( empty( $event ) ) {
            $error_field   = 'event';
            $error_message = __( 'Event id not found', 'racketmanager' );
            $status        = 404;
            $this->set_errors( $error_field, $error_message, $status );
        } else { // TODO:remove event lookup
            if ( is_int( $event ) ) {
                $event = get_event( $event );
            } elseif ( is_string( $event ) ) {
                $event = get_event( $event, 'name' );
            }
            if ( ! $event ) {
                $error_field   = 'event';
                $error_message = __( 'Event not found', 'racketmanager' );
                $status        = 404;
                $this->set_errors( $error_field, $error_message, $status );
            }
        }
        return $this;
    }
    /**
     * Validate tournament
     *
     * @param string|null $tournament tournament.
     *
     * @return object $validation updated validation object.
     */
    public function tournament( ?string $tournament ): object {
        if ( empty( $tournament ) ) {
            $this->error      = true;
            $this->err_flds[] = 'tournament';
            $this->err_msgs[] = __( 'Tournament not found', 'racketmanager' );
        } else {
            if ( is_numeric( $tournament ) ) {
                $tournament = get_tournament( $tournament );
            } else {
                $tournament = get_tournament( $tournament, 'name' );
            }
            if ( ! $tournament ) {
                $this->error      = true;
                $this->err_flds[] = 'tournament';
                $this->err_msgs[] = __( 'Tournament not valid', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate a competition type
     *
     * @param string|null $type competition type.
     *
     * @return object $validation updated validation object.
     */
    public function competition_type( ?string $type ): object {
        if ( empty( $type ) ) {
            $this->error      = true;
            $this->err_flds[] = 'type';
            $this->err_msgs[] = __( 'Competition type not specified', 'racketmanager' );
        } else {
            $valid = Util_Lookup::get_competition_type( $type );
            if ( ! $valid ) {
                $this->error      = true;
                $this->err_flds[] = 'type';
                $this->err_msgs[] = __( 'Competition type not valid', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate age group
     *
     * @param string|null $age_group age group.
     *
     * @return object $validation updated validation object.
     */
    public function age_group( ?string $age_group ): object {
        if ( empty( $age_group ) ) {
            $this->error      = true;
            $this->err_flds[] = 'age_group';
            $this->err_msgs[] = __( 'Age group not specified', 'racketmanager' );
        } else {
            $valid = Util_Lookup::get_age_group( $age_group );
            if ( ! $valid ) {
                $this->error      = true;
                $this->err_flds[] = 'age_group';
                $this->err_msgs[] = __( 'Age group not valid', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate captain details
     *
     * @param string|null $captain captain id.
     * @param string|null $contactno contact number.
     * @param string|null $contactemail email.
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function captain( ?string $captain, ?string $contactno, ?string $contactemail, string $field_ref ): object {
        if ( empty( $captain ) ) {
            $error_field   = 'captain-' . $field_ref;
            $error_message = __( 'Captain not selected', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } else {
            if ( empty( $contactno ) ) {
                $error_field   = 'contactno-' . $field_ref;
                $error_message = __( 'Telephone number required', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
            if ( empty( $contactemail ) ) {
                $error_field   = 'contactemail-' . $field_ref;
                $error_message = __( 'Email required', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        }
        return $this;
    }

    /**
     * Validate match day details
     *
     * @param int|null $match_day match day.
     * @param string   $field_ref field reference.
     * @param boolean  $match_day_restriction match day restriction indicator.
     * @param array    $match_days_allowed array of match days allowed.
     * @return object $validation updated validation object.
     */
    public function match_day( ?int $match_day, string $field_ref, bool $match_day_restriction = false, array $match_days_allowed = array() ): object {
        $error_field = 'matchday-' . $field_ref;
        if ( empty( $match_day ) && 0 !== $match_day ) {
            $error_message = __( 'Match day not selected', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( $match_day_restriction ) {
            if ( !empty( $match_days_allowed ) && empty( $match_days_allowed[ $match_day ] ) ) {
                $error_message = __( 'Match day not valid for event', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        }
        return $this;
    }

    /**
     * Validate match time details
     *
     * @param string|null $match_time match time.
     * @param string $field_ref field reference.
     * @param int|null $match_day match day.
     * @param array|null $start_times min/max start times.
     * @return object $validation updated validation object.
     */
    public function match_time( ?string $match_time, string $field_ref, ?int $match_day = null, ?array $start_times = array() ): object {
        $error_field = 'matchtime-' . $field_ref;
        if ( empty( $match_time ) ) {
            $error_message = __( 'Match time not selected', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( isset( $match_day ) &  $match_day >= 0 ) {
            $match_time = substr( $match_time, 0, 5 );
            if ( $match_day <= 4 ) {
                $index = 'weekday';
            } else {
                $index = 'weekend';
            }
            if ( isset( $start_times[ $index ] ) ) {
                if ( $match_time < $start_times[ $index ]['min'] ) {
                    $error_message = __( 'Match time less than earliest start', 'racketmanager' );
                    $this->set_errors( $error_field, $error_message );
                } elseif ( $match_time > $start_times[ $index ]['max'] ) {
                    $error_message = __( 'Match time greater than latest start', 'racketmanager' );
                    $this->set_errors( $error_field, $error_message );
                }
            }
        }
        return $this;
    }
    /**
     * Validate number of courts available
     *
     * @param int|null $num_courts_available number of courts available.
     * @return object $validation updated validation object.
     */
    public function num_courts_available( ?int $num_courts_available ): object {
        if ( empty( $num_courts_available ) ) {
            $error_field   = 'numCourtsAvailable';
            $error_message = __( 'You must specify the number of courts available', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
    /**
     * Validate venue
     *
     * @param int|null $venue venue.
     *
     * @return object $validation updated validation object.
     */
    public function venue( ?int $venue ): object {
        if ( empty( $venue ) ) {
            $this->error      = true;
            $this->err_flds[] = 'venue';
            $this->err_msgs[] = __( 'Venue is required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate league
     *
     * @param int|null $league_ref league.
     *
     * @return object $validation updated validation object.
     */
    public function league( ?int $league_ref ): object {
        if ( empty( $league_ref ) ) {
            $this->error      = true;
            $this->err_flds[] = 'league';
            $this->err_msgs[] = __( 'League id not found', 'racketmanager' );
            $this->status     = 404;
        } else {
            $league = get_league( $league_ref );
            if ( ! $league ) {
                $this->error      = true;
                $this->err_flds[] = 'league_id';
                $this->err_msgs[] = __( 'League not found', 'racketmanager' );
                $this->status     = 404;
            }
        }
        return $this;
    }
    /**
     * Validate modal
     *
     * @param ?string $modal modal name.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function modal( ?string $modal, string $error_field = 'match' ): object {
        if ( empty( $modal ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Modal name not supplied', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate type
     *
     * @param string|null $type type.
     *
     * @return object $validation updated validation object.
     */
    public function type( ?string $type ): object {
        if ( ! $type ) {
            $this->error      = true;
            $this->err_flds[] = 'type';
            $this->err_msgs[] = __( 'Type must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Get validation details
     *
     * @return stdClass
     */
    public function get_details(): object {
        $return           = new stdClass();
        $return->error    = $this->error;
        $return->err_flds = $this->err_flds;
        $return->err_msgs = $this->err_msgs;
        $return->msg      = $this->msg;
        if ( $this->error && empty( $this->status ) ) {
            $this->status = 400;
        }
        $return->status   = $this->status;
        return $return;
    }

    public function set_errors( string $error_field, string $error_message, int $status = 400 ): void {
        $this->error      = true;
        $this->err_flds[] = $error_field;
        $this->err_msgs[] = $error_message;
        $this->status     = $status;
        $this->err->add( $error_field, $error_message );
    }
}
