<?php
/**
 * Club API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Club object
 */
final class Club {
    /**
     * Id
     *
     * @var int
     */
    public int $id;
    /**
     * Match secretary contact name
     *
     * @var string
     */
    public string $match_secretary_name;
    /**
     * Match secretary contact email
     *
     * @var string
     */
    public string $match_secretary_email;
    /**
     * Match secretary contact number
     *
     * @var string
     */
    public mixed $match_secretary_contact_no;
    /**
     * Match secretary ID
     *
     * @var int|null
     */
    public ?int $matchsecretary;
    /**
     * Description
     *
     * @var string
     */
    public string $desc;
    /**
     * Name
     *
     * @var string
     */
    public string $name;
    /**
     * Short code
     *
     * @var string
     */
    public string $shortcode;
    /**
     * Type
     *
     * @var string
     */
    public string $type;
    /**
     * Website
     *
     * @var string
     */
    public string $website;
    /**
     * Contact number
     *
     * @var string
     */
    public string $contactno;
    /**
     * Founded
     *
     * @var int|null
     */
    public ? int $founded;
    /**
     * Facilities
     *
     * @var string
     */
    public string $facilities;
    /**
     * Address
     *
     * @var string
     */
    public string $address;
    /**
     * Longitude
     *
     * @var string
     */
    public string $longitude;
    /**
     * Latitude
     *
     * @var string
     */
    public string $latitude;
    /**
     * Number of players.
     *
     * @var int
     */
    public int $num_players;
    /**
     * Url link.
     *
     * @var string
     */
    public string $link;
    /**
     * Team count
     *
     * @var int
     */
    public int $team_count;
    /**
     * Player count
     *
     * @var int
     */
    public int $player_count;
    /**
     * Players variable
     *
     * @var array
     */
    public array $players;
    /**
     * Teams variable
     *
     * @var array
     */
    public array $teams;
    /**
     * Matches variable
     *
     * @var array
     */
    public array $matches;
    /**
     * Results variable
     *
     * @var array
     */
    public array $results;
    /**
     * Created date variable
     *
     * @var string|null
     */
    public ?string $created_date;
    /**
     * Removed date variable
     *
     * @var string|null
     */
    public ?string $removed_date;
    /**
     * Player variable
     *
     * @var object
     */
    public object $player;
    /**
     * Single instance variable
     *
     * @var boolean
     */
    public bool $single;
    /**
     * Invoices variable
     *
     * @var array
     */
    public array $invoices;
    /**
     * Invoice variable
     *
     * @var object
     */
    public object $invoice;
    /**
     * Entry
     *
     * @var array
     */
    public array $entry;
    /**
     * Competitions variable
     *
     * @var array
     */
    public array $competitions;
    /**
     * Competition variable
     *
     * @var object
     */
    public object $competition;
    /**
     * Team variable
     *
     * @var object
     */
    public object $team;
    /**
     * Event variable
     *
     * @var object
     */
    public object $event;
    /**
     * Player stats variable
     *
     * @var array
     */
    public array $player_stats;
    /**
     * Player club player id
     *
     * @var int
     */
    public int $club_player_id;
    /**
     * Retrieve club instance
     *
     * @param int|string $club_id club id or name.
     * @param string $search_term search.
     */
    public static function get_instance( int|string $club_id, string $search_term = 'id' ) {
        global $wpdb;

        $search = match ($search_term) {
            'name'      => $wpdb->prepare(
                '`name` = %s',
                $club_id
            ),
            'shortcode' => $wpdb->prepare(
                '`shortcode` = %s',
                $club_id
            ),
            default     => $wpdb->prepare(
                '`id` = %d',
                $club_id
            ),
        };

        if ( ! $club_id ) {
            return false;
        }

        $club = wp_cache_get( $club_id, 'clubs' );

        if ( ! $club ) {
            $club = $wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM $wpdb->racketmanager_clubs WHERE " . $search . ' LIMIT 1'
            ); // db call ok.

            if ( ! $club ) {
                return false;
            }

            $club = new Club( $club );

            wp_cache_set( $club_id, $club, 'clubs' );
        }

        return $club;
    }

    /**
     * Constructor
     *
     * @param object|null $club Club object.
     */
    public function __construct( ?object $club = null ) {
        if ( ! is_null( $club ) ) {
            foreach ( get_object_vars( $club ) as $key => $value ) {
                $this->$key = $value;
            }

            if ( ! isset( $this->id ) ) {
                $this->add();
            }
            $this->match_secretary_name       = '';
            $this->match_secretary_email      = '';
            $this->match_secretary_contact_no = '';
            if ( ! empty( $this->matchsecretary ) ) {
                $match_secretary_dtls = get_userdata( $this->matchsecretary );
                if ( $match_secretary_dtls ) {
                    $this->match_secretary_name       = $match_secretary_dtls->display_name;
                    $this->match_secretary_email      = $match_secretary_dtls->user_email;
                    $this->match_secretary_contact_no = get_user_meta( $this->matchsecretary, 'contactno', true );
                }
            }
            $this->desc = '';
            $this->link = '/clubs/' . seo_url( $this->shortcode ) . '/';
        }
    }

    /**
     * Create new club
     */
    private function add(): void {
        global $wpdb;

        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_clubs (`name`, `type`, `shortcode`, `contactno`, `website`, `founded`, `facilities`, `address`, `latitude`, `longitude`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s,%s )",
                $this->name,
                $this->type,
                $this->shortcode,
                $this->contactno,
                $this->website,
                $this->founded,
                $this->facilities,
                $this->address,
                $this->latitude,
                $this->longitude
            )
        );
        $this->id = $wpdb->insert_id;
    }

    /**
     * Update club
     *
     * @param object $club updated club information.
     * @param false|string $prev_shortcode previous short code.
     */
    public function update( object $club, false|string $prev_shortcode = false ): void {
        global $wpdb;

        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_clubs SET `name` = %s, `type` = %s, `shortcode` = %s,`matchsecretary` = %d, `contactno` = %s, `website` = %s, `founded`= %s, `facilities` = %s, `address` = %s, `latitude` = %s, `longitude` = %s WHERE `id` = %d",
                $club->name,
                $club->type,
                $club->shortcode,
                $club->matchsecretary,
                $club->contactno,
                $club->website,
                $club->founded,
                $club->facilities,
                $club->address,
                $club->latitude,
                $club->longitude,
                $this->id
            )
        );

        if ( $prev_shortcode && $prev_shortcode !== $this->shortcode ) {
            $teams = $this->get_teams();
            foreach ( $teams as $team ) {
                $team      = get_team( $team->id );
                $team_ref  = substr( $team->title, strlen( $prev_shortcode ) + 1, strlen( $team->title ) );
                $new_title = $club->shortcode . ' ' . $team_ref;
                $team->update_title( $new_title );
            }
        }
        if ( '' !== $club->matchsecretary ) {
            $player = get_player( $club->matchsecretary );
            $player->update_contact( $club->match_secretary_contact_no, $club->match_secretary_email );
        }
    }

    /**
     * Delete Club
     */
    public function delete(): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_club_players WHERE `club_id` = %d",
                $this->id
            )
        );
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_clubs WHERE `id` = %d",
                $this->id
            )
        );
    }

    /**
     * Create team in database
     *
     * @param string $type type of team to create.
     * @return object|boolean
     */
    public function add_team( string $type ): object|bool {
        global $racketmanager;

        $type_name = match (substr($type, 0, 1)) {
            'B' => 'Boys',
            'G' => 'Girls',
            'W' => 'Ladies',
            'M' => 'Mens',
            'X' => 'Mixed',
            default => 'error',
        };

        if ( 'error' === $type_name ) {
            $racketmanager->set_message( __( 'Type not selected', 'racketmanager' ), true );
            return false;
        }
        $team_count = $this->get_teams( array( 'count' => true, 'type' => $type ) );
        ++$team_count;
        $team          = new stdClass();
        $team->title   = $this->shortcode . ' ' . $type_name . ' ' . $team_count;
        $team->stadium = $this->name;
        $team->club_id = $this->id;
        $team->type    = $type;
        return new Team( $team );
    }
    /**
     * Get teams for club
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
            'count'   => false,
            'players' => false,
            'type'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $count    = $args['count'];
        $players  = $args['players'];
        $type     = $args['type'];

        $search_terms = array();
        $sql    = " FROM $wpdb->racketmanager_teams WHERE `club_id` = '%d'";
        $search_terms[] = $this->id;
        if ( ! $players ) {
            $sql .= " AND (`team_type` is null OR `team_type` != 'P')";
        } else {
            $sql .= " AND `team_type` = 'P'";
        }
        if ( $type ) {
            if ( 'OS' === $type ) {
                $sql   .= " AND `type` like '%%%s%%'";
                $search_terms[] = 'S';
            } else {
                $sql   .= " AND `type` = '%s'";
                $search_terms[] = $type;
            }
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(*) ' . $sql;
            return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    $search_terms
                )
            );
        }
        $sql  = 'SELECT `id` ' . $sql . ' ORDER BY `title`';
        $sql  = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $search_terms
        );

        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }

        $class = '';
        foreach ( $teams as $i => $team ) {
            $class       = ( 'alternate' === $class ) ? '' : 'alternate';
            $team        = get_team( $team->id );
            $teams[ $i ] = $team;
        }

        return $teams;
    }
    /**
     * Register player for Club
     *
     * @param object $new_player player details.
     * @return object
     */
    public function register_player( object $new_player ): object {
        global $racketmanager;
        $return         = new stdClass();
        $return->error  = false;
        $old_player     = false;
        $updated_player = null;
        $player_change  = false;
        $player         = get_player( $new_player->user_login, 'login' ); // get player by login.
        if ( ! $player ) {
            $player = get_player( $new_player->email, 'email' );
            if ( $player ) {
                $old_player = true;
                $return->error      = true;
                $return->msg        = __( 'Email address already used', 'racketmanager' );
                $return->status     = 401;
            } else {
                $player = get_player( $new_player->btm, 'btm' );
                if ( $player ) {
                    $old_player = true;
                    $return->error      = true;
                    $return->msg        = __( 'LTA Tennis Number already used', 'racketmanager' );
                    $return->status     = 401;
                } else {
                    $player = new Player( $new_player );
                }
            }
        } else {
            $old_player = true;
        }
        if ( empty( $return->error ) && $old_player ) {
            $updated_player = clone $player;
            if ( empty( $player->email ) ) {
                if ( ! empty( $new_player->email ) ) {
                    $player_change         = true;
                    $updated_player->email = $new_player->email;
                }
            } elseif ( ! empty( $new_player->email ) && $player->email !== $new_player->email ) {
                $return->error      = true;
                $return->msg        = __( 'Email address is does not match current email', 'racketmanager' );
                $return->status     = 401;
            }
            if ( empty( $player->btm ) ) {
                if ( ! empty( $new_player->btm ) ) {
                    $player_change       = true;
                    $updated_player->btm = $new_player->btm;
                }
            } elseif ( ! empty( $new_player->btm ) && intval( $player->btm ) !== intval( $new_player->btm ) ) {
                $return->error      = true;
                $return->msg        = __( 'LTA Tennis Number does not match current number', 'racketmanager' );
                $return->status     = 401;
            }
            if ( empty( $player->gender ) ) {
                if ( ! empty( $new_player->gender ) ) {
                    $player_change          = true;
                    $updated_player->gender = $new_player->gender;
                }
            } elseif ( ! empty( $new_player->gender ) && $player->gender !== $new_player->gender ) {
                $return->error      = true;
                $return->msg        = __( 'Gender does not match current gender', 'racketmanager' );
                $return->status     = 401;
            }
            if ( empty( $player->year_of_birth ) ) {
                if ( ! empty( $new_player->year_of_birth ) ) {
                    $player_change                 = true;
                    $updated_player->year_of_birth = $new_player->year_of_birth;
                }
            } elseif ( ! empty( $new_player->year_of_birth ) && intval( $player->year_of_birth ) !== $new_player->year_of_birth ) {
                $return->error      = true;
                $return->msg        = __( 'Year of birth does not match current', 'racketmanager' );
                $return->status     = 401;
            }
        }
        if ( empty( $return->error ) ) {
            if ( $player_change ) {
                $return = $player->update( $updated_player );
            }
            $player_active = $this->player_status( $player->id, 'active' );
            if ( ! $player_active ) {
                $player_pending = $this->player_status( $player->id, 'pending' );
                if ( $player_pending ) {
                    $return->error      = true;
                    $return->status     = 401;
                    $return->msg        = __( 'Player registration already pending', 'racketmanager' );
                } else {
                    $club_player            = new stdClass();
                    $club_player->club_id   = $this->id;
                    $club_player->player_id = $player->id;
                    $club_player            = new Club_Player( $club_player );
                    $options                = $racketmanager->get_options( 'rosters' );
                    if ( 'auto' === $options['rosterConfirmation'] || current_user_can( 'edit_teams' ) ) {
                        $club_player->approve();
                        $action = 'add';
                        $msg    = __( 'Player added to club', 'racketmanager' );
                    } else {
                        $action = 'request';
                        $msg    = __( 'Player registration pending', 'racketmanager' );
                    }
                    if ( ! empty( $options['rosterConfirmationEmail'] ) ) {
                        $headers = array();
                        $user    = wp_get_current_user();
                        if ( $this->matchsecretary !== $user->ID ) {
                            $headers[] = RACKETMANAGER_CC_EMAIL . $this->match_secretary_name . ' <' . $this->match_secretary_email . '>';
                        }
                        $email_to                  = $user->display_name . ' <' . $user->user_email . '>';
                        $message_args              = array();
                        $message_args['requestor'] = $user->display_name;
                        $message_args['action']    = $action;
                        $message_args['club']      = $this->shortcode;
                        $message_args['player']    = $player->fullname;
                        $message_args['btm']       = empty( $player->btm ) ? null : $player->btm;
                        $headers[]                 = RACKETMANAGER_FROM_EMAIL . $racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
                        $headers[]                 = RACKETMANAGER_CC_EMAIL . $racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
                        $subject                   = $racketmanager->site_name . ' - ' . $msg . ' - ' . $this->shortcode;
                        $message                   = club_players_notification( $message_args );
                        wp_mail( $email_to, $subject, $message, $headers );
                    }
                    $return->msg = $msg;
                }
            } else {
                $return->error      = true;
                $return->status     = 401;
                $return->msg        = __( 'Player already registered', 'racketmanager' );
            }
        }
        return $return;
    }
    /**
     * Check for player status
     *
     * @param int $player player id.
     * @return string count of players with status
     */
    private function player_status( int $player, string $status ): string {
        global $wpdb;
        $search = match ( $status ) {
            'active'  => ' AND `removed_date` IS NULL',
            'pending' => ' AND `created_date` IS NULL',
            default   => null,
        };
        return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT count(*) FROM $wpdb->racketmanager_club_players WHERE `club_id` = %d AND `player_id` = %d $search",
                $this->id,
                $player
            )
        );
    }
    /**
     * Gets club players from database
     *
     * @param array $args query arguments.
     * @return array|int
     */
    public function get_players( array $args ): array|int {
        global $wpdb;
        $defaults   = array(
            'count'      => false,
            'team'       => false,
            'player'     => false,
            'gender'     => false,
            'active'     => true,
            'type'       => false,
            'age_offset' => false,
            'age_limit'  => false,
        );
        $args       = array_merge( $defaults, $args );
        $count      = $args['count'];
        $team       = $args['team'];
        $player     = $args['player'];
        $gender     = $args['gender'];
        $active     = $args['active'];
        $type       = $args['type'];
        $age_limit  = $args['age_limit'];
        $age_offset = $args['age_offset'];

        $search_terms = array();
        if ( $team ) {
            $search_terms[] = $wpdb->prepare( "`club_id` in (select `club_id` from $wpdb->racketmanager_teams where `id` = %d)", intval( $team ) );
        }

        if ( $player ) {
            $search_terms[] = $wpdb->prepare( '`player_id` = %d', intval( $player ) );
        }
        if ( $type ) {
            $search_terms[] = '`system_record` IS NULL';
        }

        if ( $active ) {
            $search_terms[] = '`removed_date` IS NULL';
        }
        $search = '';
        if ( ! empty( $search_terms ) ) {
            $search = implode( ' AND ', $search_terms );
        }
        $sql   = $wpdb->prepare(" FROM $wpdb->racketmanager_club_players WHERE `club_id` = %d AND `player_id` > 0", $this->id );
        if ( ! empty( $search ) ) {
            $sql .= ' AND ' . $search;
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(ID)' . $sql;
            $cache_key = md5( $sql );
            $this->num_players = wp_cache_get( $cache_key, 'club-players' );
            if ( ! $this->num_players ) {
                $this->num_players = $wpdb->get_var(
                    $sql,
                );
                wp_cache_set( md5( $sql ), $this->num_players, 'club-players' );
            }
            return $this->num_players;
        }
        $sql         = 'SELECT `id` as `roster_id`, `player_id`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`' . $sql;
        $players_out = wp_cache_get( md5( $sql ), 'club-players' );
        if ( ! $players_out ) {
            $players_out = array();
            $players     = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            foreach ( $players as $player ) {
                $player_dtl = $this->build_player_detail( $player, $gender, $age_limit, $age_offset );
                if ( $player_dtl ) {
                    $players_out[] =  $player_dtl;
                }
            }
            wp_cache_set( md5( $sql ), $players_out, 'club-players' );
        }
        $display_name = array_column( $players_out, 'display_name' );
        array_multisort( $display_name, SORT_ASC, $players_out );
        return $players_out;
    }

    /**
     * Function to format player detail
     *
     * @param object $club_player
     * @param string|null $gender
     * @param string|null $age_limit
     * @param string|null $age_offset
     *
     * @return false|stdClass
     */
    private function build_player_detail( object $club_player, ?string $gender, ?string $age_limit, ?string $age_offset ): false|stdClass {
        global $racketmanager;
        $options            = $racketmanager->get_options( 'rosters' );
        $player_invalid     = false;
        $player_dtl         = new stdClass();
        $player_dtl->gender = get_user_meta( $club_player->player_id, 'gender', true );
        if ( $gender && $gender !== $player_dtl->gender ) {
            return false;
        }
        $player_dtl->roster_id         = $club_player->roster_id;
        $player_dtl->player_id         = $club_player->player_id;
        $player_dtl->removed_date      = $club_player->removed_date;
        $player_dtl->removed_user      = $club_player->removed_user;
        $player_dtl->removed_user_name = '';
        if ( $club_player->removed_user ) {
            $club_player->removed_user_name = get_userdata( $club_player->removed_user )->display_name;
        }
        $player_dtl->created_date      = $club_player->created_date;
        $player_dtl->created_user      = $club_player->created_user;
        $player_dtl->created_user_name = '';
        if ( $club_player->created_user ) {
            $player_dtl->created_user_name = get_userdata( $club_player->created_user )->display_name;
        }
        $player = get_player( $club_player->player_id );
        if ( $player ) {
            $player_dtl->wtn              = $player->wtn;
            $player_dtl->display_name     = $player->display_name;
            $player_dtl->fullname         = $player->display_name;
            $player_dtl->type             = $player->type;
            $player_dtl->btm              = $player->btm;
            $player_dtl->email            = $player->user_email;
            $player_dtl->locked           = $player->locked;
            $player_dtl->locked_date      = $player->locked_date;
            $player_dtl->locked_user      = $player->locked_user;
            $player_dtl->locked_user_name = $player->locked_user_name;
            $player_dtl->year_of_birth    = $player->year_of_birth;
            $player->age                  = null;
            if ( $player->year_of_birth ) {
                $player->age = gmdate( 'Y' ) - intval( $player->year_of_birth );
            }
            $player_dtl->age = $player->age;
            if ( ! empty( $options['ageLimitCheck'] ) && $age_limit && 'open' !== $age_limit ) {
                $age_check = Util::check_age_within_limit( $player->age, $age_limit, $gender, $age_offset );
                if ( ! $age_check->valid ) {
                    $player_invalid = true;
                }
            }
        } else {
            $player_invalid = true;
        }
        if ( $player_invalid ) {
            return false;
        }
        return $player_dtl;
    }
    /**
     * Gets player for club from database
     *
     * @param int $player_id player id.
     * @return object|null
     */
    public function get_player( int $player_id ): ?object {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `club_id`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM $wpdb->racketmanager_club_players A INNER JOIN $wpdb->users B ON A.`player_id` = B.`ID` WHERE `club_id` = %d AND `player_id` = %d",
            $this->id,
            $player_id
        );

        $player = wp_cache_get( md5( $sql ), 'players' );
        if ( ! $player ) {
            $player = $wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $player, 'players' );
        }

        if ( $player ) {
            $player->gender = get_user_meta( $player->player_id, 'gender', true );
            $player->type   = get_user_meta( $player->player_id, 'racketmanager_type', true );
            if ( $player->removed_user ) {
                $player->removed_user_name = get_userdata( $player->removed_user )->display_name;
            } else {
                $player->removed_user_name = '';
            }
            $player->btm = get_user_meta( $player->player_id, 'btm', true );
            if ( $player->created_user ) {
                $player->created_user_name = get_userdata( $player->created_user )->display_name;
            } else {
                $player->created_user_name = '';
            }
            $player->locked      = get_user_meta( $player->player_id, 'locked', true );
            $player->locked_date = get_user_meta( $player->player_id, 'locked_date', true );
            $player->locked_user = get_user_meta( $player->player_id, 'locked_user', true );
            if ( $player->locked_user ) {
                $player->locked_user_name = get_userdata( $player->locked_user )->display_name;
            } else {
                $player->locked_user_name = '';
            }
            $player->year_of_birth = get_user_meta( $player->player_id, 'year_of_birth', true );
        }

        return $player;
    }

    /**
     * Check if player is captain
     *
     * @param int $player player id.
     * @return string|null
     */
    public function is_player_captain( int $player ): ? string {
        global $wpdb;
        return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT count(*) FROM $wpdb->racketmanager_team_events te, $wpdb->racketmanager_teams t, $wpdb->racketmanager_clubs c WHERE c.`id` = %d AND c.`id` = t.`club_id` AND (t.`team_type` IS NULL OR t.`team_type` != 'P') AND t.`id` = te.`team_id` AND te.`captain` = %d",
                $this->id,
                $player
            )
        );
    }
    /**
     * Can user update players.
     * @return bool
     */
    public function can_user_update_players(): bool {
        global $racketmanager;
        $user_can_update = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( $this->matchsecretary === $userid ) {
                    $user_can_update = true;
                } elseif ( $this->is_player_captain( $userid ) ) {
                    $options = $racketmanager->get_options( 'rosters' );
                    if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] ) {
                        $user_can_update = true;
                    }
                }
            }
        }
        return $user_can_update;
    }
    /**
     * Can user update as a team captain in addition to as match secretary or admin user.
     * @return bool
     */
    public function can_user_update_as_captain(): bool {
        $user_can_update     = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( $this->matchsecretary === $userid || $this->is_player_captain( $userid ) ) {
                    $user_can_update = true;
                }
            }
        }
        return $user_can_update;
    }
    /**
     * Can user update as match secretary or admin user.
     * @return bool
     */
    public function can_user_update(): bool {
        $user_can_update     = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( $this->matchsecretary === $userid ) {
                    $user_can_update = true;
                }
            }
        }
        return $user_can_update;
    }
    /**
     * Cup entry function
     *
     * @param object $club_entry club cup entry object.
     */
    public function cup_entry( object $club_entry ): void {
        global $racketmanager;
        $cup_entries = array();
        foreach ( $club_entry->events as $event_entry ) {
            $cup_entry = array();
            $event     = get_event( $event_entry->id );
            if ( isset( $event->primary_league ) ) {
                $league = get_league( $event->primary_league );
            } else {
                $league = get_league( array_key_first( $event->league_index ) );
            }
            $team_id   = $event_entry->team_id;
            $team      = get_team( $team_id );
            $team_info = $event->get_team_info( $team_id );
            $match_day = Util::get_match_day( $event_entry->match_day );
            if ( ! $team_info ) {
                $team->add_event( $event->id, $event_entry->captain_id, $event_entry->telephone, $event_entry->email, $event_entry->match_day, $event_entry->match_time );
            } else {
                $team->update_event( $event->id, $event_entry->captain_id, $event_entry->telephone, $event_entry->email, $event_entry->match_day, $event_entry->match_time );
            }
            $league->add_team( $team_id, $club_entry->season );
            $cup_entry['event']        = $event->name;
            $cup_entry['teamName']     = $team->title;
            $cup_entry['captain']      = $event_entry->captain;
            $cup_entry['contactno']    = $event_entry->telephone;
            $cup_entry['contactemail'] = $event_entry->email;
            $cup_entry['matchday']     = $match_day;
            $cup_entry['matchtime']    = $event_entry->match_time;
            $cup_entries[]             = $cup_entry;
        }
        $email_to        = $this->match_secretary_name . ' <' . $this->match_secretary_email . '>';
        $email_from      = $racketmanager->get_confirmation_email( 'cup' );
        $email_subject   = $racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' ' . __('Entry', 'racketmanager' ) . ' - ' . $this->shortcode;
        $headers         = array();
        $secretary_email = __( 'Cup Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]       = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]       = RACKETMANAGER_CC_EMAIL . $secretary_email;

        $template                     = 'cup-entry';
        $template_args['cup_entries'] = $cup_entries;
        $this->entry_form_send( $template_args, $club_entry, $email_from, $template, $email_to, $email_subject, $headers );
    }

    /**
     * League entry function
     *
     * @param object $club_entry club league entry object.
     */
    public function league_entry( object $club_entry ): void {
        global $racketmanager;
        $event_details = array();
        $competition   = get_competition( $club_entry->competition );
        foreach ( $club_entry->event as $event_entry ) {
            $event                       = get_event( $event_entry->id );
            $league_event_entry['event'] = $event_entry->name;
            $league_entries              = array();
            foreach ( $event_entry->team as $team_entry ) {
                $match_day = Util::get_match_day( $team_entry->match_day );
                if ( empty( $team_entry->id ) ) {
                    $team = $this->add_team( $event->type );
                } else {
                    $team = get_team( $team_entry->id );
                }
                $team_info = $event->get_team_info( $team->id );
                if ( ! $team_info ) {
                    $team->add_event( $event_entry->id, $team_entry->captain_id, $team_entry->telephone, $team_entry->email, $team_entry->match_day, $team_entry->match_time );
                } else {
                    $team->update_event( $event_entry->id, $team_entry->captain_id, $team_entry->telephone, $team_entry->email, $team_entry->match_day, $team_entry->match_time );
                }
                if ( $team_entry->existing ) {
                    $event->mark_teams_entered( $team->id, $club_entry->season );
                } else {
                    $event->add_team_to_event( $team->id, $club_entry->season );
                }
                $league_entry                 = array();
                $league_entry['teamName']     = $team->title;
                $league_entry['captain']      = $team_entry->captain;
                $league_entry['contactno']    = $team_entry->telephone;
                $league_entry['contactemail'] = $team_entry->email;
                $league_entry['matchday']     = $match_day;
                $league_entry['matchtime']    = substr( $team_entry->match_time, 0, 5 );
                $league_entries[]             = $league_entry;
            }
            if ( ! empty( $event_entry->withdrawn_teams ) ) {
                foreach ( $event_entry->withdrawn_teams as $team ) {
                    if ( $team ) {
                        $event->mark_teams_withdrawn( $club_entry->season, $this->id, $team );
                    }
                }
            }
            $league_event_entry['teams'] = $league_entries;
            $event_details[]             = $league_event_entry;
        }
        $event_entries['events']               = $event_details;
        $event_entries['num_courts_available'] = $club_entry->num_courts_available;
        if ( ! empty( $club_entry->withdrawn_events ) ) {
            foreach ( $club_entry->withdrawn_events as $event_id ) {
                $event = get_event( $event_id );
                $event->mark_teams_withdrawn( $club_entry->season, $this->id );
            }
        }
        $competition->settings['num_courts_available'][ $this->id ] = $club_entry->num_courts_available;
        $competition->set_settings( $competition->settings );
        $email_to        = $this->match_secretary_name . ' <' . $this->match_secretary_email . '>';
        $email_from      = $racketmanager->get_confirmation_email( 'league' );
        $email_subject   = $racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' League Entry - ' . $this->shortcode;
        $headers         = array();
        $secretary_email = __( 'League Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]       = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]       = RACKETMANAGER_CC_EMAIL . $secretary_email;

        $template                       = 'league-entry';
        $template_args['event_entries'] = $event_entries;
        $this->entry_form_send( $template_args, $club_entry, $email_from, $template, $email_to, $email_subject, $headers );
    }
    /**
     * Send entry form
     * *
     * @param array  $template_args
     * @param object $club_entry
     * @param string $email_from
     * @param string $template
     * @param string $email_to
     * @param string $email_subject
     * @param array $headers
     * @return void
     */
    public function entry_form_send( array $template_args, object $club_entry, string $email_from, string $template, string $email_to, string $email_subject, array $headers ): void {
        global $racketmanager;
        $template_args['organisation']     = $racketmanager->site_name;
        $template_args['season']           = $club_entry->season;
        $template_args['competition_name'] = $club_entry->competition->name;
        $template_args['club']             = $this->name;
        $template_args['contact_email']    = $email_from;
        $template_args['comments']         = $club_entry->comments;
        $racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
    }

    /**
     * Get dummy player details
     *
     * @return array
     */
    public function get_dummy_players(): array {
        global $racketmanager;
        $player_options                = $racketmanager->get_options( 'player' );
        $players['walkover']['male']   = $this->get_player( $player_options['walkover']['male'] );
        $players['walkover']['female'] = $this->get_player( $player_options['walkover']['female'] );
        $players['noplayer']['male']   = $this->get_player( $player_options['noplayer']['male'] );
        $players['noplayer']['female'] = $this->get_player( $player_options['noplayer']['female'] );
        $players['share']['male']      = $this->get_player( $player_options['share']['male'] );
        $players['share']['female']    = $this->get_player( $player_options['share']['female'] );
        return $players;
    }
}
