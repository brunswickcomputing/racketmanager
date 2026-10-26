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

/**
 * Class to implement the season object
 */
class Season {
    public string $name;
    private int|null $id = null;

    /**
     * Construct class instance
     *
     * @param object|null $season invoice object.
     */
    public function __construct( ?object $season = null ) {
        if ( is_null( $season ) ) {
            return;
        }
        $this->id   = isset( $season->id ) ? (int) $season->id : null;
        $this->name = (string) ( $season->name ?? '' );
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
