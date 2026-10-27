<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;
use function is_object;
use function method_exists;
use function shortcode_atts;

/**
 * Driving adapter for Email and Notification template shortcodes.
 */
class Email_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly Fixture_Repository_Interface $fixture_repository,
        private readonly Email_Shortcode_Presenter $presenter,
        private readonly Shortcode_Template_Renderer $template_renderer,
        private readonly Event_Query_Service $event_query_service,
        private readonly League_Query_Service $league_query_service,
        private readonly Team_Repository_Interface $team_repository,
        private readonly League_Repository_Interface $league_repository,
        private readonly Event_Repository_Interface $event_repository,
        private readonly Competition_Repository_Interface $competition_repository
    ) {
    }

    /**
     * [match-notification match=ID template=X tournament=X competition=X emailfrom=X round=X competition_type=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_match_notification( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'match'            => '',
            'id'               => '',
            'template'         => '',
            'tournament'       => false,
            'competition'      => '',
            'emailfrom'        => '',
            'round'            => '',
            'competition_type' => '',
        ], is_array( $atts ) ? $atts : [] );

        $match    = $args['match'] ?: $args['id'];
        $match_id = (int) ( is_object( $match ) ? ( $match->id ?? 0 ) : $match );
        if ( ! $match_id ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $fixture = $this->fixture_repository->find_by_id( $match_id );
        if ( ! $fixture ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        try {
            $vars = $this->presenter->present_match_notification( $fixture, $args );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->template_renderer->render_alert( $e->getMessage() );
        }

        $template = ! empty( $args['template'] ) ? $args['template'] : ( $vars['template'] ?? '' );
        $filename = ! empty( $template ) ? 'match-notification-' . $template : 'match-notification';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * [result-notification match=ID template=X league=X round=X complete=X errors=X match_day=X from_email=X challenge=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_result_notification( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'match'      => '',
            'id'         => '',
            'template'   => '',
            'league'     => false,
            'round'      => false,
            'complete'   => false,
            'errors'     => false,
            'match_day'  => '',
            'from_email' => false,
            'challenge'  => false,
        ], is_array( $atts ) ? $atts : [] );

        $match    = $args['match'] ?: $args['id'];
        $match_id = (int) ( is_object( $match ) ? ( $match->id ?? 0 ) : $match );
        if ( ! $match_id ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $fixture = $this->fixture_repository->find_by_id( $match_id );
        if ( ! $fixture ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $vars     = $this->presenter->present_result_notification( $fixture, $args );
        $filename = ! empty( $args['template'] ) ? 'result-notification-' . $args['template'] : 'result-notification';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * [result-notification-captain match=ID template=X outstanding=X time_period=X override=X from_email=X confirmation_required=X confirmation_timeout=X timeout=X penalty=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_captain_result_notification( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'match'                 => '',
            'id'                    => '',
            'template'              => '',
            'outstanding'           => false,
            'time_period'           => false,
            'override'              => false,
            'from_email'            => false,
            'confirmation_required' => false,
            'confirmation_timeout'  => false,
            'timeout'               => false,
            'penalty'               => false,
        ], is_array( $atts ) ? $atts : [] );

        $match    = $args['match'] ?: $args['id'];
        $match_id = (int) ( is_object( $match ) ? ( $match->id ?? 0 ) : $match );
        if ( ! $match_id ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $fixture = $this->fixture_repository->find_by_id( $match_id );
        if ( ! $fixture ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $vars     = $this->presenter->present_captain_result_notification( $fixture, $args );
        $filename = ! empty( $args['template'] ) ? 'result-notification-' . $args['template'] : 'result-notification';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * Alias for show_captain_result_notification.
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_result_notification_captain( array|string $atts = [] ): string {
        return $this->show_captain_result_notification( $atts );
    }

    /**
     * [result-outstanding-notification match=ID template=X time_period=X timeout=X penalty=X from_email=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_result_outstanding_notification( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'match'       => '',
            'id'          => '',
            'template'    => '',
            'time_period' => false,
            'timeout'     => false,
            'penalty'     => false,
            'from_email'  => null,
        ], is_array( $atts ) ? $atts : [] );

        $match    = $args['match'] ?: $args['id'];
        $match_id = (int) ( is_object( $match ) ? ( $match->id ?? 0 ) : $match );
        if ( ! $match_id ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $fixture = $this->fixture_repository->find_by_id( $match_id );
        if ( ! $fixture ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        try {
            $vars = $this->presenter->present_result_outstanding_notification( $fixture, $args );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->template_renderer->render_alert( $e->getMessage() );
        }

        $template = $vars['template'] ?? ( $args['template'] ?? '' );
        $filename = ! empty( $template ) ? 'match-result-pending-' . $template : 'match-result-pending';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * [club-player-notification club=X action=X player=X requestor=X btm=X template=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_club_player_notification( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'club'      => '',
            'action'    => false,
            'player'    => false,
            'requestor' => false,
            'btm'       => false,
            'template'  => '',
        ], is_array( $atts ) ? $atts : [] );

        $vars     = $this->presenter->present_club_player_notification( $args );
        $filename = ! empty( $args['template'] ) ? 'club-player-notification-' . $args['template'] : 'club-player-notification';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * [match_date_change_notification match=ID template=X tournament=X competition=X emailfrom=X round=X competition_type=X original_date=X new_date=X delay=X email_subject=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_match_date_change_notification( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'match'            => '',
            'id'               => '',
            'template'         => '',
            'tournament'       => false,
            'competition'      => '',
            'emailfrom'        => '',
            'round'            => '',
            'competition_type' => '',
            'original_date'    => '',
            'new_date'         => '',
            'delay'            => false,
            'email_subject'    => '',
        ], is_array( $atts ) ? $atts : [] );

        $match    = $args['match'] ?: $args['id'];
        $match_id = (int) ( is_object( $match ) ? ( $match->id ?? 0 ) : $match );
        if ( ! $match_id ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        $fixture = $this->fixture_repository->find_by_id( $match_id );
        if ( ! $fixture ) {
            return $this->template_renderer->render_alert( __( 'Match not found', 'racketmanager' ) );
        }

        try {
            $vars = $this->presenter->present_match_date_change_notification( $fixture, $args );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->template_renderer->render_alert( $e->getMessage() );
        }

        $filename = ! empty( $args['template'] ) ? 'match-date-change-notification-' . $args['template'] : 'match-date-change-notification';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * [withdrawn-team team=ID league=ID season=X subject=X from=X template=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_team_withdrawn( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'team'     => false,
            'league'   => false,
            'season'   => false,
            'subject'  => false,
            'from'     => false,
            'template' => '',
        ], is_array( $atts ) ? $atts : [] );

        $team_id = $args['team'];
        if ( ! $team_id ) {
            return $this->template_renderer->render_alert( __( 'Team not supplied', 'racketmanager' ) );
        }
        $team = $this->resolve_team( $team_id );
        if ( ! $team ) {
            return $this->template_renderer->render_alert( __( 'Team not found', 'racketmanager' ) );
        }

        $league_id = $args['league'];
        if ( ! $league_id ) {
            return $this->template_renderer->render_alert( __( 'League not supplied', 'racketmanager' ) );
        }
        $league = $this->resolve_league( $league_id );
        if ( ! $league ) {
            return $this->template_renderer->render_alert( __( 'League not found', 'racketmanager' ) );
        }

        $season = $args['season'];
        if ( ! $season ) {
            return $this->template_renderer->render_alert( __( 'Season not supplied', 'racketmanager' ) );
        }
        if ( method_exists( $league, 'set_season' ) ) {
            $this->league_query_service->set_season( $league, (string) $season );
        }

        $vars     = $this->presenter->present_team_withdrawn_email( $team, $league, (string) $season, $args );
        $filename = ! empty( $args['template'] ) ? 'team-withdrawn-' . $args['template'] : 'team-withdrawn';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * Alias for show_team_withdrawn.
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_withdrawn_team( array|string $atts = [] ): string {
        return $this->show_team_withdrawn( $atts );
    }

    /**
     * [withdrawn-team-match is_tournament=X event=X emailfrom=X round=X subject=X template=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_withdrawn_team_match( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'id'            => '',
            'template'      => '',
            'is_tournament' => false,
            'event'         => '',
            'emailfrom'     => '',
            'round'         => '',
            'subject'       => null,
        ], is_array( $atts ) ? $atts : [] );

        $vars     = $this->presenter->present_withdrawn_team_match( $args );
        $filename = ! empty( $args['template'] ) ? 'match-team-withdrawn-' . $args['template'] : 'match-team-withdrawn';

        return $this->template_renderer->render( $filename, $vars, 'email' );
    }

    /**
     * [event-constitution id=ID season=X template=X]
     *
     * @param array|string $atts shortcode attributes.
     */
    public function show_event_constitution( array|string $atts = [] ): string {
        $args = shortcode_atts( [
            'id'       => false,
            'season'   => null,
            'template' => '',
        ], is_array( $atts ) ? $atts : [] );

        $event_id = $args['id'];
        $event    = $event_id ? $this->resolve_event( $event_id ) : null;
        if ( ! $event ) {
            return $this->template_renderer->render_alert( __( 'Event not found', 'racketmanager' ) );
        }

        if ( $event instanceof \Racketmanager\Domain\Competition\Event ) {
            $event->leagues = $this->event_query_service->get_leagues( $event );
        }
        if ( method_exists( $event, 'set_season' ) && null !== $args['season'] ) {
            $event->set_season( (string) $args['season'] );
        }

        $vars     = $this->presenter->present_event_constitution( $event );
        $filename = ! empty( $args['template'] ) ? 'constitution-' . $args['template'] : 'constitution';

        return $this->template_renderer->render( $filename, $vars, 'event' );
    }

    /**
     * Resolve a team from a shortcode value (legacy `get_team()` shape): entity passthrough,
     * raw object → Team, id or title → repository.
     */
    private function resolve_team( mixed $team ): ?Team {
        if ( $team instanceof Team ) {
            return $team;
        }
        if ( is_object( $team ) ) {
            return new Team( $team );
        }
        return $this->team_repository->find_by_id( is_numeric( $team ) ? (int) $team : (string) $team );
    }

    /**
     * Resolve a league from a shortcode value (legacy `get_league()` shape): entity passthrough,
     * raw object → League_Factory (+ event via embedded object or event_id, + set_season), id or name → repository.
     */
    private function resolve_league( mixed $league ): ?League {
        if ( $league instanceof League ) {
            return $league;
        }
        if ( is_object( $league ) ) {
            $event = null;
            if ( isset( $league->event ) && is_object( $league->event ) ) {
                $event = $league->event instanceof Event ? $league->event : new Event( $league->event );
            } elseif ( ! empty( $league->event_id ) ) {
                $event = $this->event_repository->find_by_id( (int) $league->event_id );
            }
            $resolved = League_Factory::from_object( $league, $event );
            if ( $resolved ) {
                $this->league_query_service->set_season( $resolved, false, true );
            }
            return $resolved;
        }
        return is_numeric( $league ) ? $this->league_repository->find_by_id( (int) $league ) : $this->league_repository->find_by_name( (string) $league );
    }

    /**
     * Resolve an event from a shortcode value (legacy `get_event()` shape): entity passthrough,
     * raw object → Event, id → repository.
     */
    private function resolve_event( mixed $event ): ?Event {
        if ( $event instanceof Event ) {
            return $event;
        }
        if ( is_object( $event ) ) {
            $competition = null;
            if ( ! empty( $event->competition_id ) ) {
                $competition = $this->competition_repository->find_by_id( (int) $event->competition_id );
            }
            return new Event( $event, $competition );
        }
        return $this->event_repository->find_by_id( is_numeric( $event ) ? (int) $event : $event );
    }
}
