<?php
/**
 * Team API: Team class
 *
 * @package RacketManager
 * @subpackage Team
 */

declare( strict_types=1 );
namespace Racketmanager\Domain\Team;

use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;
use Racketmanager\Domain\Enums\Team_Profile;

/**
 * Class to implement the Team domain entity
 */
class Team {
    /**
     * ID variable
     *
     * @var int|null
     */
    public private(set) ?int $id = null;

    /**
     * Title variable
     *
     * @var string
     */
    public private(set) string $title = '';

    /**
     * Stadium variable
     *
     * @var string|null
     */
    public private(set) ?string $stadium = null;

    /**
     * Profile variable
     *
     * @var string|int|null
     */
    public private(set) string|int|null $profile = null;

    /**
     * Club id variable
     *
     * @var int|null
     */
    public private(set) ?int $club_id = null;

    /**
     * Club object variable
     *
     * @var object|null
     */
    public private(set) ?object $club = null;

    /**
     * Status variable
     *
     * @var string|null
     */
    public private(set) ?string $status = null;

    /**
     * Player variable
     *
     * @var string|null
     */
    public private(set) ?string $player = null;

    /**
     * Player 1 name variable
     *
     * @var string|null
     */
    public private(set) ?string $player_1 = null;

    /**
     * Player 2 name variable
     *
     * @var string|null
     */
    public private(set) ?string $player_2 = null;

    /**
     * Player 1 id variable
     *
     * @var int|null
     */
    public private(set) ?int $player_1_id = null;

    /**
     * Player 2 ID variable
     *
     * @var int|null
     */
    public private(set) ?int $player_2_id = null;

    /**
     * Team type variable
     *
     * @var string
     */
    public private(set) string $type = '';

    /**
     * Team type variable
     *
     * @var string|null
     */
    public private(set) ?string $team_type = null;

    /**
     * Home variable
     *
     * @var mixed
     */
    public private(set) mixed $home = null;

    /**
     * Player id variable
     *
     * @var array
     */
    public private(set) array $player_ids = [];

    /**
     * Players variable
     *
     * @var array
     */
    public private(set) array $players = [];

    /**
     * Team ref variable
     *
     * @var string|null
     */
    public private(set) ?string $team_ref = null;

    /**
     * Info variable
     *
     * @var mixed
     */
    public private(set) mixed $info = null;

    /**
     * Standings variable
     *
     * @var mixed
     */
    public private(set) mixed $standings = [];

    /**
     * Matches variable
     *
     * @var array
     */
    public private(set) array $matches = [];

    /**
     * Constructor
     *
     * @param Team_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Team_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }

        $this->id          = $dto->id;
        $this->title       = $dto->title;
        $this->stadium     = $dto->stadium;
        $this->profile     = $dto->profile;
        $this->club_id     = $dto->club_id;
        $this->club        = $dto->club;
        $this->status      = $dto->status;
        $this->player      = $dto->player;
        $this->player_1    = $dto->player_1;
        $this->player_2    = $dto->player_2;
        $this->player_1_id = $dto->player_1_id;
        $this->player_2_id = $dto->player_2_id;
        $this->type        = $dto->type;
        $this->team_type   = $dto->team_type;
        $this->home        = $dto->home;
        $this->player_ids  = $dto->player_ids;
        $this->players     = $dto->players;
        $this->team_ref    = $dto->team_ref;
        $this->info        = $dto->info;
        $this->standings   = $dto->standings;
        $this->matches     = $dto->matches;

        if ( ! empty( $this->title ) && str_contains( $this->title, '_' ) ) {
            $placeholder = Team_Placeholder_Ref::parse( $this->title );
            if ( null !== $placeholder ) {
                $this->team_ref = $this->title;
                $this->title    = $placeholder->default_label();
            }
        }
    }

    /**
     * Create an instance from an object
     *
     * @param object $row Object containing team information.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Team_Factory::from_object( $row );
    }

    /**
     * Create an instance from array data
     *
     * @param array $data Array containing team information.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Team_Factory::from_array( $data );
    }

    /**
     * Get row id
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int|null $insert_id Team ID.
     *
     * @return void
     */
    public function set_id( ?int $insert_id ): void {
        $this->id = $insert_id;
    }

    /**
     * Get team name
     *
     * @return string
     */
    public function get_name(): string {
        return $this->title;
    }

    /**
     * Set name
     *
     * @param string $name Team name.
     *
     * @return void
     */
    public function set_name( string $name ): void {
        $this->title = $name;
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function get_title(): ?string {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title Team title.
     *
     * @return void
     */
    public function set_title( string $title ): void {
        $this->title = $title;
    }

    /**
     * Get club id
     *
     * @return int|null
     */
    public function get_club_id(): ?int {
        return $this->club_id;
    }

    /**
     * Set club id
     *
     * @param int|null $club_id Club ID.
     *
     * @return void
     */
    public function set_club_id( ?int $club_id ): void {
        $this->club_id = $club_id;
    }

    /**
     * Get club object
     *
     * @return object|null
     */
    public function get_club(): ?object {
        return $this->club;
    }

    /**
     * Set club object
     *
     * @param object|null $club Club object.
     *
     * @return void
     */
    public function set_club( ?object $club ): void {
        $this->club = $club;
    }

    /**
     * Get stadium name
     *
     * @return string|null
     */
    public function get_stadium(): ?string {
        return $this->stadium;
    }

    /**
     * Set stadium name
     *
     * @param string|null $stadium Stadium name.
     *
     * @return void
     */
    public function set_stadium( ?string $stadium ): void {
        $this->stadium = $stadium;
    }

    /**
     * Get team type
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Set team type
     *
     * @param string $type Team type.
     *
     * @return void
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }

    /**
     * Get team type
     *
     * @return string|null
     */
    public function get_team_type(): ?string {
        return $this->team_type;
    }

    /**
     * Set team type
     *
     * @param string|null $team_type Team type.
     *
     * @return void
     */
    public function set_team_type( ?string $team_type ): void {
        $this->team_type = $team_type;
    }

    /**
     * Get team players
     *
     * @return array
     */
    public function get_players(): array {
        return $this->players;
    }

    /**
     * Set team players
     *
     * @param array $players Team players.
     *
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Get player IDs
     *
     * @return array
     */
    public function get_player_ids(): array {
        return $this->player_ids;
    }

    /**
     * Set player IDs
     *
     * @param array $player_ids Player IDs.
     *
     * @return void
     */
    public function set_player_ids( array $player_ids ): void {
        $this->player_ids = $player_ids;
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function get_status(): ?string {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string|null $status Team status.
     *
     * @return void
     */
    public function set_status( ?string $status ): void {
        $this->status = $status;
    }

    /**
     * Get profile
     *
     * @return string|null
     */
    public function get_profile(): ?string {
        return null !== $this->profile ? (string) $this->profile : null;
    }

    /**
     * Set profile
     *
     * @param string|int|null $profile Team profile.
     *
     * @return void
     */
    public function set_profile( string|int|null $profile ): void {
        $this->profile = $profile;
    }

    /**
     * Get profile as typed backed Enum
     *
     * @return Team_Profile|null
     */
    public function get_profile_enum(): ?Team_Profile {
        return null !== $this->profile ? Team_Profile::from_value( (int) $this->profile ) : null;
    }

    /**
     * Set profile from typed backed Enum
     *
     * @param Team_Profile $profile Team profile.
     *
     * @return void
     */
    public function set_profile_enum( Team_Profile $profile ): void {
        $this->profile = $profile->value;
    }

    /**
     * Check if team has player
     *
     * @param int $player_id Player ID.
     *
     * @return bool
     */
    public function has_player( int $player_id ): bool {
        return -1 !== $this->id && (
                array_any(
                    $this->players,
                    fn( $player ) => (int) ( is_object( $player ) && method_exists( $player, 'get_id' ) ? $player->get_id() : ( $player->id ?? null ) ) === $player_id
                ) || in_array( $player_id, array_map( 'intval', $this->player_ids ), true )
            );
    }

    /**
     * Get player
     *
     * @return string|null
     */
    public function get_player(): ?string {
        return $this->player;
    }

    /**
     * Set player
     *
     * @param string|null $player Player.
     *
     * @return void
     */
    public function set_player( ?string $player ): void {
        $this->player = $player;
    }

    /**
     * Get player 1 name
     *
     * @return string|null
     */
    public function get_player_1(): ?string {
        return $this->player_1;
    }

    /**
     * Set player 1 name
     *
     * @param string|null $player_1 Player 1 name.
     *
     * @return void
     */
    public function set_player_1( ?string $player_1 ): void {
        $this->player_1 = $player_1;
    }

    /**
     * Get player 2 name
     *
     * @return string|null
     */
    public function get_player_2(): ?string {
        return $this->player_2;
    }

    /**
     * Set player 2 name
     *
     * @param string|null $player_2 Player 2 name.
     *
     * @return void
     */
    public function set_player_2( ?string $player_2 ): void {
        $this->player_2 = $player_2;
    }

    /**
     * Get player 1 ID
     *
     * @return int|null
     */
    public function get_player_1_id(): ?int {
        return $this->player_1_id;
    }

    /**
     * Set player 1 ID
     *
     * @param int|null $player_1_id Player 1 ID.
     *
     * @return void
     */
    public function set_player_1_id( ?int $player_1_id ): void {
        $this->player_1_id = $player_1_id;
    }

    /**
     * Get player 2 ID
     *
     * @return int|null
     */
    public function get_player_2_id(): ?int {
        return $this->player_2_id;
    }

    /**
     * Set player 2 ID
     *
     * @param int|null $player_2_id Player 2 ID.
     *
     * @return void
     */
    public function set_player_2_id( ?int $player_2_id ): void {
        $this->player_2_id = $player_2_id;
    }

    /**
     * Get home
     *
     * @return mixed
     */
    public function get_home(): mixed {
        return $this->home;
    }

    /**
     * Set home
     *
     * @param mixed $home Home value.
     *
     * @return void
     */
    public function set_home( mixed $home ): void {
        $this->home = $home;
    }

    /**
     * Get team ref
     *
     * @return string|null
     */
    public function get_team_ref(): ?string {
        return $this->team_ref;
    }

    /**
     * Set team ref
     *
     * @param string|null $team_ref Team reference.
     *
     * @return void
     */
    public function set_team_ref( ?string $team_ref ): void {
        $this->team_ref = $team_ref;
    }

    /**
     * Get info
     *
     * @return mixed
     */
    public function get_info(): mixed {
        return $this->info;
    }

    /**
     * Set info
     *
     * @param mixed $info Info value.
     *
     * @return void
     */
    public function set_info( mixed $info ): void {
        $this->info = $info;
    }

    /**
     * Get standings
     *
     * @return mixed
     */
    public function get_standings(): mixed {
        return $this->standings;
    }

    /**
     * Set standings
     *
     * @param mixed $standings Standings value.
     *
     * @return void
     */
    public function set_standings( mixed $standings ): void {
        $this->standings = $standings;
    }

    /**
     * Get matches
     *
     * @return array
     */
    public function get_matches(): array {
        return $this->matches;
    }

    /**
     * Set matches
     *
     * @param array $matches Matches array.
     *
     * @return void
     */
    public function set_matches( array $matches ): void {
        $this->matches = $matches;
    }

    /**
     * Check if team is active
     *
     * @return bool
     */
    public function is_active(): bool {
        return 'active' === $this->status;
    }

    /**
     * Check if team is a placeholder
     *
     * @return bool
     */
    public function is_placeholder(): bool {
        return ! empty( $this->team_ref ) || ( ! empty( $this->title ) && str_contains( $this->title, '_' ) && null !== Team_Placeholder_Ref::parse( $this->title ) );
    }

    /**
     * Check if team is a Bye team
     *
     * @return bool
     */
    public function is_bye(): bool {
        return -1 === $this->id || 'Bye' === $this->title;
    }
}
