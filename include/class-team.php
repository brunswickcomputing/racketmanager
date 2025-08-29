<?php
/**
 * Team API: Team class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage Team
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Team object
 */
final class Team {
    /**
     * ID variable
     *
     * @var int
     */
    public int $id;
    /**
     * Title variable
     *
     * @var string
     */
    public string $title;
    /**
     * Stadium variable
     *
     * @var string|null
     */
    public ?string $stadium = null;
    /**
     * Roster variable
     *
     * @var array
     */
    public mixed $roster;
    /**
     * Profile variable
     *
     * @var string|int
     */
    public string|int $profile;
    /**
     * Club id variable
     *
     * @var int|null
     */
    public ?int $club_id;
    /**
     * Club object variable
     *
     * @var object|null
     */
    public null|object $club;
    /**
     * Status variable
     *
     * @var string
     */
    public string $status;
    /**
     * Player variable
     *
     * @var string
     */
    public string $player;
    /**
     * Player 1 name variable
     *
     * @var string
     */
    public string $player_1;
    /**
     * Player2 name variable
     *
     * @var string
     */
    public string $player_2;
    /**
     * Player 1 id variable
     *
     * @var int
     */
    public int $player_1_id;
    /**
     * Player 2 ID variable
     *
     * @var int
     */
    public int $player_2_id;
    /**
     * Team type variable
     *
     * @var string
     */
    public string $type;
    /**
     * Team type variable
     *
     * @var string|null
     */
    public ? string $team_type;
    /**
     * Home variable
     *
     * @var string
     */
    public string $home;
    /**
     * Player id variable
     *
     * @var array
     */
    public array $player_ids;
    /**
     * Players variable
     *
     * @var array
     */
    public array $players = array();
    /**
     * Team ref variable
     *
     * @var string
     */
    public string $team_ref;
    /**
     * Team updated variable
     *
     * @var string|null
     */
    private ? string $msg_team_updated;
    /**
     * Team added variable
     *
     * @var string|null
     */
    private ? string $msg_team_added;
    /**
     * Team update error variable
     *
     * @var string|null
     */
    private ? string $msg_team_update_error;
    /**
     * Team add error variable
     *
     * @var string|null
     */
    private ? string $msg_team_add_error;
    /**
     * No updates variable
     *
     * @var string|null
     */
    private ? string $msg_no_update;
    /**
     * Team details missing message variable
     *
     * @var string|null
     */
    private ? string $msg_details_missing;
    /**
     * Error updating team contact variable
     *
     * @var string|null
     */
    private ? string $msg_team_contact_error;
    /**
     * Player not found error message variable
     *
     * @var string|null
     */
    private ? string $player_not_found_error;
    /**
     * Info variable
     *
     * @var object
     */
    public object $info;
    /**
     * Standings variable
     *
     * @var array|object
     */
    public array|object $standings;
    /**
     * Matches variable
     *
     * @var array
     */
    public array $matches;
    /**
     * Retrieve team instance
     *
     * @param int|string $team_id team id.
     * @return object|boolean
     */
    public static function get_instance( int|string $team_id ): object|bool {
        global $wpdb;
        if ( is_numeric( $team_id ) ) {
            $search = $wpdb->prepare(
                '`id` = %d',
                $team_id
            );
        } else {
            $search = $wpdb->prepare(
                '`title` = %s',
                $team_id
            );
        }
        if ( ! $team_id ) {
            return false;
        }
        $team = wp_cache_get( $team_id, 'teams' );

        if ( ! $team ) {
            if ( -1 === $team_id ) {
                $team = (object) array(
                    'id'     => $team_id,
                    'title'  => __( 'Bye', 'racketmanager' ),
                    'player' => array(),
                );
            } else {
                $team = $wpdb->get_row(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `id`, `title`, `stadium`, `home`, `roster`, `profile`, `status`, `club_id`, `type`, `team_type` FROM $wpdb->racketmanager_teams WHERE " . $search . ' LIMIT 1',
                ); // db call ok.
            }
            if ( ! $team ) {
                return false;
            }
            $team = new Team( $team );
            wp_cache_set( $team->id, $team, 'teams' );
        }

        return $team;
    }

    /**
     * Constructor
     *
     * @param object|null $team Team object.
     */
    public function __construct( ?object $team = null ) {
        $this->msg_team_updated       = __( 'Team updated', 'racketmanager' );
        $this->msg_team_added         = __( 'Team added', 'racketmanager' );
        $this->msg_team_update_error  = __( 'Team update error', 'racketmanager' );
        $this->msg_team_add_error     = __( 'Team add error', 'racketmanager' );
        $this->msg_no_update          = __( 'No updates', 'racketmanager' );
        $this->msg_details_missing    = __( 'Team details missing', 'racketmanager' );
        $this->msg_team_contact_error = __( 'Error updating team contact', 'racketmanager' );
        $this->player_not_found_error = __( 'Player not found', 'racketmanager' );

        if ( is_null( $team ) ) {
            return;
        }
        foreach ( get_object_vars( $team ) as $key => $value ) {
            $this->$key = $value;
        }
        if ( empty( $this->id ) ) {
            $this->add();
        }
        $this->title   = htmlspecialchars( stripslashes( $this->title ), ENT_QUOTES );
        $this->stadium = empty( $this->stadium) ? null : stripslashes( $this->stadium );
        $this->roster  = maybe_unserialize( $this->roster );
        $this->profile = intval( $this->profile );
        if ( $this->club_id ) {
            $this->club = get_club( $this->club_id );
        }
        if ( 'P' === $this->team_type && ! empty( $this->roster ) ) {
            $players = $this->get_players();
            $i       = 1;
            foreach ( $players as $player ) {
                $this->player_ids[ $i ] = $player->id;
                ++$i;
            }
        }
        if ( str_contains( $this->title, '_' ) ) {
            $team_name = Util::generate_team_name( $this->title );
            if ( ! empty( $team_name ) ) {
                $this->team_ref = $this->title;
                $this->title    = $team_name;
            }
        }
    }
    /**
     * Add new Team
     */
    private function add(): void {
        global $wpdb, $racketmanager;
        if ( isset( $this->team_type ) && 'P' === $this->team_type ) {
            $result = $this->add_player_team();
        } else {
            $result = $this->add_team();
        }
        if ( $result ) {
            $racketmanager->set_message( $this->msg_team_added );
        } else {
            $racketmanager->set_message( $this->msg_team_add_error, true );
            error_log( 'error with team creation' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }
    /**
     * Add player team
     *
     * @return bool
     *
     */
    private function add_player_team(): bool {
        global $wpdb;
        if ( 'LD' === $this->type ) {
            $this->type = 'XD';
        }
        $players = array();
        if ( empty( $this->title ) ) {
            $this->title = $this->player_1;
            $players[]   = $this->player_1_id;
            if ( substr( $this->type, 1, 1 ) === 'D' ) {
                $this->title .= ' / ' . $this->player_2;
                $players[]    = $this->player_2_id;
            }
            $this->roster = $players;
        }
        if ( empty( $this->club_id ) ) {
            $this->club_id = null;
        }
        $this->stadium = '';
        $this->profile = '';
        if ( ! isset( $this->status ) ) {
            $this->status  = '';
        }
        $result        = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_teams (`title`, `club_id`, `roster`, `status`, `type`, `team_type` ) VALUES (%s, %d, %s, %s, %s, %s)",
                $this->title,
                $this->club_id,
                maybe_serialize( $players ),
                $this->status,
                $this->type,
                $this->team_type,
            )
        );
        if ( $result ) {
            $this->id      = $wpdb->insert_id;
            foreach ( $players as $player ) {
                $this->add_team_player( $player );
            }
            return true;
        } else {
            return false;
        }
    }
    /**
     * Add non player team
     * @return bool
     */
    private function add_team(): bool {
        global $wpdb;
        if ( empty( $this->team_type ) ) {
            $this->team_type = null;
        }
        if ( empty( $this->club_id ) ) {
            $this->club_id = null;
        }
        if ( empty( $this->stadium ) ) {
            $this->stadium = null;
        }
        $this->roster  = '';
        $this->profile = '';
        $this->status  = '';
        $result        = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_teams (`title`, `stadium`, `club_id`, `type`, `team_type`) VALUES (%s, %s, %d, %s, %s)",
                $this->title,
                $this->stadium,
                $this->club_id,
                $this->type,
                $this->team_type,
            )
        );
        if ( $result ) {
            $this->id = $wpdb->insert_id;
            return true;
        } else {
            return false;
        }
    }
    /**
     * Update team
     *
     * @param string $title team name.
     * @param int $club_id affiliated club id.
     * @param string $type team type (mens/ladies/mixed/singles/doubles).
     */
    public function update( string $title, int $club_id, string $type ): void {
        global $wpdb, $racketmanager;

        $club    = get_club( $club_id );
        $stadium = $club->name;
        if ( $this->title !== $title || $this->club_id !== $club_id || $this->type !== $type || $this->stadium !== $stadium ) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_teams SET `title` = %s, `club_id` = %d, `stadium` = %s, `type` = %s WHERE `id` = %d",
                    $title,
                    $club_id,
                    $stadium,
                    $type,
                    $this->id
                )
            ); // db call ok, no cache ok.
            if ( $result ) {
                wp_cache_delete( $this->id, 'teams' );
                $racketmanager->set_message( $this->msg_team_updated );
            } else {
                $racketmanager->set_message( $this->msg_team_update_error, true );
                error_log( 'error with team update' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        } else {
            $racketmanager->set_message( $this->msg_no_update );
        }
    }

    /**
     * Update team for players
     *
     * @param string $player_1
     * @param int $player_1_id
     * @param string $player_2
     * @param int $player_2_id
     * @param int $club_id affiliated club id.
     */
    public function update_player( string $player_1, int $player_1_id, string $player_2, int $player_2_id, int $club_id ): void {
        global $wpdb, $racketmanager;

        $players   = array();
        $players[] = $player_1_id;
        $title     = $player_1;
        if ( $player_2_id ) {
            $title    .= ' / ' . $player_2;
            $players[] = $player_2_id;
        }

        $club    = get_club( $club_id );
        $stadium = $club->name;
        if ( $this->title !== $title || $this->club_id !== $club_id || $this->roster !== $players || $this->stadium !== $stadium ) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_teams SET `title` = %s, `club_id` = %d, `stadium` = %s, `roster` = %s WHERE `id` = %d",
                    $title,
                    $club_id,
                    $stadium,
                    maybe_serialize( $players ),
                    $this->id
                )
            ); // db call ok, no cache ok.
            if ( $result ) {
                wp_cache_delete( $this->id, 'teams' );
                $racketmanager->set_message( $this->msg_team_updated );
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $wpdb->racketmanager_team_players WHERE `id` = %d",
                        $this->id
                    )
                ); // db call ok, no cache ok.
                foreach ( $players as $player ) {
                    $this->add_team_player( $player );
                }
            } else {
                $racketmanager->set_message( $this->msg_team_update_error, true );
                error_log( 'Error with player team update' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        } else {
            $racketmanager->set_message( $this->msg_no_update );
        }
    }

    /**
     * Set event
     *
     * @param int $event_id event id.
     * @param string|null $captain optional captain id.
     * @param string|null $contact_no optional contact number.
     * @param string|null $contact_email optional contact email.
     * @param int|null $match_day optional match day.
     * @param string|null $match_time optional match time.
     * @return boolean
     */
    public function set_event( int $event_id, ?string $captain = null, ?string $contact_no = null, ?string $contact_email = null, int|null $match_day = null, string|null $match_time = null ): bool {
        global $wpdb, $racketmanager;

        $count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->racketmanager_team_events WHERE `team_id` = %d AND `event_id` = %d",
                $this->id,
                $event_id
            )
        );
        if ( $count ) {
            if ( $captain ) {
                $msg = $this->update_event( $event_id, $captain, $contact_no, $contact_email, $match_day, $match_time );
            } else {
                $msg = __( 'Team added', 'racketmanager' );
            }
            $racketmanager->set_message( $msg );
        } else {
            $this->add_event( $event_id, $captain, $contact_no, $contact_email, $match_day, $match_time );
            $racketmanager->set_message( $this->msg_team_added );
        }

        return true;
    }

    /**
     * Add team to event
     *
     * @param int $event_id event id.
     * @param string|null $captain captain id.
     * @param string|null $contactno optional contact number.
     * @param string|null $contactemail optional contact email.
     * @param int|null    $matchday optional match day.
     * @param string|null $matchtime optional match time.
     * @return int $team_event_id
     */
    public function add_event( int $event_id, ?string $captain = null, ?string $contactno = null, ?string $contactemail = null, int|null $matchday = null, string $matchtime = null ): int {
        global $wpdb;
        if ( is_null( $matchday) ) {
            $match_day = '';
        } else {
            $match_day = Util::get_match_day( $matchday );
        }
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_team_events (`team_id`, `event_id`, `captain`, `match_day`, `match_time`) VALUES (%d, %d, %d, %s, %s)",
                $this->id,
                $event_id,
                $captain,
                $match_day,
                $matchtime
            )
        );
        $team_event_id = $wpdb->insert_id;
        if ( $captain ) {
            $player = get_player( $captain );
            $player->update_contact( $contactno, $contactemail );
        }
        return $team_event_id;
    }

    /**
     * Update event details
     *
     * @param int $event_id event id.
     * @param int $captain captain id.
     * @param string $contactno optional contact number.
     * @param string $contactemail optional contact email.
     * @param int|null $matchday optional match day.
     * @param string|null $matchtime optional match time.
     * @return false|string|null $team_event_id
     */
    public function update_event( int $event_id, int $captain, string $contactno, string $contactemail, ?int $matchday, ?string $matchtime ): false|string|null {
        global $wpdb;
        $updates = false;
        $msg     = false;
        if ( is_null( $matchday) ) {
            $match_day = '';
        } else {
            $match_day = Util::get_match_day( $matchday );
        }
        $event   = get_event( $event_id );
        $current = $event->get_team_info( $this->id );
        if ( $current->captain_id !== $captain || $current->match_day !== $match_day || $current->match_time !== $matchtime ) {
            if ( $captain && ( ( $event->competition->is_team_entry && $match_day && $matchtime ) || $event->competition->is_player_entry ) ) {
                $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                        "UPDATE $wpdb->racketmanager_team_events SET `captain` = %s, `match_day` = %s, `match_time` = %s WHERE `team_id` = %d AND `event_id` = %d",
                        $captain,
                        $match_day,
                        $matchtime,
                        $this->id,
                        $event_id
                    )
                );
                $updates = true;
            } else {
                $msg = $this->msg_details_missing;
            }
        }
        if ( $current->contactno !== $contactno || $current->contactemail !== $contactemail ) {
            $response = $this->update_captain_details( $captain, $contactno, $contactemail );
            if ( $response->updates ) {
                $updates = true;
            }
        }
        if ( $updates ) {
            $msg = $this->msg_team_updated;
        } elseif ( empty( $msg ) ) {
            $msg = $this->msg_no_update;
        }
        return $msg;
    }
    /**
     * Update captain details
     *
     * @param $captain
     * @param $telephone
     * @param $email
     *
     * @return object
     */
    private function update_captain_details( $captain, $telephone, $email ): object {
        $player = get_player( $captain );
        if ( $player ) {
            $updates = $player->update_contact( $telephone, $email );
            if ( ! $updates ) {
                $msg = $this->msg_team_contact_error;
            } else {
                $msg = null;
            }
        } else {
            $updates = false;
            $msg     = $this->player_not_found_error;
        }
        $response          = new stdClass();
        $response->updates = $updates;
        $response->msg     = $msg;
        return $response;
    }
    /**
     * Delete team
     */
    public function delete(): void {
        global $wpdb, $racketmanager;

        // remove matches and rubbers.
        $matches = $racketmanager->get_matches( array( 'team' => $this->id ) );
        foreach ( $matches as $match ) {
            $match = get_match( $match->id );
            $match->delete();
        }
        // remove tables.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_table WHERE `team_id` = %d",
                $this->id
            )
        );
        // remove team event.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_team_events WHERE `team_id` = %d",
                $this->id
            )
        );
        // remove team.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_teams WHERE `id` = %d",
                $this->id
            )
        );
    }

    /**
     * Update title
     *
     * @param string $title title.
     */
    public function update_title( string $title ): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_teams SET `title` = %s WHERE `id` = %d",
                $title,
                $this->id
            )
        );
    }
    /**
     * Add team player
     *
     * @param int $player player id.
     */
    public function add_team_player( int $player ): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_team_players ( `team_id`, `player_id` ) VALUES ( %d, %d )",
                $this->id,
                $player,
            )
        );
    }
    /**
     * Get team players
     *
     * @return array
     */
    public function get_players(): array {
        global $wpdb;
        $players = $wpdb->get_results(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT `player_id` FROM $wpdb->racketmanager_team_players WHERE `team_id` = %d",
                $this->id,
            )
        );
        $i       = 0;
        foreach ( $players as $team_player ) {
            $player = get_player( $team_player->player_id );
            if ( $player ) {
                $players[ $i ] = $player;
            } else {
                unset( $players[ $i ] );
            }
            ++$i;
        }
        $this->players = $players;
        return $players;
    }
}
