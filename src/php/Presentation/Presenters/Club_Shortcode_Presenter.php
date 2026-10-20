<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Services\View\View_Renderer_Interface;
use Throwable;
use function Racketmanager\show_alert;

/**
 * Presenter for [club] shortcode view model.
 */
class Club_Shortcode_Presenter {
    public function __construct(
        private readonly View_Renderer_Interface $renderer,
        private readonly ?Club_Presenter $club_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null,
        private readonly ?Favourite_Presenter $favourite_presenter = null
    ) {}

    public function render( Club_View_Model $vm ): string {
        $default_vars = [
            'club_presenter'   => $this->club_presenter ?? new Club_Presenter(),
            'player_presenter' => $this->player_presenter ?? new Player_Presenter(),
        ];

        if ( null !== $vm->error_message ) {
            $vars = array_merge( $default_vars, [
                'msg'   => $vm->error_message,
                'class' => 'danger',
            ] );
            return $this->renderer->render_to_string( 'alert', [], $vars );
        }

        $vars = array_merge( $default_vars, $vm->template_vars() );
        if ( $this->favourite_presenter ) {
            $vars = array_merge( $vars, $this->favourite_presenter->present_template_vars( $vars ) );
        }

        $level = ob_get_level();
        try {
            return $this->renderer->render_to_string( $vm->template, $vm, $vars );
        } catch ( Template_Not_Found_Exception ) {
            $this->restore_buffer_level( $level );
            /* translators: %1$s: template %2$s: directory */
            $msg = sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $vm->template, 'templates' );

            $error_vars = array_merge( $default_vars, [
                'msg'   => $msg,
                'class' => 'danger',
            ] );
            return $this->renderer->render_to_string( 'alert', [], $error_vars );
        } catch ( Throwable $e ) {
            $this->restore_buffer_level( $level );
            throw $e;
        }
    }

    private function restore_buffer_level( int $level ): void {
        while ( ob_get_level() > $level ) {
            ob_end_clean();
        }
    }
}
