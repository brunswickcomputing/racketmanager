<?php

namespace Racketmanager\Domain\Fixture\Services;

use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;

class Fixture_Detail_Service {
    /**
     * Enriches the DTO with additional business logic data required for presentation.
     *
     * @param Fixture_Details_DTO $dto
     * @return Fixture_Details_DTO
     */
    public function enrich( Fixture_Details_DTO $dto ): Fixture_Details_DTO {
        $fixture = $dto->fixture;
        
        // Example enrichment: determine if it's a knockout match
        $dto->enriched_data['is_knockout'] = ! empty( $fixture->get_final() );
        
        // Example enrichment: resolve leagues/seasons if not fully loaded in the aggregate
        // This mirrors some of the logic in Shortcodes_League::show_match
        
        return $dto;
    }
}
