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

use Racketmanager\Domain\DTO\Player\User_Hydration_DTO;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;

/**
 * Class to implement the User object
 */
class User {
    /**
     * Id.
     *
     * @var int
     */
    public private(set) int $ID = 0;
    /**
     * ID.
     *
     * @var int
     */
    public private(set) int $id;
    /**
     * Email address.
     *
     * @var string
     */
    public private(set) string $email = '';
    /**
     * User Email address.
     *
     * @var string
     */
    public private(set) string $user_email = '';
    /**
     * Fullname - join of first name and surname.
     *
     * @var string
     */
    public private(set) string $fullname;
    /**
     * Display name.
     *
     * @var string
     */
    public private(set) string $display_name = '';
    /**
     * Date player created.
     *
     * @var string
     */
    public private(set) string $created_date;
    /**
     * Date player created.
     *
     * @var string
     */
    public private(set) string $user_registered = '';
    /**
     * First name.
     *
     * @var string|null
     */
    public private(set) ?string $firstname;
    /**
     * Surname.
     *
     * @var string|null
     */
    public private(set) ?string $surname;
    /**
     * Gender.
     *
     * @var string|null
     */
    public private(set) ?string $gender;
    /**
     * Type.
     *
     * @var string|null
     */
    public private(set) ?string $type;
    /**
     * LTA Membership Number.
     *
     * @var string|int|null
     */
    public private(set) string|int|null $btm;
    /**
     * Year of birth.
     *
     * @var string|int|null
     */
    public private(set) string|int|null $year_of_birth;
    /**
     * Age.
     *
     * @var string|int|null
     */
    public private(set) string|int|null $age;
    /**
     * Contact Number.
     *
     * @var string|null
     */
    public private(set) ?string $contactno;
    /**
     * Removed date.
     *
     * @var string|null
     */
    public private(set) ?string $removed_date;
    /**
     * Removed user.
     *
     * @var string|int|null
     */
    public private(set) string|int|null $removed_user;
    /**
     * Locked indicator.
     *
     * @var bool|int|string|null
     */
    public private(set) bool|int|string|null $locked;
    /**
     * Locked date.
     *
     * @var string|null
     */
    public private(set) ?string $locked_date;
    /**
     * Locked user.
     *
     * @var string|int|null
     */
    public private(set) string|int|null $locked_user;
    /**
     * Locked username.
     *
     * @var string
     */
    public private(set) string $locked_user_name;
    /**
     * System record.
     *
     * @var string|null
     */
    public private(set) ?string $system_record;
    /**
     * Matches.
     *
     * @var array
     */
    public private(set) array $matches = array();
    /**
     * Statistics.
     *
     * @var array
     */
    public private(set) array $statistics = array();
    /**
     * User password.
     *
     * @var string
     */
    public private(set) string $user_pass;
    /**
     * User nicename.
     *
     * @var string
     */
    public private(set) string $user_nicename;
    /**
     * User url.
     *
     * @var string
     */
    public private(set) string $user_url;
    /**
     * User activation_key.
     *
     * @var string
     */
    public private(set) string $user_activation_key;
    /**
     * User login.
     *
     * @var string
     */
    public private(set) string $user_login;
    /**
     * User status.
     *
     * @var string
     */
    public private(set) string $user_status;
    /**
     * User optins.
     *
     * @var array|string|null
     */
    public private(set) array|string|null $opt_ins;
    /**
     * WTN.
     *
     * @var array|null
     */
    public private(set) ?array $wtn = array();
    /**
     * Message.
     *
     * @var string|null
     */
    public private(set) ?string $message;
    /**
     * Update result.
     *
     * @var string
     */
    public private(set) string $update_result;
    /**
     * Error fields.
     *
     * @var array
     */
    public private(set) array $err_flds;
    /**
     * Error messages.
     *
     * @var array
     */
    public private(set) array $err_msgs;
    /**
     * Password.
     *
     * @var string
     */
    public private(set) string $password = '';
    /**
     * Repeated password.
     *
     * @var string
     */
    public private(set) string $re_password = '';
    /**
     * Favourites.
     *
     * @var array
     */
    public private(set) array $favourites = array();
    /**
     * Status.
     *
     * @var string
     */
    public private(set) string $status;
    /**
     * Entry ID.
     *
     * @var int
     */
    public private(set) int $entry_id;
    /**
     * Club.
     *
     * @var object|null
     */
    public private(set) ?object $club;
    /**
     * Index.
     *
     * @var string
     */
    public private(set) string $index;

    /**
     * Constructor
     *
     * @param User_Hydration_DTO|null $dto hydration DTO (WordPress/plugin aliases already resolved).
     */
    public function __construct( ?User_Hydration_DTO $dto = null ) {
        if ( ! is_null( $dto ) ) {
            foreach ( get_object_vars( $dto ) as $key => $value ) {
                if ( null === $value ) {
                    continue; // Column absent from the row: keep the property default.
                }
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
            $this->ID           = $this->id;
            $this->user_email   = $this->email;
            $this->display_name = $this->fullname;
        }
    }

    /**
     * Create a User from a WordPress user, a row or a plugin-shaped object via User_Factory.
     *
     * @param object $row user object.
     * @return self
     */
    public static function from_object( object $row ): self {
        return User_Factory::from_object( $row );
    }

    /**
     * Create a User from an associative array via User_Factory.
     *
     * @param array $data user data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return User_Factory::from_array( $data );
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

    /**
     * Set user ID.
     *
     * @param int $id User ID.
     * @return void
     */
    public function set_id( int $id ): void {
        $this->id = $id;
        $this->ID = $id;
    }

    /**
     * Set user email.
     *
     * @param string $email Email address.
     * @return void
     */
    public function set_email( string $email ): void {
        $this->email      = $email;
        $this->user_email = $email;
    }

    /**
     * Set display name.
     *
     * @param string $display_name Display name.
     * @return void
     */
    public function set_display_name( string $display_name ): void {
        $this->display_name = $display_name;
        $this->fullname     = $display_name;
    }

    /**
     * Set first name.
     *
     * @param ?string $firstname First name.
     * @return void
     */
    public function set_firstname( ?string $firstname ): void {
        $this->firstname = $firstname;
    }

    /**
     * Set surname.
     *
     * @param ?string $surname Surname.
     * @return void
     */
    public function set_surname( ?string $surname ): void {
        $this->surname = $surname;
    }

    /**
     * Set contact number.
     *
     * @param ?string $contactno Contact number.
     * @return void
     */
    public function set_contactno( ?string $contactno ): void {
        $this->contactno = $contactno;
    }

    /**
     * Set gender.
     *
     * @param ?string $gender Gender.
     * @return void
     */
    public function set_gender( ?string $gender ): void {
        $this->gender = $gender;
    }

    /**
     * Set LTA membership number.
     *
     * @param string|int|null $btm LTA membership number.
     * @return void
     */
    public function set_btm( string|int|null $btm ): void {
        $this->btm = $btm;
    }

    /**
     * Set year of birth.
     *
     * @param string|int|null $year_of_birth Year of birth.
     * @return void
     */
    public function set_year_of_birth( string|int|null $year_of_birth ): void {
        $this->year_of_birth = $year_of_birth;
        if ( ! empty( $year_of_birth ) && is_numeric( $year_of_birth ) ) {
            $this->age = (int) gmdate( 'Y' ) - (int) $year_of_birth;
        } else {
            $this->age = 0;
        }
    }

    /**
     * Set password.
     *
     * @param string $password Password.
     * @return void
     */
    public function set_password( string $password ): void {
        $this->password = $password;
    }

    /**
     * Set repeated password.
     *
     * @param string $re_password Repeated password.
     * @return void
     */
    public function set_re_password( string $re_password ): void {
        $this->re_password = $re_password;
    }

    /**
     * Set opt-in choices.
     *
     * @param array|string|null $opt_ins Opt-in choices.
     * @return void
     */
    public function set_opt_ins( array|string|null $opt_ins ): void {
        if ( is_array( $opt_ins ) ) {
            $this->opt_ins = array_values( array_map( 'strval', $opt_ins ) );
        } elseif ( is_string( $opt_ins ) && '' !== $opt_ins ) {
            $this->opt_ins = array( $opt_ins );
        } else {
            $this->opt_ins = array();
        }
    }

    /**
     * Set locked status.
     *
     * @param bool|int|string|null $locked Locked indicator.
     * @param ?string              $locked_date Date locked.
     * @param int|string|null      $locked_user ID of user who locked.
     * @param string               $locked_user_name Name of user who locked.
     * @return void
     */
    public function set_locked_status(
        bool|int|string|null $locked,
        ?string $locked_date = null,
        int|string|null $locked_user = null,
        string $locked_user_name = ''
    ): void {
        $this->locked           = $locked;
        $this->locked_date      = $locked_date;
        $this->locked_user      = $locked_user;
        $this->locked_user_name = $locked_user_name;
    }

    /**
     * Set favourite IDs.
     *
     * @param array $favourites Array of favourite IDs grouped by type.
     * @return void
     */
    public function set_favourites( array $favourites ): void {
        $this->favourites = $favourites;
    }

    /**
     * Update user profile information.
     *
     * @param string            $email Email address.
     * @param ?string           $firstname First name.
     * @param ?string           $surname Surname.
     * @param ?string           $contactno Contact number.
     * @param ?string           $gender Gender.
     * @param string|int|null   $btm LTA membership number.
     * @param string|int|null   $year_of_birth Year of birth.
     * @param string            $password Password.
     * @param string            $re_password Repeated password.
     * @param array|string|null $opt_ins Opt-ins.
     * @return void
     */
    public function update_profile(
        string $email,
        ?string $firstname,
        ?string $surname,
        ?string $contactno,
        ?string $gender,
        string|int|null $btm,
        string|int|null $year_of_birth,
        string $password = '',
        string $re_password = '',
        array|string|null $opt_ins = array()
    ): void {
        $this->set_email( $email );
        $this->set_firstname( $firstname );
        $this->set_surname( $surname );
        $this->set_contactno( $contactno );
        $this->set_gender( $gender );
        $this->set_btm( $btm );
        $this->set_year_of_birth( $year_of_birth );
        $this->set_password( $password );
        $this->set_re_password( $re_password );
        $this->set_opt_ins( $opt_ins );
    }
}
