<?php
/**
 * PHP View Renderer
 *
 * @package RacketManager
 * @subpackage Services/View
 */

namespace Racketmanager\Services\View;

use RuntimeException;

/**
 * Class Php_View_Renderer
 *
 * A basic PHP-based view renderer that encapsulates file-based template inclusion.
 */
final readonly class Php_View_Renderer implements View_Renderer_Interface {

    /**
     * @param string $base_path The base directory where templates are located.
     */
    public function __construct( private string $base_path ) {
    }

    /**
     * @inheritDoc
     */
    public function render( string $template_path, object $view_model, array $additional_vars = [] ): void {
        // Templates expect a variable named $vm
        $vm = $view_model;

        // Extract additional variables for templates that need them (legacy/BC support).
        if ( ! empty( $additional_vars ) ) {
            extract( $additional_vars );
        }

        $renderer = $this;

        $full_path = $this->base_path . ltrim( $template_path, '/' ) . '.php';

        if ( ! file_exists( $full_path ) ) {
            throw new RuntimeException( sprintf( 'Template not found at: %s', $full_path ) );
        }

        require $full_path;
    }
}
