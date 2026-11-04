<?php
declare( strict_types=1 );
/**
 * Presentation Service Provider
 *
 * Registers presentation layer components, presenters, and template renderers in the container.
 *
 * @package RacketManager
 * @subpackage Presentation
 */

namespace Racketmanager\Presentation;

use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Favourite_Presenter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Matches_List_Presenter;
use Racketmanager\Presentation\Presenters\Message_Presenter;
use Racketmanager\Presentation\Presenters\Notification_Presenter;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Providers\Service_Provider_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Notification\Notification_Presenter_Interface;
use Racketmanager\Services\View\View_Renderer_Interface;

final readonly class Presentation_Service_Provider implements Service_Provider_Interface {

    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Presentation presenters & helpers
        $container->set( 'club_presenter', fn() => new Club_Presenter() );
        $container->alias( Club_Presenter::class, 'club_presenter' );

        $container->set( 'player_presenter', fn( Simple_Container $c ) => new Player_Presenter(
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'option_gateway' )
        ) );
        $container->alias( Player_Presenter::class, 'player_presenter' );

        $container->set( 'favourite_presenter', fn( Simple_Container $c ) => new Favourite_Presenter( $c->get( 'user_favourites_gateway' ) ) );
        $container->alias( Favourite_Presenter::class, 'favourite_presenter' );

        $container->set( 'invoice_presenter', fn() => new Invoice_Presenter() );
        $container->alias( Invoice_Presenter::class, 'invoice_presenter' );

        $container->set( 'message_presenter', fn() => new Message_Presenter() );
        $container->alias( Message_Presenter::class, 'message_presenter' );

        $container->set( 'notification_presenter', fn( Simple_Container $c ): Notification_Presenter_Interface => new Notification_Presenter(
            $c->get( 'team_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'tournament_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'team_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'fixture_permission_service' ),
            $app?->site_url ?? '',
            $app?->site_name ?? '',
            $c->get( 'league_query_service' ),
            $c->get( 'rubber_repository' ),
            $c->get( 'option_gateway' )
        ) );
        $container->alias( Notification_Presenter_Interface::class, 'notification_presenter' );
        $container->alias( Notification_Presenter::class, 'notification_presenter' );

        $container->set( 'competition_shortcode_presenter', fn( Simple_Container $c ) => new Competition_Shortcode_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            option_gateway: $c->get( 'option_gateway' )
        ) );
        $container->alias( Competition_Shortcode_Presenter::class, 'competition_shortcode_presenter' );

        $container->set( 'tournament_presenter', fn( Simple_Container $c ) => new Tournament_Presenter(
            date_format: (string) $c->get( 'option_gateway' )->get_option( 'date_format', 'Y-m-d' ),
            fixture_detail_service: $c->get( 'fixture_detail_service' ),
            team_match_row_presenter: $c->get( 'team_match_row_presenter' )
        ) );
        $container->alias( Tournament_Presenter::class, 'tournament_presenter' );

        $container->set( 'league_archive_presenter', fn( Simple_Container $c ) => new League_Archive_Presenter(
            $c->get( 'option_gateway' )
        ) );
        $container->alias( League_Archive_Presenter::class, 'league_archive_presenter' );

        $container->set( 'league_standings_presenter', fn( Simple_Container $c ) => new League_Standings_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $container->alias( League_Standings_Presenter::class, 'league_standings_presenter' );

        $container->set( 'order_of_play_presenter', fn( Simple_Container $c ) => new Order_Of_Play_Presenter(
            fixture_repository: $c->get( 'fixture_repository' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            option_gateway: $c->get( 'option_gateway' )
        ) );
        $container->alias( Order_Of_Play_Presenter::class, 'order_of_play_presenter' );

        $container->set( 'team_details_presenter', fn( Simple_Container $c ) => new Team_Details_Presenter(
            $c->get( 'option_gateway' ),
            $c->get( 'settings_service' )
        ) );
        $container->alias( Team_Details_Presenter::class, 'team_details_presenter' );

        $container->set( 'club_shortcode_presenter', fn( Simple_Container $c ) => new Club_Shortcode_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' ),
            $c->get( 'favourite_presenter' )
        ) );
        $container->alias( Club_Shortcode_Presenter::class, 'club_shortcode_presenter' );

        $container->set( 'team_match_row_presenter', fn( Simple_Container $c ) => new Team_Match_Row_Presenter(
            $c->get( 'option_gateway' ),
            $c->get( 'fixture_relation_loader' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'club_repository' ),
            $c->get( 'league_repository' )
        ) );
        $container->alias( Team_Match_Row_Presenter::class, 'team_match_row_presenter' );

        $container->set( 'fixture_presenter', fn( Simple_Container $c ) => new Fixture_Presenter(
            $c->get( 'fixture_link_service' ),
            $c->get( 'option_gateway' ),
            $c->get( View_Renderer_Interface::class ),
            $c->get( 'team_match_row_presenter' )
        ) );
        $container->alias( Fixture_Presenter::class, 'fixture_presenter' );

        $container->set( 'results_checker_presenter', fn( Simple_Container $c ) => new Results_Checker_Presenter(
            $c->get( 'fixture_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'fixture_detail_service' )
        ) );
        $container->alias( Results_Checker_Presenter::class, 'results_checker_presenter' );

        $container->set( 'matches_list_presenter', fn( Simple_Container $c ) => new Matches_List_Presenter(
            $c->get( 'competition_service' ),
            $c->get( 'team_service' ),
            $c->get( 'view_renderer' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' ),
            $c->get( 'rubber_repository' )
        ) );
        $container->alias( Matches_List_Presenter::class, 'matches_list_presenter' );

        $container->set( 'match_shortcode_presenter', fn( Simple_Container $c ) => new Match_Shortcode_Presenter(
            $c->get( 'shortcode_view_presenter' ),
            $c->get( 'fixture_presenter' ),
            $c->get( 'player_service' )
        ) );
        $container->alias( Match_Shortcode_Presenter::class, 'match_shortcode_presenter' );

        $container->set( 'shortcode_template_renderer', fn( Simple_Container $c ) => new Shortcode_Template_Renderer(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' ),
            $c->get( 'favourite_presenter' )
        ) );
        $container->alias( Shortcode_Template_Renderer::class, 'shortcode_template_renderer' );

        $container->set( 'shortcode_view_presenter', fn( Simple_Container $c ) => new Shortcode_View_Presenter( $c->get( 'shortcode_template_renderer' ) ) );
        $container->alias( Shortcode_View_Presenter::class, 'shortcode_view_presenter' );

        $container->set( 'email_shortcode_presenter', fn( Simple_Container $c ) => new Email_Shortcode_Presenter(
            tournament_service: $c->get( 'tournament_service' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            fixture_link_service: $c->get( 'fixture_link_service' ),
            permission_service: $c->get( 'fixture_permission_service' ),
            site_url: $app?->site_url ?? '',
            site_name: $app?->site_name ?? '',
            rubber_repository: $c->get( 'rubber_repository' ),
            option_gateway: $c->get( 'option_gateway' )
        ) );
        $container->alias( Email_Shortcode_Presenter::class, 'email_shortcode_presenter' );
    }
}
