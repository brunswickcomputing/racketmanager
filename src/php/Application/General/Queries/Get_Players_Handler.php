<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use function function_exists;
use function is_object;
use function method_exists;
use function Racketmanager\get_user;
use function Racketmanager\player_search;

class Get_Players_Handler {
    public function __construct(
        private readonly ?Player_Presenter $player_presenter = null
    ) {}

    public function handle( Get_Players_Query $query ): Shortcode_View_Model {
        $search_results = null;
        if ( ! empty( $query->search_string ) ) {
            $search_results = function_exists( 'Racketmanager\player_search' )
                ? player_search( $query->search_string )
                : ( function_exists( 'player_search' ) ? \player_search( $query->search_string ) : null );
        }

        $favourites = [];
        if ( $query->is_logged_in && $query->user_id ) {
            $user = function_exists( 'Racketmanager\get_user' )
                ? get_user( $query->user_id )
                : ( function_exists( 'get_user' ) ? \get_user( $query->user_id ) : null );

            if ( is_object( $user ) && method_exists( $user, 'get_favourites' ) ) {
                $favourites = $user->get_favourites( 'player' ) ?: [];
            }
        }

        $filename  = ! empty( $query->template ) ? 'players-' . $query->template : 'players';
        $presenter = $this->player_presenter ?? new Player_Presenter();

        $player_vm = $presenter->present_players_directory(
            search_string: $query->search_string ?? '',
            search_results: $search_results,
            favourites: $favourites,
            template: $filename
        );

        return Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() );
    }
}
