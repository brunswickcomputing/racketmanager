<?php

namespace Racketmanager\Application\Fixture\Queries;

use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Services\Fixture_Detail_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;

class Get_Fixture_Details_Handler {
    private Fixture_Repository_Interface $repository;
    private Fixture_Detail_Service $detail_service;

    public function __construct(
        Fixture_Repository_Interface $repository,
        Fixture_Detail_Service $detail_service
    ) {
        $this->repository = $repository;
        $this->detail_service = $detail_service;
    }

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
