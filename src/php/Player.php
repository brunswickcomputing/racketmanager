<?php
namespace Racketmanager;

// Bridge guard: if PSR-4 loads this file but the class already exists, stop immediately.
if (class_exists('Racketmanager\\Player', false)) { return; }

// Deterministic bridge to legacy implementation to avoid partial class during transition.
if (!\defined('RACKETMANAGER_PATH')) {
    $pluginRoot = \dirname(__DIR__) . '/';
    if (!\defined('RACKETMANAGER_PATH')) {
        \define('RACKETMANAGER_PATH', $pluginRoot);
    }
}
require_once RACKETMANAGER_PATH . 'include/class-player.php';
return;

use Racketmanager\util\Util;
use Racketmanager\util\Util_Lookup;
use stdClass;
use WP_User;

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
     * Contact Number.
     *
     * @var string
     */
    public mixed $telephone;
    /**
     * Player type.
     *
     * @var string|null
     */
    public ?string $player_type;
    /**
     * Details - player details.
     *
     * @var object
     */
    public object $detail;
    /**
     * Details - player details.
     *
     * @var object
     */
    public object $detail1;
    /**
     * Details - player details.
     *
     * @var object
     */
    public object $detail2;
    /**
     * Player rating.
     *
     * @var float|null
     */
    public ?float $rating;
    /**
     * Is player locked from club listing.
     *
     * @var string|null
     */
    public ?string $locked;
    /**
     * Player lock date.
     *
     * @var string|null
     */
    public ?string $locked_date;
    /**
     * Player lock admin id.
     *
     * @var string|null
     */
    public ?string $locked_user;
    /**
     * Player lock admin name.
     *
     * @var string|null
     */
    public ?string $locked_user_name;
    /**
     * Register player status.
     *
     * @var string|null
     */
    public ?string $status;
    /**
     * Get player instance
     *
     * @param string|int $player player id or name.
     * @param string $search_term type of search required.
     */
    public static function get_instance( string|int $player, string $search_term = 'id' ) {
        global $wpdb;
        switch ( $search_term ) {
            case 'login':
                $search = $wpdb->prepare( 'user_login = %s', $player );
                break;
            case 'email':
                $search = $wpdb->prepare( 'user_email = %s', $player );
                break;
            case 'btm':
                $search = $wpdb->prepare( 'meta_value = %s AND meta_key = %s', $player, 'btm' );
                break;
            case 'id':
            default:
                $search = $wpdb->prepare( 'ID = %d', intval( $player ) );
                break;
        }
        if ( ! $player ) {
            return false;
        }
        $display = false;
        if ( is_numeric( $player ) ) {
            $display = Util::user_display_name( intval( $player ) );
        }
        if ( $display ) {
            $user_info = new stdClass();
            $user_info->ID            = $player;
            $user_info->display_name  = $display;
            $user_info->user_email    = '';
            $user_info->user_pass     = null;
            $user_info->user_nicename = '';
            $user_info->user_url      = '';
            $user_info->user_login    = '';
            $user_info->user_registered = null;
        } elseif ( 'btm' === $search_term ) {
            $user_info = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT A.`user_id` as ID, B.`display_name`, B.`user_email`, B.`user_login`, B.`user_registered` FROM $wpdb->usermeta A, $wpdb->users B WHERE A.`meta_value` = %s AND A.`meta_key` = %s AND B.`ID` = A.`user_id`",
                    $player,
                    'btm',
                )
            );
        } else {
            $user_info = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT `ID`, `display_name`, `user_email`, `user_login`, `user_registered` FROM $wpdb->users WHERE $search",
                )
            );
        }
        if ( ! $user_info ) {
            return false;
        }
        $player        = new Player( $user_info );
        return $player;
    }

    /**
     * Constructor
     *
     * @param object|null $user Player object.
     */
    public function __construct( ?object $user = null ) {
        global $wpdb, $racketmanager;
        if ( ! is_null( $user ) ) {
            foreach ( get_object_vars( $user ) as $key => $value ) {
                $this->$key = $value;
            }
            $meta_keys = array( 'gender', 'btm', 'year_of_birth', 'racketmanager_type', 'contactno', 'telephone', 'locked', 'locked_date', 'locked_user' );
            foreach ( $meta_keys as $meta_key ) {
                // phpcs:ignore WordPress.PHP.DisallowShortTernary.Found
                $this->$meta_key = ( null !== get_user_meta( $this->ID, $meta_key, true ) && '' !== get_user_meta( $this->ID, $meta_key, true ) ) ? get_user_meta( $this->ID, $meta_key, true ) : '';
            }
            $this->id            = $this->ID;
            $this->user_email    = $this->user_email ?? '';
            $this->email         = $this->user_email;
            $this->display_name  = htmlspecialchars( $this->display_name, ENT_QUOTES );
            $display_name        = explode( ' ', $this->display_name, 2 );
            $this->firstname     = $display_name[0];
            $this->surname       = isset( $display_name[1] ) ? $display_name[1] : '';
            $this->fullname      = $this->display_name;
            $this->name          = $this->display_name;
            $this->created_date  = $this->user_registered;
            $this->user_registered = $this->user_registered;
            $this->age           = null;
            if ( $this->year_of_birth ) {
                $year = intval( $this->year_of_birth );
                $this->age = gmdate( 'Y' ) - $year;
            }
        }
    }

    /**
     * Constructor to add player
     *
     * @param object $new_player Player object.
     */
    public function add( object $new_player ) {
        global $wpdb, $racketmanager;
        $firstname = explode( ' ', $new_player->fullname, 2 );
        $i         = 1;
        $username  = explode( '@', $new_player->email )[0];
        $username  = Util::seo_name( $username );
        $username  = substr( $username, 0, 19 );
        $suffix    = '';
        do {
            if ( $i > 1 ) {
                $suffix = $i - 1;
            }
            $player = get_user_by( 'login' , $username . $suffix );
            ++$i;
        } while ( $player );
        $user        = new WP_User();
        $user->email = $new_player->email;
        $user->id    = null;
        $user->login = $username . $suffix;
        $user->name  = $new_player->fullname;
        $user->url   = '';
        $user->pass  = wp_hash_password( wp_generate_password( 12, false ) );
        $user_id = $wpdb->insert( $wpdb->users, // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            array(
                'user_login' => $user->login,
                'user_pass'  => $user->pass,
                'user_email' => $user->email,
                'user_url'   => $user->url,
                'display_name' => $user->name,
                'user_registered' => gmdate( 'Y-m-d H:i:s' ),
            )
        );
        $user_id = $wpdb->insert_id;
        if ( ! $user_id ) {
            $racketmanager->set_message( __( 'Error creating new player', 'racketmanager' ), true );
            return;
        }
        $this->ID           = $user_id;
        $this->display_name = $user->name;
        $this->type         = $new_player->type;
        $this->gender       = $new_player->gender;
        $this->btm          = $new_player->btm;
        $this->year_of_birth = $new_player->year_of_birth;
        $this->email        = $new_player->email;
        $this->contactno    = $new_player->contactno;
        $this->user_email   = $new_player->email;
        $this->status       = 'new';
        $this->set_user_meta( 'racketmanager_type', $this->type );
        $this->set_user_meta( 'gender', $this->gender );
        $this->set_user_meta( 'btm', $this->btm );
        $this->set_user_meta( 'year_of_birth', $this->year_of_birth );
        $this->set_user_meta( 'contactno', $this->contactno );
        $this->add_player_role();
        $racketmanager->set_message( __( 'User registered', 'racketmanager' ) );
        return $this->ID;
    }

    /**
     * Get player meta
     *
     * @param string $key Key value for player meta.
     *
     * @return mixed|null
     */
    public function get_user_meta( string $key ): mixed {
        $value = get_user_meta( $this->ID, $key, true );
        if ( ! $value ) {
            $value = null;
        }
        return $value;
    }

    /**
     * Set player meta
     *
     * @param string $key Key for player meta on usermeta table.
     * @param mixed  $value Meta value to use.
     *
     * @return void
     */
    public function set_user_meta( string $key, mixed $value ): void {
        update_user_meta( $this->ID, $key, $value );
    }

    /**
     * Update player
     *
     * @param object $user Player object.
     *
     * @return bool
     */
    public function update( object $user ): bool {
        global $wpdb, $racketmanager;
        $updates = false;
        if ( $this->display_name !== $user->fullname ) {
            $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->users SET `display_name` = %s WHERE `ID` = %d",
                    $user->fullname,
                    $this->ID
                )
            );
            $this->display_name = $user->fullname;
            $updates    = true;
        }
        if ( $this->user_email !== $user->email ) {
            $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->users SET `user_email` = %s WHERE `ID` = %d",
                    $user->email,
                    $this->ID
                )
            );
            $this->email      = $user->email;
            $this->user_email = $this->email;
            $updates    = true;
        }
        if ( $this->btm !== $user->btm ) {
            $this->set_user_meta( 'btm', $user->btm );
            $this->btm = $user->btm;
            $updates   = true;
        }
        if ( $this->gender !== $user->gender ) {
            $this->set_user_meta( 'gender', $user->gender );
            $this->gender = $user->gender;
            $updates      = true;
        }
        if ( $this->year_of_birth !== $user->year_of_birth ) {
            $this->set_user_meta( 'year_of_birth', $user->year_of_birth );
            $this->year_of_birth = $user->year_of_birth;
            $updates             = true;
        }
        if ( $this->contactno !== $user->contactno ) {
            $this->set_user_meta( 'contactno', $user->contactno );
            $this->contactno = $user->contactno;
            $updates         = true;
        }
        if ( $updates ) {
            $racketmanager->set_message( __( 'Player updated', 'racketmanager' ) );
        } else {
            $racketmanager->set_message( __( 'No updates', 'racketmanager' ) );
        }
        wp_cache_delete( $this->id, 'players' );
        return $updates;
    }

    /**
     * Update player contact
     *
     * @param string|null $telephone Player contact number.
     * @param string|null $email Player email address.
     *
     * @return bool
     */
    public function update_contact( ?string $telephone, ?string $email ): bool {
        $updates = false;
        if ( null !== $telephone && $this->contactno !== $telephone ) {
            $this->set_user_meta( 'contactno', $telephone );
            $updates = true;
        }
        if ( null !== $email && $this->user_email !== $email ) {
            $this->email      = $email;
            $this->user_email = $email;
            $this->set_user_meta( 'user_email', $email );
            $updates = true;
        }
        return $updates;
    }

    /**
     * Add player role
     */
    public function add_player_role(): void {
        $user = new WP_User( $this->ID );
        $user->set_role( 'subscriber' );
    }

    /**
     * Get player age
     */
    public function get_age(): int|null {
        $year = $this->year_of_birth;
        if ( ! $year ) {
            return null;
        }
        return gmdate( 'Y' ) - intval( $year );
    }

    /**
     * Is player valid for competition age limit
     *
     * @param string $competition Competition type.
     * @param string $age_limit Age group limit.
     * @param string $gender Gender.
     * @param string $age_offset age offset.
     *
     * @return bool
     */
    public function age_within_limit( string $competition, string $age_limit, string $gender, string $age_offset ): bool {
        $age   = $this->get_age();
        $valid = Util::check_age_within_limit( $age, $age_limit, $gender, $age_offset );
        return $valid->valid;
    }

    /**
     * Get player from bare data returned from database
     *
     * @param object $player Player from database.
     *
     * @return object
     */
    private function get_player_from_database( object $player ): object {
        $user = get_userdata( $player->player_id );
        if ( ! $user ) {
            return $player;
        }
        $player->display_name     = $user->display_name;
        $player->email            = $user->user_email;
        $player->gender           = get_user_meta( $player->player_id, 'gender', true );
        $player->contactno        = get_user_meta( $player->player_id, 'contactno', true );
        $player->year_of_birth    = get_user_meta( $player->player_id, 'year_of_birth', true );
        $player->btm              = get_user_meta( $player->player_id, 'btm', true );
        $player->age              = null;
        $player->age_group        = null;
        if ( $player->year_of_birth ) {
            $player->age = gmdate( 'Y' ) - intval( $player->year_of_birth );
        }
        return $player;
    }

    /**
     * Get player
     *
     * @param int $player_id Player id.
     *
     * @return object
     */
    public function build_player( int $player_id ): object {
        $player = new stdClass();
        $player->player_id = $player_id;
        return $this->get_player_from_database( $player );
    }

    /**
     * Build player from roster
     *
     * @param object $player Player from roster.
     *
     * @return object
     */
    public function build_player_from_roster( object $player ): object {
        return $this->get_player_from_database( $player );
    }

    /**
     * Search for possible duplicate players with same name and date of birth
     *
     * @param int $year Year of birth.
     * @param string $name Player name.
     *
     * @return array|false
     */
    public function possible_duplicate( int $year, string $name ): array|false {
        global $wpdb;
        $players = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT `ID` FROM $wpdb->users WHERE `display_name` = %s",
                $name
            )
        );
        if ( $players ) {
            $dups = array();
            foreach ( $players as $player ) {
                $year_of_birth = get_user_meta( $player->ID, 'year_of_birth', true );
                if ( intval( $year_of_birth ) === $year ) {
                    $dups[] = $player->ID;
                }
            }
            if ( $dups ) {
                return $dups;
            }
        }
        return false;
    }
}
