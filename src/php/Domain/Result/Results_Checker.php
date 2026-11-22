<?php
declare( strict_types=1 );
/**
 * Results_Checker API: Results_Checker class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Results_Checker
 */

namespace Racketmanager\Domain\Result;

use Racketmanager\Domain\DTO\Result\Results_Checker_Data;
use Racketmanager\Domain\Enums\Results_Checker_Status;

/**
 * Class to implement the result checker object
 */
class Results_Checker {
    /**
     * @var int
     */
    public private(set) int $id = 0;

    /**
     * @var int
     */
    public private(set) int $match_id = 0;

    /**
     * @var int
     */
    public private(set) int $team_id = 0;

    /**
     * @var int|null
     */
    public private(set) ?int $player_id = null;

    /**
     * @var int
     */
    public private(set) int $league_id = 0;

    /**
     * @var int|null
     */
    public private(set) ?int $rubber_id = null;

    /**
     * @var int|null
     */
    public private(set) ?int $status = null;

    /**
     * @var string
     */
    public private(set) string $description = '';

    /**
     * @var string|null
     */
    public private(set) ?string $updated_date = null;

    /**
     * @var int|null
     */
    public private(set) ?int $updated_user = null;

    /**
     * Results_Checker constructor.
     *
     * @param Results_Checker_Data|null $data Data DTO.
     */
    public function __construct( ?Results_Checker_Data $data = null ) {
        if ( ! $data ) {
            return;
        }

        $this->id           = $data->id ?? 0;
        $this->league_id    = $data->league_id ?? 0;
        $this->match_id     = $data->match_id ?? 0;
        $this->team_id      = $data->team_id ?? 0;
        $this->player_id    = $data->player_id;
        $this->rubber_id    = $data->rubber_id;
        $this->description  = $data->description ?? '';
        $this->status       = $data->status;
        $this->updated_date = $data->updated_date;
        $this->updated_user = $data->updated_user;
    }

    /**
     * Create from a generic object (e.g. database row) via Results_Checker_Factory.
     *
     * @param object $row results checker row.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Results_Checker_Factory::from_object( $row );
    }

    /**
     * Create from an array via Results_Checker_Factory.
     *
     * @param array $data results checker data.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Results_Checker_Factory::from_array( $data );
    }

    /**
     * Get entity ID.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Set entity ID.
     *
     * @param int $id Entity ID.
     */
    public function set_id( int $id ): void {
        $this->id = $id;
    }

    /**
     * Get match ID.
     *
     * @return int
     */
    public function get_match_id(): int {
        return $this->match_id;
    }

    /**
     * Set match ID.
     *
     * @param int $match_id Match ID.
     */
    public function set_match_id( int $match_id ): void {
        $this->match_id = $match_id;
    }

    /**
     * Get team ID.
     *
     * @return int
     */
    public function get_team_id(): int {
        return $this->team_id;
    }

    /**
     * Set team ID.
     *
     * @param int $team_id Team ID.
     */
    public function set_team_id( int $team_id ): void {
        $this->team_id = $team_id;
    }

    /**
     * Get player ID.
     *
     * @return int|null
     */
    public function get_player_id(): ?int {
        return $this->player_id;
    }

    /**
     * Set player ID.
     *
     * @param int|null $player_id Player ID.
     */
    public function set_player_id( ?int $player_id ): void {
        $this->player_id = $player_id;
    }

    /**
     * Get league ID.
     *
     * @return int
     */
    public function get_league_id(): int {
        return $this->league_id;
    }

    /**
     * Set league ID.
     *
     * @param int $league_id League ID.
     */
    public function set_league_id( int $league_id ): void {
        $this->league_id = $league_id;
    }

    /**
     * Get rubber ID.
     *
     * @return int|null
     */
    public function get_rubber_id(): ?int {
        return $this->rubber_id;
    }

    /**
     * Set rubber ID.
     *
     * @param int|null $rubber_id Rubber ID.
     */
    public function set_rubber_id( ?int $rubber_id ): void {
        $this->rubber_id = $rubber_id;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function get_status(): ?int {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param int|null $status Status value.
     */
    public function set_status( ?int $status ): void {
        $this->status = $status;
    }

    /**
     * Get error description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Set error description.
     *
     * @param string $description Description.
     */
    public function set_description( string $description ): void {
        $this->description = $description;
    }

    /**
     * Get updated date.
     *
     * @return string|null
     */
    public function get_updated_date(): ?string {
        return $this->updated_date;
    }

    /**
     * Set updated date.
     *
     * @param string|null $updated_date Updated date.
     */
    public function set_updated_date( ?string $updated_date ): void {
        $this->updated_date = $updated_date;
    }

    /**
     * Get updated user ID.
     *
     * @return int|null
     */
    public function get_updated_user(): ?int {
        return $this->updated_user;
    }

    /**
     * Set updated user ID.
     *
     * @param int|null $updated_user Updated user ID.
     */
    public function set_updated_user( ?int $updated_user ): void {
        $this->updated_user = $updated_user;
    }

    /**
     * Mark the error as approved.
     *
     * @param int|null $user_id User ID performing the approval.
     */
    public function approve( ?int $user_id = null ): void {
        $this->status       = Results_Checker_Status::APPROVED->value;
        $this->updated_user = $user_id;
    }

    /**
     * Mark the error as handled (penalties applied).
     *
     * @param int|null $user_id User ID handling the error.
     */
    public function handle( ?int $user_id = null ): void {
        $this->status       = Results_Checker_Status::HANDLED->value;
        $this->updated_user = $user_id;
    }

    /**
     * Check if status is approved.
     *
     * @return bool
     */
    public function is_approved(): bool {
        return Results_Checker_Status::APPROVED->value === $this->status;
    }

    /**
     * Check if status is handled.
     *
     * @return bool
     */
    public function is_handled(): bool {
        return Results_Checker_Status::HANDLED->value === $this->status;
    }

    /**
     * Check if status is outstanding.
     *
     * @return bool
     */
    public function is_outstanding(): bool {
        return null === $this->status || Results_Checker_Status::OUTSTANDING->value === $this->status;
    }

    /**
     * Check if this check represents a player-level error.
     *
     * @return bool
     */
    public function is_player_error(): bool {
        return ! empty( $this->player_id );
    }

    /**
     * Check if this check represents a match-level error.
     *
     * @return bool
     */
    public function is_match_error(): bool {
        return empty( $this->player_id );
    }
}
