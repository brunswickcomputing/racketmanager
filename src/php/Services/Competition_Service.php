<?php
/**
 * Competition_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Competition;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\Duplicate_Competition_Exception;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use stdClass;

/**
 * Class to implement the Competition Management Service
 */
class Competition_Service {
    private Competition_Repository $competition_repository;
    private Event_Repository $event_repository;

    /**
     * Constructor
     *
     * @param Competition_Repository $competition_repository
     */
    public function __construct( Competition_Repository $competition_repository, Event_Repository $event_repository ) {
        $this->competition_repository = $competition_repository;
        $this->event_repository       = $event_repository;
    }

    public function get_by_id( null|string|int $competition_id ): Competition {
        $competition = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( sprintf( __( 'Competition %s not found', 'racketmanager' ), $competition_id ) );
        }
        return $competition;
    }

    public function get_all(): array {
        return $this->competition_repository->find_all();
    }

    public function get_by_criteria( array $criteria ): array {
        return $this->competition_repository->find_by( $criteria );
    }

    public function find_competitions_with_summary( ?string $age_group, ?string $type ): array {
        return $this->competition_repository->find_competitions_with_summary( $age_group, $type );
    }
    public function create( stdClass $competition ): Competition {
        $competition_check = $this->competition_repository->find_by( [ 'name' => $competition->name ] );
        if ( $competition_check ) {
            throw new Duplicate_Competition_Exception( __( 'Competition already exists', 'racketmanager' ) );
        }
        if ( 'league' === $competition->type ) {
            $mode       = 'default';
            $entry_type = 'team';
        } elseif ( 'cup' === $competition->type ) {
            $mode       = 'championship';
            $entry_type = 'team';
        } elseif ( 'tournament' === $competition->type ) {
            $mode       = 'championship';
            $entry_type = 'player';
        }
        if ( ! empty( $mode ) && ! empty( $entry_type ) ) {
            $competition->settings = array(
                'mode'       => $mode,
                'entry_type' => $entry_type,
            );
        }
        $competition = new Competition( $competition );
        $this->competition_repository->save( $competition );
        return $competition;
    }

    public function update_details( int $competition_id, Competition $competition_data ): int {
        $competition_check = $this->competition_repository->find_by_id( $competition_id );
        if ( ! $competition_check ) {
            throw new Competition_Not_Found_Exception( sprintf( __( 'Competition %s not found', 'racketmanager' ), $competition_id ) );
        }
        $result = $this->competition_repository->save( $competition_data );
        if ( false === $result ) {
            throw new Database_Operation_Exception( __( 'Failed to update competition', 'racketmanager' ) );
        }
        return ( int ) $result; // Returns 1 if updated, 0 if no change
    }

    public function remove( $competition_id ): void {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception ) {
            return;
        }
        $events = $this->event_repository->find_by_competition_id( $competition->get_id() );
        foreach ( $events as $event ) {
            $this->event_repository->delete( $event->id );
        }
        $this->competition_repository->delete( $competition_id );
    }

}
