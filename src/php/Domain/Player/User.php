<?php
declare( strict_types=1 );
/**
 * User API: user class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage User
 */
namespace Racketmanager\Domain\Player;

/**
 * Class to implement the User object
 */
class User {
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
    public string $password = '';
    public string $re_password = '';
    public array $favourites = array();
    public string $status;
    public int $entry_id;
    public ?object $club;
    public string $index;

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
            $this->id           = $this->ID ?? $this->id ?? 0;
            $this->ID           = $this->id;
            $this->email        = $this->user_email ?? $this->email ?? '';
            $this->user_email   = $this->email;
            $this->fullname     = $this->display_name ?? $this->fullname ?? '';
            $this->display_name = $this->fullname;
        }
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
        if ( isset( $this->favourites[ $favourites_type ] ) && is_array( $this->favourites[ $favourites_type ] ) ) {
            return $this->favourites[ $favourites_type ];
        }
        return array();
    }
}
