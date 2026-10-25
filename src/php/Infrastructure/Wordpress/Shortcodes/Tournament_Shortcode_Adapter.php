<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

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
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;
use function is_object;
use function is_string;
use function wp_get_referer;

/**
 * Driving adapter for Tournament shortcodes.
 */
class Tournament_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_Tournament_Handler $tournament_handler,
        private readonly Get_Tournament_Overview_Handler $overview_handler,
        private readonly Get_Tournament_Events_Handler $events_handler,
        private readonly Get_Tournament_Draws_Handler $draws_handler,
        private readonly Get_Tournament_Players_Handler $players_handler,
        private readonly Get_Tournament_Winners_Handler $winners_handler,
        private readonly Get_Tournament_Matches_Handler $matches_handler,
        private readonly Get_Tournament_Match_Handler $match_handler,
        private readonly Get_Order_Of_Play_Handler $order_of_play_handler,
        private readonly Get_Latest_Tournament_Handler $latest_tournament_handler,
        private readonly Get_Tournament_Withdrawal_Handler $withdrawal_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        private readonly ?Competition_Service $competition_service = null
    ) {}

    public function show_tournament( array|string $atts = [] ): string {
        $query = Get_Tournament_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->tournament_handler->handle( $query ) );
    }

    public function show_tournament_overview( array|string $atts = [] ): string {
        $query = Get_Tournament_Overview_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->overview_handler->handle( $query ) );
    }

    public function show_tournament_events( array|string $atts = [] ): string {
        $query = Get_Tournament_Events_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $vm = $this->events_handler->handle( $query );

        if ( ! empty( $vm->template ) && ! empty( $vm->vars['tournament'] ) && is_object( $vm->vars['tournament'] ) ) {
            $tournament = $vm->vars['tournament'];
            if ( 'event' === $vm->template && isset( $vm->vars['event'] ) ) {
                $resolved = $this->resolve_tournament_event( $tournament, $vm->vars['event'] );
                if ( null === $resolved ) {
                    return $this->view_presenter->render( Shortcode_View_Model::for_error( __( 'Event not found for tournament', 'racketmanager' ) ) );
                }
            } elseif ( 'events' === $vm->template && isset( $vm->vars['events'] ) && is_array( $vm->vars['events'] ) ) {
                $vm = $this->with_view_vars( $vm, [ 'events' => $this->filter_allowed_events( $tournament, $vm->vars['events'] ) ] );
            }
        }

        return $this->view_presenter->render( $vm );
    }

    public function show_draws( array|string $atts = [] ): string {
        $query = Get_Tournament_Draws_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $vm = $this->draws_handler->handle( $query );

        if ( ! empty( $vm->template ) && ! empty( $vm->vars['tournament'] ) && is_object( $vm->vars['tournament'] ) ) {
            $tournament = $vm->vars['tournament'];
            if ( 'draw' === $vm->template && isset( $vm->vars['draw'] ) ) {
                $resolved = $this->resolve_tournament_event( $tournament, $vm->vars['draw'] );
                if ( null === $resolved ) {
                    return $this->view_presenter->render( Shortcode_View_Model::for_error( __( 'Event not found for tournament', 'racketmanager' ) ) );
                }
            } elseif ( 'draws' === $vm->template && isset( $vm->vars['draws'] ) && is_array( $vm->vars['draws'] ) ) {
                $vm = $this->with_view_vars( $vm, [ 'draws' => $this->filter_allowed_events( $tournament, $vm->vars['draws'] ) ] );
            }
        }

        return $this->view_presenter->render( $vm );
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

    private function with_view_vars( Shortcode_View_Model $vm, array $overrides ): Shortcode_View_Model {
        return Shortcode_View_Model::for_template(
            $vm->template,
            array_merge( $vm->vars, $overrides ),
            $vm->template_type
        );
    }

    public function show_tournament_players( array|string $atts = [] ): string {
        $query = Get_Tournament_Players_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->players_handler->handle( $query ) );
    }

    public function show_tournament_winners( array|string $atts = [] ): string {
        $query = Get_Tournament_Winners_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->winners_handler->handle( $query ) );
    }

    public function show_tournament_matches( array|string $atts = [] ): string {
        $query = Get_Tournament_Matches_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->matches_handler->handle( $query ) );
    }

    public function show_tournament_match( array|string $atts = [] ): string {
        $query = Get_Tournament_Match_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_referrer()
        );

        return $this->view_presenter->render( $this->match_handler->handle( $query ) );
    }

    public function show_order_of_play( array|string $atts = [] ): string {
        $query = Get_Order_Of_Play_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->order_of_play_handler->handle( $query ) );
    }

    public function show_latest_tournament( array|string $atts = [] ): string {
        $query = Get_Latest_Tournament_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->latest_tournament_handler->handle( $query ) );
    }

    public function show_tournament_withdrawal_modal( array|string $atts = [] ): string {
        $query = Get_Tournament_Withdrawal_Query::from_atts(
            $this->normalize_atts( $atts )
        );

        return $this->view_presenter->render( $this->withdrawal_handler->handle( $query ) );
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
}
