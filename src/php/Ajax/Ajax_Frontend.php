<?php
/**
 * AJAX Front end response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager\Ajax;

use DateMalformedStringException;
use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Validator\Validator;
use Racketmanager\Validator\Validator_Entry_Form;
use stdClass;
use function Racketmanager\event_team_match_dropdown;
use function Racketmanager\get_club;
use function Racketmanager\get_club_player;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;
use function Racketmanager\player_search;
use function Racketmanager\show_alert;
use function Racketmanager\show_team_edit_modal;
use function Racketmanager\team_order_players;

/**
 * Implement AJAX front end responses.
 *
 * @author Paul Moffat
 */
class Ajax_Frontend extends Ajax {
    public string $team_not_found;
    public string $no_event_id;
    public string $no_match_id ;
    public string $no_modal;
    public string $not_played;
    public string $match_not_found;
    public string $club_not_found;
    /**
     * Register ajax actions.
     */
    public function __construct() {
        parent::__construct();

        add_action( 'wp_ajax_nopriv_racketmanager_update_team',     array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_player',   array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_cup_entry',       array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_league_entry',    array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_team_edit_modal', array( &$this, 'logged_out_modal' ) );

        add_action( 'wp_ajax_racketmanager_update_team',   array( &$this, 'update_team' ) );
        add_action( 'wp_ajax_racketmanager_update_player', array( &$this, 'update_player' ) );
        add_action( 'wp_ajax_racketmanager_get_team_info', array( &$this, 'get_team_event_info' ) );
        add_action( 'wp_ajax_racketmanager_cup_entry',     array( &$this, 'cup_entry_request' ) );
        add_action( 'wp_ajax_racketmanager_league_entry',  array( &$this, 'league_entry_request' ) );

        add_action( 'wp_ajax_racketmanager_search_players',                       array( &$this, 'search_players' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_search_players',                array( &$this, 'search_players' ) );
        add_action( 'wp_ajax_racketmanager_get_tab_data',                         array( &$this, 'tab_data' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_get_tab_data',                  array( &$this, 'tab_data' ) );
        add_action( 'wp_ajax_racketmanager_show_team_order_players',              array( &$this, 'show_team_order_players' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_show_team_order_players',       array( &$this, 'show_team_order_players' ) );
        add_action( 'wp_ajax_racketmanager_validate_team_order',                  array( &$this, 'validate_team_order' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_validate_team_order',           array( &$this, 'validate_team_order' ) );
        add_action( 'wp_ajax_racketmanager_team_edit_modal',                      array( &$this, 'show_team_edit_modal' ) );
        add_action( 'wp_ajax_racketmanager_get_event_team_match_dropdown',        array( &$this, 'get_event_team_match_dropdown' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_get_event_team_match_dropdown', array( &$this, 'get_event_team_match_dropdown' ) );
        $this->team_not_found = __( 'Team not found', 'racketmanager' );
        $this->no_event_id = __( 'Event id not supplied', 'racketmanager' );
        $this->no_match_id = __( 'Match id not supplied', 'racketmanager' );
        $this->no_modal = __( 'Modal name not supplied', 'racketmanager' );
        $this->not_played = __( 'Not played', 'racketmanager' );
        $this->match_not_found = __( 'Match not found', 'racketmanager' );
        $this->club_not_found = __( 'Club not found', 'racketmanager' );
        $this->event_not_found   = __( 'Event not found', 'racketmanager' );
    }
    /**
     * Update Team
     *
     * @see templates/team.php
     */
    public function update_team(): void {
        $team_details = null;
        $team_id      = null;
        $event_id     = null;
        $validator    = new Validator();
        $validator    = $validator->check_security_token( 'racketmanager_nonce', 'team-update' );
        if ( empty( $validator->error ) ) {
            $event_id = empty( $_POST['event_id'] ) ? null : intval( $_POST['event_id'] );
            $team_id  = empty( $_POST['team_id'] ) ? null : intval( $_POST['team_id'] );
            $validator = $validator->event( $event_id );
            $validator = $validator->team( $team_id );
        }
        if ( empty( $validator->error ) ) {
            $field_ref    = $event_id . '-' . $team_id;
            $team_details = $this->get_team_input( $field_ref );
            $validator    = $validator->captain( $team_details->captain_id, $team_details->contactno, $team_details->contactemail, $field_ref );
            $validator    = $validator->telephone( $team_details->contactno, $field_ref );
            $validator    = $validator->email( $team_details->contactemail, $team_details->captain_id, true, $field_ref );
            $validator    = $validator->match_day( $team_details->match_day, $field_ref );
            $validator    = $validator->match_time( $team_details->match_time, $field_ref );
        }
        if ( empty( $validator->error ) ) {
            $team = get_team( $team_id );
            $msg = $team->update_event( $event_id, $team_details->captain_id, $team_details->contactno, $team_details->contactemail, $team_details->match_day, $team_details->match_time );
            wp_send_json_success( $msg );
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to update team', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }
    /**
     * @param string $field_ref
     */
    private function get_team_input( string $field_ref ): object {
        $team_details               = new stdClass();
        $field_ref                  = '-' . $field_ref;
        $team_details->captain_id   = empty( $_POST['captainId' . $field_ref ] ) ? null : sanitize_text_field( wp_unslash( $_POST['captainId' . $field_ref ] ) );
        $team_details->contactno    = empty( $_POST['contactno' . $field_ref ] ) ? null : sanitize_text_field( wp_unslash( $_POST['contactno' . $field_ref ] ) );
        $team_details->contactemail = empty( $_POST['contactemail' . $field_ref] ) ? null : sanitize_text_field( wp_unslash( $_POST['contactemail' . $field_ref] ) );
        $team_details->match_day    = isset( $_POST['matchday' . $field_ref ] ) ? intval( $_POST[ 'matchday' . $field_ref ] ) : null;
        $team_details->match_time   = empty( $_POST['matchtime' . $field_ref ] ) ? null : sanitize_text_field( wp_unslash( $_POST['matchtime' . $field_ref ] ) );
        return $team_details;
    }

    /**
     * Update Player
     *
     * @return void
     */
    public function update_player(): void {
        $update  = null;
        $team_id = null;
        $player  = null;
        $type    = null;
        $userid  = null;
        $value   = null;
        $player_id = null;
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'update-player' );
        if ( empty( $validator->error ) ) {
            $team_id   = empty( $_POST['team_id'] ) ? null : intval( $_POST['team_id'] );
            $validator = $validator->team( $team_id );
        }
        if ( empty( $validator->error ) ) {
            $type     = empty( $_POST['type'] ) ? null : sanitize_text_field( wp_unslash( $_POST['type'] ) );
            $userid   = empty( $_POST['userid'] ) ? null : intval( $_POST['userid'] );
            $player   = empty( $_POST['player'] ) ? null : intval( $_POST['player'] );
            $value    = empty( $_POST['value'] ) ? null : sanitize_textarea_field( wp_unslash( $_POST['value'] ) );
            $player_id = empty( $_POST['playerId'] ) ? null : intval( $_POST['playerId'] );
            $validator = $validator->team_player( $userid, $player, $player_id, $type );
        }
        if ( empty( $validator->error ) ) {
            $team = get_team( $team_id );
            $msg  = $team->update_player( $type, $userid, $player_id, $value );
            wp_send_json_success( $msg );
        }
        $return = $validator->get_details();
        wp_send_json_error( $return, $return->status );
    }

    /**
     * Get team event info
     */
    public function get_team_event_info(): void {
        $return   = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $event_id = empty( $_POST['event_id'] ) ? null : intval( $_POST['event_id'] );
            $team_id  = empty( $_POST['team_id'] ) ? null : intval( $_POST['team_id'] );
            $validator = new Validator();
            $validator = $validator->event( $event_id );
            $validator = $validator->team( $team_id );
            if ( empty( $validator->error ) ) {
                $team             = get_team( $team_id );
                $team_event_info  = $team->get_event_info( $event_id );
                $return->message  = __( 'Team information', 'racketmanager' );
                $return->info     = $team_event_info;
                wp_send_json_success( $return );
            }
            $return = $validator->get_details();
        }
        wp_send_json_error( $return, $return->status );
    }

    /**
     * Update player entries from tournament entry form
     */
    public function cup_entry_request(): void {
        $start_times = array();
        $club_id     = null;
        $club_entry  = null;
        $validator   = new Validator_Entry_Form();
        //phpcs:disable WordPress.Security.NonceVerification.Missing
        $validator = $validator->nonce( 'cup-entry' );
        if ( ! $validator->error ) {
            if ( ! is_user_logged_in() ) {
                $validator = $validator->logged_in_entry();
            } else {
                $season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : '';
                $competition_id = isset( $_POST['competitionId'] ) ? sanitize_text_field( wp_unslash( $_POST['competitionId'] ) ) : '';
                $club_id        = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : '';
                //phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $events         = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : array();
                $teams          = isset( $_POST['team'] ) ? wp_unslash( $_POST['team'] ) : array();
                $captains       = isset( $_POST['captain'] ) ? wp_unslash( $_POST['captain'] ) : array();
                $captain_ids    = isset( $_POST['captainId'] ) ? wp_unslash( $_POST['captainId'] ) : array();
                $contact_nos    = isset( $_POST['contactno'] ) ? wp_unslash( $_POST['contactno'] ) : array();
                $contact_emails = isset( $_POST['contactemail'] ) ? wp_unslash( $_POST['contactemail'] ) : array();
                $match_days     = isset( $_POST['matchday'] ) ? wp_unslash( $_POST['matchday'] ) : array();
                $match_times    = isset( $_POST['matchtime'] ) ? wp_unslash( $_POST['matchtime'] ) : array();
                //phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $comments             = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
                $club_entry           = new stdClass();
                $club_entry->club     = $club_id;
                $club_entry->season   = $season;
                $club_entry->comments = $comments;
                if ( $competition_id ) {
                    $competition = get_competition( $competition_id );
                    if ( $competition ) {
                        if ( ! empty( $competition->start_time['weekday']['min'] ) && ! empty( $competition->start_time['weekday']['max'] ) ) {
                            $start_times['weekday']['min'] = $competition->start_time['weekday']['min'];
                            $start_times['weekday']['max'] = $competition->start_time['weekday']['max'];
                        }
                        if ( ! empty( $competition->start_time['weekend']['min'] ) && ! empty( $competition->start_time['weekend']['max'] ) ) {
                            $start_times['weekend']['min'] = $competition->start_time['weekend']['min'];
                            $start_times['weekend']['max'] = $competition->start_time['weekend']['max'];
                        }
                    } else {
                        $validator = $validator->competition( $competition );
                    }
                    $club_entry->competition = $competition;
                }

                $validator = $validator->club( $club_id );
                $validator = $validator->events_entry( $events );
                foreach ( $events as $event_id ) {
                    $event      = get_event( $event_id );
                    $team       = $teams[$event->id] ?? null;
                    $field_ref  = $event->id;
                    $field_name = $event->name;
                    $validator  = $validator->teams( $team, $field_ref, $field_name );
                    if ( ! empty( $team ) ) {
                        $captain      = $captains[$event->id] ?? null;
                        $captain_id   = $captain_ids[$event->id] ?? null;
                        $contactno    = $contact_nos[$event->id] ?? null;
                        $contactemail = $contact_emails[$event->id] ?? null;
                        $match_day    = $match_days[$event->id] ?? null;
                        $matchtime    = $match_times[$event->id] ?? null;
                        $validator    = $validator->match_day( $match_day, $field_ref );
                        $validator    = $validator->match_time( $matchtime, $field_ref, $match_day, $start_times );
                        $validator    = $validator->captain( $captain, $contactno, $contactemail, $field_ref );

                        $event_entry             = new stdClass();
                        $event_entry->id         = $event->id;
                        $event_entry->name       = $event->name;
                        $event_entry->team_id    = $team;
                        $event_entry->match_day  = $match_day;
                        $event_entry->match_time = $matchtime;
                        $event_entry->captain_id = $captain_id;
                        $event_entry->captain    = $captain;
                        $event_entry->telephone  = $contactno;
                        $event_entry->email      = $contactemail;
                        $club_entry->events[]    = $event_entry;
                    }
                }
                $acceptance = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
                $validator  = $validator->entry_acceptance( $acceptance );
            }
        }
        if ( empty( $validator->error ) ) {
            $club = get_club( $club_id );
            $club->cup_entry( $club_entry );
            $msg = __( 'Cup entry complete', 'racketmanager' );
            wp_send_json_success( $msg );
        } else {
            $return = $validator;
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
            if ( empty( $return->status ) ) {
                $return->status = 400;
            }
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Update player entries from league entry form
     */
    public function league_entry_request(): void {
        $validator             = new Validator_Entry_Form();
        $club_id               = null;
        $club_entry            = null;
        $courts_needed         = array();
        $match_day_restriction = null;
        $weekend_allowed       = null;
        $start_times           = array();
        $competition_days      = array();
        check_admin_referer( 'league-entry' );
        if ( ! is_user_logged_in() ) {
            $validator = $validator->logged_in_entry();
        } else {
            $season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : '';
            $competition_id = isset( $_POST['competitionId'] ) ? intval( $_POST['competitionId'] ) : null;
            $validator      = $validator->competition( $competition_id );
            $club_id        = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : '';
            $validator      = $validator->club( $club_id );
            $events         = isset( $_POST['event'] ) ? array_map( 'intval', $_POST['event'] ) : array();
            $validator      = $validator->events_entry( $events );
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $team_event           = isset( $_POST['teamEvent'] ) ? wp_unslash( $_POST['teamEvent'] ) : array();
            $team_event_league    = isset( $_POST['teamEventLeague'] ) ? wp_unslash( $_POST['teamEventLeague'] ) : array();
            $competition_events   = explode( ',', isset( $_POST['competition_events'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_events'] ) ) : '' );
            $captains             = isset( $_POST['captain'] ) ? wp_unslash( $_POST['captain'] ) : array();
            $captain_ids          = isset( $_POST['captainId'] ) ? wp_unslash( $_POST['captainId'] ) : array();
            $contact_nos          = isset( $_POST['contactno'] ) ? wp_unslash( $_POST['contactno'] ) : array();
            $contact_emails       = isset( $_POST['contactemail'] ) ? wp_unslash( $_POST['contactemail'] ) : array();
            $match_days           = isset( $_POST['matchday'] ) ? wp_unslash( $_POST['matchday'] ) : array();
            $match_times          = isset( $_POST['matchtime'] ) ? wp_unslash( $_POST['matchtime'] ) : array();
            $comments             = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
            $num_courts_available = isset( $_POST['numCourtsAvailable'] ) ? intval( $_POST['numCourtsAvailable'] ) : 0;
            $validator            = $validator->num_courts_available( $num_courts_available );

            $club_entry           = new stdClass();
            $club_entry->club     = $club_id;
            $club_entry->season   = $season;
            $club_entry->comments = $comments;
            if ( $competition_id ) {
                $competition = get_competition( $competition_id );
                if ( $competition ) {
                    $competition->set_season( $season );
                    $validator = $validator->competition_open( $competition );
                } else {
                    $validator = $validator->competition( $competition );
                }
                if ( empty( $competition->match_day_restriction ) ) {
                    $match_day_restriction  = false;
                } else {
                    $match_day_restriction = true;
                }
                $weekend_allowed = isset( $competition->match_day_weekends );
                if ( ! empty( $competition->start_time['weekday']['min'] ) && ! empty( $competition->start_time['weekday']['max'] ) ) {
                    $start_times['weekday']['min'] = $competition->start_time['weekday']['min'];
                    $start_times['weekday']['max'] = $competition->start_time['weekday']['max'];
                }
                if ( ! empty( $competition->start_time['weekend']['min'] ) && ! empty( $competition->start_time['weekend']['max'] ) ) {
                    $start_times['weekend']['min'] = $competition->start_time['weekend']['min'];
                    $start_times['weekend']['max'] = $competition->start_time['weekend']['max'];
                }
                $club_entry->competition = $competition;
                for ( $i = 0; $i < 7; ++$i ) {
                    $competition_days['teams'][ $i ]     = array();
                    $competition_days['available'][ $i ] = array();
                }
                $weekend_matches = array();
            }

            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( $events as $event_id ) {
                $pos = array_search( strval( $event_id ), $competition_events, true );
                if ( false !== $pos ) {
                    unset( $competition_events[ $pos ] );
                }
                $event = get_event( $event_id );
                $week  = $event->offset ?? '0';
                if ( ! isset( $courts_needed[ $week ] ) ) {
                    $courts_needed[ $week ] = array();
                }
                $weekend_matches[ $event->type ] = 0;
                $event_days                      = $event->match_days_allowed ?? array();
                if ( $match_day_restriction && ! empty( $event_days ) ) {
                    foreach ( $event_days as $event_day => $value ) {
                        if ( ! isset( $competition_days['teams'][ $event_day ][ $event->type ] ) ) {
                            $competition_days['teams'][ $event_day ][ $event->type ] = 0;
                        }
                    }
                }
                $event_entry       = new stdClass();
                $event_entry->id   = $event->id;
                $event_entry->name = $event->name;

                $teams      = $team_event[$event->id] ?? array();
                $field_ref  = $event->id;
                $field_name = $event->name;
                $validator  = $validator->teams( $teams, $field_ref, $field_name );
                if ( ! empty( $teams ) ) {
                    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $event_teams = explode( ',', isset( $_POST['event_teams'][ $event->id ] ) ? wp_unslash( $_POST['event_teams'][ $event->id ] ) : '' );
                    // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    foreach ( $teams as $team_id ) {
                        $pos = array_search( $team_id, $event_teams, true );
                        if ( false !== $pos ) {
                            array_splice( $event_teams, $pos, 1 );
                        }
                        $captain          = $captains[ $event->id ][ $team_id] ?? '';
                        $captain_id       = $captain_ids[ $event->id ][ $team_id ] ?? 0;
                        $contactno        = $contact_nos[ $event->id ][ $team_id ] ?? '';
                        $contactemail     = $contact_emails[ $event->id ][ $team_id ] ?? '';
                        $match_day        = $match_days[ $event->id ] [$team_id ] ?? '';
                        $match_time       = $match_times[ $event->id ][ $team_id ] ?? '';
                        $league_id        = $team_event_league[ $event->id ][ $team_id ] ?? null;
                        $field_ref        = $event->id . '-' . $team_id;
                        $validator        = $validator->match_day( $match_day, $field_ref, $match_day_restriction, $event_days );
                        $validator        = $validator->match_time( $match_time, $field_ref, $match_day, $start_times );
                        $validator        = $validator->captain( $captain, $contactno, $contactemail, $field_ref );
                        if ( $match_day_restriction && $weekend_allowed && ( '5' === $match_day || '6' === $match_day ) ) {
                            if ( empty( $weekend_matches[ $event->type ] ) ) {
                                ++$weekend_matches[ $event->type ];
                            } else {
                                $validator = $validator->weekend_match( $field_ref );
                            }
                        }
                        if ( ! $validator->error ) {
                            if ( $match_day_restriction ) {
                                ++$competition_days['teams'][ $match_day ][ $event->type ];
                                $competition_days['available'][ $match_day ] = $num_courts_available / $event->num_rubbers;
                            }
                            if ( strlen( $match_time ) === 5 ) {
                                $match_time = $match_time . ':00';
                            }
                            if ( ! isset( $courts_needed[ $week ][ $match_day ] ) ) {
                                $courts_needed[ $week ][ $match_day ] = array();
                            } elseif ( ! isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
                                foreach ( $courts_needed[ $week ][ $match_day ] as $schedule_time => $value ) {
                                    $validator = $validator->match_overlap( $match_time, $schedule_time, $field_ref );
                                }
                            }
                            if ( isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
                                $courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  += 1;
                                $courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] += $event->num_rubbers;
                            } else {
                                $courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  = 1;
                                $courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] = $event->num_rubbers;
                            }
                            $team_entry             = new stdClass();
                            $team_entry->id         = $team_id;
                            $team_entry->match_day  = $match_day;
                            $team_entry->match_time = $match_time;
                            $team_entry->captain_id = $captain_id;
                            $team_entry->captain    = $captain;
                            $team_entry->telephone  = $contactno;
                            $team_entry->email      = $contactemail;
                            $team_entry->existing   = $league_id;

                            $event_entry->team[] = $team_entry;
                        }
                    }
                    if ( ! empty( $event_teams ) ) {
                        $event_entry->withdrawn_teams = $event_teams;
                    }
                    $club_entry->event[] = $event_entry;
                }
            }
            if ( ! empty( $competition_events ) ) {
                $club_entry->withdrawn_events = $competition_events;
            }
            if ( ! empty( $num_courts_available ) ) {
                $club_entry->num_courts_available = $num_courts_available;
                foreach ( $courts_needed as $week ) {
                    foreach ( $week as $match_day => $match_day_value ) {
                        foreach ( $match_day_value as $match_time => $court_data ) {
                            $validator = $validator->court_needs( $num_courts_available, $court_data, $match_day, $match_time );
                        }
                    }
                }
                if ( ! $validator->error && $match_day_restriction && $weekend_allowed && ! empty( $weekend_matches ) ) {
                    foreach ( $weekend_matches as $event_type => $team_count ) {
                        if ( $team_count ) {
                            $i = 0;
                            foreach ( $competition_days['teams'] as $match_day => $value ) {
                                if ( isset( $value[ $event_type ] ) && $i < 5 ) {
                                    $num_teams[ $match_day ] = array_sum( $value );
                                    if ( $num_teams[ $match_day ] ) {
                                        $free_slots = $num_teams[ $match_day ] / 2 / $competition_days['available'][ $i ];
                                        $validator  = $validator->free_slots( $free_slots );
                                    }
                                }
                                ++$i;
                            }
                        }
                    }
                }
            }
            $acceptance = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
            $validator  = $validator->entry_acceptance( $acceptance );
        }
        if ( ! $validator->error ) {
            $club = get_club( $club_id );
            $club->league_entry( $club_entry );
            $msg = __( 'League entry complete', 'racketmanager' );
            wp_send_json_success( $msg );
        } else {
            $return = $validator->get_details();
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
            if ( empty( $return->status ) ) {
                $return->status = 400;
            }
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Search players
     */
    public function search_players(): void {
        $output = null;
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $search_string = isset( $_GET['search_string'] ) ? sanitize_text_field( wp_unslash( $_GET['search_string'] ) ) : null;
            if ( $search_string ) {
                $output = player_search( $search_string );
            } else {
                $return->error = true;
                $return->msg   = __( 'Search string not supplied', 'racketmanager' );
            }
        }
        if ( ! empty( $return->error ) ) {
            $output = show_alert( $return->msg, 'danger' );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Get data for tab
     */
    public function tab_data(): void {
        $target = null;
        $output = null;
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $target_ref = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : null;
            if ( $target_ref ) {
                $target_id = isset( $_POST['targetId'] ) ? intval( $_POST['targetId'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
                switch ( $target_ref ) {
                    case 'competition':
                        $target      = get_competition( $target_id );
                        $target_name = 'competition';
                        break;
                    case 'event':
                        $target      = get_event( $target_id );
                        $target_name = 'event';
                        break;
                    case 'league':
                        $target      = get_league( $target_id );
                        $target_name = 'league';
                        break;
                    case 'tournament':
                        $target      = get_tournament( $target_id );
                        $target_name = 'tournament';
                        break;
                    default:
                        $target_name   = 'null';
                        $return->error = true;
                        $return->msg   = __( 'Invalid target', 'racketmanager' );
                }
                if ( empty( $return->error ) ) {
                    if ( $target ) {
                        $tab = isset( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : null;
                        if ( $tab ) {
                            $valid_tabs = array( 'clubs', 'draws', 'events', 'matches', 'players', 'teams', 'standings', 'crosstable', 'winners', 'overview', 'order_of_play', 'draw' );
                            $tab_pos    = array_search( $tab, $valid_tabs, true );
                            if ( $tab_pos !== false ) {
                                $tab_name = $valid_tabs[ $tab_pos ];
                            } else {
                                $tab_name = null;
                            }
                            $args    = array();
                            $link_id = isset( $_POST['link_id'] ) ? sanitize_text_field( wp_unslash( $_POST['link_id'] ) ) : null;
                            if ( ! is_null( $link_id ) ) {
                                $args[ $tab ] = $link_id;
                            }
                            $season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                            if ( $season ) {
                                $args['season'] = $season;
                            }
                            $function_name = 'Racketmanager\\' . $target_name . '_' . $tab_name;
                            if ( function_exists( $function_name ) ) {
                                $output = $function_name( $target->id, $args );
                            } else {
                                $return->error = true;
                                $return->msg   = __( 'Tab not valid', 'racketmanager' );
                            }
                        } else {
                            $return->error = true;
                            $return->msg   = __( 'Tab not found', 'racketmanager' );
                        }
                    } else {
                        $return->error = true;
                        $return->msg   = __( 'Target not found', 'racketmanager' );
                    }
                }
            } else {
                $return->error = true;
                $return->msg   = __( 'Target ref not found', 'racketmanager' );
            }
        }
        if ( ! empty( $return->error ) ) {
            $output = $return->msg;
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Show team order players modal
     */
    public function show_team_order_players(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $club_id  = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : null;
            $event_id = isset( $_POST['eventId'] ) ? sanitize_text_field( wp_unslash( $_POST['eventId'] ) ) : null;
            $output   = team_order_players( $event_id, array( 'club_id' => $club_id ) );
        } else {
            $output = show_alert( $return->msg, 'danger' );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Validate team order and save
     */
    public function validate_team_order(): void {
        $team_id  = null;
        $match_id = null;
        $set_team = false;
        $rubber   = null;
        $event    = null;
        $rubbers  = null;
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $club_id  = isset( $_POST['clubId'] ) ? intval( $_POST['clubId'] ) : null;
            $event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
            $team_id  = isset( $_POST['teamId'] ) ? intval( $_POST['teamId'] ) : null;
            $match_id = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : null;
            $set_team = ! empty( $_POST['setTeam'] ) && sanitize_text_field( wp_unslash( $_POST['setTeam'] ) );
            if ( $club_id ) {
                $club = get_club( $club_id );
                if ( ! $club ) {
                    $return->error      = true;
                    $return->err_msgs[] = $this->club_not_found;
                    $return->err_flds[] = 'club_id';
                    $return->status     = 404;
                }
            } else {
                $return->error      = true;
                $return->err_msgs[] = __( 'Club id not supplied', 'racketmanager' );
                $return->err_flds[] = 'club_id';
                $return->status     = 404;
            }
            if ( $event_id ) {
                $event = get_event( $event_id );
                if ( ! $event ) {
                    $return->error      = true;
                    $return->err_msgs[] = $this->event_not_found;
                    $return->err_flds[] = 'event_id';
                    $return->status     = 404;
                }
            } else {
                $return->error      = true;
                $return->err_msgs[] = $this->no_event_id;
                $return->err_flds[] = 'event_id';
                $return->status     = 404;
            }
        }
        if ( empty( $return->error ) ) {
            $rubber_nums = isset( $_POST['rubber_num'] ) ? wp_unslash( $_POST['rubber_num'] ) : null;
            $players     = isset( $_POST['players'] ) ? wp_unslash( $_POST['players'] ) : null;
            $wtns        = isset( $_POST['wtn'] ) ? wp_unslash( $_POST['wtn'] ) : null;
            $rubbers     = array();
            foreach ( $rubber_nums as $rubber_num ) {
                $new_rubber             = new stdClass();
                $new_rubber->num        = $rubber_num;
                $new_rubber->players    = $players[ $rubber_num ];
                $new_rubber->wtn        = $wtns[ $rubber_num ];
                $new_rubber->status     = null;
                $rubbers[ $rubber_num ] = $new_rubber;
            }
            $match_type    = substr( $event->type, 1, 1 );
            $match_players = array();
            foreach ( $rubbers as $rubber ) {
                $team_wtn = 0;
                foreach( $rubber->players as $player_ref => $player_id ) {
                    if ( $player_id ) {
                        $player = get_club_player( $player_id );
                        if ( $player ) {
                            $player_found = in_array( $player_id, $match_players, true );
                            if ( $player_found ) {
                                $return->error      = true;
                                $return->err_msgs[] = __( 'Player already selected', 'racketmanager' );
                                $return->err_flds[] = 'players_' . $rubber->num . '_' . $player_ref;
                                $return->status     = 400;
                            } else {
                                $team_wtn       += empty( $player->player->wtn[ $match_type ] ) ? 40.9 : $player->player->wtn[ $match_type ];
                                $match_players[] = $player_id;
                            }
                        }
                    } else {
                        $return->error      = true;
                        $return->err_msgs[] = __( 'Player not selected', 'racketmanager' );
                        $return->err_flds[] = 'players_' . $rubber->num . '_' . $player_ref;
                        $return->status     = 400;
                    }
                }
                $rubber->wtn = round( $team_wtn, 1 );
            }
            $rubbers[ $rubber->num ] = $rubber;
        }
        if ( empty( $return->error ) ) {
            $valid_order = $this->check_player_order( $rubbers );
            $rubbers[ $rubber->num ] = $rubber;
            if ( $valid_order ) {
                if ( $set_team ) {
                    $updates = false;
                    if ( $match_id ) {
                        $match = get_match( $match_id );
                        if ( $match && $team_id ) {
                            if ( $team_id === intval( $match->home_team ) ) {
                                $opponent = 'home';
                            } elseif ( $team_id === intval( $match->away_team ) ) {
                                $opponent = 'away';
                            } else {
                                $opponent = null;
                            }
                            if ( $opponent ) {
                                $match_rubbers = $match->get_rubbers();
                                foreach ( $match_rubbers as $match_rubber ) {
                                    $rubber = $rubbers[$match_rubber->rubber_number] ?? null;
                                    if ( $rubber ) {
                                        $rubber_players[ $opponent ] = $rubber->players;
                                        $match_rubber->set_players( $rubber_players );
                                        $updates = true;
                                    }
                                }
                            }
                        }
                    }
                    if ( $updates ) {
                        $msg = __( 'Team players set', 'racketmanager' );
                    } else {
                        $msg = __( 'Valid playing order but unable to set team', 'racketmanager' );
                    }
                } else {
                    $msg = __( 'Valid playing order', 'racketmanager' );
                }
            } else {
                $msg = __( 'Invalid playing order', 'racketmanager' );
            }
            $return->rubbers = $rubbers;
            $return->msg     = $msg;
            $return->valid   = $valid_order;
            wp_send_json_success( $return );
        } else {
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Unable to validate match', 'racketmanager' );
            }
            wp_send_json_error( $return );
        }
    }

    /**
     * Function to check player order
     *
     * @param $rubbers
     *
     * @return bool
     */
    private function check_player_order( $rubbers ): bool {
        $valid_order = true;
        foreach( $rubbers as $rubber_num => $rubber ) {
            if ( isset( $rubbers[ $rubber_num + 1 ] ) ) {
                if ( $rubber->wtn <= $rubbers[ $rubber_num + 1 ]->wtn ) {
                    $rubber->status       = 'W';
                    $rubber->status_class = 'winner';
                } else {
                    $valid_order          = false;
                    $rubber->status       = 'L';
                    $rubber->status_class = 'loser';
                }
            }
            if ( empty( $rubbers[ $rubber_num - 1 ] ) ) {
                continue;
            }
            if ( $rubber->wtn >= $rubbers[ $rubber_num - 1 ]->wtn ) {
                if ( 'L' !== $rubber->status ) {
                    $rubber->status       = 'W';
                    $rubber->status_class = 'winner';
                }
            } else {
                $valid_order          = false;
                $rubber->status       = 'L';
                $rubber->status_class = 'loser';
            }
        }
        return $valid_order;
    }
    /**
     * Build screen to show team edit
     */
    #[NoReturn]
    public function show_team_edit_modal(): void {
        $return  = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $team_id  = isset( $_POST['teamId'] ) ? intval( $_POST['teamId'] ) : null;
            $event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
            $modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $output   = show_team_edit_modal( $team_id, array( 'event_id' => $event_id, 'modal' => $modal ) );
        } else {
            $output = show_alert( $return->msg, 'danger', 'modal' );
            if ( ! empty( $return->status ) ) {
                status_header( $return->status );
            }
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Get team/match dropdown for event
     */
    public function get_event_team_match_dropdown(): void {
        $return  = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $team_id  = isset( $_POST['teamId'] ) ? intval( $_POST['teamId'] ) : null;
            $event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
            $output   = event_team_match_dropdown( $event_id, array( 'team_id' => $team_id ) );
        } else {
            $output = show_alert( $return->msg, 'danger' );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

}
