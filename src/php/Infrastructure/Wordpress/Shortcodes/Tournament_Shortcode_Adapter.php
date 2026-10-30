<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Tournament\DTOs\Latest_Tournament_Result;
use Racketmanager\Application\Tournament\DTOs\Order_Of_Play_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Draws_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Events_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Match_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Matches_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Overview_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Players_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Winners_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Withdrawal_Result;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;
use function __;
use function esc_url;
use function get_permalink;
use function is_array;
use function is_object;
use function is_string;
use function wp_get_referer;

/**
 * Driving adapter for Tournament shortcodes.
 */
class Tournament_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    private readonly ?Shortcode_View_Presenter $view_presenter;
    private readonly Tournament_Presenter $tournament_presenter;
    private readonly Player_Presenter $player_presenter;
    private readonly ?Order_Of_Play_Presenter $order_of_play_presenter;

    public function __construct(
        private readonly ?RacketManager $racketmanager = null,
        ?Shortcode_View_Presenter $view_presenter = null,
        private readonly ?Get_Tournament_Handler $tournament_handler = null,
        private readonly ?Get_Tournament_Overview_Handler $overview_handler = null,
        private readonly ?Get_Tournament_Events_Handler $events_handler = null,
        private readonly ?Get_Tournament_Draws_Handler $draws_handler = null,
        private readonly ?Get_Tournament_Players_Handler $players_handler = null,
        private readonly ?Get_Tournament_Winners_Handler $winners_handler = null,
        private readonly ?Get_Tournament_Matches_Handler $matches_handler = null,
        private readonly ?Get_Tournament_Match_Handler $match_handler = null,
        private readonly ?Get_Order_Of_Play_Handler $order_of_play_handler = null,
        private readonly ?Get_Latest_Tournament_Handler $latest_tournament_handler = null,
        private readonly ?Get_Tournament_Withdrawal_Handler $withdrawal_handler = null,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        private readonly ?Competition_Service $competition_service = null,
        ?Tournament_Presenter $tournament_presenter = null,
        ?Player_Presenter $player_presenter = null,
        ?Order_Of_Play_Presenter $order_of_play_presenter = null
    ) {
        $this->view_presenter          = $view_presenter ?? ( $template_renderer ? new Shortcode_View_Presenter( $template_renderer ) : null );
        $this->tournament_presenter    = $tournament_presenter ?? new Tournament_Presenter();
        $this->player_presenter        = $player_presenter ?? new Player_Presenter();
        $this->order_of_play_presenter = $order_of_play_presenter;
    }

    public function show_tournament( array|string $atts = [] ): string {
        $query = Get_Tournament_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->tournament_handler ? $this->tournament_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_tournament( $result ) ) : '';
    }

    public function present_tournament( Tournament_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        if ( $result->is_redirect() ) {
            return Shortcode_View_Model::for_html( '<script>location.href = "' . esc_url( $result->redirect_url ) . '"</script>' );
        }

        $tournament_details  = $result->tournament_details;
        $tournament          = $result->tournament ?? ( $tournament_details ? $tournament_details->tournament : null );
        $tournament_seo_name = $tournament ? Util::seo_url( (string) $tournament->name ) : '';
        $post_id             = $result->post_id;
        $filename            = ( '' !== $result->template ) ? 'tournament-' . $result->template : 'tournament';

        $vars = [
            'tournament_details'  => $tournament_details,
            'tournaments'         => $result->seasons,
            'tab'                 => $result->tab,
            'menu_options'        => $result->menu_options,
            'post_id'             => $post_id,
            'form_action'         => $post_id ? (string) get_permalink( (int) $post_id ) : '',
            'tournament_seo_name'  => $tournament_seo_name,
            'season'              => $tournament?->season,
            'entry_option'        => true,
            'tournament_header'   => $tournament ? $this->tournament_presenter->present_header( $tournament, true ) : null,
        ];

        return Shortcode_View_Model::for_template(
            $filename,
            $vars,
            false
        );
    }

    public function show_tournament_overview( array|string $atts = [] ): string {
        $query = Get_Tournament_Overview_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->overview_handler ? $this->overview_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_overview( $result ) ) : '';
    }

    public function present_overview( Tournament_Overview_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament = $result->tournament;
        $dates_vm   = $tournament ? $this->tournament_presenter->present_dates( $tournament ) : null;
        $filename   = ( '' !== $result->template ) ? 'overview-' . $result->template : 'overview';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tournament'       => $tournament,
                'tournament_dates' => $dates_vm,
            ],
            'tournament'
        );
    }

    public function show_tournament_events( array|string $atts = [] ): string {
        $query = Get_Tournament_Events_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->events_handler ? $this->events_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_events( $result ) ) : '';
    }

    public function present_events( Tournament_Events_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament      = $result->tournament;
        $tournament_link = $tournament ? $this->tournament_presenter->get_link( $tournament ) : '';

        if ( $result->event ) {
            $event = $result->event;
            if ( is_object( $tournament ) ) {
                $resolved = $this->resolve_tournament_event( $tournament, $event );
                if ( null === $resolved ) {
                    return Shortcode_View_Model::for_error( __( 'Event not found for tournament', 'racketmanager' ) );
                }
            }

            return Shortcode_View_Model::for_template(
                'event',
                [
                    'tournament'      => $tournament,
                    'event'           => $event,
                    'tournament_link' => $tournament_link,
                    'draw_link'       => $this->tournament_presenter->get_draw_link( $tournament, $event ),
                    'player_links'    => $this->tournament_presenter->present_event_player_links( $tournament, $event ),
                    'tab'             => 'events',
                ],
                'tournament'
            );
        }

        $events = $result->events;
        if ( is_object( $tournament ) && is_array( $events ) ) {
            $events = $this->filter_allowed_events( $tournament, $events );
        }

        return Shortcode_View_Model::for_template(
            'events',
            [
                'tournament'      => $tournament,
                'events'          => $this->tournament_presenter->present_event_list_rows(
                    $tournament,
                    $events
                ),
                'tournament_link' => $tournament_link,
                'tab'             => 'events',
            ],
            'tournament'
        );
    }

    public function show_draws( array|string $atts = [] ): string {
        $query = Get_Tournament_Draws_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->draws_handler ? $this->draws_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_draws( $result ) ) : '';
    }

    public function present_draws( Tournament_Draws_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament = $result->tournament;

        if ( $result->draw ) {
            $draw = $result->draw;
            if ( is_object( $tournament ) ) {
                $resolved = $this->resolve_tournament_event( $tournament, $draw );
                if ( null === $resolved ) {
                    return Shortcode_View_Model::for_error( __( 'Event not found for tournament', 'racketmanager' ) );
                }
            }

            $raw_fixtures = method_exists( $draw, 'get_meta' ) ? $draw->get_meta( 'fixtures' ) : ( $draw->fixtures ?? [] );
            if ( is_array( $raw_fixtures ) && method_exists( $draw, 'set_meta' ) ) {
                $draw->set_meta( 'fixtures', $this->tournament_presenter->present_match_cards( $raw_fixtures, $tournament ) );
            }

            $draw = $this->tournament_presenter->present_tournament_draw( $draw, $tournament );

            return Shortcode_View_Model::for_template(
                'draw',
                [
                    'tournament'      => $tournament,
                    'draw'            => $draw,
                    'tournament_link' => $this->tournament_presenter->get_link( $tournament ),
                    'tab'             => 'draws',
                ],
                'tournament'
            );
        }

        $draws = $result->draws;
        if ( is_object( $tournament ) && is_array( $draws ) ) {
            $draws = $this->filter_allowed_events( $tournament, $draws );
        }

        return Shortcode_View_Model::for_template(
            'draws',
            [
                'tournament'      => $tournament,
                'draws'           => $this->tournament_presenter->present_draw_list_rows(
                    $tournament,
                    $draws
                ),
                'tournament_link' => $this->tournament_presenter->get_link( $tournament ),
                'tab'             => 'draws',
            ],
            'tournament'
        );
    }

    /**
     * Resolve a tournament event by object, ID, or slug, verifying allowlist membership.
     *
     * @param object            $tournament       Tournament object.
     * @param int|string|object $event_identifier Event ID, slug, or Event/DTO object.
     * @return object|null Resolved and allowed event, or null if invalid/not allowed.
     */
    public function resolve_tournament_event( object $tournament, int|string|object $event_identifier ): ?object {
        if ( $event_identifier instanceof Event ) {
            if ( ! $tournament->is_event_allowed( $event_identifier ) ) {
                return null;
            }
            return $event_identifier;
        }

        $competition = method_exists( $tournament, 'get_competition_object' ) ? $tournament->get_competition_object() : null;
        if ( ! $competition instanceof Competition ) {
            return null;
        }

        if ( ! $this->competition_service ) {
            // Fail closed: raw IDs/slugs cannot be resolved without the application service.
            return null;
        }

        $season    = $tournament->season ?? null;
        $event_obj = $this->competition_service->resolve_competition_event( $competition, $event_identifier, $season );
        if ( ! $event_obj ) {
            return null;
        }

        if ( ! $tournament->is_event_allowed( $event_obj ) ) {
            return null;
        }

        return $event_obj;
    }

    /**
     * @param array<int|string, mixed> $items
     * @return array<int|string, mixed>
     */
    private function filter_allowed_events( object $tournament, array $items ): array {
        $competition = method_exists( $tournament, 'get_competition_object' ) ? $tournament->get_competition_object() : null;
        $candidates  = $competition instanceof Competition && $this->competition_service
            ? $this->competition_service->get_competition_events( $competition )
            : [];

        $filtered = [];
        foreach ( $items as $key => $item ) {
            if ( $tournament->is_event_allowed( $item, false, $candidates ) ) {
                $filtered[ $key ] = $item;
            }
        }

        return $filtered;
    }

    public function show_tournament_players( array|string $atts = [] ): string {
        $query = Get_Tournament_Players_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->players_handler ? $this->players_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_players( $result ) ) : '';
    }

    public function present_players( Tournament_Players_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament = $result->tournament;

        if ( $result->player ) {
            $tournament_player = $result->player;
            if ( ! empty( $tournament_player->matches ) && is_array( $tournament_player->matches ) ) {
                $tournament_player->matches = $this->tournament_presenter->present_match_cards(
                    $tournament_player->matches,
                    $tournament,
                    $tournament_player
                );
            }

            $player_vm = $this->player_presenter->present_player( $tournament_player );

            return Shortcode_View_Model::for_template(
                'player',
                [
                    'tournament'         => $tournament,
                    'tournament_player'  => $tournament_player,
                    'player_vm'          => $player_vm,
                    'player_entry_rows'  => $this->tournament_presenter->present_player_entry_rows(
                        $tournament,
                        is_array( $tournament_player->teams ?? null ) ? $tournament_player->teams : []
                    ),
                    'tournament_link'    => $this->tournament_presenter->get_link( $tournament ),
                    'tab'                => 'players',
                ],
                'tournament'
            );
        }

        $tournament_players = $result->players;
        $tournament_link    = $tournament ? $this->tournament_presenter->get_link( $tournament ) : '';

        return Shortcode_View_Model::for_template(
            'players',
            [
                'tournament'         => $tournament,
                'tournament_players' => $tournament_players,
                'tournament_link'    => $tournament_link,
                'player_link'        => $tournament_link . 'player/',
                'player_links'       => $this->tournament_presenter->present_player_list_links( $tournament, $tournament_players ),
                'tab'                => 'players',
            ],
            'tournament'
        );
    }

    public function show_tournament_winners( array|string $atts = [] ): string {
        $query = Get_Tournament_Winners_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->winners_handler ? $this->winners_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_winners( $result ) ) : '';
    }

    public function present_winners( Tournament_Winners_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament = $result->tournament;
        $winners    = $this->tournament_presenter->present_tournament_winners( $result->winners, $tournament );
        $filename   = ( '' !== $result->template ) ? 'winners-' . $result->template : 'winners';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tournament' => $tournament,
                'winners'    => $winners,
            ],
            'tournament'
        );
    }

    public function show_tournament_matches( array|string $atts = [] ): string {
        $query = Get_Tournament_Matches_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->matches_handler ? $this->matches_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_matches( $result ) ) : '';
    }

    public function present_matches( Tournament_Matches_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament          = $result->tournament;
        $fixture_dates       = $result->fixture_dates;
        $fixture_date        = $result->fixture_date;
        $tournament_fixtures = $result->tournament_fixtures;
        $filename            = ( '' !== $result->template ) ? 'matches-' . $result->template : 'matches';

        $matches_vm = $this->tournament_presenter->present_matches(
            $tournament,
            $fixture_dates,
            $fixture_date,
            $tournament_fixtures
        );

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tab'                => 'matches',
                'vm'                 => $matches_vm,
                // Existing theme overrides and the match.php child still consume the tournament variables.
                'tournament'         => $tournament,
                'match_dates'        => $fixture_dates,
                'tournament_matches' => $tournament_fixtures,
                'current_match_date' => $fixture_date,
            ],
            'tournament'
        );
    }

    public function show_tournament_match( array|string $atts = [] ): string {
        $query = Get_Tournament_Match_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_referrer()
        );

        $result = $this->match_handler ? $this->match_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_match( $result ) ) : '';
    }

    public function present_match( Tournament_Match_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Fixture not found', 'racketmanager' ) );
        }

        $vars = $this->tournament_presenter->present_tournament_match_data(
            $result->tournament,
            $result->fixture,
            $result->is_update_allowed,
            $result->referrer
        );

        return Shortcode_View_Model::for_template(
            'match-tournament',
            $vars,
            false
        );
    }

    public function show_order_of_play( array|string $atts = [] ): string {
        $query = Get_Order_Of_Play_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->order_of_play_handler ? $this->order_of_play_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_order_of_play( $result ) ) : '';
    }

    public function present_order_of_play( Order_Of_Play_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Competition not found', 'racketmanager' ) );
        }

        $presenter = $this->order_of_play_presenter ?? $this->resolve_service( 'order_of_play_presenter' );
        if ( ! $presenter instanceof Order_Of_Play_Presenter ) {
            return Shortcode_View_Model::for_error( __( 'Competition not found', 'racketmanager' ) );
        }

        $order_of_play = $presenter->present( $result->finals_order );
        $filename      = ( '' !== $result->template ) ? 'orderofplay-' . $result->template : 'orderofplay';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'order_of_play' => $order_of_play,
            ],
            false
        );
    }

    public function show_latest_tournament( array|string $atts = [] ): string {
        $query = Get_Latest_Tournament_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->latest_tournament_handler ? $this->latest_tournament_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_latest_tournament( $result ) ) : '';
    }

    public function present_latest_tournament( Latest_Tournament_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament not found', 'racketmanager' ) );
        }

        $script = '<script>location.href = "' . esc_url( $result->redirect_url ) . '"</script>';

        return Shortcode_View_Model::for_html( $script );
    }

    public function show_tournament_withdrawal_modal( array|string $atts = [] ): string {
        $query = Get_Tournament_Withdrawal_Query::from_atts(
            $this->normalize_atts( $atts )
        );

        $result = $this->withdrawal_handler ? $this->withdrawal_handler->handle( $query ) : null;
        if ( ! $result ) {
            return '';
        }

        return $this->view_presenter ? $this->view_presenter->render( $this->present_withdrawal( $result ) ) : '';
    }

    public function present_withdrawal( Tournament_Withdrawal_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'Tournament id not found', 'racketmanager' ), $result->error_type );
        }

        return Shortcode_View_Model::for_template(
            'withdrawal-modal',
            [
                'tournament_id'  => $result->tournament_id,
                'player_id'      => $result->player_id,
                'modal'          => $result->modal,
                'msg'            => $result->msg,
                'events_entered' => $result->events_entered,
            ],
            'tournament'
        );
    }

    public function show_tournament_withdrawal( array|string $atts = [] ): string {
        return $this->show_tournament_withdrawal_modal( $atts );
    }

    private function extract_query_vars(): array {
        global $wp;
        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
            return $wp->query_vars;
        }

        return [];
    }

    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    private function extract_referrer(): ?string {
        $referrer = wp_get_referer();

        return is_string( $referrer ) && '' !== $referrer ? $referrer : null;
    }

    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }

    private function resolve_service( string $id ): mixed {
        return $this->racketmanager?->container->get( $id );
    }
}
