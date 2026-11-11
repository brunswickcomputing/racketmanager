<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;

/**
 * Renders a Shortcode_View_Model (template + variables, or an error alert) to HTML.
 */
readonly class Shortcode_View_Presenter {
    /**
     * Constructor
     *
     * @param Shortcode_Template_Renderer $template_renderer Shortcode_Template_Renderer.
     */
    public function __construct( private Shortcode_Template_Renderer $template_renderer ) {}

    /**
     * Renders a Shortcode_View_Model (template + variables, or an error alert) to HTML.
     *
     * @param Shortcode_View_Model $vm Shortcode_View_Model.
     *
     * @return string
     */
    public function render( Shortcode_View_Model $vm ): string {
        if ( $vm->is_raw_html() ) {
            return (string) $vm->raw_html;
        }

        if ( $vm->is_error() ) {
            return $this->template_renderer->render_alert( (string) $vm->error_message, $vm->error_variant );
        }

        return $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type );
    }

    /**
     * Renders an error alert.
     *
     * @param string      $message Error message.
     * @param string|null $variant Alert variant.
     *
     * @return string
     */
    public function render_error( string $message, ?string $variant = null ): string {
        return $this->template_renderer->render_alert( $message, $variant );
    }
}
