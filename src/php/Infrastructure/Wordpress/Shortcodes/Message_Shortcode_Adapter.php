<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Query;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Query;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\Presenters\Message_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use function is_array;
use function is_object;

/**
 * Driving adapter for Message shortcodes.
 */
class Message_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_Messages_Handler $messages_handler,
        private readonly Get_Message_Handler $message_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        private readonly ?Security_Service_Interface $security_service = null,
        private readonly ?Message_Presenter $message_presenter = null
    ) {}

    public function show_messages( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null ) ) : null;

        $query = Get_Messages_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $is_logged_in,
            $user_id
        );

        $result = $this->messages_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        $presenter   = $this->message_presenter ?? new Message_Presenter();
        $messages_vm = $presenter->present_messages( $result->messages );
        $filename    = ! empty( $result->template ) ? 'messages-' . $result->template : 'messages';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [
            'messages' => $messages_vm,
        ], 'account' ) );
    }

    public function show_message( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null ) ) : null;

        $query = Get_Message_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $is_logged_in,
            $user_id
        );

        $result = $this->message_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        $presenter  = $this->message_presenter ?? new Message_Presenter();
        $vm_message = $result->message ? $presenter->present_message( $result->message ) : null;
        $filename   = ! empty( $result->template ) ? 'message-' . $result->template : 'message';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [
            'message_dtl' => $vm_message,
        ], 'account' ) );
    }

    private function extract_query_vars(): array {
        global $wp;
        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
            return $wp->query_vars;
        }

        return [];
    }

    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }
}
