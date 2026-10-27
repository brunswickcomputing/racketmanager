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

/**
 * Class to implement the results report object
 */
class Results_Report {
    /**
     * Id
     *
     * @var int
     */
    public int $id = 0;
    /**
     * Season
     *
     * @var int
     */
    public int $match_id;
    /**
     * Results report object stored in database as string
     *
     * @var string|false
     */
    public string|false $result_object;
    /**
     * Results report object
     *
     * @var object
     */
    public mixed $data;

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
     * Create from a generic object (e.g., database row) without factory validation.
     *
     * @param object $row results report row.
     *
     * @return static
     */
    public static function from_object( object $row ): static {
        return new static( Results_Report_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Create from an array without factory validation.
     *
     * @param array $data results report data.
     *
     * @return static
     */
    public static function from_array( array $data ): static {
        return static::from_object( (object) $data );
    }
}
