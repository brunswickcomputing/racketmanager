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
     * Renders a template with a given view model.
     *
     * @param string $template_path   Relative path to the template (e.g. 'admin/tournament/overview')
     * @param object $view_model      The View Model instance
     * @param array  $additional_vars Optional additional variables to extract into the template scope.
     */
    public function render( string $template_path, object $view_model, array $additional_vars = [] ): void;
}
