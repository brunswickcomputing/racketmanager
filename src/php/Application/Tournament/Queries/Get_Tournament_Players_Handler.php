<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Entry_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;
use function is_array;

class Get_Tournament_Players_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Tournament_Presenter $tournament_presenter,
        private readonly Player_Presenter $player_presenter
    ) {}

    public function handle( Get_Tournament_Players_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament_id ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        if ( $query->player_id ) {
            try {
                $tournament_player = $this->tournament_service->get_player_details_for_tournament( $tournament, $query->player_id );
            } catch ( Player_Not_Found_Exception|Tournament_Entry_Not_Found_Exception $e ) {
                return Shortcode_View_Model::for_error( $e->getMessage() );
            }

            if ( ! empty( $tournament_player->matches ) && is_array( $tournament_player->matches ) ) {
                $tournament_player->matches = $this->tournament_presenter->present_match_cards(
                    $tournament_player->matches,
                    $tournament,
                    $tournament_player
                );
            }

            $player_vm = $this->player_presenter->present_player( $tournament_player );

            return Shortcode_View_Model::for_template(
                'player',
                [
                    'tournament'         => $tournament,
                    'tournament_player'  => $tournament_player,
                    'player_vm'          => $player_vm,
                    'player_entry_rows'  => $this->tournament_presenter->present_player_entry_rows(
                        $tournament,
                        is_array( $tournament_player->teams ?? null ) ? $tournament_player->teams : []
                    ),
                    'tournament_link'    => $this->tournament_presenter->get_link( $tournament ),
                    'tab'                => 'players',
                ],
                'tournament'
            );
        }

        $tournament_players = $this->tournament_service->get_players_for_tournament( $tournament->get_id() ) ?? [];
        $tournament_link    = $this->tournament_presenter->get_link( $tournament );

        return Shortcode_View_Model::for_template(
            'players',
            [
                'tournament'         => $tournament,
                'tournament_players' => $tournament_players,
                'tournament_link'    => $tournament_link,
                'player_link'        => $tournament_link . 'player/',
                'player_links'       => $this->tournament_presenter->present_player_list_links( $tournament, $tournament_players ),
                'tab'                => 'players',
            ],
            'tournament'
        );
    }
}
