<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function is_object;
use function method_exists;

class Get_Players_Handler {
    public function __construct(
        private readonly Player_Repository_Interface $player_repository,
        private readonly User_Repository_Interface $user_repository,
        private readonly Shortcode_Template_Renderer $template_renderer,
        private readonly Player_Presenter $player_presenter
    ) {}

    public function handle( Get_Players_Query $query ): Shortcode_View_Model {
        $search_results = null;
        if ( ! empty( $query->search_string ) ) {
            $players = $this->player_repository->find_players_summary( [ 'name' => $query->search_string ] );
            $search_results = $this->template_renderer->render( 'players-list', [ 'players' => $players ] );
        }

        $favourites = [];
        if ( $query->is_logged_in && $query->user_id ) {
            $user = $this->user_repository->find( $query->user_id );

            if ( is_object( $user ) ) {
                $favourites = $this->user_repository->get_favourites( $user, 'player' ) ?: [];
            }
        }

        $filename = ! empty( $query->template ) ? 'players-' . $query->template : 'players';

        $player_vm = $this->player_presenter->present_players_directory(
            search_string: $query->search_string ?? '',
            search_results: $search_results,
            favourites: $favourites,
            template: $filename
        );

        return Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() );
    }
}
