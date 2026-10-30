<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Latest_Tournament_Result;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;

class Get_Latest_Tournament_Handler {
    private const BASE_TOURNAMENTS = '/tournaments/';

    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Latest_Tournament_Query $query ): Latest_Tournament_Result {
        $age_group         = $query->age_group;
        $active_tournament = $this->tournament_service->get_active_tournament( $age_group );

        if ( $active_tournament ) {
            $new_url = '/tournament/' . Util::seo_url( $active_tournament->get_name() ) . '/';
        } elseif ( $age_group ) {
            $new_url = self::BASE_TOURNAMENTS . $age_group . '/';
        } else {
            $new_url = self::BASE_TOURNAMENTS;
        }

        return new Latest_Tournament_Result(
            tournament: $active_tournament,
            redirect_url: $new_url
        );
    }
}
