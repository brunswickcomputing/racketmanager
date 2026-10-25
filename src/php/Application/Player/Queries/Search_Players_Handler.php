<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Queries;

use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use function __;

/**
 * Handler for searching players.
 */
class Search_Players_Handler {
    public function __construct(
        private readonly Get_Player_Search_Handler $player_search_handler,
        private readonly Get_Alert_Handler $alert_handler,
        private readonly Shortcode_View_Presenter $view_presenter
    ) {
    }

    /**
     * Handle searching players.
     *
     * @param Search_Players_Query $query
     * @return string HTML output
     */
    public function handle( Search_Players_Query $query ): string {
        if ( ! empty( $query->search_string ) ) {
            $view_model = $this->player_search_handler->handle(
                new Get_Player_Search_Query( search: $query->search_string )
            );

            return $this->view_presenter->render( $view_model );
        }

        $view_model = $this->alert_handler->handle(
            new Get_Alert_Query( msg: __( 'Search string not supplied', 'racketmanager' ), type: 'danger' )
        );

        return $this->view_presenter->render( $view_model );
    }
}
