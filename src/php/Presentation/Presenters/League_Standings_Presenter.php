<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Services\View\View_Renderer_Interface;
use Throwable;
use function Racketmanager\show_alert;

/**
 * Presenter for [standings] shortcode view model.
 */
class League_Standings_Presenter {
    public function __construct(
        private readonly View_Renderer_Interface $renderer,
        private readonly ?Club_Presenter $club_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null
    ) {}

    public function render( ?League_Standings_View_Model $vm ): string {
        if ( null === $vm || null === $vm->league ) {
            return '';
        }

        $template = $vm->template;
        $sport    = is_object( $vm->league ) && isset( $vm->league->sport ) ? (string) $vm->league->sport : '';

        if ( empty( $template ) ) {
            $filename = 'standings';
        } elseif ( ! $vm->widget && ! empty( $sport ) && $this->renderer->template_exists( 'standings-' . $sport ) ) {
            $filename = 'standings-' . $sport;
        } else {
            $filename = 'standings-' . $template;
        }

        $vars = array_merge(
            [
                'club_presenter'   => $this->club_presenter ?? new Club_Presenter(),
                'player_presenter' => $this->player_presenter ?? new Player_Presenter(),
            ],
            $vm->template_vars()
        );

        $old_global_league = $GLOBALS['league'] ?? null;
        $GLOBALS['league'] = $vm->league;

        $level = ob_get_level();
        try {
            return $this->renderer->render_to_string( $filename, $vm, $vars );
        } catch ( Template_Not_Found_Exception ) {
            $this->restore_buffer_level( $level );
            /* translators: %1$s: template %2$s: directory */
            $msg = sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $filename, 'templates' );

            $error_vars = [
                'msg'              => $msg,
                'class'            => 'danger',
                'club_presenter'   => $this->club_presenter ?? new Club_Presenter(),
                'player_presenter' => $this->player_presenter ?? new Player_Presenter(),
            ];
            return $this->renderer->render_to_string( 'alert', [], $error_vars );
        } catch ( Throwable $e ) {
            $this->restore_buffer_level( $level );
            throw $e;
        } finally {
            $GLOBALS['league'] = $old_global_league;
        }
    }

    private function restore_buffer_level( int $level ): void {
        while ( ob_get_level() > $level ) {
            ob_end_clean();
        }
    }
}
