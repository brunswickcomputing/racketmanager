<?php
/**
 * Tournament overview action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Overview
 */

namespace Racketmanager\Services\Admin\Overview;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Overview\Tournament_Overview_Action_Request_DTO;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

class Tournament_Overview_Action_Dispatcher {

    public function __construct(
        private Tournament_Overview_Action_Handler_Interface $handler,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    public function handle( Tournament_Overview_Action_Request_DTO $dto ): Action_Result_DTO {
        $post = $dto->post;

        if ( isset( $post['contactTeam'] ) || isset( $post['contactTeamActive'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

            return $this->handler->contact_teams( $dto );
        }

        return new Action_Result_DTO();
    }
}
