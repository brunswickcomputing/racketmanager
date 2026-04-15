<?php
/**
 * View Renderer Interface
 *
 * @package RacketManager
 * @subpackage Services/View
 */

namespace Racketmanager\Services\View;

/**
 * Interface View_Renderer_Interface
 *
 * Provides a contract for rendering templates with a view model.
 */
interface View_Renderer_Interface {

    /**
     * Renders a template with a given view model and outputs it directly.
     *
     * @param string       $template_path   Relative path to the template (e.g. 'admin/tournament/overview')
     * @param object|array $view_model      The View Model instance or an array of variables
     * @param array        $additional_vars Optional additional variables to extract into the template scope.
     */
    public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void;

    /**
     * Renders a template with a given view model and returns it as a string.
     *
     * @param string       $template_path   Relative path to the template (e.g. 'admin/tournament/overview')
     * @param object|array $view_model      The View Model instance or an array of variables
     * @param array        $additional_vars Optional additional variables to extract into the template scope.
     * @return string
     */
    public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string;
}
