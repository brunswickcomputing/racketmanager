<?php
declare( strict_types=1 );
/**
 * Admin redirect URL builder
 *
 * Centralizes PRG redirect URL construction for admin controllers.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;

/**
 * Builds redirect URLs for RacketManager admin screens and portal-managed views.
 *
 * Provides a shared location for post/redirect/get URL construction, including
 * tournament setup, draw, fixture, information, and related admin navigation
 * targets. Uses an injectable manage URL resolver when portal routing is
 * available, and falls back to WordPress admin URLs for standard admin context.
 *
 * @package RacketManager
 * @subpackage Admin\Controllers
 */
final class Admin_Redirect_Url_Builder {

    private static ?Manage_Url_Resolver_Interface $resolver = null;

    /**
     * Set the URL resolver instance.
     *
     * @param Manage_Url_Resolver_Interface|null $resolver URL resolver instance.
     *
     * @return void
     */
    public static function set_resolver( ?Manage_Url_Resolver_Interface $resolver ): void {
        self::$resolver = $resolver;
    }

    /**
     * Get the manage URL resolver instance.
     *
     * @return Manage_Url_Resolver_Interface
     */
    private static function get_resolver(): Manage_Url_Resolver_Interface {
        if ( null === self::$resolver ) {
            self::$resolver = new Manage_Url_Resolver( new Manage_Route_Mapper() );
        }

        return self::$resolver;
    }

    /**
     * Resolve the target redirect URL using the URL resolver in portal context, or admin_url() in admin context.
     *
     * @param string               $section            Section slug.
     * @param array<string, mixed> $args               Arguments including view and query parameters.
     * @param string               $default_admin_page Default admin page slug.
     *
     * @return string
     */
    private static function resolve_screen_url( string $section, array $args, string $default_admin_page ): string {
        $resolver = self::get_resolver();
        if ( $resolver->is_portal_context() ) {
            $view       = strval( $args['view'] ?? '' );
            $query_args = $args;
            unset( $query_args['page'], $query_args['view'] );
            return $resolver->get_url( $section, $view, $query_args );
        }

        if ( ! isset( $args['page'] ) || '' === $args['page'] ) {
            $args['page'] = $default_admin_page;
        }

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Map an admin page slug to a portal section slug.
     *
     * @param string $page            Admin page slug.
     * @param string $default_section Default section slug.
     *
     * @return string
     */
    private static function section_from_page( string $page, string $default_section = 'leagues' ): string {
        return match ( $page ) {
            'racketmanager-tournaments' => 'tournaments',
            'racketmanager-cups'        => 'cups',
            'racketmanager-clubs'       => 'clubs',
            'racketmanager'             => 'competitions',
            'racketmanager-results'     => 'results',
            'racketmanager-players'     => 'players',
            'racketmanager-seasons'     => 'seasons',
            'racketmanager-finances'    => 'finances',
            'racketmanager-settings'    => 'settings',
            'racketmanager-import'      => 'import',
            'racketmanager-doc'         => 'documentation',
            default                     => $default_section,
        };
    }

    /**
     * Preserve optional context parameters from request data.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     *
     * @return array<string, string>
     */
    private static function preserve_optional_context_params( array $query, array $post ): array {
        $keys     = array( 'leg', 'match_day', 'mode', 'season' );
        $optional = array();

        foreach ( $keys as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $optional[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
                continue;
            }

            if ( isset( $post[ $key ] ) && '' !== strval( $post[ $key ] ) ) {
                $optional[ $key ] = sanitize_text_field( wp_unslash( strval( $post[ $key ] ) ) );
            }
        }

        return $optional;
    }

    /**
     * Build redirect URL for tournament draw-like views.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     * @param string               $view  'draw'|'setup-event'.
     * @param int|null             $tournament_id Tournament ID.
     * @param int|null             $league_id     League ID.
     * @param string               $tab           Tab.
     *
     * @return string
     */
    public static function tournament_draw_view(
        array $query,
        array $post,
        string $view,
        ?int $tournament_id,
        ?int $league_id,
        string $tab
    ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            // For PRG, force the target view explicitly (do not carry over a stale query value).
            'view'       => $view,
            'tournament' => $tournament_id,
            'league'     => $league_id,
            'tab'        => $tab,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL for the tournament setup view (admin.php?page=racketmanager-tournaments&view=setup).
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     * @param int|null             $tournament_id Tournament ID.
     *
     * @return string
     */
    public static function tournament_setup_view(
        array $query,
        array $post,
        ?int $tournament_id
    ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            // For PRG, force the target view explicitly (do not carry over a stale query value).
            'view'       => 'setup',
            'tournament' => $tournament_id,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );
        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL for tournament fixture match view.
     *
     * @param array<string, mixed> $query query string.
     * @param array<string, mixed> $post query string.
     * @param int|null             $tournament_id tournament id.
     * @param int|null             $league_id league id.
     * @param string|null          $final_key final key.
     * @param int|null             $match_id match id.
     *
     * @return string
     */
    public static function tournament_fixture( array $query, array $post, ?int $tournament_id, ?int $league_id, ?string $final_key, ?int $match_id ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            'view'       => 'match',
            'tournament' => $tournament_id,
            'league'     => $league_id,
            'final'      => $final_key,
            'edit'       => $match_id,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL for a tournament fixtures list view.
     *
     * @param array<string, mixed> $query query string.
     * @param array<string, mixed> $post query string.
     * @param int|null             $tournament_id tournament id.
     * @param int|null             $league_id league id.
     * @param string|null          $final_key final key.
     *
     * @return string
     */
    public static function tournament_fixtures( array $query, array $post, ?int $tournament_id, ?int $league_id, ?string $final_key ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            'view'       => 'fixtures',
            'tournament' => $tournament_id,
            'league_id'  => $league_id,
            'final'      => $final_key,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL for the tournament information view (admin.php?page=racketmanager-tournaments&view=information).
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     * @param int|null             $tournament_id tournament id.
     *
     * @return string
     */
    public static function tournament_information_view(
        array $query,
        array $post,
        ?int $tournament_id
    ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            // For PRG, force the target view explicitly (do not carry over a stale query value).
            'view'       => 'information',
            'tournament' => $tournament_id,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );
        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL for the tournament plan view (admin.php?page=racketmanager-tournaments&view=plan).
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     * @param int|null             $tournament_id tournament id.
     * @param array<string, mixed> $flags flags.
     *
     * @return string
     */
    public static function tournament_plan_view(
        array $query,
        array $post,
        ?int $tournament_id,
        array $flags = array()
    ): string {
        $args = array_merge(
            array(
                'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
                'view'       => 'plan',
                'tournament' => $tournament_id,
            ),
            $flags
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL for tournament modify view.
     *
     * @param array<string, mixed> $query query string.
     * @param array<string, mixed> $post query string.
     * @param int|null             $tournament_id tournament id.
     * @param array<string, mixed> $flags flags.
     *
     * @return string
     */
    public static function tournament_modify( array $query, array $post, ?int $tournament_id, array $flags = array() ): string {
        $args = array_merge(
            array(
                'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
                'view'       => 'modify',
                'tournament' => $tournament_id,
            ),
            $flags
        );

        return self::resolve_screen_url( 'tournaments', $args, 'racketmanager-tournaments' );
    }

    /**
     * Build redirect URL that returns to the current league/competition screen after a mutation.
     *
     * Only the identifying query args of the screen are carried over (page, view, league_id, competition_id, season).
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override (e.g. tab).
     *
     * @return string
     */
    public static function league_screen( array $query, array $extra_args = array() ): string {
        $args = array();

        foreach ( array( 'page', 'view', 'league_id', 'competition_id', 'event_id', 'season', 'group', 'match_day', 'team_id', 'match_paged', 'standingstable' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'leagues', $args, 'racketmanager-leagues' );
    }

    /**
     * Build redirect URL that returns to the current club screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function club_screen( array $query, array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-clubs',
        );

        foreach ( array( 'view', 'club_id', 'team_id', 'edit', 'tournament', 'active', 'gender', 'season' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'clubs', $args, 'racketmanager-clubs' );
    }

    /**
     * Build redirect URL that returns to the current season screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function season_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-seasons',
        );

        foreach ( array( 'view' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'seasons', $args, 'racketmanager-seasons' );
    }

    /**
     * Build redirect URL that returns to the current competition screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function competition_screen( array $query = array(), array $extra_args = array() ): string {
        $page = isset( $query['page'] ) && '' !== strval( $query['page'] )
            ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) )
            : strval( $extra_args['page'] ?? 'racketmanager-leagues' );

        $args = array(
            'page' => $page,
        );

        foreach ( array( 'view', 'competition_id', 'season', 'tournament', 'tab' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        $section = self::section_from_page( strval( $args['page'] ?? $page ), 'leagues' );
        return self::resolve_screen_url( $section, $args, 'racketmanager-leagues' );
    }

    /**
     * Build redirect URL that returns to the current event screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function event_screen( array $query = array(), array $extra_args = array() ): string {
        $page = isset( $query['page'] ) && '' !== strval( $query['page'] )
            ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) )
            : strval( $extra_args['page'] ?? 'racketmanager-leagues' );

        $args = array(
            'page' => $page,
        );

        foreach ( array( 'view', 'competition_id', 'event_id', 'season', 'tournament', 'tab' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        $section = self::section_from_page( strval( $args['page'] ?? $page ), 'leagues' );
        return self::resolve_screen_url( $section, $args, 'racketmanager-leagues' );
    }

    /**
     * Build redirect URL that returns to the current finances screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function finances_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-finances',
        );

        foreach ( array( 'view', 'tab', 'competition', 'competition_id', 'season', 'club', 'club_id', 'charge', 'charges', 'invoice', 'invoice_id', 'status' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'finances', $args, 'racketmanager-finances' );
    }

    /**
     * Build redirect URL that returns to the current players screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function players_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-players',
        );

        foreach ( array( 'view', 'tab', 'player_id', 'club_id', 'club', 'status', 'name' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'players', $args, 'racketmanager-players' );
    }

    /**
     * Build redirect URL that returns to the current cup screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function cup_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-cups',
        );

        foreach ( array( 'view', 'competition_id', 'league_id', 'league', 'season', 'tab', 'final', 'edit' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'cups', $args, 'racketmanager-cups' );
    }

    /**
     * Build redirect URL that returns to the current results screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function results_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-results',
        );

        foreach ( array( 'view', 'tab', 'season', 'competition', 'event', 'filterResultsChecker' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'results', $args, 'racketmanager-results' );
    }

    /**
     * Build redirect URL that returns to the current index (competitions) screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function index_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager',
        );

        foreach ( array( 'age_group' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'competitions', $args, 'racketmanager' );
    }

    /**
     * Build redirect URL that returns to the current options screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function options_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) && '' !== strval( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-settings',
        );

        foreach ( array( 'tab' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'settings', $args, 'racketmanager-settings' );
    }

    /**
     * Build redirect URL that returns to the current import screen after a mutation.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function import_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) && '' !== strval( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-import',
        );

        $args = array_merge( $args, $extra_args );

        return self::resolve_screen_url( 'import', $args, 'racketmanager-import' );
    }

    /**
     * Build redirect URL that returns to the upgrade screen after a mutation.
     *
     * @param array<string, mixed> $query      Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function upgrade_screen( array $query = array(), array $extra_args = array() ): string {
        $args = array(
            'page' => isset( $query['page'] ) && '' !== strval( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager',
        );

        $args = array_merge( $args, $extra_args );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Build redirect URL that returns to the championship screen after a mutation.
     *
     * @param array<string, mixed> $query      Typically $_GET.
     * @param array<string, mixed> $extra_args Additional parameters to append or override.
     *
     * @return string
     */
    public static function championship_screen( array $query = array(), array $extra_args = array() ): string {
        $page = isset( $query['page'] ) && '' !== strval( $query['page'] )
            ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) )
            : strval( $extra_args['page'] ?? 'racketmanager-cups' );

        $args = array(
            'page' => $page,
        );

        foreach ( array( 'view', 'competition_id', 'league_id', 'league', 'tournament', 'season', 'tab', 'final' ) as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $args[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
            }
        }

        $args = array_merge( $args, $extra_args );

        $section = self::section_from_page( strval( $args['page'] ?? $page ), 'cups' );
        return self::resolve_screen_url( $section, $args, 'racketmanager-cups' );
    }

    /**
     * Build redirect URL for teams list view mutations across contexts.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post Typically $_POST.
     * @param Admin_Page_Context   $context page context.
     * @param int|null             $league_id league ID.
     * @param int|null             $tournament_id tournament ID.
     * @param int|null             $event_id event ID.
     * @param string|null          $season season.
     * @param string|null          $type type.
     * @param int|null             $competition_id competition ID.
     *
     * @return string
     */
    public static function teams_view(
        array $query,
        array $post,
        Admin_Page_Context $context,
        ?int $league_id,
        ?int $tournament_id = null,
        ?int $event_id = null,
        ?string $season = null,
        ?string $type = null,
        ?int $competition_id = null
    ): string {
        if ( 'tournament' === $context->target_type || 'racketmanager-tournaments' === $context->page ) {
            return self::tournament_draw_view( $query, $post, 'draw', $tournament_id, $league_id, 'preliminary' );
        }

        if ( 'cup' === $context->target_type || 'racketmanager-cups' === $context->page ) {
            $extra = array(
                'view'   => 'draw',
                'league' => $league_id,
                'tab'    => 'teams',
            );
            if ( null !== $competition_id ) {
                $extra['competition_id'] = $competition_id;
            }
            if ( null !== $season && '' !== $season ) {
                $extra['season'] = $season;
            }
            return self::cup_screen( $query, $extra );
        }

        if ( 'constitution' === $type && null !== $event_id ) {
            $extra = array(
                'view'     => 'constitution',
                'event_id' => $event_id,
            );
            if ( null !== $season && '' !== $season ) {
                $extra['season'] = $season;
            }
            return self::league_screen( $query, $extra );
        }

        $extra = array(
            'view'      => 'league',
            'league_id' => $league_id,
        );
        if ( null !== $season && '' !== $season ) {
            $extra['season'] = $season;
        }
        return self::league_screen( $query, $extra );
    }

    /**
     * Build redirect URL for contact page mutations across contexts.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post Typically $_POST.
     * @param Admin_Page_Context   $context page context.
     * @param int|null             $object_id object ID.
     * @param string|null          $object_name object name.
     * @param string|null          $season season.
     *
     * @return string
     */
    public static function contact_view(
        array $query,
        array $post,
        Admin_Page_Context $context,
        ?int $object_id = null,
        ?string $object_name = null,
        ?string $season = null
    ): string {
        $page = $context->page;
        $args = array(
            'page' => $page,
            'view' => 'contact',
        );

        if ( 'tournament' === $context->target_type || 'racketmanager-tournaments' === $page ) {
            if ( $object_id ) {
                $args['tournament'] = $object_id;
            }
        } elseif ( $object_name && $object_id ) {
            $args[ $object_name ] = $object_id;
        }

        if ( $season ) {
            $args['season'] = $season;
        }

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        $section = self::section_from_page( $page, 'leagues' );
        return self::resolve_screen_url( $section, $args, $page );
    }
}
