<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Queries;

use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;

/**
 * Handles retrieval of detailed fixture information for a fixture details query.
 *
 * Resolves the target fixture by fixture identifier when provided, or by slug
 * criteria when no identifier is available. If a matching fixture is found, it
 * creates a fixture details data transfer object with the requested focus player
 * and team context, then delegates enrichment to the fixture details assembler.
 *
 * Returns null when no fixture can be resolved from the query.
 */
class Get_Fixture_Details_Handler {
    private Fixture_Repository_Interface $repository;
    private Fixture_Details_Assembler $detail_service;

    /**
     * Initialises the service with the fixture repository and details assembler.
     *
     * @param Fixture_Repository_Interface $repository Fixture repository instance.
     * @param Fixture_Details_Assembler    $detail_service Fixture details assembler instance.
     *
     * @return void
     */
    public function __construct(
        Fixture_Repository_Interface $repository,
        Fixture_Details_Assembler $detail_service
    ) {
        $this->repository     = $repository;
        $this->detail_service = $detail_service;
    }

    /**
     * Handles a fixture details query and returns enriched fixture details when found.
     *
     * @param Get_Fixture_Details_Query $query Query containing fixture lookup criteria and optional focus identifiers.
     *
     * @return Fixture_Details_DTO|null Enriched fixture details DTO, or null when no matching fixture is found.
     */
    public function handle( Get_Fixture_Details_Query $query ): ?Fixture_Details_DTO {

        $fixture = null;

        if ( $query->fixture_id ) {
            $fixture = $this->repository->find_by_id( $query->fixture_id );
        } elseif ( ! empty( $query->slug_criteria ) ) {
            $fixture = $this->repository->find_one_by_slug_criteria( $query->slug_criteria );
        }

        if ( ! $fixture ) {
            return null;
        }

        $dto = new Fixture_Details_DTO(
            $fixture,
            $query->focus_player_id,
            $query->focus_team_id
        );

        return $this->detail_service->enrich( $dto );
    }
}
