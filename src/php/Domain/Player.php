<?php
/**
 * Player API: player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Player
 */

namespace Racketmanager\Domain;

use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Value_Objects\Player_Ranking;

/**
 * Class to implement the Player object
 */
class Player {
    /**
     * ID.
     *
     * @var ?int
     */
    public ?int $id = null;

    /**
     * Id alias for WordPress user compatibility.
     *
     * @var ?int
     */
    public ?int $ID {
        get => $this->id;
        set => $this->id = $value;
    }

    /**
     * Email address.
     *
     * @var ?string
     */
    public ?string $email = null;

    /**
     * User Email address alias for WordPress user compatibility.
     *
     * @var ?string
     */
    public ?string $user_email {
        get => $this->email;
        set => $this->email = $value;
    }

    /**
     * Display name.
     *
     * @var ?string
     */
    public ?string $display_name = null;

    /**
     * Fullname - join of first name and surname alias.
     *
     * @var ?string
     */
    public ?string $fullname {
        get => $this->display_name;
        set => $this->display_name = $value;
    }
    /**
     * Name.
     *
     * @var string
     */
    public string $name = '';
    /**
     * Email address.
     *
     * @var ?string
     */
    public ?string $user_registered = null;
    /**
     * First name.
     *
     * @var string|null
     */
    public mixed $firstname = null;
    /**
     * Surname.
     *
     * @var string|null
     */
    public mixed $surname = null;
    /**
     * Gender.
     *
     * @var string|null
     */
    public mixed $gender = null;
    /**
     * Type.
     *
     * @var string|null
     */
    public mixed $type = null;
    /**
     * LTA Membership Number.
     *
     * @var int|string|null
     */
    public mixed $btm = null;
    /**
     * Year of birth.
     *
     * @var int|null
     */
    public int|null $year_of_birth = null;
    /**
     * Age.
     *
     * @var int|null
     */
    public ?int $age = null;
    /**
     * Contact Number.
     *
     * @var string|null
     */
    public mixed $contactno = null;
    /**
     * Removed date.
     *
     * @var string|null
     */
    public ?string $removed_date = null;
    /**
     * Removed user.
     *
     * @var int|null
     */
    public int|null $removed_user = null;
    /**
     * Locked indicator.
     *
     * @var boolean|null
     */
    public mixed $locked = null;
    /**
     * Locked date.
     *
     * @var string|null
     */
    public ?string $locked_date = null;
    /**
     * Locked user.
     *
     * @var string|null
     */
    public ?string $locked_user = null;
    /**
     * Locked username.
     *
     * @var string|null
     */
    public ?string $locked_user_name = null;
    /**
     * System record.
     *
     * @var string|null
     */
    public mixed $system_record = null;
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
     * Url link.
     *
     * @var string
     */
    public string $link = '';
    /**
     * Opt in detail.
     *
     * @var array
     */
    public array $opt_ins = array();
    /**
     * WTN.
     *
     * @var array
     */
    public array $wtn = array();
    /**
     * User password.
     *
     * @var string
     */
    public string $user_pass = '';
    /**
     * User nicename.
     *
     * @var string
     */
    public string $user_nicename = '';
    /**
     * User url.
     *
     * @var string
     */
    public string $user_url = '';
    /**
     * User activation_key.
     *
     * @var string
     */
    public string $user_activation_key = '';
    /**
     * User login.
     *
     * @var string
     */
    public string $user_login = '';
    /**
     * Index.
     *
     * @var string
     */
    public string $index = '';
    /**
     * Stats
     *
     * @var array
     */
    public array $stats = array();
    /**
     * Matches won variable
     *
     * @var int
     */
    public int $matches_won = 0;
    /**
     * Matches lost variable
     *
     * @var int
     */
    public int $matches_lost = 0;
    /**
     * Win pct variable
     *
     * @var float|null
     */
    public ?float $win_pct = null;
    /**
     * Played variable
     *
     * @var int
     */
    public int $played = 0;
    /**
     * Competitions variable
     *
     * @var array
     */
    public array $competitions = array();
    /**
     * Cup variable
     *
     * @var array
     */
    public array $cup = array();
    /**
     * League variable
     *
     * @var array
     */
    public array $league = array();
    /**
     * Tournament variable
     *
     * @var array
     */
    public array $tournament = array();
    /**
     * Teams variable
     *
     * @var array
     */
    public array $teams = array();
    /**
     * Club variable
     *
     * @var object|null
     */
    public ?object $club = null;
    /**
     * Team
     *
     * @var object|null
     */
    public ?object $team = null;
    /**
     * Status
     *
     * @var string|null
     */
    public ?string $status = null;
    /**
     * User status
     *
     * @var string|null
     */
    public ?string $user_status = null;
    /**
     * Entry id
     *
     * @var int|null
     */
    public ?int $entry_id = null;
    /**
     * Entry
     *
     * @var array
     */
    public array $entry = array();
    /**
     * Tournament Entry
     *
     * @var object|null
     */
    public ?object $tournament_entry = null;

    /**
     * Constructor
     *
     * @param Player_Hydration_DTO|null $dto Typed hydration DTO. Use Player_Factory for raw rows.
     */
    public function __construct(
        ?Player_Hydration_DTO $dto = null
    ) {
        if ( is_null( $dto ) ) {
            return;
        }

        $this->id                  = $dto->id;
        $this->email               = $dto->email;
        $this->display_name        = $dto->display_name;
        $this->name                = $dto->display_name ?? '';
        $this->user_registered     = $dto->user_registered;
        $this->firstname           = $dto->firstname;
        $this->surname             = $dto->surname;
        $this->gender              = $dto->gender;
        $this->type                = $dto->type;
        $this->btm                 = $dto->btm;
        $this->year_of_birth       = $dto->year_of_birth;
        $this->age                 = $dto->age;
        $this->contactno           = $dto->contactno;
        $this->removed_date        = $dto->removed_date;
        $this->removed_user        = $dto->removed_user;
        $this->locked              = $dto->locked;
        $this->locked_date         = $dto->locked_date;
        $this->locked_user         = $dto->locked_user;
        $this->locked_user_name    = $dto->locked_user_name;
        $this->system_record       = $dto->system_record;
        $this->matches             = $dto->matches;
        $this->statistics          = $dto->statistics;
        $this->link                = $dto->link ?? '';
        $this->opt_ins             = $dto->opt_ins;
        $this->wtn                 = $dto->wtn;
        $this->user_pass           = $dto->user_pass ?? '';
        $this->user_nicename       = $dto->user_nicename ?? '';
        $this->user_url            = $dto->user_url ?? '';
        $this->user_activation_key = $dto->user_activation_key ?? '';
        $this->user_login          = $dto->user_login ?? '';
        $this->index               = $dto->index ?? '';
        $this->stats               = $dto->stats;
        $this->matches_won         = $dto->matches_won;
        $this->matches_lost        = $dto->matches_lost;
        $this->win_pct             = $dto->win_pct;
        $this->played              = $dto->played;
        $this->competitions        = $dto->competitions;
        $this->cup                 = $dto->cup;
        $this->league              = $dto->league;
        $this->tournament          = $dto->tournament;
        $this->teams               = $dto->teams;
        $this->club                = $dto->club;
        if ( $dto->team ) {
            $this->team = $dto->team;
        }
        if ( $dto->status ) {
            $this->status = $dto->status;
        }
        if ( $dto->user_status ) {
            $this->user_status = $dto->user_status;
        }
        if ( $dto->entry_id ) {
            $this->entry_id = $dto->entry_id;
        }
        $this->entry            = $dto->entry;
        $this->tournament_entry = $dto->tournament_entry;

        if ( null === $this->age ) {
            $this->calculate_age();
        }
        if ( empty( $this->locked_user_name ) ) {
            $this->locked_user_name = '';
        }
    }

    /**
     * Create from a generic object.
     *
     * @param object $data
     * @return self
     */
    public static function from_object( object $data ): self {
        return Player_Factory::from_object( $data );
    }

    /**
     * Create from an associative array.
     *
     * @param array $data
     * @return self
     */
    public static function from_array( array $data ): self {
        return Player_Factory::from_array( $data );
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return void
     */
    public function set_id( int $id ): void {
        $this->id = $id;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return void
     */
    public function set_email( string $email ): void {
        $this->email = $email;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return void
     */
    public function set_firstname( string $firstname ): void {
        $this->firstname = $firstname;
    }

    /**
     * Set surname
     *
     * @param string $surname
     *
     * @return void
     */
    public function set_surname( string $surname ): void {
        $this->surname = $surname;
    }

    /**
     * Set display name
     *
     * @param string $display_name
     *
     * @return void
     */
    public function set_display_name( string $display_name ): void {
        $this->display_name = $display_name;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return void
     */
    public function set_gender( string $gender ): void {
        $this->gender = $gender;
    }

    /**
     * Set LTA tennis number
     *
     * @param string $btm
     *
     * @return void
     */
    public function set_btm( string $btm ): void {
        $this->btm = $btm;
    }

    /**
     * Set year of birth
     *
     * @param string|null $year_of_birth
     *
     * @return void
     */
    public function set_year_of_birth( ?string $year_of_birth ): void {
        $this->year_of_birth = $year_of_birth;
    }

    /**
     * Set contact number
     *
     * @param string $contactno
     *
     * @return void
     */
    public function set_contactno( string $contactno ): void {
        $this->contactno = $contactno;
    }

    /**
     * Set locked
     *
     * @param bool $locked
     *
     * @return void
     */
    public function set_locked( bool $locked ): void {
        $this->locked = $locked;
    }

    /**
     * Set the locked date
     *
     * @param string $locked_date
     *
     * @return void
     */
    public function set_locked_date( string $locked_date ): void {
        $this->locked_date = $locked_date;
    }

    /**
     * Set locked user
     *
     * @param int $locked_user
     *
     * @return void
     */
    public function set_locked_user( int $locked_user ): void {
        $this->locked_user = $locked_user;
    }
    /**
     * Set the removed date
     *
     * @param string $removed_date
     *
     * @return void
     */
    public function set_removed_date( string $removed_date ): void {
        $this->removed_date = $removed_date;
    }
    /**
     * Set removed user
     *
     * @param int $removed_user
     *
     * @return void
     */
    public function set_removed_user( int $removed_user ): void {
        $this->removed_user = $removed_user;
    }
    /**
     * Get player ID
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function get_firstname(): string {
        return (string) ( $this->firstname ?? '' );
    }

    /**
     * Get surname
     *
     * @return string
     */
    public function get_surname(): string {
        return (string) ( $this->surname ?? '' );
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function get_fullname(): string {
        return (string) ( $this->display_name ?? '' );
    }

    /**
     * Get name
     *
     * @return string
     */
    public function get_name(): string {
        return (string) ( $this->name !== '' ? $this->name : ( $this->display_name ?? '' ) );
    }

    /**
     * Get email
     *
     * @return string|null
     */
    public function get_email(): ?string {
        return $this->user_email;
    }

    /**
     * Get user login
     *
     * @return string
     */
    public function get_login(): string {
        return (string) ( $this->user_login ?? '' );
    }

    /**
     * Get user password
     *
     * @return string
     */
    public function get_password(): string {
        return (string) ( $this->user_pass ?? '' );
    }

    /**
     * Get user registered date
     *
     * @return string
     */
    public function get_date_registered(): string {
        return (string) ( $this->user_registered ?? '' );
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function get_gender(): string {
        return (string) ( $this->gender ?? '' );
    }

    /**
     * Get LTA tennis number
     *
     * @return string|null
     */
    public function get_btm(): ?string {
        return $this->btm;
    }

    /**
     * Get year of birth
     *
     * @return string|null
     */
    public function get_year_of_birth(): ?string {
        return $this->year_of_birth;
    }

    /**
     * Get the contact number
     *
     * @return string|null
     */
    public function get_contactno(): ?string {
        return $this->contactno;
    }

    /**
     * Get locked indicator
     *
     * @return string|null
     */
    public function get_locked(): ?string {
        return $this->locked;
    }

    /**
     * Get locked date
     *
     * @return string|null
     */
    public function get_locked_date(): ?string {
        return $this->locked_date;
    }

    /**
     * Get locked user ID
     *
     * @return int|null
     */
    public function get_locked_user(): ?int {
        return $this->locked_user;
    }
    /**
     * Get removed date
     *
     * @return string|null
     */
    public function get_removed_date(): ?string {
        return $this->removed_date;
    }

    /**
     * Get removed user ID
     *
     * @return int|null
     */
    public function get_removed_user(): ?int {
        return $this->removed_user;
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
     * Get player wtn
     *
     * @return array
     */
    public function get_wtn(): array {
        return $this->wtn;
    }

    /**
     * Get typed Player_Ranking Value Object for a discipline (e.g. 'singles' or 'doubles').
     *
     * @param string $discipline 'singles' or 'doubles' (or 'S', 'D').
     * @return Player_Ranking
     */
    public function get_ranking( string $discipline = 'singles' ): Player_Ranking {
        $disc_lower = strtolower( $discipline );
        $val        = $this->wtn[ $discipline ] 
                   ?? $this->wtn[ $disc_lower ] 
                   ?? $this->wtn[ strtoupper( $discipline ) ] 
                   ?? null;

        if ( null === $val ) {
            if ( 's' === $disc_lower || 'singles' === $disc_lower ) {
                $val = $this->wtn['singles'] ?? $this->wtn['S'] ?? $this->wtn['Singles'] ?? null;
            } elseif ( 'd' === $disc_lower || 'doubles' === $disc_lower ) {
                $val = $this->wtn['doubles'] ?? $this->wtn['D'] ?? $this->wtn['Doubles'] ?? null;
            }
        }

        return Player_Ranking::from_raw( $val, $discipline );
    }

    /**
     * Get player age
     *
     * @return int|null
     */
    public function get_age(): ?int {
        return $this->age;
    }

    public function __serialize(): array {
        return get_object_vars( $this );
    }

    public function __unserialize( array $data ): void {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Set player wtn function
     *
     * @param array $wtn world tennis number array.
     * @return void
     */
    public function set_wtn( array $wtn ): void {
        foreach( $wtn as $match_type => $value ) {
            $this->set_wtn_type( $match_type, $value );
        }
    }
    /**
     * Set player wtn function
     *
     * @param string $match_type match type.
     * @param string $wtn world tennis number.
     * @return void
     */
    public function set_wtn_type( string $match_type, string $wtn ): void {
        $this->wtn[ $match_type ] = $wtn;
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
                $this->opt_ins[] = $opt_in;
            }
        }
    }
}
