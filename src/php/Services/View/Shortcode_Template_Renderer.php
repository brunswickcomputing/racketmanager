<?php
declare( strict_types=1 );

namespace Racketmanager\Services\View;

use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presenters\Club_Presenter;
use Racketmanager\Presenters\Player_Presenter;
use Throwable;
use function __;
use function file_exists;
use function function_exists;
use function get_stylesheet_directory;
use function get_template_directory;
use function sprintf;

/**
 * Shared shortcode template loader built on View_Renderer_Interface.
 *
 * Template lookup order (compatible with the legacy Shortcodes::load_template()):
 *  1. flat theme override        <stylesheet|template dir>/racketmanager/<template>.php
 *  2. typed theme/plugin lookup  View_Renderer_Interface (<type>/<template>)
 * A missing template renders the standard danger alert rather than failing.
 */
class Shortcode_Template_Renderer {
    private const TYPE_DIRECTORIES = [
        'competition' => 'competition/',
        'event'       => 'event/',
        'email'       => 'email/',
        'entry'       => 'entry/',
        'form'        => 'forms/',
        'includes'    => 'includes/',
        'page'        => 'page/',
        'tournament'  => 'tournament/',
        'account'     => 'account/',
        'league'      => 'league/',
        'match'       => 'match/',
        'club'        => 'club/',
    ];

    private readonly Club_Presenter $club_presenter;
    private readonly Player_Presenter $player_presenter;

    public function __construct(
        private readonly View_Renderer_Interface $renderer,
        ?Club_Presenter $club_presenter = null,
        ?Player_Presenter $player_presenter = null
    ) {
        $this->club_presenter   = $club_presenter ?? new Club_Presenter();
        $this->player_presenter = $player_presenter ?? new Player_Presenter();
    }

    /**
     * Render a template to a string.
     *
     * @param string       $template      Template file name without extension.
     * @param array        $vars          Variables exposed to the template.
     * @param false|string $template_type Template group (e.g. 'club', 'match'); false for the templates root.
     */
    public function render( string $template, array $vars = [], false|string $template_type = false ): string {
        $directory = $template_type ? ( self::TYPE_DIRECTORIES[ $template_type ] ?? '' ) : '';
        $vars      = array_merge(
            [
                'club_presenter'   => $this->club_presenter,
                'player_presenter' => $this->player_presenter,
            ],
            $vars
        );

        $had_league = array_key_exists( 'league', $GLOBALS );
        $old_league = $GLOBALS['league'] ?? null;
        if ( isset( $vars['league'] ) && is_object( $vars['league'] ) ) {
            $GLOBALS['league'] = $vars['league'];
        }

        $had_event = array_key_exists( 'event', $GLOBALS );
        $old_event = $GLOBALS['event'] ?? null;
        if ( isset( $vars['event'] ) && is_object( $vars['event'] ) ) {
            $GLOBALS['event'] = $vars['event'];
        }

        try {
            $override = $this->find_flat_theme_override( $template );
            if ( null !== $override ) {
                return $this->include_file( $override, $vars );
            }

            try {
                return $this->renderer->render_to_string( $directory . $template, [], $vars );
            } catch ( Template_Not_Found_Exception ) {
                /* translators: %1$s: template %2$s: directory */
                $msg = sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $template, 'templates/' . rtrim( $directory, '/' ) );

                return $this->render_alert( $msg );
            }
        } finally {
            if ( $had_league ) {
                $GLOBALS['league'] = $old_league;
            } else {
                unset( $GLOBALS['league'] );
            }

            if ( $had_event ) {
                $GLOBALS['event'] = $old_event;
            } else {
                unset( $GLOBALS['event'] );
            }
        }
    }

    /**
     * Render the standard danger alert.
     *
     * @param string      $msg     Message text.
     * @param string|null $variant Alert template variant (e.g. 'modal' => alert-modal).
     */
    public function render_alert( string $msg, ?string $variant = null ): string {
        $filename = ! empty( $variant ) ? 'alert-' . $variant : 'alert';

        return $this->renderer->render_to_string(
            $filename,
            [],
            [
                'club_presenter'   => $this->club_presenter,
                'player_presenter' => $this->player_presenter,
                'msg'              => $msg,
                'class'            => 'danger',
            ]
        );
    }

    /**
     * Check if a template file exists.
     *
     * @param string       $template      Template file name without extension.
     * @param false|string $template_type Template group (e.g. 'club', 'match'); false for root templates.
     */
    public function template_exists( string $template, false|string $template_type = false ): bool {
        $directory = $template_type ? ( self::TYPE_DIRECTORIES[ $template_type ] ?? '' ) : '';
        if ( null !== $this->find_flat_theme_override( $template ) ) {
            return true;
        }

        return $this->renderer->template_exists( $directory . $template );
    }

    private function find_flat_theme_override( string $template ): ?string {
        $directories = [];
        if ( function_exists( 'get_stylesheet_directory' ) ) {
            $directories[] = get_stylesheet_directory();
        }
        if ( function_exists( 'get_template_directory' ) ) {
            $directories[] = get_template_directory();
        }
        foreach ( $directories as $directory ) {
            $file = $directory . "/racketmanager/$template.php";
            if ( file_exists( $file ) ) {
                return $file;
            }
        }

        return null;
    }

    private function include_file( string $file, array $vars ): string {
        $level = ob_get_level();
        ob_start();
        try {
            ( static function ( string $__file, array $__vars ): void {
                extract( $__vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
                require $__file;
            } )( $file, $vars );

            return ob_get_clean() ?: '';
        } catch ( Throwable $e ) {
            while ( ob_get_level() > $level ) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
