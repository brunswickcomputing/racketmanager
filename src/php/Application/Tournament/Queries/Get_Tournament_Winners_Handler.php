<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Winners_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Winners_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament_id ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
            $winners    = $this->tournament_service->get_winners_for_tournament( $tournament );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $filename = ( '' !== $query->template ) ? 'winners-' . $query->template : 'winners';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tournament' => $tournament,
                'winners'    => $winners,
            ],
            'tournament'
        );
    }
}
