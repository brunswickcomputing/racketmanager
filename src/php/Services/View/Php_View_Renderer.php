<?php
/**
 * PHP View Renderer
 *
 * @package RacketManager
 * @subpackage Services/View
 */

namespace Racketmanager\Services\View;

use Racketmanager\Exceptions\Template_Not_Found_Exception;

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
    public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void {
        // Templates expect variables named $vm and $renderer
        $vm       = $view_model;
        $renderer = $this;

        // Extract variables for templates that use them (legacy/BC support).
        if ( is_array( $view_model ) ) {
            extract( $view_model );
        }
        if ( ! empty( $additional_vars ) ) {
            extract( $additional_vars );
        }

        $full_path = $this->get_full_path( $template_path );

        require $full_path;
    }

    /**
     * @inheritDoc
     */
    public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
        ob_start();
        $this->render( $template_path, $view_model, $additional_vars );
        return ob_get_clean() ?: '';
    }

    /**
     * Handle theme overrides for templates
     *
     * @param string $template_path
     * @return string
     * @throws Template_Not_Found_Exception
     */
    private function get_full_path( string $template_path ): string {
        $template_file = ltrim( $template_path, '/' ) . '.php';

        // Check for theme overrides
        if ( function_exists( 'get_stylesheet_directory' ) && file_exists( get_stylesheet_directory() . "/racketmanager/$template_file" ) ) {
            return get_stylesheet_directory() . "/racketmanager/$template_file";
        }

        if ( function_exists( 'get_template_directory' ) && file_exists( get_template_directory() . "/racketmanager/$template_file" ) ) {
            return get_template_directory() . "/racketmanager/$template_file";
        }

        $full_path = $this->base_path . $template_file;
        if ( ! file_exists( $full_path ) ) {
            throw new Template_Not_Found_Exception( sprintf( __( 'Template not found at: %s', 'racketmanager' ), $full_path ) );
        }

        return $full_path;
    }
}
