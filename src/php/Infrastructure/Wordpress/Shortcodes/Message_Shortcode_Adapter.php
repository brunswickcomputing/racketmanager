<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Query;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Query;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Message_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use function is_array;

/**
 * Driving adapter for Message shortcodes.
 */
readonly class Message_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    /**
     * Initialises the shortcode controller with message retrieval, presentation, rendering, security, and query services.
     *
     * @param RacketManager|null                 $racketmanager Optional racket manager used for legacy or integration support.
     * @param Shortcode_View_Presenter_Interface $view_presenter Presenter used to render shortcode view models.
     * @param Get_Messages_Handler               $messages_handler Handler used to retrieve message collections.
     * @param Get_Message_Handler                $message_handler Handler used to retrieve a single message.
     * @param Message_Presenter_Interface        $message_presenter Presenter used to convert messages into view models.
     * @param Template_Renderer_Interface|null   $template_renderer Optional template renderer used for custom rendering.
     * @param Security_Service_Interface|null    $security_service Optional security service used to inspect the current user context.
     * @param Query_Vars_Interface|null          $query_vars Optional query variables provider used to read request query data.
     *
     * @return void
     */
    public function __construct(
        private ?RacketManager $racketmanager,
        private Shortcode_View_Presenter_Interface $view_presenter,
        private Get_Messages_Handler $messages_handler,
        private Get_Message_Handler $message_handler,
        private Message_Presenter_Interface $message_presenter,
        private ?Template_Renderer_Interface $template_renderer = null,
        private ?Security_Service_Interface $security_service = null,
        private ?Query_Vars_Interface $query_vars = null
    ) {}

    /**
     * Renders the messages view for the current request.
     *
     * @param array|string $atts Shortcode attributes used to build the messages query.
     *
     * @return string Rendered messages output or rendered error output.
     */
    public function show_messages( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : null ) ) : null;

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

        $messages_vm = $this->message_presenter->present_messages( $result->messages );
        $filename    = ! empty( $result->template ) ? 'messages-' . $result->template : 'messages';

        return $this->view_presenter->render(
            Shortcode_View_Model::for_template(
                $filename,
                [
                    'messages' => $messages_vm,
                ],
                'account'
            )
        );
    }

    /**
     * Renders the message view for the current request.
     *
     * @param array|string $atts Shortcode attributes used to build the message query.
     *
     * @return string Rendered message output or rendered error output.
     */
    public function show_message( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : null ) ) : null;

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

        $vm_message = $result->message ? $this->message_presenter->present_message( $result->message ) : null;
        $filename   = ! empty( $result->template ) ? 'message-' . $result->template : 'message';

        return $this->view_presenter->render(
            Shortcode_View_Model::for_template(
                $filename,
                [
                    'message_dtl' => $vm_message,
                ],
                'account'
            )
        );
    }

    /**
     * Extracts the available query variables.
     *
     * @return array The query variables, or an empty array when none are available.
     */
    private function extract_query_vars(): array {
        return $this->query_vars?->all() ?? [];
    }

    /**
     * Retrieves the current HTTP GET parameters.
     *
     * @return array The contents of the $_GET superglobal, or an empty array when unavailable.
     */
    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    /**
     * Normalises the provided attributes to an array.
     *
     * @param array|string $atts Attributes to normalize.
     *
     * @return array Normalized attributes, or an empty array when the input is not an array.
     */
    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }
}
