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

    public function remove( $competition_id ) {
        try {
            $competition = $this->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return;
        }
        $this->competition_repository->delete( $competition_id );
        $title     = $competition->name . ' ' . __( 'Tables', 'racketmanager' );
        $page_name = sanitize_title_with_dashes( $title );
        $this->delete_racketmanager_page( $page_name );
        $title     = $competition->name;
        $page_name = sanitize_title_with_dashes( $title );
        $this->delete_racketmanager_page( $page_name );
    }

    /**
     * Delete page
     *
     * @param string $page_name page name.
     */
    private function delete_racketmanager_page( string $page_name ): void {
        $option  = 'racketmanager_page_' . $page_name . '_id';
        $page_id = intval( get_option( $option ) );
        // Force delete this so the Title/slug "Menu" can be used again.
        if ( $page_id ) {
            wp_delete_post( $page_id, true );
            delete_option( $option );
        }
    }
}
