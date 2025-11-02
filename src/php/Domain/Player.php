<?php
/**
 * Player API: player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Player
 */

namespace Racketmanager\Domain;

use Racketmanager\util\Util;
use Racketmanager\util\Util_Lookup;
use stdClass;
use WP_User;
use function Racketmanager\get_club;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_tournament;
use function Racketmanager\seo_url;

/**
 * Class to implement the Player object
 */
final class Player {
    /**
     * Id.
     *
     * @var int
     */
    public int $ID;
    /**
     * ID.
     *
     * @var int
     */
    public int $id;
    /**
     * Club player id.
     *
     * @var int
     */
    public int $club_player_id;
    /**
     * Email address.
     *
     * @var string
     */
    public string $email;
    /**
     * User Email address.
     *
     * @var string
     */
    public string $user_email;
    /**
     * Fullname - join of first name and surname.
     *
     * @var string
     */
    public string $fullname;
    /**
     * Display name.
     *
     * @var string
     */
    public string $display_name;
    /**
     * Name.
     *
     * @var string
     */
    public string $name;
    /**
     * Date player created.
     *
     * @var string
     */
    public string $created_date;
    /**
     * Email address.
     *
     * @var string
     */
    public string $user_registered;
    /**
     * First name.
     *
     * @var string
     */
    public mixed $firstname;
    /**
     * Surname.
     *
     * @var string
     */
    public mixed $surname;
    /**
     * Gender.
     *
     * @var string
     */
    public mixed $gender;
    /**
     * Type.
     *
     * @var string
     */
    public mixed $type;
    /**
     * LTA Membership Number.
     *
     * @var int
     */
    public mixed $btm;
    /**
     * Year of birth.
     *
     * @var int|null
     */
    public mixed $year_of_birth;
    /**
     * Age.
     *
     * @var int|null
     */
    public ?int $age;
    /**
     * Contact Number.
     *
     * @var string
     */
    public mixed $contactno;
    /**
     * Removed date.
     *
     * @var string|null
     */
    public ?string $removed_date;
    /**
     * Removed user.
     *
     * @var string|null
     */
    public ?string $removed_user;
    /**
     * Created user.
     *
     * @var int|null
     */
    public ?int $created_user;
    /**
     * Created username.
     *
     * @var string|null
     */
    public ?string $created_user_name;
    /**
     * Locked indicator.
     *
     * @var boolean
     */
    public mixed $locked;
    /**
     * Locked date.
     *
     * @var string|null
     */
    public ?string $locked_date;
    /**
     * Locked user.
     *
     * @var string|null
     */
    public ?string $locked_user;
    /**
     * Locked username.
     *
     * @var string|null
     */
    public ?string $locked_user_name;
    /**
     * System record.
     *
     * @var string
     */
    public mixed $system_record;
    /**
     * Matches.
     *
     * @var array
     */
    public array $matches = array();
    /**
     * Statistics.
     *
     * @var array
     */
    public array $statistics = array();
    /**
     * Clubs.
     *
     * @var array
     */
    public array $clubs = array();
    /**
     * Clubs archive.
     *
     * @var array
     */
    public array $clubs_archive = array();
    /**
     * Titles.
     *
     * @var array
     */
    public array $titles = array();
    /**
     * Url link.
     *
     * @var string
     */
    public string $link;
    /**
     * Rating.
     *
     * @var array
     */
    public array $rating;
    /**
     * Opt in detail.
     *
     * @var array
     */
    public mixed $opt_ins;
    /**
     * WTN.
     *
     * @var array
     */
    public array $wtn;
    /**
     * User password.
     *
     * @var string
     */
    public string $user_pass;
    /**
     * User nicename.
     *
     * @var string
     */
    public string $user_nicename;
    /**
     * User url.
     *
     * @var string
     */
    public string $user_url;
    /**
     * User activation_key.
     *
     * @var string
     */
    public string $user_activation_key;
    /**
     * User login.
     *
     * @var string
     */
    public string $user_login;
    /**
     * Index.
     *
     * @var string
     */
    public string $index;
    /**
     * Stats
     *
     * @var array
     */
    public array $stats;
    /**
     * Class
     *
     * @var string|null
     */
    public ?string $class;
    /**
     * Matches won variable
     *
     * @var int
     */
    public int $matches_won;
    /**
     * Matches lost variable
     *
     * @var int
     */
    public int $matches_lost;
    /**
     * Win pct variable
     *
     * @var float|null
     */
    public ?float $win_pct;
    /**
     * Played variable
     *
     * @var int
     */
    public int $played;
    /**
     * Error description variable
     *
     * @var string|null
     */
    public ?string $description;
    /**
     * Competitions variable
     *
     * @var array
     */
    public array $competitions;
    /**
     * Cup variable
     *
     * @var array
     */
    public array $cup;
    /**
     * League variable
     *
     * @var array
     */
    public array $league;
    /**
     * Tournament variable
     *
     * @var array
     */
    public array $tournament;
    /**
     * Teams variable
     *
     * @var array
     */
    public array $teams;
    /**
     * Club variable
     *
     * @var object|null
     */
    public ?object $club;
    /**
     * Team
     *
     * @var object
     */
    public object $team;
    /**
     * Status
     *
     * @var string
     */
    public string $status;
    /**
     * User status
     *
     * @var string
     */
    public string $user_status;
    /**
     * Entry id
     *
     * @var int
     */
    public int $entry_id;
    /**
     * Entry
     *
     * @var array
     */
    public array $entry;
    /**
     * Tournament Entry
     *
     * @var object|null
     */
    public ?object $tournament_entry;
    /**
     * Retrieve player instance
     *
     * @param int|string $player_id player id.
     * @param string $search_type type of id to search for.
     */
    public static function get_instance( int|string $player_id, string $search_type ) {
        if ( ! $player_id ) {
            return false;
        }
        $player = wp_cache_get( $player_id, 'players' );

        if ( ! $player ) {
            $player = match ( $search_type ) {
                'btm'   => self::get_player_by_btm( $player_id ),
                'email' => get_user_by( 'email', $player_id ),
                'login' => self::get_player_by_login( $player_id ),
                'name'  => self::get_player_by_name( $player_id ),
                default => get_userdata( $player_id ),
            };
            if ( ! $player ) {
                return false;
            }
            $player = new Player( $player->data );
            wp_cache_set( $player_id, $player, 'players' );
        }

        return $player;
    }
    /**
     * Get player by LTA tennis number
     *
     * @param $player_id
     *
     * @return false|mixed
     */
    private static function get_player_by_btm( $player_id ): mixed {
        $players = get_users(
            array(
                'meta_key'     => 'btm', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value'   => $player_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'meta_compare' => '=',
            )
        );
        if ( $players ) {
            return $players[0];
        } else {
            return false;
        }
    }
    /**
     * Get player by login
     *
     * @param $player_id
     *
     * @return false|WP_User
     */
    private static function get_player_by_login( $player_id ): false|WP_User {
        // format of login is first.surname( can contain spaces ).
        if ( ! str_contains( $player_id, '.' ) ) {
            $pos = strpos( $player_id, ' ' );
            if ( false !== $pos ) {
                $player_id = substr_replace( $player_id, '.', $pos, strlen( ' ' ) );
            }
        }
        return get_user_by( 'login', strtolower( $player_id ) );
    }
    /**
     * Get player by name
     *
     * @param $player_id
     *
     * @return false|WP_User
     */
    private static function get_player_by_name( $player_id ): false|WP_User {
        // format of nicename is first-surname( where surname spaces are converted to - ).
        if ( str_contains( $player_id, ' ' ) ) {
            $player_id = str_replace( ' ', '-', $player_id );
        }
        return get_user_by( 'slug', strtolower( $player_id ) );
    }
    /**
     * Constructor
     *
     * @param object|null $player Player object.
     */
    public function __construct( ?object $player = null ) {
        if ( ! is_null( $player ) ) {
            foreach ( $player as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! isset( $this->ID ) ) {
                $this->ID = $this->add();
            }
            $this->id            = $this->ID;
            $this->email         = $this->user_email;
            $this->fullname      = $this->display_name;
            $this->created_date  = $this->user_registered;
            $this->firstname     = get_user_meta( $this->ID, 'first_name', true );
            $this->surname       = get_user_meta( $this->ID, 'last_name', true );
            $this->gender        = get_user_meta( $this->ID, 'gender', true );
            $this->type          = get_user_meta( $this->ID, 'racketmanager_type', true );
            $this->btm           = get_user_meta( $this->ID, 'btm', true );
            $this->year_of_birth = get_user_meta( $this->ID, 'year_of_birth', true );
            $this->calculate_age();
            $this->contactno    = get_user_meta( $this->ID, 'contactno', true );
            $this->removed_date = get_user_meta( $this->ID, 'remove_date', true );
            $this->removed_user = get_user_meta( $this->ID, 'remove_user', true );
            $this->locked       = get_user_meta( $this->ID, 'locked', true );
            $this->locked_date  = get_user_meta( $this->ID, 'locked_date', true );
            $this->locked_user  = get_user_meta( $this->ID, 'locked_user', true );
            if ( $this->locked_user ) {
                $this->locked_user_name = get_userdata( $this->locked_user )->display_name;
            } else {
                $this->locked_user_name = '';
            }
            $this->system_record = get_user_meta( $this->ID, 'leaguemanager_type', true );
            $this->link          = '/player/' . seo_url( $this->display_name ) . '/';
            if ( ! empty( $this->btm ) ) {
                $this->link .= $this->btm . '/';
            }
            $match_types = Util_Lookup::get_match_types();
            foreach ( $match_types as $match_type => $description ) {
                $wtn_type                 = 'wtn_' . $match_type;
                $this->wtn[ $match_type ] = get_user_meta( $this->ID, $wtn_type, true );
            }
            $this->opt_ins = get_user_meta( $this->ID, 'racketmanager_opt_in' );
        }
    }
    /**
     * Add player
     *
     * @return int $user_id id of inserted record.
     */
    private function add(): int {
        $this->display_name          = $this->firstname . ' ' . $this->surname;
        $this->user_email            = $this->email;
        $this->user_registered       = gmdate( 'Y-m-d H:i:s' );
        $userdata                    = array();
        $userdata['first_name']      = $this->firstname;
        $userdata['last_name']       = $this->surname;
        $userdata['display_name']    = $this->display_name;
        $userdata['user_login']      = strtolower( $this->firstname ) . '.' . strtolower( $this->surname );
        $userdata['user_pass']       = $userdata['user_login'] . '1';
        $userdata['user_registered'] = $this->user_registered;
        if ( $this->email ) {
            $userdata['user_email'] = $this->email;
        }
        $user_id = wp_insert_user( $userdata );
        if ( ! is_wp_error( $user_id ) ) {
            update_user_meta( $user_id, 'show_admin_bar_front', false );
            update_user_meta( $user_id, 'gender', $this->gender );
            if ( isset( $this->btm ) && $this->btm > '' ) {
                update_user_meta( $user_id, 'btm', $this->btm );
            }
            if ( isset( $this->contactno ) && $this->contactno > '' ) {
                update_user_meta( $user_id, 'contactno', $this->contactno );
            }
            if ( isset( $this->year_of_birth ) && $this->year_of_birth > '' ) {
                update_user_meta( $user_id, 'year_of_birth', $this->year_of_birth );
            }
        }
        return $user_id;
    }
    /**
     * Calculate player age function
     *
     * @return void
     */
    private function calculate_age(): void {
        if ( $this->year_of_birth ) {
            $this->age = gmdate( 'Y' ) - intval( $this->year_of_birth );
        } else {
            $this->age = 0;
        }
    }
    /**
     * Update player
     *
     * @param object $player player object with updated data.
     */
    public function update( object $player ): object {
        $return    = new stdClass();
        $update    = false;
        $user_data = $this->set_user_data( $player );
        if ( ! empty( $user_data ) ) {
            $update = true;
        }
        if ( $this->gender !== $player->gender ) {
            $update = true;
            update_user_meta( $this->ID, 'gender', $player->gender );
            $this->gender = $player->gender;
        }
        $btm_update = $this->update_btm( $player->btm );
        if ( $btm_update ) {
            $update = true;
        }
        $year_of_birth_update = $this->update_year_of_birth( $player->year_of_birth );
        if ( $year_of_birth_update ) {
            $update = true;
        }
        if ( $this->contactno !== $player->contactno ) {
            $update = true;
            update_user_meta( $this->ID, 'contactno', $player->contactno );
            $this->contactno = $player->contactno;
        }
        if ( $this->locked !== $player->locked ) {
            $this->update_locked( $player->locked );
            $update = true;
        }

        if ( $update ) {
            wp_cache_set( $this->id, $this, 'players' );
            if ( $user_data ) {
                $user_data['ID'] = $this->ID;
                $user_id         = wp_update_user( $user_data );
                if ( is_wp_error( $user_id ) ) {
                    $return->msg   = $user_id->get_error_message();
                    $return->state = 'danger';
                } else {
                    $return->msg   = __( 'Player details updated', 'racketmanager' );
                    $return->state = 'success';
                }
            } else {
                $return->msg   = __( 'Player details updated', 'racketmanager' );
                $return->state = 'success';
            }
        } else {
            $return->msg = __( 'No updates', 'racketmanager' );
            $return->state = 'warning';
        }
        return $return;
    }

    /**
     * Set main user data to be updated
     *
     * @param object $player
     *
     * @return array
     */
    private function set_user_data( object $player ): array {
        $user_data            = array();
        $player->display_name = $player->firstname . ' ' . $player->surname;
        if ( $this->firstname !== $player->firstname ) {
            $user_data['first_name'] = $player->firstname;
            $this->firstname         = $player->firstname;
        }
        if ( $this->surname !== $player->surname ) {
            $user_data['last_name'] = $player->surname;
            $this->surname          = $player->surname;
        }
        if ( $this->display_name !== $player->display_name ) {
            $user_data['display_name']  = $player->display_name;
            $user_data['user_nicename'] = sanitize_title( $user_data['display_name'] );
            $this->display_name         = $player->display_name;
        }
        if ( $this->user_email !== $player->email ) {
            $user_data['user_email'] = $player->email;
            $this->user_email        = $player->email;
            $this->email             = $this->user_email;
        }
        return $user_data;
    }

    /**
     * Update locked
     *
     * @param object $player
     *
     * @return void
     */
    private function update_locked( object $player ): void {
        if ( $player->locked ) {
            update_user_meta( $this->ID, 'locked', $player->locked );
            update_user_meta( $this->ID, 'locked_date', gmdate( 'Y-m-d' ) );
            update_user_meta( $this->ID, 'locked_user', get_current_user_id() );
        } else {
            delete_user_meta( $this->ID, 'locked' );
            delete_user_meta( $this->ID, 'locked_date' );
            delete_user_meta( $this->ID, 'locked_user' );
        }
        $this->locked = $player->locked;
    }
    /**
     * Update player contact details
     *
     * @param string $contact_no telephone number.
     * @param string $contact_email email address.
     * @return boolean
     */
    public function update_contact( string $contact_no, string $contact_email ): bool {
        $current_contact_no    = get_user_meta( $this->ID, 'contactno', true );
        $current_contact_email = $this->user_email;
        if ( $current_contact_no !== $contact_no ) {
            update_user_meta( $this->ID, 'contactno', $contact_no );
            $this->contactno = $contact_no;
        }
        if ( $current_contact_email !== $contact_email ) {
            $userdata               = array();
            $userdata['ID']         = $this->ID;
            $userdata['user_email'] = $contact_email;
            $user_id                = wp_update_user( $userdata );
            if ( is_wp_error( $user_id ) ) {
                $error_msg = $user_id->get_error_message();
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'Unable to update user email ' . $this->ID . ' - ' . $contact_email . ' - ' . $error_msg );
                return false;
            }
            $this->user_email = $contact_email;
            $this->email      = $this->user_email;
        }
        wp_cache_set( $this->id, $this, 'players' );
        return true;
    }
    /**
     * Update player btm
     *
     * @param int $btm LTA tennis number.
     * @return boolean
     */
    public function update_btm( int $btm ): bool {
        if ( intval( $this->btm ) !== $btm ) {
            if ( empty( $this->btm ) ) {
                $this->check_results_warning( 'btm' );
            }
            update_user_meta( $this->ID, 'btm', $btm );
            $this->btm = $btm;
            wp_cache_set( $this->id, $this, 'players' );
            return true;
        } else {
            return false;
        }
    }
    /**
     * Update player year of birth
     *
     * @param int|null $year_of_birth year of birth.
     * @return boolean
     */
    public function update_year_of_birth( ?int $year_of_birth ): bool {
        if ( empty( $this->year_of_birth ) && empty( $year_of_birth ) ) {
            return false;
        } elseif ( intval( $this->year_of_birth ) !== $year_of_birth ) {
            if ( empty( $this->year_of_birth ) ) {
                $this->check_results_warning( 'dob' );
            }
            update_user_meta( $this->ID, 'year_of_birth', $year_of_birth );
            $this->year_of_birth = $year_of_birth;
            wp_cache_set( $this->id, $this, 'players' );
            $this->calculate_age();
            return true;
        } else {
            return false;
        }
    }
    /**
     * Handle outstanding results warnings function
     *
     * @param string $type type of check.
     */
    private function check_results_warning( string $type ): void {
        global $racketmanager;
        $args = array();
        switch ( $type ) {
            case 'btm':
                $description = __( 'LTA tennis number missing', 'racketmanager' );
                break;
            case 'dob':
                $description = __( 'no age provided', 'racketmanager' );
                break;
            default:
                return;
        }
        $args['count']     = true;
        $args['status']    = 'outstanding';
        $args['player']    = $this->id;
        $args['type']      = $description;
        $args['confirmed'] = 'Y';
        $count             = $racketmanager->get_result_warnings( $args );
        if ( $count ) {
            $args['count']    = false;
            $results_warnings = $racketmanager->get_result_warnings( $args );
            foreach ( $results_warnings as $result_warning ) {
                $result_warning->delete();
            }
        }
    }
    /**
     * Delete player
     */
    public function delete(): void {
        global $wpdb;

        $club_player = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT count(*) FROM $wpdb->racketmanager_club_players WHERE `player_id` = %d",
                $this->id
            )
        );
        if ( ! $club_player ) {
            wp_delete_user( $this->id );
        } else {
            update_user_meta( $this->id, 'remove_date', gmdate( 'Y-m-d' ) );
        }
        wp_cache_flush_group( 'players' );
    }

    /**
     * Get clubs for player
     *
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_clubs( array|string $args = array() ): array {
        global $wpdb;
        $defaults     = array(
            'type'  => 'active',
        );
        $args         = array_merge( $defaults, (array) $args );
        $type         = $args['type'];
        $search_args  = array();
        $search_terms = array();
        if ( $type ) {
            switch ( $type ) {
                case 'active':
                    $search_terms[] = '`removed_date` is null';
                    break;
                case 'inactive':
                    $search_terms[] = '`removed_date` is not null';
                    break;
                case 'all':
                default:
                    break;
            }
        }
        $search_args[] = $this->id;
        $search        = Util::search_string( $search_terms );
        $sql           = "SELECT `club_id`, `created_date`, `removed_date`, cp.`id` as `club_player_id` FROM $wpdb->racketmanager_club_players cp, $wpdb->racketmanager_clubs c WHERE cp.`club_id` = c.`id` AND `player_id` = %d";
        if ( '' !== $search ) {
            $sql .= " $search";
        }
        $sql = $wpdb->prepare($sql, $search_args );

        $player_clubs = wp_cache_get( md5( $sql ), 'player_clubs' );
        if ( ! $player_clubs ) {
            $player_clubs = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $player_clubs, 'player_clubs' );
        }
        $clubs        = array();
        foreach ( $player_clubs as $player_club ) {
            $club = get_club( $player_club->club_id );
            if ( $club ) {
                $club->created_date   = $player_club->created_date;
                $club->removed_date   = $player_club->removed_date;
                $club->club_player_id = $player_club->club_player_id;
                $clubs[]              = $club;
            }
        }
        return $clubs;
    }
    /**
     * Get matches for player
     *
     * @param object|null $grouping source of matches.
     * @param string|null $season season for matches.
     * @param string $match_source source of matches - either 'league' / 'event' / 'competition'.
     * @param int|null $period optional time period for matches.
     * @param string|null $type match type.
     * @return array of matches.
     */
    public function get_matches( ?object $grouping, ?string $season, string $match_source, ?int $period = null, ?string $type = null ): array {
        global $racketmanager;
        $statistics = array();
        if ( 'league' === $match_source ) {
            $league  = get_league( $grouping );
            $matches = $league->get_matches(
                array(
                    'season'    => $season,
                    'player'    => $this->id,
                    'match_day' => false,
                    'final'     => 'all',
                    'orderby'   => array(
                        'date' => 'ASC',
                    ),
                )
            );
        } elseif ( 'event' === $match_source ) {
            $event   = get_event( $grouping );
            $matches = $event->get_matches(
                array(
                    'season'  => $season,
                    'player'  => $this->id,
                    'orderby' => array(
                        'date'      => 'ASC',
                        'league_id' => 'DESC',
                    ),
                )
            );
        } elseif ( 'competition' === $match_source ) {
            $competition = get_competition( $grouping );
            $matches     = $competition->get_matches(
                array(
                    'season'  => $season,
                    'player'  => $this->id,
                    'orderby' => array(
                        'date'      => 'ASC',
                        'league_id' => 'DESC',
                    ),
                )
            );
        } elseif ( 'all' === $match_source ) {
            $matches = $racketmanager->get_matches(
                array(
                    'season'  => $season,
                    'player'  => $this->id,
                    'time'    => $period,
                    'type'    => $type,
                    'status'  => 'Y',
                    'orderby' => array(
                        'date' => 'ASC',
                    ),
                )
            );
        } else {
            $matches = array();
        }
        $opponents_pt = array( 'player1', 'player2' );
        $opponents    = array( 'home', 'away' );
        $stats_ref    = $statistics;
        $league_ref   = 0;
        foreach ( $matches as $match ) {
            if ( 'all' === $match_source ) {
                if ( $match->league->is_championship ) {
                    $league_no = 1;
                } else {
                    $league_title = explode( ' ', $match->league->title );
                    $league_no    = end( $league_title );
                }
                $age_limit    = $match->league->event->age_limit ?? 'open';
                $rubber_order = empty( $match->league->event->reverse_rubbers ) ? 'true' : 'false';
                $league_ref   = $league_no . '-' . $match->league->num_rubbers . '-' . $age_limit . '-' . $rubber_order;
            } else {
                $league_ref = 0;
            }
            if ( 'competition' === $match_source || 'event' === $match_source ) {
                $key = $match->league->title;
                if ( false === array_key_exists( $key, $this->matches ) ) {
                    $this->matches[ $key ]                   = array();
                    $this->matches[ $key ]['league']         = $match->league;
                    $this->matches[ $key ]['league']->season = $match->season;
                }
                $this->matches[ $key ]['matches'][] = $match;
            } else {
                $this->matches[] = $match;
            }
            foreach ( $match->rubbers as $rubber ) {
                $player_team = null;
                $player_ref  = null;
                $winner       = null;
                $loser        = null;
                if ( ! empty( $rubber->winner_id ) ) {
                    if ( $rubber->winner_id === $match->home_team ) {
                        $winner = 'home';
                        $loser  = 'away';
                    } elseif ( $rubber->winner_id === $match->away_team ) {
                        $winner = 'away';
                        $loser  = 'home';
                    }
                }
                $match_type          = strtolower( substr( $rubber->type, 1, 1 ) );
                $rubber_players['1'] = array();
                if ( 'd' === $match_type ) {
                    $rubber_players['2'] = array();
                }
                foreach ( $opponents as $opponent ) {
                    foreach ( $rubber_players as $p => $rubber_player ) {
                        if ( $rubber->players[ $opponent ][ $p ]->fullname === $this->display_name ) {
                            $player_team = $opponent;
                            if ( 'home' === $player_team ) {
                                $player_ref = 'player1';
                            } else {
                                $player_ref = 'player2';
                            }
                            break 2;
                        }
                    }
                }
                if ( $winner === $player_team ) {
                    $player_team_status = 'winner';
                } elseif ( $loser === $player_team ) {
                    $player_team_status = 'loser';
                } else {
                    $player_team_status = 'draw';
                }
                if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['played'][ $player_team_status ] ) ) {
                    $stats_ref[ $league_ref ][ $rubber->title ]['played'][ $player_team_status ] = 0;
                }
                ++$stats_ref[ $league_ref ][ $rubber->title ]['played'][ $player_team_status ];
                $sets = ! empty( $rubber->custom['sets'] ) ? $rubber->custom['sets'] : array();
                foreach ( $sets as $set ) {
                    if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
                        if ( $set['player1'] > $set['player2'] ) {
                            if ( 'player1' === $player_ref ) {
                                $stat_ref = 'winner';
                            } else {
                                $stat_ref = 'loser';
                            }
                        } elseif ( 'player1' === $player_ref ) {
                            $stat_ref = 'loser';
                        } else {
                            $stat_ref = 'winner';
                        }
                        if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['sets'][ $stat_ref ] ) ) {
                            $stats_ref[ $league_ref ][ $rubber->title ]['sets'][ $stat_ref ] = 0;
                        }
                        ++$stats_ref[ $league_ref ][ $rubber->title ]['sets'][ $stat_ref ];
                        foreach ( $opponents_pt as $opponent ) {
                            if ( is_numeric( $set[ $opponent ] ) ) {
                                if ( $player_ref === $opponent ) {
                                    if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['games']['winner'] ) ) {
                                        $stats_ref[ $league_ref ][ $rubber->title ]['games']['winner'] = 0;
                                    }
                                    $stats_ref[ $league_ref ][ $rubber->title ]['games']['winner'] += $set[ $opponent ];
                                } else {
                                    if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['games']['loser'] ) ) {
                                        $stats_ref[ $league_ref ][ $rubber->title ]['games']['loser'] = 0;
                                    }
                                    $stats_ref[ $league_ref ][ $rubber->title ]['games']['loser'] += $set[ $opponent ];
                                }
                            }
                        }
                    }
                }
            }
        }
        if ( $league_ref ) {
            $this->statistics = $stats_ref;
        } else {
            $this->statistics = $stats_ref[0] ?? array();
        }
        return $this->matches;
    }
    /**
     * Get player statistics function
     *
     * @param false|array $stats optional array of statistics to use.
     * @return array of statistics
     */
    public function get_stats(false|array $stats = false ): array {
        if ( ! $stats ) {
            $stats = $this->statistics;
        }
        ksort( $stats );
        $this->statistics           = array();
        $this->statistics['detail'] = $stats;
        $total_stats                = array();
        $total_stats_sets           = array();
        $total_stats_games          = array();
        $stat_types                 = array( 'winner', 'loser', 'draw' );
        foreach ( $stat_types as $stat_type ) {
            $total_stats[ $stat_type ]       = 0;
            $total_stats_sets[ $stat_type ]  = 0;
            $total_stats_games[ $stat_type ] = 0;
        }
        $total_stats_walkover = 0;
        foreach ( $stats as $statistics ) {
            if ( ! empty( $statistics['played'] ) ) {
                foreach ( $stat_types as $stat_type ) {
                    if ( ! empty( $statistics['played'][ $stat_type ] ) ) {
                        $total_stats[ $stat_type ] += $statistics['played'][ $stat_type ];
                    }
                }
            }
            if ( ! empty( $statistics['sets'] ) ) {
                foreach ( $stat_types as $stat_type ) {
                    if ( ! empty( $statistics['sets'][ $stat_type ] ) ) {
                        $total_stats_sets[ $stat_type ] += $statistics['sets'][ $stat_type ];
                    }
                }
            }
            if ( ! empty( $statistics['games'] ) ) {
                foreach ( $stat_types as $stat_type ) {
                    if ( ! empty( $statistics['games'][ $stat_type ] ) ) {
                        $total_stats_games[ $stat_type ] += $statistics['games'][ $stat_type ];
                    }
                }
            }
            if ( ! empty( $statistics['walkover'] ) ) {
                $total_stats_walkover += $statistics['walkover'];
            }
        }
        $this->statistics['total']               = new stdClass();
        $this->statistics['total']->matches_won  = $total_stats['winner'];
        $this->statistics['total']->matches_lost = $total_stats['loser'];
        $this->statistics['total']->matches_tie  = $total_stats['draw'];
        $this->statistics['total']->played       = $this->statistics['total']->matches_won + $this->statistics['total']->matches_lost + $this->statistics['total']->matches_tie;
        if ( $this->statistics['total']->played ) {
            $this->statistics['total']->win_pct = ceil( ( $this->statistics['total']->matches_won / $this->statistics['total']->played ) * 100 );
        }
        $this->statistics['total']->sets_won   = $total_stats_sets['winner'];
        $this->statistics['total']->sets_lost  = $total_stats_sets['loser'];
        $this->statistics['total']->games_won  = $total_stats_games['winner'];
        $this->statistics['total']->games_lost = $total_stats_games['loser'];
        $this->statistics['total']->walkover   = empty( $total_stats_walkover ) ? '' : $total_stats_walkover;
        return $this->statistics;
    }
    /**
     * Get competitions for player function
     *
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_competitions( array|string $args = array() ): array {
        global $wpdb;
        $defaults     = array(
            'type'   => false,
            'season' => false,
        );
        $args         = array_merge( $defaults, (array) $args );
        $type         = $args['type'];
        $season       = $args['season'];
        $search_terms = array();
        if ( $type ) {
            $search_terms[] = $wpdb->prepare( 'c.`type` = %s', $type );
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $sql          = "SELECT c.id, c.name, m.season FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE `player_id` = $this->ID AND rp.rubber_id = r.id AND r.match_id = m.id AND m.league_id = l.id AND l.event_id = e.id AND e.competition_id = c.id";
        $sql         .= Util::search_string( $search_terms );
        $sql         .= " GROUP BY c.id, c.name, m.season";
        $sql         .= " ORDER BY m.season DESC, c.name ASC";
        $competitions = wp_cache_get( md5( $sql ), 'player_competitions' );
        if ( ! $competitions ) {
            $competitions = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $competitions, 'player_competitions' );
        }
        $i = 0;
        foreach ( $competitions as $competition ) {
            $competition_dtl = get_competition( $competition->id );
            if ( $competition_dtl ) {
                $competition_dtl->season = $competition->season;
                if ( isset( $competition_dtl->seasons[ $competition->season ] ) ) {
                    $competition_season          = $competition_dtl->seasons[ $competition->season ];
                    $competition_dtl->date_start = $competition_season['date_start'] ?? null;
                    $competition_dtl->date_end   = $competition_season['date_end'] ?? null;
                    $competitions[ $i ]          = $competition_dtl;
                } else {
                    unset( $competitions [ $i ] );
                }
            }
            ++$i;
        }
        return $competitions;
    }
    /**
     * Get tournaments for player function
     *
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_tournaments( array|string $args = array() ): array {
        global $wpdb;
        $defaults     = array(
            'type'   => false,
            'season' => false,
        );
        $args         = array_merge( $defaults, (array) $args );
        $type         = $args['type'];
        $season       = $args['season'];
        $search_terms = array();
        if ( $type ) {
            $search_terms[] = $wpdb->prepare( 'c.`type` = %s', $type );
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 't.`season` = %s', $season );
        }
        $sql         = "SELECT t3.id FROM $wpdb->racketmanager_team_players tp, $wpdb->racketmanager_table t, $wpdb->racketmanager l, $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c, $wpdb->racketmanager_tournaments t3 WHERE tp.`player_id` = $this->ID AND tp.`team_id` = t.`team_id` AND t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.competition_id = c.`id` AND t3.`competition_id` = c.`id` AND t3.`season` = t.`season`";
        $sql        .= Util::search_string( $search_terms );
        $sql        .= " GROUP BY t3.`id`";
        $sql        .= " ORDER BY t3.`season` DESC, t3.`name` ASC";
        $tournaments = wp_cache_get( md5( $sql ), 'player_tournaments' );
        if ( ! $tournaments ) {
            $tournaments = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $tournaments, 'player_tournaments' );
        }
        $i = 0;
        foreach ( $tournaments as $tournament ) {
            $tournament_dtl = get_tournament( $tournament->id );
            if ( $tournament_dtl ) {
                $tournament_dtl->type     = $type;
                $tournament_dtl->date_end = $tournament_dtl->date;
                $tournaments[ $i ]        = $tournament_dtl;
            }
            ++$i;
        }
        return $tournaments;
    }
    /**
     * Get titles for player function
     *
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_titles( array|string $args = array() ): array {
        global $wpdb;
        $defaults     = array(
            'season' => false,
        );
        $args         = array_merge( $defaults, (array) $args );
        $season       = $args['season'];
        $search_terms = array();
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $sql     = "SELECT m.`season`, t.`name` as `tournament`, e.`name` as `draw`, l.`title` as `title`, tp.`team_id`, m.`winner_id`, m.`loser_id` FROM $wpdb->racketmanager_team_players tp, $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c, $wpdb->racketmanager_tournaments t WHERE tp.`player_id` = $this->ID AND (tp.`team_id` = m.`winner_id` OR tp.`team_id` = m.`loser_id`) AND m.`final` = 'final' AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.competition_id = c.`id` AND t.`competition_id` = c.`id` AND t.`season` = m.`season`";
        $sql    .= Util::search_string( $search_terms );
        $sql    .= " ORDER BY m.`season` DESC, t.`name` ASC";
        $matches = wp_cache_get( md5( $sql ), 'player_finals' );
        if ( ! $matches ) {
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $matches, 'player_finals' );
        }
        $seasons = array();
        foreach ( $matches as $match ) {
            $season     = $match->season;
            $tournament = $match->tournament;
            if ( false === array_key_exists( $season, $seasons ) ) {
                $seasons[ $season ] = array();
            }
            if ( false === array_key_exists( $tournament, $seasons[ $season ] ) ) {
                $seasons[ $season ][ $tournament ] = array();
            }
            $seasons[ $season ][ $tournament ][] = $match;
        }
        $this->titles = $seasons;
        return $this->titles;
    }
    /**
     * Get player career statistics function
     *
     * @return array of statistics
     */
    public function get_career_stats(): array {
        $result         = null;
        $types          = array( 'S', 'D', 'X' );
        $stat_types     = array( 'win', 'loss', 'tie' );
        $team_matches   = $this->get_stats_teams();
        $player_matches = $this->get_stats_players();
        $matches        = array_merge( $team_matches, $player_matches );
        $seasons        = array();
        foreach ( $matches as $match ) {
            if ( 'teams' === $match->stat_type ) {
                if ( $match->winner_id ) {
                    if ( 'home' === $match->player_team ) {
                        if ( $match->winner_id === $match->home_team ) {
                            $result = 'win';
                        } elseif ( $match->winner_id === $match->away_team ) {
                            $result = 'loss';
                        } else {
                            $result = 'tie';
                        }
                    } elseif ( 'away' === $match->player_team ) {
                        if ( $match->winner_id === $match->away_team ) {
                            $result = 'win';
                        } elseif ( $match->winner_id === $match->home_team ) {
                            $result = 'loss';
                        } else {
                            $result = 'tie';
                        }
                    }
                }
            } elseif ( 'players' === $match->stat_type ) {
                if ( $match->winner_id ) {
                    if ( $match->winner_id === $match->team_id ) {
                        $result = 'win';
                    } elseif ( $match->loser_id === $match->team_id ) {
                        $result = 'loss';
                    } else {
                        $result = 'tie';
                    }
                }
            }
            if ( 'XD' === $match->type || 'LD' === $match->type ) {
                $type = 'X';
            } else {
                $type = substr( $match->type, 1, 1 );
            }
            $season = $match->season;
            if ( false === array_key_exists( $season, $seasons ) ) {
                $seasons[ $season ] = array();
                foreach ( $types as $match_type ) {
                    $seasons [ $season ][ $match_type ] = array();
                    foreach ( $stat_types as $stat_type ) {
                        $seasons [ $season ][ $match_type ][ $stat_type ] = 0;
                    }
                }
            }
            if ( false === array_key_exists( $type, $seasons[ $season ] ) ) {
                $seasons[ $season ][ $type ] = array();
            }
            if ( false === array_key_exists( $result, $seasons[ $season ][ $type ] ) ) {
                $seasons[ $season ][ $type ][ $result ] = 0;
            }
            ++$seasons[ $season ][ $type ][ $result ];
        }
        krsort( $seasons );
        $totals = array();
        foreach ( $stat_types as $stat_type ) {
            $totals['total'][ $stat_type ] = 0;
        }
        foreach ( $seasons as $season => $types ) {
            unset( $seasons[ $season ] );
            $seasons[ $season ]['breakdown'] = $types;
            $seasons[ $season ]['total']     = array();
            foreach ( $stat_types as $stat_type ) {
                $seasons[ $season ]['total'][ $stat_type ] = 0;
            }
            foreach ( $types as $type => $results ) {
                foreach ( $results as $result => $count ) {
                    if ( ! isset( $totals['breakdown'][ $type ][ $result ] ) ) {
                        $totals['breakdown'][ $type ][ $result ] = 0;
                    }
                    $totals['breakdown'][ $type ][ $result ] += $count;
                    $totals['total'][ $result ]              += $count;
                    $seasons[ $season ]['total'][ $result ]  += $count;
                }
            }
        }
        $statistics            = array();
        $statistics['totals']  = $totals;
        $statistics['seasons'] = $seasons;
        return $statistics;
    }
    /**
     * Get stats for teams for player function
     *
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_stats_teams( array|string $args = array() ): array {
        global $wpdb;
        $defaults     = array(
            'season' => false,
        );
        $args         = array_merge( $defaults, (array) $args );
        $season       = $args['season'];
        $search_terms = array();
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $search  = Util::search_string( $search_terms );
        $sql     = "SELECT 'teams' as `stat_type`, m.`season`, e.`type` ,rp.`player_team`, m.`home_team`, m.`away_team`, r.`winner_id`, r.`loser_id` FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE rp.`player_id` = $this->ID AND rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` ";
        $sql    .= $search . " ORDER BY m.`season`, e.`type`";
        $matches = wp_cache_get( md5( $sql ), 'player_stats_teams' );
        if ( ! $matches ) {
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $matches, 'player_stats_teams' );
        }
        return $matches;
    }
    /**
     * Get stats for players for player function
     *
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_stats_players( array|string $args = array() ): array {
        global $wpdb;
        $defaults     = array(
            'season' => false,
        );
        $args         = array_merge( $defaults, (array) $args );
        $season       = $args['season'];
        $search_terms = array();
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $search  = Util::search_string( $search_terms );
        $sql     = "SELECT 'players' as `stat_type`,m.`season`, e.`type` ,tp.`team_id`, m.`home_team`, m.`away_team`, m.`winner_id`, m.`loser_id` FROM $wpdb->racketmanager_team_players tp, $wpdb->racketmanager_teams t,$wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE tp.`player_id` = $this->ID AND tp.`team_id` = t.`id` AND (m.`home_team` = t.`id` OR m.`away_team` = t.`id`) AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` ";
        $sql    .= $search . " ORDER BY m.`season`, e.`type`";
        $matches = wp_cache_get( md5( $sql ), 'player_stats_players' );
        if ( ! $matches ) {
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $matches, 'player_stats_players' );
        }
        return $matches;
    }
    /**
     * Set player wtn function
     *
     * @param array $wtn world tennis number array.
     * @return void
     */
    public function set_wtn(array $wtn ): void {
        foreach( $wtn as $match_type => $value ) {
            $this->set_wtn_type( $match_type, $value );
        }
        wp_cache_delete( $this->id, 'players' );
    }
    /**
     * Set player wtn function
     *
     * @param string $match_type match type.
     * @param string $wtn world tennis number.
     * @return void
     */
    public function set_wtn_type(string $match_type, string $wtn ): void {
        $wtn_type = 'wtn_' . $match_type;
        update_user_meta( $this->ID, $wtn_type, $wtn );
        $this->wtn[ $wtn_type ] = $wtn;
    }
    /**
     * Set opt in function
     *
     * @param string $opt_in opt in type.
     * @return void.
     */
    public function set_opt_in( string $opt_in ): void {
        if ( $opt_in ) {
            $opt_in_found = in_array( $opt_in, $this->opt_ins, true );
            if ( ! $opt_in_found ) {
                add_user_meta( $this->ID, 'racketmanager_opt_in', $opt_in, true );
            }
        }
    }
}
