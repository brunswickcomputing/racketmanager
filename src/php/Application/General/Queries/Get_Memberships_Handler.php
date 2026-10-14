<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use function __;

class Get_Memberships_Handler {
    public function __construct(
        private readonly ?Player_Service $player_service = null,
        private readonly ?Registration_Service $registration_service = null
    ) {}

    public function handle( Get_Memberships_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Shortcode_View_Model::for_error( __( 'You must be logged in to view memberships', 'racketmanager' ) );
        }

        if ( ! $this->player_service || ! $this->registration_service ) {
            return Shortcode_View_Model::for_error( __( 'Player not found', 'racketmanager' ) );
        }

        try {
            $player                         = $this->player_service->get_player( $query->user_id );
            $template_args['clubs']         = $this->registration_service->get_clubs_for_player( $player->get_id() );
            $template_args['clubs_archive'] = $this->registration_service->get_clubs_for_player( $player->get_id(), 'removed' );
            $filename                       = ! empty( $query->template ) ? 'player-clubs-' . $query->template : 'player-clubs';

            return Shortcode_View_Model::for_template( $filename, $template_args, 'account' );
        } catch ( Player_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }
    }
}
