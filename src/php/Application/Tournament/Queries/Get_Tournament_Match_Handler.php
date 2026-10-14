<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Match_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Fixture_Service $fixture_service
    ) {}

    public function handle( Get_Tournament_Match_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament || ! $query->fixture_id ) {
            return Shortcode_View_Model::for_error( __( 'Fixture not found', 'racketmanager' ) );
        }

        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details_by_name( $query->tournament );
            $tournament         = $tournament_details->tournament;
            $fixture            = $this->fixture_service->get_tournament_fixture_with_details( $query->fixture_id );
        } catch ( Tournament_Not_Found_Exception|Fixture_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $is_update_allowed = $this->fixture_service->is_update_allowed( $query->fixture_id );

        return Shortcode_View_Model::for_template(
            'match-tournament',
            [
                'tournament'        => $tournament,
                'fixture_details'   => $fixture,
                'is_update_allowed' => $is_update_allowed,
            ],
            false
        );
    }
}
