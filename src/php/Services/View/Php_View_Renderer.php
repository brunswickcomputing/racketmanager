<?php
declare( strict_types=1 );
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
        // Explicit template scope (no extract()). The template sees exactly:
        // - $vm       the object view model, passed through untouched (identity preserved) when an object is given;
        //             for an array view model it is built from the merged array via Template_Scope::resolve_vm().
        // - $vars     the flat array of additional vars (merged over the array view model, if one was given).
        //             Supplemental data for a typed object view model is read from $vars, never written onto $vm.
        // - $renderer this renderer, for nested rendering.
        $vars = is_array( $view_model ) ? array_merge( $view_model, $additional_vars ) : $additional_vars;
        $vm   = is_object( $view_model ) ? $view_model : Template_Scope::resolve_vm( $vars );

        $renderer  = $this;
        $full_path = $this->get_full_path( $template_path );

        require $full_path;
    }

    /**
     * @inheritDoc
     */
    public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
        ob_start();
        try {
            $this->render( $template_path, $view_model, $additional_vars );
            return ob_get_clean() ?: '';
        } catch ( \Throwable $e ) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function template_exists( string $template_path ): bool {
        $template_file = ltrim( $template_path, '/' ) . '.php';
        $legacy_file   = basename( $template_file );

        // Check for theme overrides
        if ( function_exists( 'get_stylesheet_directory' ) && file_exists( get_stylesheet_directory() . "/racketmanager/$template_file" ) ) {
            return true;
        }

        if ( function_exists( 'get_template_directory' ) && file_exists( get_template_directory() . "/racketmanager/$template_file" ) ) {
            return true;
        }

        if ( $legacy_file !== $template_file ) {
            if ( function_exists( 'get_stylesheet_directory' ) && file_exists( get_stylesheet_directory() . "/racketmanager/$legacy_file" ) ) {
                return true;
            }

            if ( function_exists( 'get_template_directory' ) && file_exists( get_template_directory() . "/racketmanager/$legacy_file" ) ) {
                return true;
            }
        }

        return file_exists( $this->base_path . $template_file );
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
        $legacy_file   = basename( $template_file );

        // Check for theme overrides
        if ( function_exists( 'get_stylesheet_directory' ) && file_exists( get_stylesheet_directory() . "/racketmanager/$template_file" ) ) {
            return get_stylesheet_directory() . "/racketmanager/$template_file";
        }

        if ( function_exists( 'get_template_directory' ) && file_exists( get_template_directory() . "/racketmanager/$template_file" ) ) {
            return get_template_directory() . "/racketmanager/$template_file";
        }

        // Check for legacy theme overrides (e.g. racketmanager/send-fixtures.php without email/ subfolder)
        if ( $legacy_file !== $template_file ) {
            if ( function_exists( 'get_stylesheet_directory' ) && file_exists( get_stylesheet_directory() . "/racketmanager/$legacy_file" ) ) {
                return get_stylesheet_directory() . "/racketmanager/$legacy_file";
            }

            if ( function_exists( 'get_template_directory' ) && file_exists( get_template_directory() . "/racketmanager/$legacy_file" ) ) {
                return get_template_directory() . "/racketmanager/$legacy_file";
            }
        }

        $full_path = $this->base_path . $template_file;
        if ( ! file_exists( $full_path ) ) {
            throw new Template_Not_Found_Exception( sprintf( __( 'Template not found at: %s', 'racketmanager' ), $full_path ) );
        }

        return $full_path;
    }
}
