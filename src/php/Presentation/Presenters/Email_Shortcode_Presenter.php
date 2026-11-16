<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Email_Shortcode_Presenter_Interface;
use Racketmanager\Presentation\Formatters\Email_Date_Formatter;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;
use function __;
use function admin_url;
use function is_string;
use function sanitize_title;
use function strlen;
use function substr;

/**
 * Presenter specifically responsible for preparing view data for email and notification shortcodes.
 */
readonly class Email_Shortcode_Presenter implements Email_Shortcode_Presenter_Interface {
    /**
     * Initialises the service with required dependencies and site configuration.
     *
     * @param Tournament_Service               $tournament_service Tournament service dependency.
     * @param Competition_Service              $competition_service Competition service dependency.
     * @param Team_Service                     $team_service Team service dependency.
     * @param Fixture_Link_Service             $fixture_link_service Fixture link service dependency.
     * @param Fixture_Permission_Service       $permission_service Fixture permission service dependency.
     * @param string                           $site_url Site URL.
     * @param string                           $site_name Site name.
     * @param Rubber_Repository_Interface|null $rubber_repository Optional rubber repository dependency.
     * @param Option_Gateway_Interface|null    $option_gateway Optional option gateway dependency.
     *
     * @return void
     */
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

    /**
     * Create the email date formatter.
     *
     * @return Email_Date_Formatter
     */
    private function date_formatter(): Email_Date_Formatter {
        return new Email_Date_Formatter( $this->option_gateway );
    }

    /**
     * Prepare presentation variables for [match-notification].
     *
     * @param Fixture $fixture Fixture details.
     * @param array   $args    Query arguments.
     * @return array
     * @throws Tournament_Not_Found_Exception Tournament not found.
     */
    public function present_match_notification( Fixture $fixture, array $args ): array {
        $competition_type = $this->normalize_competition_type( $args['competition_type'] ?? '' );
        $match            = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            $this->fixture_link_service,
            $this->permission_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $comp_context = $this->build_competition_context( $match, $competition_type, $args['tournament'] ?? false );
        $teams        = $this->build_teams_presentation( $match, $comp_context['home_title'], $comp_context['away_title'] );
        $formatter    = $this->date_formatter();
        $comp_name    = ( $args['competition'] ?? '' ) ?: ( $match->league->title ?? '' );

        return [
            'template'         => $comp_context['template'] ?: ( $args['template'] ?? '' ),
            'tournament'       => $comp_context['tournament'],
            'competition'      => $comp_name,
            'competition_name' => $comp_name,
            'match'            => $match,
            'home_dtls'        => $comp_context['home_dtls'],
            'away_dtls'        => $comp_context['away_dtls'],
            'round'            => $args['round'] ?? '',
            'organisation'     => $this->site_name,
            'site_url'         => $this->site_url,
            'email_from'       => $args['email_from'] ?? ( $args['emailfrom'] ?? '' ),
            'teams'            => $teams,
            'tournament_link'  => $comp_context['tournament_link'],
            'draw_link'        => $comp_context['draw_link'],
            'action_url'       => $comp_context['action_url'],
            'rules_link'       => $this->site_url . '/rules/' . $competition_type . '-rules/',
            'cup_link'         => $comp_context['cup_link'],
            'match_date'       => $formatter->date( $fixture->get_date() ),
            'match_time'       => $formatter->time( $fixture->get_date() ),
        ];
    }

    /**
     * Prepare presentation variables for [result-notification].
     *
     * @param Fixture $fixture Fixture details.
     * @param array   $args    Query arguments.
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
     * @param Fixture $fixture Fixture details.
     * @param array   $args    Query arguments.
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
     * @param Fixture $fixture Fixture details.
     * @param array   $args    Query arguments.
     * @return array
     * @throws Tournament_Not_Found_Exception Tournament not found.
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

        $raw_comp_type = $match->league->event->competition->type ?? '';
        $comp_type     = $raw_comp_type instanceof BackedEnum ? $raw_comp_type->value : (string) $raw_comp_type;
        if ( empty( $template ) && 'tournament' === $comp_type ) {
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
     * @param array $args Query arguments.
     * @return array
     */
    public function present_club_player_notification( array $args ): array {
        $club       = $args['club'] ?? '';
        $player     = $args['player'] ?? '';
        $action_url = $this->site_url . '/clubs/' . Seo_Slug::encode( (string) $club ) . '/' . Seo_Slug::encode( (string) $player ) . '/';

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
     * @param Fixture $fixture Fixture details.
     * @param array   $args Query arguments.
     * @return array
     * @throws Tournament_Not_Found_Exception Tournament not found.
     */
    public function present_match_date_change_notification( Fixture $fixture, array $args ): array {
        $match = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $this->competition_service,
            $this->team_service,
            option_gateway: $this->option_gateway,
            rubber_repository: $this->rubber_repository
        );

        $competition_type = $this->normalize_competition_type( $args['competition_type'] ?? '' );
        $tournament_param = $args['tournament'] ?? false;
        $tournament       = $tournament_param;
        $draw_link        = '';
        $match_link       = '';
        $competition_link = '';
        $tournament_link  = '';
        $rules_link       = $this->site_url . '/rules/' . $competition_type . '-rules/';

        if ( 'tournament' === $competition_type ) {
            $tournament      = $this->tournament_service->get_tournament( $tournament_param );
            $tournament_path = new Tournament_Presenter()->get_link( $tournament );
            $tournament_link = '<a href="' . $this->site_url . $tournament_path . '">' . $tournament->name . '</a>';
            $draw_link       = '<a href="' . $this->site_url . $tournament_path . 'draw/' . Seo_Slug::encode( $match->league->event->name ?? '' ) . '/">' . ( $match->league->event->name ?? '' ) . '</a>';
            $match_link      = $this->site_url . $tournament_path . 'match/' . Seo_Slug::encode( $match->league->title ?? '' ) . '/' . Seo_Slug::encode( $match->teams['home']->title ?? '' ) . '-vs-' . Seo_Slug::encode( $match->teams['away']->title ?? '' ) . '/' . $match->id . '/';
        } elseif ( 'cup' === $competition_type ) {
            $competition_link = '<a href="' . $this->site_url . '/cups/' . Seo_Slug::encode( $match->league->title ?? '' ) . '/' . ( $match->season ?? '' ) . '/">' . ( $match->league->title ?? '' ) . '</a>';
            $match_link       = $this->site_url . ( $match->link ?? '' );
            if ( ! empty( $match->leg ) ) {
                $match_link .= 'leg-' . $match->leg . '/';
            }
        } elseif ( 'league' === $competition_type ) {
            $competition_link = '<a href="' . $this->site_url . '/leagues/' . Seo_Slug::encode( $match->league->event->name ?? '' ) . '/' . ( $match->season ?? '' ) . '/">' . ( $match->league->title ?? '' ) . '</a>';
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
     * @param object $team Team details.
     * @param object $league League details.
     * @param string $season Season details.
     * @param array  $args Query arguments.
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
     * @param array $args Query arguments.
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
     * @param object $event Event details.
     * @return array
     */
    public function present_event_constitution( object $event ): array {
        $league_urls = [];
        if ( isset( $event->leagues ) && is_iterable( $event->leagues ) ) {
            $league_urls = Competition_Shortcode_Presenter::present_event_league_urls( $event, $event->leagues, null, $this->site_url );
        }

        return [
            'event'        => $event,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
            'league_urls'  => $league_urls,
        ];
    }

    /**
     * Determine whether a tournament league is formatted for doubles.
     *
     * @param mixed $league_type League type identifier.
     *
     * @return bool
     */
    private function is_doubles_league( mixed $league_type ): bool {
        return is_string( $league_type ) && strlen( $league_type ) >= 2 && 'D' === substr( $league_type, 1, 1 );
    }

    /**
     * Build competition context including links, template, and titles.
     *
     * @param object $match            Match adapter object.
     * @param mixed  $competition_type Competition type identifier.
     * @param mixed  $tournament_param Tournament parameter identifier.
     *
     * @return array
     * @throws Tournament_Not_Found_Exception When tournament cannot be found.
     */
    private function build_competition_context( object $match, mixed $competition_type, mixed $tournament_param ): array {
        $tournament      = $tournament_param;
        $tournament_link = '';
        $draw_link       = '';
        $action_url      = '';
        $cup_link        = '';
        $template        = '';
        $home_dtls       = [];
        $away_dtls       = [];
        $home_title      = null;
        $away_title      = null;

        if ( 'tournament' === $competition_type ) {
            $tournament      = $this->tournament_service->get_tournament( $tournament_param );
            $tournament_path = new Tournament_Presenter()->get_link( $tournament );
            $tournament_link = '<a href="' . $this->site_url . $tournament_path . '">' . $tournament->name . '</a>';
            $draw_link       = '<a href="' . $this->site_url . $tournament_path . 'draw/' . Seo_Slug::encode( $match->league->event->name ?? '' ) . '/">' . ( $match->league->event->name ?? '' ) . '</a>';
            $action_url      = $this->site_url . $tournament_path . 'match/' . Seo_Slug::encode( $match->league->title ?? '' ) . '/' . Seo_Slug::encode( $match->teams['home']->title ?? '' ) . '-vs-' . Seo_Slug::encode( $match->teams['away']->title ?? '' ) . '/' . $match->id . '/';

            if ( $this->is_doubles_league( $match->league->type ?? '' ) ) {
                $home_title         = __( 'Home Players', 'racketmanager' );
                $away_title         = __( 'Away Players', 'racketmanager' );
                $home_dtls['title'] = 'Home Players';
                $away_dtls['title'] = 'Away Players';
            } else {
                $home_title         = __( 'Home Player', 'racketmanager' );
                $away_title         = __( 'Away Player', 'racketmanager' );
                $home_dtls['title'] = 'Home Player';
                $away_dtls['title'] = 'Away Player';
            }
        } elseif ( 'cup' === $competition_type ) {
            $cup_link   = '<a href="' . $this->site_url . '/cups/' . Seo_Slug::encode( $match->league->title ?? '' ) . '/' . ( $match->season ?? '' ) . '/">' . ( $match->league->title ?? '' ) . '</a>';
            $action_url = $this->site_url . ( $match->link ?? '' );
            if ( ! empty( $match->leg ) ) {
                $action_url .= 'leg-' . $match->leg . '/';
            }
            $template           = 'cup';
            $home_title         = __( 'Home Team', 'racketmanager' );
            $away_title         = __( 'Away Team', 'racketmanager' );
            $home_dtls['title'] = 'Home Team';
            $away_dtls['title'] = 'Away Team';
        }

        return [
            'tournament'      => $tournament,
            'tournament_link' => $tournament_link,
            'draw_link'       => $draw_link,
            'action_url'      => $action_url,
            'cup_link'        => $cup_link,
            'template'        => $template,
            'home_dtls'       => $home_dtls,
            'away_dtls'       => $away_dtls,
            'home_title'      => $home_title,
            'away_title'      => $away_title,
        ];
    }

    /**
     * Populate captain contact details on target presentation object.
     *
     * @param stdClass    $target      Target presentation object.
     * @param object|null $source_team Source team object.
     *
     * @return void
     */
    private function populate_captain_info( stdClass $target, ?object $source_team ): void {
        $target->captain       = $source_team->captain ?? '';
        $target->captain_email = $source_team->contactemail ?? '';
        $target->captain_tel   = $source_team->contactno ?? '';
        $target->matchDay      = $source_team->match_day ?? ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
        $target->matchTime     = $source_team->match_time ?? ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
    }

    /**
     * Populate team presentation attributes including name, club, and roster or captain info.
     *
     * @param stdClass    $target         Target presentation object.
     * @param object|null $source_team    Source team object.
     * @param bool        $is_player_type Whether team is in player mode.
     *
     * @return void
     */
    private function populate_team_attributes( stdClass $target, ?object $source_team, bool $is_player_type ): void {
        $target->name = $source_team->title ?? '';

        if ( ! empty( $source_team->club->shortcode ) ) {
            $target->club = $source_team->club->shortcode;
        } else {
            $target->club = __( 'Unknown', 'racketmanager' );
        }

        if ( $is_player_type ) {
            $target->player = $source_team->players ?? [];
        } else {
            $this->populate_captain_info( $target, $source_team );
        }
    }

    /**
     * Build teams presentation array with host/visitor orientation and titles.
     *
     * @param object      $match      Match adapter object.
     * @param string|null $home_title Optional home team/player title.
     * @param string|null $away_title Optional away team/player title.
     *
     * @return array<string, stdClass>
     */
    private function build_teams_presentation( object $match, ?string $home_title, ?string $away_title ): array {
        $teams = [
            'home' => new stdClass(),
            'away' => new stdClass(),
        ];

        if ( null !== $home_title ) {
            $teams['home']->title = $home_title;
        }
        if ( null !== $away_title ) {
            $teams['away']->title = $away_title;
        }

        $is_host_home   = 'home' === ( $match->host ?? 'home' );
        $source_home    = $match->teams['home'] ?? null;
        $source_away    = $match->teams['away'] ?? null;
        $is_player_type = isset( $source_home->team_type ) && 'P' === $source_home->team_type;

        $target_for_source_home = $is_host_home ? $teams['home'] : $teams['away'];
        $target_for_source_away = $is_host_home ? $teams['away'] : $teams['home'];

        $this->populate_team_attributes( $target_for_source_home, $source_home, $is_player_type );
        $this->populate_team_attributes( $target_for_source_away, $source_away, $is_player_type );

        return $teams;
    }

    /**
     * Normalise competition type to string value.
     *
     * @param mixed $type Competition type.
     * @return string
     */
    private function normalize_competition_type( mixed $type ): string {
        if ( $type instanceof BackedEnum ) {
            return (string) $type->value;
        }

        return (string) ( $type ?? '' );
    }
}
