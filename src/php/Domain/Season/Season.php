<?php
declare( strict_types=1 );
/**
 * Season API: season class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */
namespace Racketmanager\Domain\Season;

use Racketmanager\Domain\DTO\Season\Season_Hydration_DTO;

/**
 * Class to implement the season object
 */
class Season {
    public string $name;
    private int|null $id = null;

    /**
     * Construct class instance
     *
     * @param Season_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Season_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        $this->id   = $dto->id;
        $this->name = $dto->name;
    }

    /**
     * Create from a generic object (e.g. database row) without factory validation.
     *
     * @param object $row
     * @return static
     */
    public static function from_object( object $row ): static {
        return new static( Season_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Create from an array without factory validation.
     *
     * @param array $data
     * @return static
     */
    public static function from_array( array $data ): static {
        return static::from_object( (object) $data );
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_id(): null|int {
        return $this->id;
    }

    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function set_name( string $name ): void {
        $this->name = $name;
    }

}
