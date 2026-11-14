<?php
/**
 * Shortcode view presenter port
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Presentation/Ports
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Presentation\Ports;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;

/**
 * Interface for rendering shortcode view models.
 */
interface Shortcode_View_Presenter_Interface {

    /**
     * Renders a Shortcode_View_Model (template + variables, or an error alert) to HTML.
     *
     * @param Shortcode_View_Model $vm Shortcode_View_Model.
     *
     * @return string
     */
    public function render( Shortcode_View_Model $vm ): string;

    /**
     * Renders an error alert.
     *
     * @param string      $message Error message.
     * @param string|null $variant Alert variant.
     *
     * @return string
     */
    public function render_error( string $message, ?string $variant = null ): string;
}
