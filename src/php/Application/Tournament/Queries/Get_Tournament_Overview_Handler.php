<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Overview_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Tournament_Presenter $tournament_presenter
    ) {}

    public function handle( Get_Tournament_Overview_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament_id ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament_overview( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $dates_vm = $this->tournament_presenter->present_dates( $tournament );

        $filename = ( '' !== $query->template ) ? 'overview-' . $query->template : 'overview';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tournament'       => $tournament,
                'tournament_dates' => $dates_vm,
            ],
            'tournament'
        );
    }
}
