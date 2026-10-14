<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

use Racketmanager\Application\Matches\DTOs\Match_List_Result;
use Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;

/**
 * Handles the [latest_results] query.
 */
class Get_Latest_Results_Handler {
    public function __construct(
        private readonly Fixture_Listing_Finder_Interface $finder,
        private readonly Club_Repository_Interface $club_repository,
        private readonly Competition_Repository_Interface $competition_repository
    ) {}

    public function handle( Get_Latest_Results_Query $query ): Match_List_Result {
        $type = Competition_Type::tryFrom( $query->competition_type );
        if ( null === $type || $query->has_invalid_filter ) {
            return new Match_List_Result();
        }

        $club_id = $query->club_id;
        if ( null !== $query->club_shortcode ) {
            $club = $this->club_repository->find_by_id( $query->club_shortcode, 'shortcode' );
            if ( ! $club ) {
                // An unknown club in the URL must not widen the listing to every club.
                return new Match_List_Result();
            }
            $club_id = (int) $club->id;
        }

        $competition_id = $query->competition_id;
        if ( null !== $query->competition_name ) {
            $competition = $this->competition_repository->find_by_name( $query->competition_name );
            if ( $competition ) {
                $competition_id = (int) $competition->id;
            }
        }

        return new Match_List_Result(
            $this->finder->find_latest_results( $query->days, $type, $club_id, $competition_id, $query->age_group )
        );
    }
}
