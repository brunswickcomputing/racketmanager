<?php
declare( strict_types=1 );
/**
 * Results_Report API: Results_Report class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Results_Report
 */

namespace Racketmanager\Domain\Communication;

use Racketmanager\Domain\DTO\Communication\Results_Report_Hydration_DTO;
use Racketmanager\Domain\Value_Objects\Json_Encoder;

/**
 * Class to implement the results report object
 */
class Results_Report {
    /**
     * Id
     *
     * @var int
     */
    public private(set) int $id = 0;
    /**
     * Match ID
     *
     * @var int
     */
    public private(set) int $match_id;
    /**
     * Results report object stored in database as string
     *
     * @var string|false
     */
    public private(set) string|false $result_object;
    /**
     * Results report object
     *
     * @var mixed
     */
    public private(set) mixed $data;

    /**
     * Construct class instance
     *
     * @param Results_Report_Hydration_DTO|null $dto hydration DTO (keeps `result_object` and `data` in sync).
     */
    public function __construct( ?Results_Report_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        if ( null !== $dto->id ) {
            $this->id = $dto->id;
        }
        if ( null !== $dto->match_id ) {
            $this->match_id = $dto->match_id;
        }
        if ( null !== $dto->result_object ) {
            $this->result_object = $dto->result_object;
        }
        if ( null !== $dto->data || is_string( $dto->result_object ) ) {
            $this->data = $dto->data;
        }
    }

    /**
     * Create from a generic object (e.g., database row) via Results_Report_Factory.
     *
     * @param object $row results report row.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Results_Report_Factory::from_object( $row );
    }

    /**
     * Create from an array via Results_Report_Factory.
     *
     * @param array $data results report data.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Results_Report_Factory::from_array( $data );
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
     * @return int|null
     */
    public function get_match_id(): ?int {
        return $this->match_id ?? null;
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
     * Get result object string.
     *
     * @return string|false|null
     */
    public function get_result_object(): string|false|null {
        return $this->result_object ?? null;
    }

    /**
     * Set result object string and sync decoded data.
     *
     * @param string|false $result_object Result object JSON string or false.
     */
    public function set_result_object( string|false $result_object ): void {
        $this->result_object = $result_object;
        $this->data          = is_string( $result_object ) ? json_decode( $result_object ) : null;
    }

    /**
     * Get decoded report data.
     *
     * @return mixed
     */
    public function get_data(): mixed {
        return $this->data ?? null;
    }

    /**
     * Set report data and sync encoded result object.
     *
     * @param mixed $data Decoded data payload.
     */
    public function set_data( mixed $data ): void {
        $this->data          = $data;
        $this->result_object = null !== $data ? Json_Encoder::encode( $data ) : false;
    }

    /**
     * Check whether report has non-empty decoded data.
     *
     * @return bool
     */
    public function has_data(): bool {
        return isset( $this->data ) && ! empty( $this->data );
    }

    /**
     * Check whether report has non-empty result object string.
     *
     * @return bool
     */
    public function has_report(): bool {
        return isset( $this->result_object ) && ! empty( $this->result_object );
    }

    /**
     * Check whether report belongs to a given match.
     *
     * @param int $match_id Match ID.
     *
     * @return bool
     */
    public function is_for_match( int $match_id ): bool {
        return isset( $this->match_id ) && $this->match_id === $match_id;
    }
}
