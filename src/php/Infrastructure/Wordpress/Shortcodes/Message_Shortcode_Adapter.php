<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Query;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Query;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
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
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function show_messages( array|string $atts = [] ): string {
        $query = Get_Messages_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->messages_handler->handle( $query ) );
    }

    public function show_message( array|string $atts = [] ): string {
        $query = Get_Message_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->message_handler->handle( $query ) );
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
