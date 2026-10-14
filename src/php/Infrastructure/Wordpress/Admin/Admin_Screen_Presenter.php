<?php
/**
 * Admin screen presenter (GET rendering)
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Admin
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Admin;

use Closure;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\View\View_Renderer_Interface;

/**
 * Driving adapter for the display (GET) half of an admin screen.
 *
 * Shows pending flash/result notices and renders a typed view model through the view renderer. It never
 * mutates state or redirects.
 */
final readonly class Admin_Screen_Presenter {

    public function __construct(
        private View_Renderer_Interface $renderer,
        private Admin_Message_Service $message_service,
    ) {
    }

    /**
     * Move the pending flash message (set by a previous PRG redirect) into the current request.
     */
    public function apply_flash(): array {
        $flash = $this->message_service->pop_flash_message();
        if ( ! empty( $flash['message'] ) ) {
            $this->message_service->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        return $flash;
    }

    /**
     * Show notices and render the view model of a controller result.
     *
     * @param array{view_model?:object,message?:string,message_type?:bool|string} $result           Controller result.
     * @param class-string                                                         $view_model_class Expected view model type.
     * @param string|Closure(object):string                                        $template         Template path, or a resolver using the view model.
     * @param bool                                                                 $strict           Throw on an unexpected view model instead of silently stopping.
     *
     * @throws Invalid_Status_Exception When strict and the view model is missing or of the wrong type.
     */
    public function present( array $result, string $view_model_class, string|Closure $template, bool $strict = true ): void {
        if ( ! empty( $result['message'] ) ) {
            $this->message_service->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }
        $this->message_service->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof $view_model_class ) ) {
            if ( $strict ) {
                throw new Invalid_Status_Exception( __( 'Invalid view model', 'racketmanager' ) );
            }
            return;
        }

        $this->renderer->render( $template instanceof Closure ? $template( $vm ) : $template, $vm );
    }
}
