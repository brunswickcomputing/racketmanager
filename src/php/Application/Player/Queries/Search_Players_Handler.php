<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Queries;

use Racketmanager\Application\General\DTOs\Alert_Result;
use Racketmanager\Application\General\DTOs\Player_Search_Result;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use function __;

/**
 * Handler for searching players.
 */
class Search_Players_Handler {
    public function __construct(
        private readonly Get_Player_Search_Handler $player_search_handler,
        private readonly Get_Alert_Handler $alert_handler
    ) {
    }

    /**
     * Handle searching players.
     *
     * @param Search_Players_Query $query
     * @return Player_Search_Result|Alert_Result
     */
    public function handle( Search_Players_Query $query ): Player_Search_Result|Alert_Result {
        if ( ! empty( $query->search_string ) ) {
            return $this->player_search_handler->handle(
                new Get_Player_Search_Query( search: $query->search_string )
            );
        }

        return $this->alert_handler->handle(
            new Get_Alert_Query( msg: __( 'Search string not supplied', 'racketmanager' ), type: 'danger' )
        );
    }
}
