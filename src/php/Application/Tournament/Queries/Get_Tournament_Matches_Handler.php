<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Matches_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Matches_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament_id ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $fixture_dates = $this->tournament_service->get_fixture_dates_for_tournament( $tournament );
        $fixture_date  = $query->match_date;

        if ( empty( $fixture_date ) && ! empty( $fixture_dates ) ) {
            $fixture_date = end( $fixture_dates );
        }

        $tournament_fixtures = $this->tournament_service->get_fixtures_by_date_for_tournament( $tournament, $fixture_date );
        $filename            = ( '' !== $query->template ) ? 'matches-' . $query->template : 'matches';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tournament'         => $tournament,
                'match_dates'        => $fixture_dates,
                'tournament_matches' => $tournament_fixtures,
                'current_match_date' => $fixture_date,
                'tab'                => 'matches',
            ],
            'tournament'
        );
    }
}
