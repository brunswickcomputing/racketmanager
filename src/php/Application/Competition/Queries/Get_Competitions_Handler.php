<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Competitions_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Tournament_Service $tournament_service,
        private readonly Player_Service $player_service,
        private readonly Player_Query_Service $player_query_service,
        private readonly Competition_Shortcode_Presenter $presenter
    ) {}

    public function handle( Get_Competitions_Query $query ): Shortcode_View_Model {
        if ( ! $query->type ) {
            return Shortcode_View_Model::error( __( 'Competition type not set', 'racketmanager' ) );
        }

        $type  = Seo_Slug::decode( $query->type );
        $args  = [ 'type' => $type ];
        if ( $query->age_group ) {
            $args['age_group'] = $query->age_group;
        }
        $player = null;
        if ( $query->user_id ) {
            try {
                $player = $this->player_service->get_player( $query->user_id );
            } catch ( Player_Not_Found_Exception ) {
                // A WordPress user need not have a player record.
            }
        }

        $user_competitions = [];
        if ( 'tournament' === $type ) {
            $args['orderby']  = [ 'date_end' => 'DESC' ];
            $competitions = $this->tournament_service->get_tournaments( $args );
            foreach ( $competitions as $competition ) {
                $competition->type = $type;
            }
            if ( $player ) {
                $user_competitions = $this->tournament_service->get_tournaments_for_player( $player->get_id() );
            }
        } else {
            $competitions      = $this->competition_service->get_by_criteria( $args );
            $user_competitions = $player ? $this->player_query_service->get_competitions( $player, $args ) : [];
        }

        $competition_type = match ( $type ) {
            'league'     => __( 'Leagues', 'racketmanager' ),
            'cup'        => __( 'Cups', 'racketmanager' ),
            'tournament' => __( 'Tournaments', 'racketmanager' ),
            default      => __( 'Competitions', 'racketmanager' ),
        };

        $user_competition_items = $this->presenter->present_competition_list( $user_competitions, $type, null );
        $competition_items      = $this->presenter->present_competition_list( $competitions, $type, null );

        return new Shortcode_View_Model( template: 'competitions', vars: [
            'competitions'           => $competitions,
            'competition_items'      => $competition_items,
            'type'                   => $competition_type,
            'competition_type'       => $type,
            'user_competitions'      => $user_competitions,
            'user_competition_items' => $user_competition_items,
        ] );
    }
}