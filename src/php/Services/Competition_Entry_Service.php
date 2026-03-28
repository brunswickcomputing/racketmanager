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
use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Team\Team_Entry_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Invoice_Details_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Team;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Clubs_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Update_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
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
    private Tournament_Entry_Repository $tournament_entry_repository;
    private Tournament_Repository $tournament_repository;
    private Finance_Service $finance_service;
    private Notify_Service $notify_service;
    private Tournament_Service $tournament_service;

    /**
     * Constructor
     *
     */
    public function __construct( RacketManager $plugin_instance, Club_Repository $club_repository, League_Repository $league_repository, League_Team_Repository $league_team_repository, Team_Repository $team_repository, Tournament_Repository $tournament_repository, Tournament_Entry_Repository $tournament_entry_repository, Club_Service $club_service, Competition_Service $competition_service, Finance_Service $finance_service, Player_Service $player_service, Tournament_Service $tournament_service, Notify_Service $notify_service ) {
        $this->racketmanager               = $plugin_instance;
        $this->club_repository             = $club_repository;
        $this->club_service                = $club_service;
        $this->competition_service         = $competition_service;
        $this->league_repository           = $league_repository;
        $this->league_team_repository      = $league_team_repository;
        $this->tournament_repository       = $tournament_repository;
        $this->tournament_entry_repository = $tournament_entry_repository;
        $this->team_repository             = $team_repository;
        $this->finance_service             = $finance_service;
        $this->player_service              = $player_service;
        $this->tournament_service          = $tournament_service;
        $this->notify_service              = $notify_service;
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
        $days_remaining   = $this->get_remaining_days( $season_dtls->date_closing );
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

    private function get_remaining_days( string $date ): int {
        $date_closing   = date_create( $date );
        $now            = date_create();
        $remaining_time = date_diff( $now, $date_closing );

        return $remaining_time->days;
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
                $league_teams = $this->league_team_repository->find_by_event_id( $event_id, $club_entry->season, $team_id );
                if ( $league_teams ) {
                    foreach ( $league_teams as $league_team ) {
                        $league_team->set_captain( $team_entry->captain_id );
                        $league_team->set_match_day( $match_day );
                        $league_team->set_match_time( $team_entry->match_time );
                        $league_team->set_entered_state( Team_Profile::ACTIVE );
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
            $league_team_entry->set_entered_state( Team_Profile::WITHDRAWN );
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
        $this->notify_service->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
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
            $league_teams = $this->league_team_repository->find_by_event_id( $event_id, $club_entry->season, $team_id );
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

    public function notify_tournament_entry_open( int $tournament_id ): int {
        return $this->notify_tournament_entry( $tournament_id );
    }

    private function notify_tournament_entry( int $tournament_id, string $type = 'open' ): int {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }
        $from_email = $this->racketmanager->get_confirmation_email( 'tournament' );
        if ( ! $from_email ) {
            throw new Invalid_Argument_Exception( Util_Messages::secretary_email_not_found( 'tournament' ) );
        }
        $url              = $tournament->entry_link;
        $competition_name = $tournament->name . ' ' . __( 'Tournament', 'racketmanager' );

        switch ( $type ) {
            case 'open':
                $entered      = false;
                $notify_clubs = true;
                $confirm_fees = true;
                $subject      = __( 'Entry Open', 'racketmanager' );
                $remaining    = null;
                break;
            case 'reminder':
                $entered      = true;
                $notify_clubs = false;
                $confirm_fees = false;
                $subject      = __( 'Entry Reminder', 'racketmanager' );
                $remaining    = $this->get_remaining_days( $tournament->date_closing );
                break;
            default:
                throw new Invalid_Argument_Exception( Util_Messages::invalid_notification_type( $type ) );
        }
        $limit   = 2;
        $players = $this->tournament_repository->find_previous_tournament_players_with_optin( $tournament_id, $limit, $entered );

        $tournament_secretary = __( 'Tournament Secretary', 'racketmanager' ) . ' <' . $from_email . '>';

        $headers           = array();
        $messages_sent     = 0;
        $headers[]         = RACKETMANAGER_FROM_EMAIL . $tournament_secretary;
        $headers[]         = RACKETMANAGER_CC_EMAIL . $tournament_secretary;
        $organisation_name = $this->racketmanager->site_name;
        $account_link      = '<a href="' . $this->racketmanager->site_url . '/member-account/" style="text-decoration: none; color: #006800;">' . __( 'link', 'racketmanager' ) . '</a>';
        $email_subject     = $this->racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . $subject;
        foreach ( $players as $player ) {
            $email_to      = $player->fullname . ' <' . $player->email . '>';
            $action_url    = $url;
            $email_message = $this->racketmanager->shortcodes->load_template( 'tournament-entry-open', array(
                    'email_subject'  => $email_subject,
                    'from_email'     => $from_email,
                    'action_url'     => $action_url,
                    'organisation'   => $organisation_name,
                    'tournament'     => $tournament,
                    'addressee'      => $player->fullname,
                    'days_remaining' => $remaining,
                    'type'           => $type,
                    'account_link'   => $account_link,
                ), 'email' );
            wp_mail( $email_to, $email_subject, $email_message, $headers );
            ++ $messages_sent;
        }
        if ( $notify_clubs ) {
            $base_subject = $email_subject;
            $clubs        = $this->club_service->get_clubs_with_details();
            foreach ( $clubs as $club ) {
                $email_subject = $base_subject . ' - ' . $club->name;
                $email_to      = $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
                $action_url    = $url . seo_url( $club->shortcode ) . '/';
                $email_message = $this->racketmanager->shortcodes->load_template( 'tournament-entry-open', array(
                        'email_subject' => $email_subject,
                        'from_email'    => $from_email,
                        'action_url'    => $action_url,
                        'organisation'  => $organisation_name,
                        'tournament'    => $tournament,
                        'addressee'     => $club->match_secretary->display_name,
                        'type'          => 'club',
                    ), 'email' );
                wp_mail( $email_to, $email_subject, $email_message, $headers );
                ++ $messages_sent;
            }
        }
        if ( $confirm_fees ) {
            $key = $tournament->competition_id . '_' . $tournament->season;
            try {
                $this->finance_service->set_charge_used( $key );
            } catch ( Charge_Not_Found_Exception $e ) {
                error_log( $e->getMessage() );
            }
        }

        return $messages_sent;

    }

    public function notify_tournament_entry_open_reminder( int $tournament_id ): int {
        return $this->notify_tournament_entry( $tournament_id, 'reminder' );
    }

    public function request_tournament_entry( Tournament_Entry_Request_DTO $request ): Tournament_Entry_Response_DTO|WP_Error {
        $player_id  = $request->player_id;
        $season     = $request->season;
        $validator  = new Validator_Entry_Form();
        $tournament = $this->tournament_repository->find_by_id( $request->tournament_id );
        if ( ! $tournament ) {
            $validator->set_errors( 'event', Util_Messages::tournament_not_found( $request->tournament_id ) );
        } else {
            $validator = $validator->tournament_open( $tournament );
            try {
                $player = $this->player_service->get_player( $player_id );
            } catch ( Player_Not_Found_Exception $e ) {
                $validator->set_errors( 'contactno', $e->getMessage(), 404 );
            }
            if ( ! current_user_can( 'manage_racketmanager' ) ) {
                $validator = $validator->telephone( $request->phone );
            }
            $validator = $validator->email( $request->email, $player_id );
            $validator = $validator->btm( $request->lta_number, $player_id );
            try {
                $club = $this->club_service->get_club( $request->club_id );
            } catch ( Club_Not_Found_Exception $e ) {
                $validator->set_errors( 'clubId', $e->getMessage(), 404 );
            }
            $validator = $validator->events_entry( $request->entries, $tournament->num_entries );
            foreach ( $request->entries as $event_id => $entry ) {
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Event_Not_Found_Exception ) {
                    $validator = $validator->event();
                    continue;
                }
                if ( isset( $event->primary_league ) ) {
                    $league = get_league( $event->primary_league );
                } else {
                    $league = get_league( array_key_first( $event->league_index ) );
                }
                $league_id        = $league->id;
                $entry->league_id = $league_id;
                $player_team      = $this->league_team_repository->player_already_entered_league( $player_id, $league_id, $season );
                if ( substr( $event->type, 1, 1 ) === 'D' ) {
                    $entry->is_doubles = true;
                    $err_field         = 'partner-' . $event_id;
                    if ( empty( $entry->partner_id ) ) {
                        $validator->set_errors( $err_field, __( 'Partner not selected', 'racketmanager' ) );
                    } else {
                        try {
                            $partner      = $this->player_service->get_player( $entry->partner_id );
                            $partner_team = $this->league_team_repository->player_already_entered_league( $partner->get_id(), $league_id, $season );
                            if ( empty( $partner_team ) ) {
                                $entry->partner = $partner;
                                if ( $player_team ) {
                                    $entry->new_partner = true;
                                }
                            } elseif ( ! empty( $player_team->team_id ) && $player_team->team_id === $partner_team->team_id ) {
                                $entry->partner     = $partner;
                                $entry->new_partner = false;
                            } else {
                                $validator->set_errors( $err_field, __( 'Partner is in another team in this event', 'racketmanager' ) );
                            }
                        } catch ( Player_Not_Found_Exception $e ) {
                            $validator->set_errors( $err_field, $e->getMessage(), 404 );
                        }
                    }
                } else {
                    $entry->is_doubles = false;
                }
                $entry->event_type             = $event->get_type();
                $entry->event_name             = $event->get_name();
                $entry->team_id                = $player_team->team_id ?? null;
                $entry->league_team_id         = $player_team->league_team_id ?? null;
                $request->entries[ $event_id ] = $entry;
            }
            $validator = $validator->entry_acceptance( $request->acceptance );
            if ( empty( $validator->error ) ) {
                $tournament_entry                  = new stdClass();
                $tournament_entry->missed_events   = $request->missed_event_ids;
                $tournament_entry->entries         = $request->entries;
                $tournament_entry->player_id       = $player_id;
                $tournament_entry->club_id         = $request->club_id;
                $tournament_entry->btm             = $request->lta_number;
                $tournament_entry->contactno       = $request->phone;
                $tournament_entry->contactemail    = $request->email;
                $tournament_entry->comments        = $request->comments;
                $tournament_entry->paid            = $request->paid_amt;
                $tournament_entry->total_cost      = $request->total_cost;
                $tournament_entry->competition_fee = $request->competition_fee;

                return $this->tournament_entry_valid( $tournament, $player, $club, $tournament_entry );
            }
        }

        return $validator->err;
    }

    private function tournament_entry_valid( Tournament $tournament, Player $player, Club $club, stdclass $tournament_entry ): Tournament_Entry_Response_DTO {
        $updates       = false;
        $player_id     = $player->get_id();
        $tournament_id = $tournament->get_id();
        try {
            $this->player_service->handle_tournament_entry_personal_information( $player_id, $tournament_entry->btm, $tournament_entry->contactno, $tournament_entry->contactemail );
        } catch ( Player_Update_Exception|Exception $e ) {
            error_log( $e->getMessage() );
        }
        if ( ! empty( $tournament_entry->missed_events ) ) {
            $entries_to_withdraw = $this->league_team_repository->find_player_teams_by_player_for_events( $player_id, $tournament_entry->missed_events, $tournament->season );
            foreach ( $entries_to_withdraw as $entry_to_withdraw ) {
                $entry_to_withdraw->set_entered_state( Team_Profile::WITHDRAWN );
                $this->league_team_repository->save( $entry_to_withdraw );
                $updates = true;
            }
        }
        $fees               = $this->tournament_service->get_fees( $tournament_id );
        $tournament_entries = array();
        $invoice_entries    = array();
        foreach ( $tournament_entry->entries as $entry ) {
            $entry_details = array();
            if ( empty( $entry->team_id ) ) {
                $team_id        = null;
                $existing_entry = false;
            } else {
                $team_id        = $entry->team_id;
                $existing_entry = true;
            }
            if ( $entry->is_doubles ) {
                $partner_id               = $entry->partner->get_id();
                $entry_details['partner'] = $entry->partner->get_fullname();
                if ( $existing_entry ) {
                    if ( $entry->new_partner ) {
                        $existing_entry = false;
                        // delete existing league team entry
                        $this->league_team_repository->delete( $entry->league_team_id );
                        // check if the new team exists
                        $team_id = $this->team_repository->find_team_by_players( array( $player_id, $partner_id ) );
                    }
                } else {
                    // check if the team exists
                    $team_id = $this->team_repository->find_team_by_players( array( $player_id, $partner_id ) );
                }
                // if team does not exist create team
                if ( empty( $team_id ) ) {
                    $team_id = $this->add_team_with_players( array( $player, $entry->partner ), $entry->event_type );
                }
            } elseif ( ! $existing_entry ) {
                // check if the team exists
                $team_id = $this->team_repository->find_team_by_players( array( $player_id ) );
                // if team does not exist create team
                if ( ! $team_id ) {
                    $team_id = $this->add_team_with_players( array( $player ), $entry->event_type );
                }
            }
            if ( ! $existing_entry ) {
                // add team to league team table
                $league_team = new League_Team();
                $league_team->set_team_id( $team_id );
                $league_team->set_league_id( $entry->league_id );
                $league_team->set_season( $tournament->season );
                $league_team->set_captain( $player_id );
                $league_team->set_entered_state( Team_Profile::ACTIVE );
                $this->league_team_repository->save( $league_team );
                $updates = true;
            }
            $entry_details['event_name'] = $entry->event_name;
            $tournament_entries[]        = $entry_details;

            $invoice_event        = new stdClass();
            $invoice_event->name  = $entry->event_name;
            $invoice_event->type  = $entry->event_type;
            $invoice_event->count = 1;
            $invoice_event->fee   = $fees->event;
            $invoice_entries[]    = $invoice_event;
        }
        $paid_total      = $this->finance_service->get_tournament_paid_total_for_player( $player_id, $tournament_id );
        $invoice_details = new Tournament_Invoice_Details_DTO( $player_id, $player->get_fullname(), $invoice_entries, $fees->event, $fees->competition, $paid_total );
        $fee_due         = $invoice_details->total;
        $this->set_tournament_entry( $tournament_id, $player_id, $club->get_id(), $fee_due );
        if ( empty( $fee_due ) ) {
            $status = 0;
            $this->finance_service->cancel_player_invoices_by_tournament( $player_id, $tournament );
        } else {
            if ( $fee_due > 0 ) {
                if ( $player_id === get_current_user_id() ) {
                    $status = 1;
                } else {
                    $status = 4;
                }
            } else {
                $status = 2;
            }
            $this->finance_service->cancel_player_invoices_by_tournament( $player_id, $tournament );
            $this->finance_service->add_player_invoice_for_tournament( $tournament, $invoice_details );
        }

        if ( $updates ) {
            $player->set_opt_in( '1' );
            $tournament_name                     = $tournament->get_name();
            $email_to                            = $player->display_name . ' <' . $player->email . '>';
            $email_from                          = $this->racketmanager->get_confirmation_email( 'tournament' );
            $email_subject                       = $this->racketmanager->site_name . ' - ' . $tournament_name . ' ' . __( 'Tournament Entry', 'racketmanager' );
            $action_url                          = $tournament->entry_link;
            $tournament_link                     = '<a href="' . $this->racketmanager->site_url . ( $tournament->link ) . '/">' . $tournament_name . '</a>';
            $headers                             = array();
            $secretary_email                     = __( 'Tournament Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
            $headers[]                           = RACKETMANAGER_FROM_EMAIL . $secretary_email;
            $headers[]                           = RACKETMANAGER_CC_EMAIL . $secretary_email;
            $template                            = 'tournament-entry';
            $template_args['tournament_name']    = $tournament_name;
            $template_args['tournament_link']    = $tournament_link;
            $template_args['action_url']         = $action_url;
            $template_args['tournament_entries'] = $tournament_entries;
            $template_args['organisation']       = $this->racketmanager->site_name;
            $template_args['season']             = $tournament->get_season();
            $template_args['contactno']          = $player->get_contactno();
            $template_args['contactemail']       = $player->get_email();
            $template_args['player']             = $player;
            $template_args['club']               = $club->get_name();
            $template_args['comments']           = $tournament_entry->comments;
            $template_args['contact_email']      = $email_from;
            $this->notify_service->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
        } else {
            $status = 3;
        }
        $return_link      = null;
        $payment_required = false;
        switch ( $status ) {
            case 1:
                $msg              = __( 'Tournament entered and payment outstanding', 'racketmanager' );
                $return_link      = '/entry-form/' . seo_url( $tournament->name ) . '-tournament/payment/';
                $payment_required = true;
                $message_type     = 'success';
                break;
            case 2:
                $msg          = __( 'Tournament entry complete and refund outstanding', 'racketmanager' );
                $message_type = 'warning';
                break;
            case 3:
                $msg          = __( 'No updates to tournament entry', 'racketmanager' );
                $message_type = 'info';
                break;
            case 4:
                $msg          = __( 'Tournament entered and payment outstanding for player', 'racketmanager' );
                $message_type = 'success';
                break;
            default:
                $msg          = __( 'Tournament entry complete', 'racketmanager' );
                $message_type = 'success';
                break;
        }

        return new Tournament_Entry_Response_DTO( $status, $msg, $message_type, $return_link, $payment_required );
    }

    public function add_team_with_players( array $players, string $type ): int {
        global $wpdb;
        // 1. Generate name (First Last / First Last)
        $names      = [];
        $player_ids = [];
        foreach ( $players as $player ) {
            $names[]      = $player->get_fullname();
            $player_ids[] = $player->get_id();
        }
        $team = new Team();
        $team->set_name( implode( ' / ', $names ) );
        $team->set_type( $type );
        $team->set_team_type( 'P' );

        $wpdb->query( 'START TRANSACTION' );
        try {
            $team_id = $this->team_repository->save( $team );
            if ( ! $team_id ) {
                throw new Database_Operation_Exception( __( 'Unable to save team', 'racketmanager' ) );
            }
            // 4. Batch insert the current players
            $this->team_repository->save_team_players( $team_id, $player_ids );
            $wpdb->query( 'COMMIT' );

            return $team_id;
        } catch ( Database_Operation_Exception ) {
            $wpdb->query( 'ROLLBACK' );

            return false;
        }
    }

    private function set_tournament_entry( int $tournament_id, int $player_id, false|int $club_id = false, null|string $payment_required = null ): void {
        if ( $club_id ) {
            $status = 2;
        } else {
            $status = 0;
        }
        $tournament_entry = $this->get_tournament_entry_by_tournament_and_player( $tournament_id, $player_id );
        if ( $tournament_entry ) {
            if ( $club_id ) {
                if ( empty( $tournament_entry->get_club_id() ) ) {
                    $tournament_entry->set_club_id( $club_id );
                }
                $tournament_entry->set_status( $status );
                $tournament_entry->set_fee( $payment_required );
            }
        } else {
            $tournament_entry = new Tournament_Entry();
            $tournament_entry->set_status( $status );
            $tournament_entry->set_tournament_id( $tournament_id );
            $tournament_entry->set_player_id( $player_id );
            $tournament_entry->set_fee( $payment_required );
            $tournament_entry->set_club_id( $club_id );
        }
        $this->tournament_entry_repository->save( $tournament_entry );
    }

    public function get_tournament_entry_by_tournament_and_player( int $tournament_id, int $player_id ): Tournament_Entry|null {
        return $this->tournament_entry_repository->find_by_tournament_and_player( $tournament_id, $player_id );
    }

    public function validate_tournament_partner( Tournament_Partner_Request_DTO $partner_request, bool $is_modal = true ): bool|WP_Error {
        $event_id      = $partner_request->event_id;
        $tournament_id = $partner_request->tournament_id;
        $partner_id    = $partner_request->partner_id;
        $player_id     = $partner_request->player_id;
        $validator     = new Validator_Entry_Form();
        $err_field     = $is_modal ? 'partner' : 'partner-' . $event_id;
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $partner    = $this->player_service->get_player( $partner_id );
            $event      = $this->competition_service->get_event_by_id( $event_id );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception|Event_Not_Found_Exception $e ) {
            $validator->set_errors( $err_field, $e->getMessage(), 404 );
        }
        if ( empty( $validator->error ) ) {
            $league_id    = $event->primary_league ?? array_key_first( $event->league_index );
            $season       = $tournament->get_season();
            $player_team  = $this->league_team_repository->player_already_entered_league( $player_id, $league_id, $season );
            $partner_team = $this->league_team_repository->player_already_entered_league( $partner->get_id(), $league_id, $season );
            if ( ! empty( $partner_team ) && ( $player_team->team_id ?? null ) !== $partner_team->team_id ) {
                $validator->set_errors( $err_field, __( 'Partner is in another team in this event', 'racketmanager' ) );
            }
        }
        if ( $validator->error ) {
            return $validator->err;
        }

        // Perform age check (Since we didn't return above, we know they are in the same team or no team)
//        if ( isset( $partner ) && $partner->age < $event->min_age ) {
//            $validator->set_errors( $err_field, __( 'Partner does not meet the age requirement.', 'racketmanager' ) );
//        }
        return true;
    }

    public function confirm_tournament_withdrawal( ?int $tournament_id, ?int $player_id ): int {
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $player     = $this->player_service->get_player( $player_id );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception ) {
            return false;
        }
        $amount_refund    = 0;
        $updates          = false;
        $tournament_entry = $this->get_tournament_entry_by_tournament_and_player( $tournament_id, $player_id );
        if ( $tournament_entry ) {
            $tournament_entry->set_status( 3 );
            $this->tournament_entry_repository->save( $tournament_entry );
        }
        $events = $this->tournament_service->get_events_for_tournament( $tournament_id );
        foreach ( $events as $event ) {
            $entries_to_withdraw = $this->get_player_teams_for_event( $event->get_id(), $player->get_id(), $tournament->get_season() );
            foreach ( $entries_to_withdraw as $entry_to_withdraw ) {
                $entry_to_withdraw->set_entered_state( Team_Profile::WITHDRAWN );
                $this->league_team_repository->save( $entry_to_withdraw );
                $updates = true;
            }
        }
        if ( ! $updates ) {
            return $amount_refund;
        }
        $amount_paid = $this->finance_service->get_tournament_paid_total_for_player( $player_id, $tournament_id );
        $this->finance_service->cancel_player_invoices_by_tournament( $player_id, $tournament );
        if ( $amount_paid ) {
            $amount_refund   = 0 - $amount_paid;
            $invoice_details = new Tournament_Invoice_Details_DTO( $player_id, $player->get_fullname(), array(), 0, 0, $amount_paid );
            $this->finance_service->add_player_invoice_for_tournament( $tournament, $invoice_details );
        }
        $email_to                         = $player->display_name . ' <' . $player->email . '>';
        $email_from                       = $this->racketmanager->get_confirmation_email( 'tournament' );
        $email_subject                    = $this->racketmanager->site_name . ' - ' . $tournament->name . ' ' . __( 'Tournament Withdrawal', 'racketmanager' );
        $action_url                       = $tournament->entry_link;
        $tournament_link                  = '<a href="' . $this->racketmanager->site_url . ( $tournament->link ) . '">' . $tournament->name . '</a>';
        $headers                          = array();
        $secretary_email                  = __( 'Tournament Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]                        = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]                        = RACKETMANAGER_CC_EMAIL . $secretary_email;
        $template                         = 'tournament-withdrawal';
        $template_args['tournament']      = $tournament;
        $template_args['tournament_name'] = $tournament->name;
        $template_args['tournament_link'] = $tournament_link;
        $template_args['action_url']      = $action_url;
        $template_args['organisation']    = $this->racketmanager->site_name;
        $template_args['player']          = $player;
        $template_args['contact_email']   = $email_from;
        $email_message                    = $this->racketmanager->shortcodes->load_template( $template, $template_args, 'email' );
        wp_mail( $email_to, $email_subject, $email_message, $headers );

        return $amount_refund;
    }

    public function get_player_teams_for_event( int $event_id, int $player_id, int $season ): array {
        return $this->league_team_repository->find_player_teams_by_player_for_events( $player_id, array( $event_id ), $season );

    }

}
