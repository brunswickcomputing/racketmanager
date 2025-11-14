<?php
/**
 * User API: user class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage User
 */

namespace Racketmanager\Domain;

use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_message;
use function Racketmanager\get_player;
use function Racketmanager\get_team;

/**
 * Class to implement the User object
 */
final class User {
    /**
     * Id.
     *
     * @var int
     */
    public int $ID = 0;
    /**
     * ID.
     *
     * @var int
     */
    public int $id;
    /**
     * Email address.
     *
     * @var string
     */
    public string $email = '';
    /**
     * User Email address.
     *
     * @var string
     */
    public string $user_email = '';
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
    public string $display_name = '';
    /**
     * Date player created.
     *
     * @var string
     */
    public string $created_date;
    /**
     * Date player created.
     *
     * @var string
     */
    public string $user_registered = '';
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
     * @var int
     */
    public mixed $year_of_birth;
    /**
     * Age.
     *
     * @var string|int|null
     */
    public string|int|null $age;
    /**
     * Contact Number.
     *
     * @var string
     */
    public mixed $contactno;
    /**
     * Removed date.
     *
     * @var string
     */
    public mixed $removed_date;
    /**
     * Removed user.
     *
     * @var int
     */
    public mixed $removed_user;
    /**
     * Locked indicator.
     *
     * @var boolean
     */
    public mixed $locked;
    /**
     * Locked date.
     *
     * @var string
     */
    public mixed $locked_date;
    /**
     * Locked user.
     *
     * @var int
     */
    public mixed $locked_user;
    /**
     * Locked username.
     *
     * @var string
     */
    public string $locked_user_name;
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
     * User status.
     *
     * @var string
     */
    public string $user_status;
    /**
     * User optins.
     *
     * @var array
     */
    public mixed $opt_ins;
    /**
     * WTN.
     *
     * @var array|null
     */
    public ?array $wtn = array();
    public ?string $message;
    public string $update_result;
    public array $err_flds;
    public array $err_msgs;
    public string $password;
    public string $re_password;
    public string $status;
    public int $entry_id;
    public ?object $club;
    public string $index;

    /**
     * Retrieve user instance
     *
     * @param int $user_id user id.
     */
    public static function get_instance( int $user_id ): false|object {
        if ( ! $user_id ) {
            return false;
        }
        $user = wp_cache_get( $user_id, 'users' );
        if ( ! $user ) {
            $user =  get_userdata( $user_id );
            if ( ! $user ) {
                return false;
            }
            $user = new User( $user->data );
            wp_cache_set( $user_id, $user, 'users' );
        } else {
            $user = new User( $user );
        }
        return $user;
    }

    /**
     * Constructor
     *
     * @param object|null $user User object.
     */
    public function __construct( ?object $user = null ) {
        if ( ! is_null( $user ) ) {
            foreach ( $user as $key => $value ) {
                $this->$key = $value;
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
            if ( $this->year_of_birth ) {
                $this->age = gmdate( 'Y' ) - intval( $this->year_of_birth );
            } else {
                $this->age = 0;
            }
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
            $this->system_record = get_user_meta( $this->ID, 'racketmanager_type', true );
            $this->opt_ins       = get_user_meta( $this->ID, 'racketmanager_opt_in' );
        }
    }
    /**
     * Update function
     *
     * @param object $user updated details.
     * @return object||false
     */
    public function update( object $user ): object {
        global $racketmanager;
        $valid   = true;
        $err_fld = null;
        $err_msg = null;
        if ( empty( $user->email ) ) {
            $valid = false;
            $err_fld[] = 'username';
            $err_msg[] = Util::get_error_message( 'empty_username' );
        }
        if ( empty( $user->firstname ) ) {
            $valid = false;
            $err_fld[] = 'firstname';
            $err_msg[] = Util::get_error_message( 'firstname_field_empty' );
        }
        if ( empty( $user->surname ) ) {
            $valid = false;
            $err_fld[] = 'lastname';
            $err_msg[] = Util::get_error_message( 'lastname_field_empty' );
        }
        if ( empty( $user->gender ) ) {
            $valid = false;
            $err_fld[] = 'gender';
            $err_msg[] = Util::get_error_message( 'gender_field_empty' );
        }
        if ( empty( $user->btm ) ) {
            $player_options = $racketmanager->get_options( 'rosters' );
            if ( isset( $player_options['btm'] ) && '1' === $player_options['btm'] ) {
                $valid = false;
                $err_fld[] = 'btm';
                $err_msg[] = Util::get_error_message( 'btm_field_empty' );
            }
        }
        if ( $user->password !== $user->re_password ) {
            $valid = false;
            $err_fld[] = 'password';
            $err_msg[] = Util::get_error_message( 'password_reset_mismatch' );
        }
        if ( $valid ) {
            $updates = $this->set_details( $user );
            if ( $updates ) {
                $this->message = __( 'User updated', 'racketmanager' );
                $this->update_result = 'success';
            } else {
                $this->message = __( 'No updates', 'racketmanager' );
                $this->update_result = 'warning';
            }
        } else {
            $this->err_flds = $err_fld;
            $this->err_msgs = $err_msg;
            $this->message = __( 'Errors found', 'racketmanager' );
            $this->update_result = 'danger';
        }
        return $this;
    }
    /**
     * Set details function
     *
     * @param object $user updated details.
     * @return boolean
     */
    private function set_details( object $user ): bool {
        $updates = false;
        $updated = $this->set_user_fields_for_update( $user );
        if ( ! empty( $updated ) ) {
            $updates = true;
        }
        $opt_in_updates = $this->update_opt_ins( $user );
        if ( $opt_in_updates ) {
            $updates = true;
        }
        if ( ! $updates ) {
            return false;
        }
        foreach ( $updated as $key => $value ) {
            // http://codex.wordpress.org/Function_Reference/wp_update_user.
            if ( 'contactno' === $key ) {
                update_user_meta( $this->ID, $key, $value );
            } elseif ( 'btm' === $key ) {
                update_user_meta( $this->ID, $key, $value );
            } elseif ( 'year_of_birth' === $key ) {
                update_user_meta( $this->ID, $key, $value );
            } elseif ( 'gender' === $key ) {
                update_user_meta( $this->ID, $key, $value );
            } elseif ( 'first_name' === $key ) {
                update_user_meta( $this->ID, $key, $value );
                $display_name = $value . ' ' . sanitize_text_field( $this->surname );
                wp_update_user(
                    array(
                        'ID'           => $this->ID,
                        'display_name' => $display_name,
                    )
                );
                $this->display_name = $display_name;
                $this->fullname     = $display_name;
            } elseif ( 'last_name' === $key ) {
                update_user_meta( $this->ID, $key, $value );
                $display_name = sanitize_text_field( $this->firstname ) . ' ' . $value;
                wp_update_user(
                    array(
                        'ID'           => $this->ID,
                        'display_name' => $display_name,
                    )
                );
                $this->display_name = $display_name;
                $this->fullname     = $display_name;
            } elseif ( 'password' === $key ) {
                wp_set_password( $value, $this->ID );
                wp_set_auth_cookie( $this->ID, 1, true );
            } else {
                wp_update_user(
                    array(
                        'ID' => $this->ID,
                        $key => $value,
                    )
                );
            }
        }
        return true;
    }
    /**
     * Function to set user fields that are updated
     *
     * @param object $user
     *
     * @return array
     */
    private function set_user_fields_for_update( object $user ): array {
        $updated = array();
        if ( $this->user_email !== $user->email ) {
            $this->user_email      = $user->email;
            $updated['user_email'] = $user->email;
        }
        if ( $this->firstname !== $user->firstname ) {
            $this->firstname       = $user->firstname;
            $updated['first_name'] = $user->firstname;
        }
        if ( $this->surname !== $user->surname ) {
            $this->surname        = $user->surname;
            $updated['last_name'] = $user->surname;
        }
        if ( $this->contactno !== $user->contactno ) {
            $this->contactno      = $user->contactno;
            $updated['contactno'] = $user->contactno;
        }
        if ( $this->gender !== $user->gender ) {
            $this->gender      = $user->gender;
            $updated['gender'] = $user->gender;
        }
        if ( empty( $this->btm ) ) {
            if ( ! empty( $user->btm ) ) {
                $this->btm      = $user->btm;
                $updated['btm'] = $user->btm;
            }
        } elseif ( intval( $this->btm ) !== intval( $user->btm ) ) {
            $this->btm      = $user->btm;
            $updated['btm'] = $user->btm;
        }
        if ( intval( $this->year_of_birth ) !== intval( $user->year_of_birth ) ) {
            $this->year_of_birth      = $user->year_of_birth;
            $updated['year_of_birth'] = $user->year_of_birth;
        }
        if ( ! empty( $user->password ) ) {
            $this->password      = $user->password;
            $updated['password'] = $user->password;
        }
        return $updated;
    }

    /**
     * Function to update opt ins.
     *
     * @param object $user user.
     *
     * @return bool
     */
    private function update_opt_ins( object $user ): bool {
        $updates        = false;
        $opt_in_choices = Util::get_email_opt_ins();
        $opt_ins        = array();
        foreach ( $opt_in_choices as $opt_in_choice => $opt_in_desc ) {
            $user_opt_in[ $opt_in_choice ] = ! empty( $user->opt_ins[ $opt_in_choice ] );
            if ( in_array( strval( $opt_in_choice ), $this->opt_ins, true ) ) {
                if ( empty( $user_opt_in[ $opt_in_choice ] ) ) {
                    $updates = true;
                    delete_user_meta( $this->id, 'racketmanager_opt_in', $opt_in_choice );
                } else {
                    $opt_ins[] = strval( $opt_in_choice );
                }
            } elseif ( ! empty( $user_opt_in[ $opt_in_choice ] ) ) {
                $updates   = true;
                $opt_ins[] = strval( $opt_in_choice );
                add_user_meta( $this->id, 'racketmanager_opt_in', $opt_in_choice );
            }
        }
        $this->opt_ins = $opt_ins;
        return $updates;
    }
    /**
     * Get messages function
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_messages( array $args = array() ): array|int {
        global $wpdb;

        $defaults     = array(
            'count'   => false,
            'status'  => false,
            'orderby' => array( 'date' => 'DESC' ),
        );
        $args         = array_merge( $defaults, $args );
        $count        = $args['count'];
        $status       = $args['status'];
        $orderby      = $args['orderby'];
        $sql          = " FROM $wpdb->racketmanager_messages WHERE `userid` = $this->ID";
        $search_terms = array();
        if ( $status ) {
            switch ( $status ) {
                case 'unread':
                    $status = '1';
                    break;
                case 'read':
                    $status = '0';
                    break;
                default:
                    break;
            }
            $search_terms[] = $wpdb->prepare( '`status` = %s', $status );
        }
        if ( ! empty( $search_terms ) ) {
            $search = implode( ' AND ', $search_terms );
            $sql   .= " AND $search";
        }

        $order = Util::order_by_string( $orderby );
        if ( $count ) {
            $sql = 'SELECT COUNT(ID)' . $sql;
            return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
        }

        $sql = 'SELECT `id` ' . $sql;
        if ( '' !== $order ) {
            $sql .= $order;
        }

        $messages = wp_cache_get( md5( $sql ), 'messages' );
        if ( ! $messages ) {
            $messages = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $messages, 'messages' );
        }
        $i = 0;
        foreach ( $messages as $message ) {
            $message_dtl    = get_message( $message->id );
            $messages[ $i ] = $message_dtl;
            ++$i;
        }
        return $messages;
    }
    /**
     * Delete messages function
     *
     * @param string $type type of messages to delete.
     * @return int||error object
     */
    public function delete_messages( string $type ): int {
        global $wpdb;
        return $wpdb->delete( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->racketmanager_messages,
            array( 'status' => $type, 'userid' => $this->ID ),
            array( '%s', '%d' ),
        );
    }
    /**
     * Get favourites function
     *
     * @param string|null $favourites_type optional favourites search criteria.
     * @return array
     */
    public function get_favourites( ?string $favourites_type = null ): array {
        $favourites_types = array( 'competition', 'league', 'club', 'team', 'player' );
        $favourites       = array();
        if ( $favourites_type ) {
            if ( in_array( $favourites_type, $favourites_types, true ) ) {
                $favourites = $this->get_favourites_for_type( $favourites_type );
            }
        } else {
            foreach ( $favourites_types as $f => $favourites_type ) {
                $favourites[ $f ]['name'] = $favourites_type;
                $favourites_type          = $this->get_favourites_for_type( $favourites_type );
                array_multisort( $favourites_type );
                $favourites[ $f ]['favourites'] = $favourites_type;
            }
        }
        return $favourites;
    }
    /**
     * Get favourites for a type function
     *
     * @param string $favourites_type type of favourite.
     * @return array
     */
    private function get_favourites_for_type( string $favourites_type ): array {
        $userid          = $this->ID;
        $meta_key        = 'favourite-' . $favourites_type;
        $meta_favourites = get_user_meta( $userid, $meta_key );
        $favourites      = array();
        foreach ( $meta_favourites as $i => $favourite ) {
            $favourite_item = new stdClass();
            if ( 'league' === $favourites_type ) {
                $league                 = get_league( $favourite );
                $favourite_item->name   = $league->title;
                $favourite_item->detail = $league;
            } elseif ( 'club' === $favourites_type ) {
                $club                   = get_club( $favourite );
                $favourite_item->name   = $club->name;
                $favourite_item->detail = $club;
            } elseif ( 'competition' === $favourites_type ) {
                $event                  = get_event( $favourite );
                $favourite_item->name   = $event->name;
                $favourite_item->detail = $event;
            } elseif ( 'player' === $favourites_type ) {
                $player                 = get_player( $favourite );
                $favourite_item->name   = $player->display_name;
                $favourite_item->detail = $player;
            } elseif ( 'team' === $favourites_type ) {
                $team                   = get_team( $favourite );
                $favourite_item->name   = $team->title;
                $favourite_item->detail = $team;
            }
            $favourite_item->id = $favourite;
            $favourites[ $i ]   = $favourite_item;
        }
        return $favourites;
    }
    /**
     * Update user contact details
     *
     * @param string $contact_no telephone number.
     * @param string $contact_email email address.
     * @return boolean
     */
    public function update_contact( string $contact_no, string $contact_email ): bool {
        $updates               = false;
        $current_contact_no    = get_user_meta( $this->ID, 'contactno', true );
        $current_contact_email = $this->user_email;
        if ( $current_contact_no !== $contact_no ) {
            update_user_meta( $this->ID, 'contactno', $contact_no );
            $this->contactno = $contact_no;
            $updates         = true;
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
            $updates          = true;
        }
        wp_cache_set( $this->id, $this, 'users' );
        return $updates;
    }
}
