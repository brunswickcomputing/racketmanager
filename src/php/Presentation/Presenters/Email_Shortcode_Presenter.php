<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Formatters\Email_Date_Formatter;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;
use stdClass;
use function __;
use function admin_url;
use function is_string;
use function sanitize_title;
use function strlen;
use function substr;
use function Racketmanager\seo_url;

/**
 * Presenter specifically responsible for preparing view data for email and notification shortcodes.
 */
readonly class Email_Shortcode_Presenter {
    public function __construct(
        private Tournament_Service $tournament_service,
        private Competition_Service $competition_service,
        private Team_Service $team_service,
        private Fixture_Link_Service $fixture_link_service,
        private Fixture_Permission_Service $permission_service,
        private string $site_url,
        private string $site_name,
        private ?Rubber_Repository_Interface $rubber_repository = null,
        private ?Option_Gateway_Interface $option_gateway = null,
    ) {
    }

    private function date_formatter(): Email_Date_Formatter {
        return new Email_Date_Formatter( $this->option_gateway );
    }

    /**
     * Prepare presentation variables for [match-notification].
     *
     * @param Fixture $fixture
     * @param array $args
     * @return array
     * @throws Tournament_Not_Found_Exception
     */
    public function present_match_notification( Fixture $fixture, array $args ): array {
        $competition_type = $args['competition_type'] ?? '';
        $tournament_param = $args['tournament'] ?? false;
        $competition      = $args['competition'] ?? '';
        $email_from       = $args['email_from'] ?? ( $args['emailfrom'] ?? '' );
        $round            = $args['round'] ?? '';
        $template         = $args['template'] ?? '';

        $match = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            $this->fixture_link_service,
            $this->permission_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $teams = [
            'home' => new stdClass(),
            'away' => new stdClass(),
        ];

        $home_dtls       = [];
        $away_dtls       = [];
        $tournament_link = '';
        $match_link      = '';
        $cup_link        = '';
        $draw_link       = '';
        $rules_link      = $this->site_url . '/rules/' . $competition_type . '-rules/';
        $tournament      = $tournament_param;

        if ( 'tournament' === $competition_type ) {
            $tournament      = $this->tournament_service->get_tournament( $tournament_param );
            $tournament_path = ( new Tournament_Presenter() )->get_link( $tournament );
            $tournament_link = '<a href="' . $this->site_url . $tournament_path . '">' . $tournament->name . '</a>';
            $draw_link       = '<a href="' . $this->site_url . $tournament_path . 'draw/' . seo_url( $match->league->event->name ?? '' ) . '/">' . ( $match->league->event->name ?? '' ) . '</a>';
            $match_link      = $this->site_url . $tournament_path . 'match/' . seo_url( $match->league->title ?? '' ) . '/' . seo_url( $match->teams['home']->title ?? '' ) . '-vs-' . seo_url( $match->teams['away']->title ?? '' ) . '/' . $match->id . '/';

            $league_type = $match->league->type ?? '';
            if ( is_string( $league_type ) && strlen( $league_type ) >= 2 && 'D' === substr( $league_type, 1, 1 ) ) {
                $teams['home']->title = __( 'Home Players', 'racketmanager' );
                $teams['away']->title = __( 'Away Players', 'racketmanager' );
                $home_dtls['title']   = 'Home Players';
                $away_dtls['title']   = 'Away Players';
            } else {
                $teams['home']->title = __( 'Home Player', 'racketmanager' );
                $teams['away']->title = __( 'Away Player', 'racketmanager' );
                $home_dtls['title']   = 'Home Player';
                $away_dtls['title']   = 'Away Player';
            }
        } elseif ( 'cup' === $competition_type ) {
            $cup_link   = '<a href="' . $this->site_url . '/cups/' . seo_url( $match->league->title ?? '' ) . '/' . ( $match->season ?? '' ) . '/">' . ( $match->league->title ?? '' ) . '</a>';
            $match_link = $this->site_url . ( $match->link ?? '' );
            if ( ! empty( $match->leg ) ) {
                $match_link .= 'leg-' . $match->leg . '/';
            }
            $template             = 'cup';
            $teams['home']->title = __( 'Home Team', 'racketmanager' );
            $teams['away']->title = __( 'Away Team', 'racketmanager' );
            $home_dtls['title']   = 'Home Team';
            $away_dtls['title']   = 'Away Team';
        }

        if ( 'home' === ( $match->host ?? 'home' ) ) {
            $team_1     = 'home';
            $opponent_1 = 'away';
        } else {
            $team_1     = 'away';
            $opponent_1 = 'home';
        }
        $team_2                     = 'home';
        $opponent_2                 = 'away';
        $teams[ $team_1 ]->name     = $match->teams[ $team_2 ]->title ?? '';
        $teams[ $opponent_1 ]->name = $match->teams[ $opponent_2 ]->title ?? '';

        if ( ! empty( $match->teams[ $team_2 ]->club->shortcode ) ) {
            $teams[ $team_1 ]->club = $match->teams[ $team_2 ]->club->shortcode;
        } else {
            $teams[ $team_1 ]->club = __( 'Unknown', 'racketmanager' );
        }
        if ( ! empty( $match->teams[ $opponent_2 ]->club->shortcode ) ) {
            $teams[ $opponent_1 ]->club = $match->teams[ $opponent_2 ]->club->shortcode;
        } else {
            $teams[ $opponent_1 ]->club = __( 'Unknown', 'racketmanager' );
        }

        if ( isset( $match->teams['home']->team_type ) && 'P' === $match->teams['home']->team_type ) {
            $teams[ $team_1 ]->player     = $match->teams[ $team_2 ]->players ?? [];
            $teams[ $opponent_1 ]->player = $match->teams[ $opponent_2 ]->players ?? [];
        } else {
            $teams[ $team_1 ]->captain           = $match->teams[ $team_2 ]->captain ?? '';
            $teams[ $team_1 ]->captain_email     = $match->teams[ $team_2 ]->contactemail ?? '';
            $teams[ $team_1 ]->captain_tel       = $match->teams[ $team_2 ]->contactno ?? '';
            $teams[ $team_1 ]->matchDay          = $match->teams[ $team_2 ]->match_day ?? '';
            $teams[ $team_1 ]->matchTime         = $match->teams[ $team_2 ]->match_time ?? '';
            $teams[ $opponent_1 ]->captain       = $match->teams[ $opponent_2 ]->captain ?? '';
            $teams[ $opponent_1 ]->captain_email = $match->teams[ $opponent_2 ]->contactemail ?? '';
            $teams[ $opponent_1 ]->captain_tel   = $match->teams[ $opponent_2 ]->contactno ?? '';
            $teams[ $opponent_1 ]->matchDay      = $match->teams[ $opponent_2 ]->match_day ?? '';
            $teams[ $opponent_1 ]->matchTime     = $match->teams[ $opponent_2 ]->match_time ?? '';
        }

        $formatter   = $this->date_formatter();
        $comp_name   = $competition ?: ( $match->league->title ?? '' );
        $match_date  = $formatter->date( $fixture->get_date() );
        $match_time  = $formatter->time( $fixture->get_date() );

        return [
            'template'         => $template,
            'tournament'       => $tournament,
            'competition'      => $comp_name,
            'competition_name' => $comp_name,
            'match'            => $match,
            'home_dtls'        => $home_dtls,
            'away_dtls'        => $away_dtls,
            'round'            => $round,
            'organisation'     => $this->site_name,
            'site_url'         => $this->site_url,
            'email_from'       => $email_from,
            'teams'            => $teams,
            'tournament_link'  => $tournament_link,
            'draw_link'        => $draw_link,
            'action_url'       => $match_link,
            'rules_link'       => $rules_link,
            'cup_link'         => $cup_link,
            'match_date'       => $match_date,
            'match_time'       => $match_time,
        ];
    }

    /**
     * Prepare presentation variables for [result-notification].
     *
     * @param Fixture $fixture
     * @param array $args
     * @return array
     */
    public function present_result_notification( Fixture $fixture, array $args ): array {
        $match = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            $this->fixture_link_service,
            $this->permission_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $league    = $args['league'] ?? false;
        $match_day = $args['match_day'] ?? '';
        $round     = $args['round'] ?? false;

        $action_url = admin_url() . '?page=racketmanager&view=results';
        if ( ! empty( $league ) ) {
            $action_url .= '&subpage=show-league&league_id=' . $league;
        }
        if ( ! empty( $match_day ) ) {
            $action_url .= '&match_day=' . $match_day;
        }
        if ( ! empty( $round ) ) {
            $action_url .= '&final=' . $round . '&tab=matches';
        }

        return [
            'match'        => $match,
            'organisation' => $this->site_name,
            'action_url'   => $action_url,
            'complete'     => $args['complete'] ?? false,
            'errors'       => $args['errors'] ?? false,
            'challenge'    => $args['challenge'] ?? false,
            'from_email'   => $args['from_email'] ?? false,
        ];
    }

    /**
     * Prepare presentation variables for [result-notification-captain].
     *
     * @param Fixture $fixture
     * @param array $args
     * @return array
     */
    public function present_captain_result_notification( Fixture $fixture, array $args ): array {
        $match = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $action_url  = $this->site_url;
        $season_name = $match->league->current_season['name'] ?? '';
        if ( isset( $match->league->event->competition->is_championship ) && $match->league->event->competition->is_championship ) {
            $action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ?? '' ) . '/' . $season_name . '/' . ( $match->final_round ?? '' ) . '/' . sanitize_title( $match->teams['home']->title ?? '' ) . '-vs-' . sanitize_title( $match->teams['away']->title ?? '' ) . '/';
            if ( ! empty( $match->leg ) ) {
                $action_url .= 'leg-' . $match->leg . '/';
            }
        } else {
            $action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ?? '' ) . '/' . $season_name . '/day' . ( $match->match_day ?? '' ) . '/' . sanitize_title( $match->teams['home']->title ?? '' ) . '-vs-' . sanitize_title( $match->teams['away']->title ?? '' ) . '/';
        }
        $action_url .= 'result/';

        return [
            'match'                 => $match,
            'organisation'          => $this->site_name,
            'action_url'            => $action_url,
            'outstanding'           => $args['outstanding'] ?? false,
            'time_period'           => $args['time_period'] ?? false,
            'override'              => $args['override'] ?? false,
            'from_email'            => $args['from_email'] ?? false,
            'confirmation_required' => $args['confirmation_required'] ?? false,
            'confirmation_timeout'  => $args['confirmation_timeout'] ?? false,
            'timeout'               => $args['timeout'] ?? false,
            'penalty'               => $args['penalty'] ?? false,
        ];
    }

    /**
     * Prepare presentation variables for [result-outstanding-notification].
     *
     * @param Fixture $fixture
     * @param array $args
     * @return array
     * @throws Tournament_Not_Found_Exception
     */
    public function present_result_outstanding_notification( Fixture $fixture, array $args ): array {
        $match = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            $this->fixture_link_service,
            $this->permission_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $action_url  = $this->site_url;
        $template    = $args['template'] ?? '';
        $season_name = $match->league->current_season['name'] ?? '';

        if ( isset( $match->league->event->competition->is_cup ) && $match->league->event->competition->is_cup ) {
            $action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ?? '' ) . '/' . $season_name . '/' . ( $match->final_round ?? '' ) . '/' . sanitize_title( $match->teams['home']->title ?? '' ) . '-vs-' . sanitize_title( $match->teams['away']->title ?? '' );
        } elseif ( isset( $match->league->event->competition->is_tournament ) && $match->league->event->competition->is_tournament ) {
            $tournament_code = ( $match->league->event->competition->id ?? '' ) . ',' . ( $match->season ?? '' );
            $tournament      = $this->tournament_service->get_tournament( $tournament_code, 'shortcode' );
            $action_url     .= '/' . __( 'tournament', 'racketmanager' ) . '/' . sanitize_title( $tournament->name ) . '/' . __( 'match', 'racketmanager' ) . '/' . $match->id . '/';
        } else {
            $action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ?? '' ) . '/' . $season_name . '/day' . ( $match->match_day ?? '' ) . '/' . sanitize_title( $match->teams['home']->title ?? '' ) . '-vs-' . sanitize_title( $match->teams['away']->title ?? '' );
        }

        if ( empty( $template ) && isset( $match->league->event->competition->type ) && 'tournament' === $match->league->event->competition->type ) {
            $template = 'tournament';
        }

        return [
            'template'     => $template,
            'match'        => $match,
            'organisation' => $this->site_name,
            'action_url'   => $action_url,
            'time_period'  => $args['time_period'] ?? false,
            'timeout'      => $args['timeout'] ?? false,
            'penalty'      => $args['penalty'] ?? false,
            'from_email'   => $args['from_email'] ?? null,
        ];
    }

    /**
     * Prepare presentation variables for [club-player-notification].
     *
     * @param array $args
     * @return array
     */
    public function present_club_player_notification( array $args ): array {
        $club       = $args['club'] ?? '';
        $player     = $args['player'] ?? '';
        $action_url = $this->site_url . '/clubs/' . seo_url( (string) $club ) . '/' . seo_url( (string) $player ) . '/';

        return [
            'action'        => $args['action'] ?? false,
            'club'          => $club,
            'player'        => $player,
            'organisation'  => $this->site_name,
            'action_url'    => $action_url,
            'requestor'     => $args['requestor'] ?? false,
            'btm'           => $args['btm'] ?? false,
            'email_subject' => $this->site_name . ' - ' . __( 'Club Player Request', 'racketmanager' ) . ' - ' . $club,
        ];
    }

    /**
     * Prepare presentation variables for [match_date_change_notification].
     *
     * @param Fixture $fixture
     * @param array $args
     * @return array
     * @throws Tournament_Not_Found_Exception
     */
    public function present_match_date_change_notification( Fixture $fixture, array $args ): array {
        $match = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $competition_type = $args['competition_type'] ?? '';
        $tournament_param = $args['tournament'] ?? false;
        $tournament       = $tournament_param;
        $draw_link        = '';
        $match_link       = '';
        $competition_link = '';
        $tournament_link  = '';
        $rules_link       = $this->site_url . '/rules/' . $competition_type . '-rules/';

        if ( 'tournament' === $competition_type ) {
            $tournament      = $this->tournament_service->get_tournament( $tournament_param );
            $tournament_path = ( new Tournament_Presenter() )->get_link( $tournament );
            $tournament_link = '<a href="' . $this->site_url . $tournament_path . '">' . $tournament->name . '</a>';
            $draw_link       = '<a href="' . $this->site_url . $tournament_path . 'draw/' . seo_url( $match->league->event->name ?? '' ) . '/">' . ( $match->league->event->name ?? '' ) . '</a>';
            $match_link      = $this->site_url . $tournament_path . 'match/' . seo_url( $match->league->title ?? '' ) . '/' . seo_url( $match->teams['home']->title ?? '' ) . '-vs-' . seo_url( $match->teams['away']->title ?? '' ) . '/' . $match->id . '/';
        } elseif ( 'cup' === $competition_type ) {
            $competition_link = '<a href="' . $this->site_url . '/cups/' . seo_url( $match->league->title ?? '' ) . '/' . ( $match->season ?? '' ) . '/">' . ( $match->league->title ?? '' ) . '</a>';
            $match_link       = $this->site_url . ( $match->link ?? '' );
            if ( ! empty( $match->leg ) ) {
                $match_link .= 'leg-' . $match->leg . '/';
            }
        } elseif ( 'league' === $competition_type ) {
            $competition_link = '<a href="' . $this->site_url . '/leagues/' . seo_url( $match->league->event->name ?? '' ) . '/' . ( $match->season ?? '' ) . '/">' . ( $match->league->title ?? '' ) . '</a>';
            $match_link       = $this->site_url . ( $match->link ?? '' );
        }

        // The [match-date-change-notification] shortcode receives raw MySQL datetimes; always format them here.
        $formatter         = $this->date_formatter();
        $raw_new_date      = ! empty( $args['new_date'] ) ? (string) $args['new_date'] : $fixture->get_date();
        $raw_original_date = ! empty( $args['original_date'] ) ? (string) $args['original_date'] : $fixture->get_date_original();
        $match_date        = $formatter->date_change( $raw_new_date );
        $original_date     = $formatter->date_change( $raw_original_date );
        $comp_name         = ( $args['competition'] ?? '' ) ?: ( $match->league->title ?? '' );

        return [
            'tournament'       => $tournament,
            'competition'      => $comp_name,
            'competition_name' => $comp_name,
            'match'            => $match,
            'match_date'       => $match_date,
            'new_date'         => $match_date,
            'original_date'    => $original_date,
            'round'            => $args['round'] ?? '',
            'organisation'     => $this->site_name,
            'site_url'         => $this->site_url,
            'email_from'       => $args['email_from'] ?? ( $args['emailfrom'] ?? '' ),
            'from_email'       => $args['email_from'] ?? ( $args['emailfrom'] ?? '' ),
            'draw_link'        => $draw_link,
            'action_url'       => $match_link,
            'rules_link'       => $rules_link,
            'competition_link' => $competition_link,
            'tournament_link'  => $tournament_link,
            'delay'            => $args['delay'] ?? false,
            'email_subject'    => $args['email_subject'] ?? '',
        ];
    }

    /**
     * Prepare presentation variables for [team-withdrawn].
     *
     * @param object $team
     * @param object $league
     * @param string $season
     * @param array $args
     * @return array
     */
    public function present_team_withdrawn_email( object $team, object $league, string $season, array $args ): array {
        return [
            'team'          => $team,
            'league'        => $league,
            'season'        => $season,
            'organisation'  => $this->site_name,
            'email_subject' => $args['subject'] ?? false,
            'email_from'    => $args['from'] ?? false,
        ];
    }

    /**
     * Prepare presentation variables for [withdrawn-team-match].
     *
     * @param array $args
     * @return array
     */
    public function present_withdrawn_team_match( array $args ): array {
        return [
            'is_tournament' => $args['is_tournament'] ?? false,
            'event'         => $args['event'] ?? '',
            'round'         => $args['round'] ?? '',
            'organisation'  => $this->site_name,
            'email_from'    => $args['emailfrom'] ?? ( $args['email_from'] ?? '' ),
            'email_subject' => $args['subject'] ?? null,
        ];
    }

    /**
     * Prepare presentation variables for [event-constitution].
     *
     * @param object $event
     * @return array
     */
    public function present_event_constitution( object $event ): array {
        $league_urls = [];
        if ( isset( $event->leagues ) && is_iterable( $event->leagues ) ) {
            $presenter   = new Competition_Shortcode_Presenter();
            $league_urls = $presenter->present_event_league_urls( $event, $event->leagues, null, $this->site_url );
        }

        return [
            'event'        => $event,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
            'league_urls'  => $league_urls,
        ];
    }
}
