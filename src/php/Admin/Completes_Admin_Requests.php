<?php
declare( strict_types=1 );
/**
 * Trait Completes_Admin_Requests
 *
 * Provides complete_request() logic for admin screen bridges.
 *
 * @package RacketManager
 * @subpackage Admin
 */

namespace Racketmanager\Admin;

use Closure;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;

trait Completes_Admin_Requests {

    /**
     * POST delivery (guarded mutation result -> flash + PRG redirect).
     */
    private readonly Admin_Action_Adapter $actions;

    /**
     * GET delivery (flash/notice + view model rendering).
     */
    private readonly Admin_Screen_Presenter $screens;

    /**
     * Complete a screen request: PRG redirect for mutations, otherwise render the view model.
     *
     * A delivered redirect ends processing so a POST can never also render the screen.
     *
     * @param array                         $result           Controller result.
     * @param class-string                  $view_model_class Expected view model type.
     * @param string|Closure(object):string $template         Template path or resolver.
     * @param bool                          $redirect         Whether the screen supports PRG redirects.
     * @param bool                          $post_only        Only honour redirects for POST requests.
     * @param bool                          $strict           Throw on an invalid view model.
     */
    private function complete_request(
        array $result,
        string $view_model_class,
        string|Closure $template,
        bool $redirect = true,
        bool $post_only = false,
        bool $strict = true
    ): void {
        if ( $redirect && $this->actions->deliver_redirect( $result, $post_only ) ) {
            return;
        }

        $this->screens->present( $result, $view_model_class, $template, $strict );
    }
}
