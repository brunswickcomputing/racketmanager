<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;

/**
 * Renders a Shortcode_View_Model (template + variables, or an error alert) to HTML.
 */
class Shortcode_View_Presenter {
    public function __construct( private readonly Shortcode_Template_Renderer $template_renderer ) {}

    public function render( Shortcode_View_Model $vm ): string {
        if ( $vm->is_raw_html() ) {
            return (string) $vm->raw_html;
        }

        if ( $vm->is_error() ) {
            return $this->template_renderer->render_alert( (string) $vm->error_message, $vm->error_variant );
        }

        return $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type );
    }

    public function render_error( string $message, ?string $variant = null ): string {
        return $this->template_renderer->render_alert( $message, $variant );
    }
}
