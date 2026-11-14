<?php
/**
 * Message presenter port
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Presentation/Ports
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Presentation\Ports;

use Racketmanager\Presentation\View_Models\Message_View_Model;
use Racketmanager\Presentation\View_Models\Messages_List_View_Model;

/**
 * Interface for presenting message view models.
 */
interface Message_Presenter_Interface {

    /**
     * Present single message view model.
     *
     * @param object $message Message object.
     *
     * @return Message_View_Model
     */
    public function present_message( object $message ): Message_View_Model;

    /**
     * Present list of messages view model.
     *
     * @param array $messages_data Messages data array.
     *
     * @return Messages_List_View_Model
     */
    public function present_messages( array $messages_data ): Messages_List_View_Model;
}
