<?php
/**
 * Competition_Entry_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Exception;
use Racketmanager\Domain\DTO\Team_Entry_DTO;
use Racketmanager\Domain\League_Team;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Clubs_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Player_Update_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Entry_Form;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;
use stdClass;
use WP_Error;
use function Racketmanager\get_league;
use function Racketmanager\seo_url;

/**
 * Class to implement the Competition Entry Management Service
 */
class Competition_Entry_Service {
    private Club_Repository $club_repository;
    private Club_Service $club_service;
    private Competition_Service $competition_service;
    private League_Repository $league_repository;
    private League_Team_Repository $league_team_repository;
    private Player_Service $player_service;
    private RacketManager $racketmanager;
    private Team_Repository $team_repository;

    /**
     * Constructor
     *
     */
    public function __construct( RacketManager $plugin_instance, Club_Repository $club_repository, League_Repository $league_repository, League_Team_Repository $league_team_repository, Team_Repository $team_repository, Club_Service $club_service, Competition_Service $competition_service, Player_Service $player_service ) {
        $this->racketmanager          = $plugin_instance;
        $this->club_repository        = $club_repository;
        $this->club_service           = $club_service;
        $this->competition_service    = $competition_service;
        $this->league_repository      = $league_repository;
        $this->league_team_repository = $league_team_repository;
        $this->team_repository        = $team_repository;
        $this->player_service         = $player_service;
    }

    /**
     * Notify team entry open
     *
     * @param int|null $competition_id
     * @param int|null $season season name.
     *
     * @return object
     */
    public function notify_team_entry_open( ?int $competition_id, ?int $season ): object {
        $competition    = $this->competition_service->get_by_id( $competition_id );
        $current_season = $this->competition_service->is_season_valid_for_competition( $competition, $season );
        $msg            = null;
        $return         = new stdClass();
        $season_dtls    = (object) $current_season;
        if ( $competition->is_league ) {
            $events = $this->competition_service->get_events_for_competition( $competition->get_id(), $season );
            foreach ( $events as $event ) {
                $leagues = $this->competition_service->get_leagues_for_event( $event->id, $season );
                if ( empty( $leagues ) ) {
                    $return->error = true;
                    $msg[]         = __( 'No leagues found for event', 'racketmanager' ) . ' ' . $event->name;
                } elseif ( count( $event->get_seasons() ) > 1 ) {
                    $constitution = $event->get_constitution( array(
                        'season' => $season,
                        'count'  => true,
                    ) );
                    if ( ! $constitution ) {
                        $return->error = true;
                        $msg[]         = __( 'Constitution not set', 'racketmanager' ) . ' ' . $event->name;
                    }
                }
            }
            $is_championship         = false;
            $season_dtls->venue_name = null;
        } elseif ( $competition->is_cup ) {
            $is_championship         = true;
            $season_dtls->venue_name = $this->get_venue_name( $season_dtls->venue );
        } else {
            $is_championship = false;
            $return->error   = true;
            $return->msg     = __( 'Competition type not valid', 'racketmanager' );
        }
        if ( empty( $return->error ) ) {
            $url              = $this->racketmanager->site_url . '/entry-form/' . seo_url( $competition->name ) . '/' . $season . '/';
            $competition_name = $competition->name . ' ' . $season;
            $clubs            = $this->racketmanager->get_clubs();
            $headers          = array();
            $from_email       = $this->racketmanager->get_confirmation_email( $competition->type );
            if ( $from_email ) {
                $headers[]         = RACKETMANAGER_FROM_EMAIL . ucfirst( $competition->type ) . 'Secretary <' . $from_email . '>';
                $headers[]         = RACKETMANAGER_CC_EMAIL . ucfirst( $competition->type ) . 'Secretary <' . $from_email . '>';
                $organisation_name = $this->racketmanager->site_name;
                $messages_sent     = 0;
                foreach ( $clubs as $club ) {
                    $match_secretary = $this->player_service->get_match_secretary_details( $club->id );
                    $email_subject   = $this->racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . $club->name;
                    $email_to        = $match_secretary->display_name . ' <' . $match_secretary->email . '>';
                    $action_url      = $url . seo_url( $club->shortcode ) . '/';
                    $email_message   = $this->racketmanager->shortcodes->load_template( 'competition-entry-open', array(
                        'email_subject'   => $email_subject,
                        'from_email'      => $from_email,
                        'action_url'      => $action_url,
                        'organisation'    => $organisation_name,
                        'is_championship' => $is_championship,
                        'competition'     => $competition_name,
                        'addressee'       => $match_secretary->display_name,
                        'season_dtls'     => $season_dtls,
                    ), 'email' );
                    wp_mail( $email_to, $email_subject, $email_message, $headers );
                    ++ $messages_sent;
                }
                if ( $messages_sent ) {
                    /* translation: %d number of messages sent */
                    $return->msg = sprintf( __( '%d match secretaries notified', 'racketmanager' ), $messages_sent );
                } else {
                    $return->error = true;
                    $msg[]         = __( 'No notification', 'racketmanager' );
                }
            } else {
                $return->error = true;
                $msg[]         = __( 'No secretary email', 'racketmanager' );
            }
        }
        if ( ! empty( $return->error ) ) {
            $return->msg = __( 'Notification error', 'racketmanager' );
            foreach ( $msg as $error ) {
                $return->msg .= '<br>' . $error;
            }
        }

        return $return;
    }

    /**
     * Get the venue name for the competition
     *
     * @param int|null $club_id
     *
     * @return string
     */
    private function get_venue_name( ?int $club_id ): string {
        if ( ! empty( $club_id ) ) {
            try {
                $venue_club = $this->club_service->get_club( $club_id );
                $venue_name = $venue_club->shortcode;
            } catch ( Club_Not_Found_Exception $e ) {
                $venue_name = $e;
            }
        } else {
            $venue_name = null;
        }

        return $venue_name;

    }

    /**
     * Remind clubs that a competition with team entry is closing soon
     *
     * @param int|null $competition_id
     * @param int|null $season
     *
     * @return Validator
     */
    public function notify_team_entry_reminder( ?int $competition_id, ?int $season ): object {
        $competition = $this->competition_service->get_by_id( $competition_id );
        $clubs       = $this->get_clubs_pending_entry( $competition_id, $season );
        if ( empty( $clubs ) ) {
            throw new Clubs_Not_Found_Exception( __( 'No clubs with outstanding entries', 'racketmanager' ) );
        }
        $validator     = new Validator();
        $messages_sent = 0;

        $season_dtls             = (object) $competition->get_season_by_name( $season );
        $season_dtls->venue_name = null;
        if ( $competition->is_league ) {
            $is_championship = false;
        } else {
            $is_championship         = true;
            $season_dtls->venue_name = $this->get_venue_name( $season_dtls->venue );
        }
        $date_closing     = date_create( $season_dtls->date_closing );
        $now              = date_create();
        $remaining_time   = date_diff( $date_closing, $now, true );
        $days_remaining   = $remaining_time->days;
        $url              = $this->racketmanager->site_url . '/entry-form/' . seo_url( $competition->get_name() ) . '/' . $season . '/';
        $competition_name = $competition->get_name() . ' ' . $season;
        $headers          = array();
        $from_email       = $this->racketmanager->get_confirmation_email( $competition->get_type() );
        if ( $from_email ) {
            $headers[]         = RACKETMANAGER_FROM_EMAIL . ucfirst( $competition->get_type() ) . 'Secretary <' . $from_email . '>';
            $headers[]         = RACKETMANAGER_CC_EMAIL . ucfirst( $competition->get_type() ) . 'Secretary <' . $from_email . '>';
            $organisation_name = $this->racketmanager->site_name;
            foreach ( $clubs as $club ) {
                $match_secretary = $this->player_service->get_match_secretary_details( $club->id );
                $email_subject   = $this->racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entries Closing Soon', 'racketmanager' ) . ' - ' . $club->name;
                $email_to        = $match_secretary->display_name . ' <' . $match_secretary->email . '>';
                $action_url      = $url . seo_url( $club->shortcode ) . '/';
                $email_message   = $this->racketmanager->shortcodes->load_template( 'competition-entry-open', array(
                    'email_subject'   => $email_subject,
                    'from_email'      => $from_email,
                    'action_url'      => $action_url,
                    'organisation'    => $organisation_name,
                    'is_championship' => $is_championship,
                    'competition'     => $competition_name,
                    'addressee'       => $match_secretary->display_name,
                    'season_dtls'     => $season_dtls,
                    'days_remaining'  => $days_remaining,
                ), 'email' );
                wp_mail( $email_to, $email_subject, $email_message, $headers );
                ++ $messages_sent;
            }
            /* translation: %d number of messages sent */
            $validator->msg = sprintf( __( '%d match secretaries notified', 'racketmanager' ), $messages_sent );
        } else {
            $validator->error      = true;
            $validator->err_msgs[] = __( 'No secretary email', 'racketmanager' );
        }
        if ( ! empty( $validator->error ) ) {
            $validator->msg = __( 'Notification error', 'racketmanager' );
            foreach ( $validator->err_msgs as $error ) {
                $validator->msg .= '<br>' . $error;
            }
        }

        return $validator;
    }

    /**
     * Get clubs missing from a specific competition.
     */
    public function get_clubs_pending_entry( ?int $competition_id, ?int $season ): array {
        $competition = $this->competition_service->get_by_id( $competition_id );
        $this->competition_service->is_season_valid_for_competition( $competition, $season );

        return $this->club_repository->find_clubs_not_entered( $competition->get_id(), $season );
    }

    /**
     * Request league entry for a club
     *
     * @param $request
     *
     * @return bool|WP_Error
     */
    public function request_league_entry( $request ): bool|WP_Error {
        $validator   = new Validator_Entry_Form();
        $club_entry  = new stdClass();
        $competition = null;
        try {
            $competition = $this->competition_service->get_by_id( $request->competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $validator->error = true;
            $validator->msg   = $e->getMessage();
        }
        if ( empty( $validator->error ) ) {
            $validator = $validator->season_set( $request->season, $competition->get_seasons() );
            $competition->set_current_season( $request->season );
            $validator             = $validator->competition_open( $competition );
            $start_times           = $this->get_start_times( $competition->settings );
            $validator             = $validator->club( $request->club_id );
            $validator             = $validator->events_entry( $request->events_entered );
            $validator             = $validator->num_courts_available( $request->num_courts_available );
            $match_day_restriction = isset( $competition->settings['match_day_restriction'] );
            $weekend_allowed       = isset( $competition->settings['match_day_weekends'] );
            for ( $i = 0; $i < 7; ++ $i ) {
                $competition_days['teams'][ $i ]     = array();
                $competition_days['available'][ $i ] = array();
            }
            $weekend_matches = array();
            $courts_needed   = array();

            foreach ( $request->events_entered as $event_id => $teams ) {
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Event_Not_Found_Exception ) {
                    $validator = $validator->event();
                    continue;
                }
                $week = $event->offset ?? '0';
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
                $event_entry->id   = $event_id;
                $event_entry->name = $event->get_name();
                $event_entry->type = $event->get_type();

                foreach ( $teams as $team_id => $team ) {
                    $team_name    = $team->team_name ?? null;
                    $captain      = $team->captain_name ?? null;
                    $captain_id   = $team->captain_id ?? null;
                    $contactno    = $team->phone ?? null;
                    $contactemail = $team->email ?? null;
                    $match_day    = $team->match_day ?? null;
                    $match_time   = $team->match_time ?? null;
                    $league_id    = $team->league_id ?? null;
                    $field_ref    = $event_id . '-' . $team_id;
                    $validator    = $validator->match_day( $match_day, $field_ref, $match_day_restriction, $event_days );
                    $validator    = $validator->match_time( $match_time, $field_ref, $match_day, $start_times );
                    $validator    = $validator->captain( $captain, $contactno, $contactemail, $field_ref );
                    if ( $match_day_restriction && $weekend_allowed && $match_day >= '5' ) {
                        if ( empty( $weekend_matches[ $event->type ] ) ) {
                            ++ $weekend_matches[ $event->type ];
                        } else {
                            $validator = $validator->weekend_match( $field_ref );
                        }
                    }
                    if ( empty( $validator->error ) ) {
                        if ( $match_day_restriction ) {
                            if ( ! isset( $competition_days['teams'][ $match_day ][ $event->type ] ) ) {
                                $competition_days['teams'][ $match_day ][ $event->type ] = 0;
                            }
                            ++ $competition_days['teams'][ $match_day ][ $event->type ];
                            $competition_days['available'][ $match_day ] = $request->num_courts_available / $event->num_rubbers;
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
                        if ( ! isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
                            $courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  = 0;
                            $courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] = 0;
                        }
                        $courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  += 1;
                        $courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] += $event->num_rubbers;

                        $event_entry->team[] = Team_Entry_DTO::from_array( array(
                            'id'         => $team_id,
                            'team_name'  => $team_name,
                            'match_day'  => $match_day,
                            'match_time' => $match_time,
                            'captain_id' => $captain_id,
                            'captain'    => $captain,
                            'telephone'  => $contactno,
                            'email'      => $contactemail,
                            'existing'   => empty( $league_id ),
                        ) );
                    }
                }
                $club_entry->events[] = $event_entry;
            }

            $validator = $validator->events_missing_teams( $request->empty_event_ids );
            $validator = $validator->entry_acceptance( $request->acceptance );
            if ( ! empty( $request->num_courts_available ) ) {
                $club_entry->num_courts_available = $request->num_courts_available;
                foreach ( $courts_needed as $week ) {
                    foreach ( $week as $match_day => $match_day_value ) {
                        foreach ( $match_day_value as $match_time => $court_data ) {
                            $validator = $validator->court_needs( $request->num_courts_available, $court_data, $match_day, $match_time );
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
                                ++ $i;
                            }
                        }
                    }
                }
            }
        }
        if ( empty( $validator->error ) ) {
            $club_entry->club             = $request->club_id;
            $club_entry->season           = $request->season;
            $club_entry->comments         = $request->comments;
            $club_entry->withdrawn_events = $request->missed_event_ids;
            $club_entry->withdrawn_teams  = $request->missed_team_ids;
            $club_entry->competition      = $competition;

            return $this->league_entry_valid( $request->club_id, $club_entry );
        }

        return $validator->err;
    }

    /**
     * Get start times from settings
     *
     * @param array $settings
     *
     * @return array
     */
    private function get_start_times( array $settings ): array {
        $start_times = array();
        if ( ! empty( $settings['start_time']['weekday']['min'] ) && ! empty( $settings['start_time']['weekday']['max'] ) ) {
            $start_times['weekday']['min'] = $settings['start_time']['weekday']['min'];
            $start_times['weekday']['max'] = $settings['start_time']['weekday']['max'];
        }
        if ( ! empty( $settings['start_time']['weekend']['min'] ) && ! empty( $settings['start_time']['weekend']['max'] ) ) {
            $start_times['weekend']['min'] = $settings['start_time']['weekend']['min'];
            $start_times['weekend']['max'] = $settings['start_time']['weekend']['max'];
        }

        return $start_times;
    }

    /**
     * Handle valid league entries
     *
     * @param int $club_id
     * @param object $club_entry
     *
     * @return bool
     */
    public function league_entry_valid( int $club_id, object $club_entry ): bool {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util_Messages::club_not_found( $club_id ) );
        }
        $club_entry->club_name = $club->get_name();
        $event_details         = array();
        foreach ( $club_entry->events as $event_entry ) {
            $event_id                    = $event_entry->id;
            $league_event_entry['event'] = $event_entry->name;
            $league_entries              = array();
            foreach ( $event_entry->team as $team_entry ) {
                $match_day = Util_Lookup::get_match_day( $team_entry->match_day );

                if ( empty( $team_entry->id ) ) {
                    $team    = $this->club_service->create_team( $club_id, $event_entry->type );
                    $team_id = $team->id;
                } else {
                    $team_id = $team_entry->id;
                }
                $league_teams = $this->league_team_repository->get_by_event_id( $event_id, $club_entry->season, $team_id );
                if ( $league_teams ) {
                    foreach ( $league_teams as $league_team ) {
                        $league_team->set_captain( $team_entry->captain_id );
                        $league_team->set_match_day( $match_day );
                        $league_team->set_match_time( $team_entry->match_time );
                        $league_team->set_entered_state( 1 );
                        $league_team->set_status();
                        $this->league_team_repository->save( $league_team );
                    }
                } else {
                    $league_id               = $this->league_repository->get_lowest_league_id_by_event( $event_id );
                    $league_team             = new stdClass();
                    $league_team->team_id    = $team_entry->team_id;
                    $league_team->league_id  = $league_id;
                    $league_team->season     = $club_entry->season;
                    $league_team->captain    = $team_entry->captain_id;
                    $league_team->match_day  = $match_day;
                    $league_team->match_time = $team_entry->match_time;
                    $league_team->rank       = 99;
                    $league_team->status     = 'NT';
                    $league_team->profile    = 1;
                    $league_team             = new League_Team( $league_team );
                    $this->league_team_repository->save( $league_team );
                }
                try {
                    $this->player_service->update_contact_details( $team_entry->captain_id, $team_entry->telephone, $team_entry->email );
                } catch ( Player_Update_Exception|Exception $e ) {
                    error_log( __( 'Unable to update contact details', 'racketmanager' ) . ': ' . $e->getMessage() );
                }

                $league_entry                 = array();
                $league_entry['teamName']     = $team_entry->team_name;
                $league_entry['captain']      = $team_entry->captain;
                $league_entry['contactno']    = $team_entry->telephone;
                $league_entry['contactemail'] = $team_entry->email;
                $league_entry['matchday']     = $match_day;
                $league_entry['matchtime']    = substr( $team_entry->match_time, 0, 5 );
                $league_entries[]             = $league_entry;
            }
            $league_event_entry['teams'] = $league_entries;
            $event_details[]             = $league_event_entry;
        }
        $event_entries['events']               = $event_details;
        $event_entries['num_courts_available'] = $club_entry->num_courts_available;
        if ( ! empty( $club_entry->withdrawn_teams ) ) {
            foreach ( $club_entry->withdrawn_teams as $event_id => $teams ) {
                $this->withdraw_teams( $club_id, $club_entry->season, $event_id, $teams );
            }
        }
        if ( ! empty( $club_entry->withdrawn_events ) ) {
            foreach ( $club_entry->withdrawn_events as $event_id ) {
                $this->withdraw_teams( $club_id, $club_entry->season, $event_id );
            }
        }
        $this->competition_service->set_court_availability( $club_entry->competition->id, $club_id, $club_entry->num_courts_available );

        $email_from      = $this->racketmanager->get_confirmation_email( 'league' );
        $email_subject   = $this->racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' League Entry - ' . $club->get_shortcode();
        $headers         = array();
        $secretary_email = __( 'League Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]       = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]       = RACKETMANAGER_CC_EMAIL . $secretary_email;

        $template                       = 'league-entry';
        $template_args['event_entries'] = $event_entries;
        $this->send_entry_form( $club->get_id(), $template_args, $club_entry, $email_from, $template, $email_subject, $headers );

        return true;
    }

    /**
     * Withdraw teams from an event
     *
     * @param int $club_id
     * @param string $season
     * @param int $event_id
     * @param array $teams
     *
     * @return int
     */
    public function withdraw_teams( int $club_id, string $season, int $event_id, array $teams = array() ): int {
        $teams_to_withdraw = $this->league_team_repository->find_teams_to_withdraw_from_league( $club_id, $season, $event_id, $teams );
        foreach ( $teams_to_withdraw as $league_team_entry ) {
            $league_team_entry->set_entered_state( 3 );
            $league_team_entry->set_status( 'W' );
            $this->league_team_repository->save( $league_team_entry );
        }

        return count( $teams_to_withdraw );
    }

    /**
     * Send the entry form
     * *
     *
     * @param int $club_id
     * @param array $template_args
     * @param object $club_entry
     * @param string $email_from
     * @param string $template
     * @param string $email_subject
     * @param array $headers
     *
     * @return void
     */
    public function send_entry_form( int $club_id, array $template_args, object $club_entry, string $email_from, string $template, string $email_subject, array $headers ): void {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util_Messages::club_not_found( $club_id ) );
        }
        $match_secretary = $this->player_service->get_match_secretary_details( $club_id );
        $email_to        = $match_secretary->display_name . ' <' . $match_secretary->email . '> ';

        $template_args['organisation']     = $this->racketmanager->site_name;
        $template_args['season']           = $club_entry->season;
        $template_args['competition_name'] = $club_entry->competition->name;
        $template_args['club']             = $club_entry->club_name;
        $template_args['contact_email']    = $email_from;
        $template_args['comments']         = $club_entry->comments;
        $this->racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
    }

    /**
     * Request cup entry for a club
     *
     * @param $request
     *
     * @return bool|WP_Error
     */
    public function request_cup_entry( $request ): bool|WP_Error {
        $validator   = new Validator_Entry_Form();
        $club_entry  = new stdClass();
        $competition = null;
        try {
            $competition = $this->competition_service->get_by_id( $request->competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $validator->error = true;
            $validator->msg   = $e->getMessage();
        }
        if ( empty( $validator->error ) ) {
            $validator   = $validator->season_set( $request->season, $competition->get_seasons() );
            $validator   = $validator->competition_open( $competition );
            $start_times = $this->get_start_times( $competition->settings );
            $validator   = $validator->club( $request->club_id );
            $validator   = $validator->events_entry( $request->events_entered );
            foreach ( $request->events_entered as $event_id => $team ) {
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Event_Not_Found_Exception ) {
                    $validator = $validator->event();
                    continue;
                }
                $team_id      = $team->team_id ?? null;
                $captain      = $team->captain ?? null;
                $captain_id   = $team->captain_id ?? null;
                $contactno    = $team->phone ?? null;
                $contactemail = $team->email ?? null;
                $match_day    = $team->match_day ?? null;
                $match_time   = $team->match_time ?? null;
                $field_ref    = $event_id;
                $validator    = $validator->events_has_teams( $team_id, $event_id );
                if ( empty( $team_id ) ) {
                    continue;
                }

                try {
                    $team_details = $this->team_repository->find_by_id( $team_id );
                } catch ( Team_Not_Found_Exception ) {
                    $validator = $validator->team( $team_id );
                    continue;
                }

                $validator = $validator->match_day( $match_day, $field_ref );
                $validator = $validator->match_time( $match_time, $field_ref, $match_day, $start_times );
                $validator = $validator->captain( $captain, $contactno, $contactemail, $field_ref );
                if ( isset( $event->primary_league ) ) {
                    $league = get_league( $event->primary_league );
                } else {
                    $league = get_league( array_key_first( $event->league_index ) );
                }

                $event_entry          = new stdClass();
                $event_entry->id      = $event_id;
                $event_entry->name    = $event->get_name();
                $event_entry->type    = $event->get_type();
                $event_entry->team    = Team_Entry_DTO::from_array( array(
                    'id'         => $team_id,
                    'team_name'  => $team_details->get_name(),
                    'match_day'  => $match_day,
                    'match_time' => $match_time,
                    'captain_id' => $captain_id,
                    'captain'    => $captain,
                    'telephone'  => $contactno,
                    'email'      => $contactemail,
                    'league_id'  => $league->id,
                ) );
                $club_entry->events[] = $event_entry;

            }
            $validator = $validator->entry_acceptance( $request->acceptance );
        }
        if ( empty( $validator->error ) ) {
            $club_entry->competition = $competition;
            $club_entry->club        = $request->club_id;
            $club_entry->season      = $request->season;
            $club_entry->comments    = $request->comments;
            $this->cup_entry_valid( $request->club_id, $club_entry );

            return true;
        }

        return $validator->err;
    }

    /**
     * Handle valid cup entry for a club
     *
     * @param int $club_id club id.
     * @param object $club_entry club cup entry object.
     */
    public function cup_entry_valid( int $club_id, object $club_entry ): void {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util_Messages::club_not_found( $club_id ) );
        }
        $club_entry->club_name = $club->get_name();
        $cup_entries           = array();
        foreach ( $club_entry->events as $event_entry ) {
            $event_id     = $event_entry->id;
            $event_name   = $event_entry->name;
            $cup_entry    = array();
            $team         = $event_entry->team;
            $team_id      = $team->id;
            $match_day    = Util_Lookup::get_match_day( $team->match_day );
            $league_teams = $this->league_team_repository->get_by_event_id( $event_id, $club_entry->season, $team_id );
            if ( $league_teams ) {
                foreach ( $league_teams as $league_team ) {
                    $league_team->set_captain( $team->captain_id );
                    $league_team->set_match_day( $match_day );
                    $league_team->set_match_time( $team->match_time );
                    $this->league_team_repository->save( $league_team );
                }
            } else {
                $league_team             = new stdClass();
                $league_team->team_id    = $team->id;
                $league_team->league_id  = $team->league_id;
                $league_team->season     = $club_entry->season;
                $league_team->captain    = $team->captain_id;
                $league_team->match_day  = $match_day;
                $league_team->match_time = $team->match_time;
                $league_team             = new League_Team( $league_team );
                $this->league_team_repository->save( $league_team );
            }
            try {
                $this->player_service->update_contact_details( $team->captain_id, $team->telephone, $team->email );
            } catch ( Player_Update_Exception|Exception $e ) {
                error_log( __( 'Unable to update contact details', 'racketmanager' ) . ': ' . $e->getMessage() );
            }
            $cup_entry['event']        = $event_name;
            $cup_entry['teamName']     = $team->team_name;
            $cup_entry['captain']      = $team->captain;
            $cup_entry['contactno']    = $team->telephone;
            $cup_entry['contactemail'] = $team->email;
            $cup_entry['matchday']     = $match_day;
            $cup_entry['matchtime']    = $team->match_time;
            $cup_entries[]             = $cup_entry;
        }
        $email_from      = $this->racketmanager->get_confirmation_email( 'cup' );
        $email_subject   = $this->racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' ' . __( 'Entry', 'racketmanager' ) . ' - ' . $club->get_shortcode();
        $headers         = array();
        $secretary_email = __( 'Cup Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]       = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]       = RACKETMANAGER_CC_EMAIL . $secretary_email;

        $template                     = 'cup-entry';
        $template_args['cup_entries'] = $cup_entries;
        $this->send_entry_form( $club->get_id(), $template_args, $club_entry, $email_from, $template, $email_subject, $headers );
    }
}
