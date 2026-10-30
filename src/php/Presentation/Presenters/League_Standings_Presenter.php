<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Application\League\DTOs\League_Standings_Result;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Util\Util;
use Throwable;
use function __;
use function esc_html;
use function is_admin;
use function is_array;
use function is_object;
use function ob_end_clean;
use function ob_get_level;
use function sprintf;
use function vsprintf;
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

    public function render( League_Standings_View_Model|League_Standings_Result|null $vm ): string {
        if ( null === $vm ) {
            return '';
        }

        if ( $vm instanceof League_Standings_Result ) {
            if ( $vm->is_error() || null === $vm->league ) {
                return '';
            }
            $vm = new League_Standings_View_Model(
                template: $vm->template,
                league: $vm->league,
                teams: $vm->teams,
                widget: $vm->widget,
                season: $vm->season,
                group: $vm->group
            );
        }

        if ( null === $vm->league ) {
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

        $team_links = ! empty( $vm->team_links ) ? $vm->team_links : $this->build_team_links( $vm );

        $vars = array_merge(
            $vm->template_vars(),
            [
                'club_presenter'   => $this->club_presenter ?? new Club_Presenter(),
                'player_presenter' => $this->player_presenter ?? new Player_Presenter(),
                'team_links'       => $team_links,
            ]
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

    /**
     * @param League_Standings_View_Model $vm
     * @return array<int|string, string>
     */
    private function build_team_links( League_Standings_View_Model $vm ): array {
        $team_links = [];
        $league     = $vm->league;
        if ( ! $league || empty( $vm->teams ) ) {
            return $team_links;
        }

        $is_box              = isset( $league->event ) && ! empty( $league->event->is_box );
        $current_season_name = is_array( $league->current_season ?? null ) ? ( $league->current_season['name'] ?? '' ) : (string) ( $league->current_season ?? '' );
        $season_link         = $is_box ? ( __( 'round', 'racketmanager' ) . '-' . $current_season_name ) : $current_season_name;
        $comp_type           = ( isset( $league->event ) && isset( $league->event->competition->type ) ) ? $league->event->competition->type : 'league';
        $competition_type    = $comp_type instanceof BackedEnum ? $comp_type->value : (string) $comp_type;
        $league_slug         = Util::seo_url( (string) ( $league->title ?? '' ) );

        foreach ( $vm->teams as $team ) {
            $team_id    = is_object( $team ) ? ( $team->id ?? $team->table_id ?? '' ) : ( $team['id'] ?? '' );
            $team_title = is_object( $team ) ? ( $team->title ?? '' ) : ( $team['title'] ?? '' );
            $link       = '/' . $competition_type . '/' . $league_slug . '/' . $season_link . '/team/' . Util::seo_url( (string) $team_title ) . '/';

            if ( '' !== (string) $team_id ) {
                $team_links[ (string) $team_id ] = $link;
                $team_links[ (int) $team_id ]    = $link;
            }
            if ( '' !== (string) $team_title ) {
                $team_links[ (string) $team_title ] = $link;
            }
        }

        return $team_links;
    }

    /**
     * Check if a specific standings column is activated for display in the given league.
     *
     * @param object|null $league League domain entity or object.
     * @param string      $key    Column key (e.g. 'status', 'pld', 'won', 'sets', 'games', 'last5').
     * @return bool
     */
    public static function is_column_visible( ?object $league, string $key ): bool {
        if ( null === $league ) {
            return false;
        }

        if ( isset( $league->standings ) && is_array( $league->standings ) ) {
            return isset( $league->standings[ $key ] ) && 1 === (int) $league->standings[ $key ];
        }

        return false;
    }

    /**
     * Get specific template file configured for the given league.
     *
     * @param object|null $league   League domain entity or object.
     * @param string      $template Template key.
     * @return string
     */
    public static function get_league_template( ?object $league, string $template = '' ): string {
        if ( null === $league || empty( $template ) ) {
            return '';
        }

        if ( isset( $league->templates ) && is_array( $league->templates ) && isset( $league->templates[ $template ] ) ) {
            return (string) $league->templates[ $template ];
        }

        return '';
    }

    /**
     * Render custom standings columns for a team.
     *
     * @param object $league League object or entity.
     * @param object $team   Team data object.
     * @param string $rule   Point rule ('manual', etc.).
     * @return string
     */
    public static function render_custom_standings_columns( object $league, object $team, string $rule ): string {
        $output      = '';
        $fields_team = $league->fields_team ?? array();
        if ( ! empty( $fields_team ) && is_array( $fields_team ) ) {
            $point_2_format = $league->point_2_format ?? ( isset( $league->event->competition->settings['point_2_format'] ) ? $league->event->competition->settings['point_2_format'] : '%d-%d' );
            foreach ( $fields_team as $key => $data ) {
                if ( ! isset( $team->{$key} ) ) {
                    if ( isset( $data['keys'] ) && is_array( $data['keys'] ) ) {
                        $team->{$key} = array();
                        foreach ( $data['keys'] as $k ) {
                            $team->{$key}[ $k ] = '';
                        }
                    } else {
                        $team->{$key} = '';
                    }
                }

                if ( ( isset( $data['type'] ) && 'input' === $data['type'] ) && is_admin() && 'manual' === $rule ) {
                    $output .= '<td class="column-' . esc_html( (string) $key ) . ' column-num d-none d-md-table-cell" data-colname="' . esc_html( (string) ( $data['label'] ?? '' ) ) . '">';
                    if ( is_array( $team->{$key} ) ) {
                        foreach ( $team->{$key} as $k => $v ) {
                            $val     = $team->{$key}[ $k ] ?? '';
                            $output .= '<input class="points" type="text" size="2" id="home_' . esc_html( (string) $team->id ) . '_' . esc_html( (string) $k ) . '" name="custom[' . esc_html( (string) $team->id ) . '][' . esc_html( (string) $key ) . '][' . esc_html( (string) $k ) . ']" value="' . esc_html( (string) $val ) . '" />';
                        }
                    } else {
                        $output .= '<input class="points" type="text" size="2" id="home_' . esc_html( (string) $team->id ) . '" name="custom[' . esc_html( (string) $team->id ) . '][' . esc_html( (string) $key ) . ']" value="' . esc_html( (string) $team->{$key} ) . '" />';
                    }
                    $output .= '</td>';
                } elseif ( self::is_column_visible( $league, (string) $key ) ) {
                    $val = $team->{$key};
                    if ( is_array( $val ) ) {
                        $val = vsprintf( $point_2_format, $val );
                    }
                    $output .= '<td class="num column-' . esc_html( (string) $key ) . ' d-none d-md-table-cell d-none d-md-table-cell" data-colname="' . esc_html( (string) ( $data['label'] ?? '' ) ) . '">' . esc_html( (string) $val ) . '</td>';
                }
            }
        }
        return $output;
    }

    private function restore_buffer_level( int $level ): void {
        while ( ob_get_level() > $level ) {
            ob_end_clean();
        }
    }
}
