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

use Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Email_Shortcode_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Invoice_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Message_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Player_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface;
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
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Container\Providers\Service_Provider_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Notification\Notification_Presenter_Interface;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\View_Renderer_Interface;

/**
 * Service provider for Presentation layer.
 */
final readonly class Presentation_Service_Provider implements Service_Provider_Interface {

    /**
     * Presentation_Service_Provider constructor.
     *
     * @param RacketManager|null $app Application instance.
     */
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    /**
     * Registers presentation services in the container.
     *
     * @param Simple_Container $container The service container.
     * @return void
     */
    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Presentation presenters & helpers.
        $container->set( Club_Presenter::class, fn(): Club_Presenter => new Club_Presenter() );
        $container->alias( 'club_presenter', Club_Presenter::class );

        $container->set(
            Player_Presenter_Interface::class,
            fn( Simple_Container $c ): Player_Presenter_Interface => new Player_Presenter(
                fn() => $c->get( Competition_Shortcode_Presenter::class ),
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( Player_Presenter::class, Player_Presenter_Interface::class );
        $container->alias( 'player_presenter', Player_Presenter_Interface::class );

        $container->set( Favourite_Presenter::class, fn( Simple_Container $c ): Favourite_Presenter => new Favourite_Presenter( $c->get( User_Favourites_Gateway_Interface::class ) ) );
        $container->alias( 'favourite_presenter', Favourite_Presenter::class );

        $container->set( Invoice_Presenter_Interface::class, fn(): Invoice_Presenter_Interface => new Invoice_Presenter() );
        $container->alias( Invoice_Presenter::class, Invoice_Presenter_Interface::class );
        $container->alias( 'invoice_presenter', Invoice_Presenter_Interface::class );

        $container->set( Message_Presenter_Interface::class, fn(): Message_Presenter_Interface => new Message_Presenter() );
        $container->alias( Message_Presenter::class, Message_Presenter_Interface::class );
        $container->alias( 'message_presenter', Message_Presenter_Interface::class );

        $container->set(
            Notification_Presenter_Interface::class,
            fn( Simple_Container $c ): Notification_Presenter_Interface => new Notification_Presenter(
                $c->get( Team_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Tournament_Repository_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Fixture_Link_Service::class ),
                $c->get( Team_Service_Interface::class ),
                $c->get( Competition_Service_Interface::class ),
                $c->get( Fixture_Permission_Service::class ),
                $app?->site_url ?? '',
                $app?->site_name ?? '',
                $c->get( League_Query_Service_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( Notification_Presenter::class, Notification_Presenter_Interface::class );
        $container->alias( 'notification_presenter', Notification_Presenter_Interface::class );

        $container->set(
            Competition_Shortcode_Presenter::class,
            fn( Simple_Container $c ): Competition_Shortcode_Presenter => new Competition_Shortcode_Presenter(
                $c->get( View_Renderer_Interface::class ),
                $c->get( Club_Presenter::class ),
                $c->get( Player_Presenter_Interface::class ),
                option_gateway: $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( 'competition_shortcode_presenter', Competition_Shortcode_Presenter::class );

        $container->set(
            Tournament_Presenter::class,
            fn( Simple_Container $c ): Tournament_Presenter => new Tournament_Presenter(
                date_format: (string) $c->get( Option_Gateway_Interface::class )->get_option( 'date_format', 'Y-m-d' ),
                fixture_detail_service: $c->get( Fixture_Detail_Service::class ),
                team_match_row_presenter: $c->get( Team_Match_Row_Presenter::class )
            )
        );
        $container->alias( 'tournament_presenter', Tournament_Presenter::class );

        $container->set(
            League_Archive_Presenter::class,
            fn( Simple_Container $c ): League_Archive_Presenter => new League_Archive_Presenter(
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( 'league_archive_presenter', League_Archive_Presenter::class );

        $container->set(
            League_Standings_Presenter::class,
            fn( Simple_Container $c ): League_Standings_Presenter => new League_Standings_Presenter(
                $c->get( View_Renderer_Interface::class ),
                $c->get( Club_Presenter::class ),
                $c->get( Player_Presenter_Interface::class )
            )
        );
        $container->alias( 'league_standings_presenter', League_Standings_Presenter::class );

        $container->set(
            Order_Of_Play_Presenter::class,
            fn( Simple_Container $c ): Order_Of_Play_Presenter => new Order_Of_Play_Presenter(
                fixture_repository: $c->get( Fixture_Repository_Interface::class ),
                competition_service: $c->get( Competition_Service_Interface::class ),
                team_service: $c->get( Team_Service_Interface::class ),
                option_gateway: $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( 'order_of_play_presenter', Order_Of_Play_Presenter::class );

        $container->set(
            Team_Details_Presenter::class,
            fn( Simple_Container $c ): Team_Details_Presenter => new Team_Details_Presenter(
                $c->get( Option_Gateway_Interface::class ),
                $c->get( Settings_Service::class )
            )
        );
        $container->alias( 'team_details_presenter', Team_Details_Presenter::class );

        $container->set(
            Club_Shortcode_Presenter::class,
            fn( Simple_Container $c ): Club_Shortcode_Presenter => new Club_Shortcode_Presenter(
                $c->get( View_Renderer_Interface::class ),
                $c->get( Club_Presenter::class ),
                $c->get( Player_Presenter_Interface::class ),
                $c->get( Favourite_Presenter::class )
            )
        );
        $container->alias( 'club_shortcode_presenter', Club_Shortcode_Presenter::class );

        $container->set(
            Team_Match_Row_Presenter::class,
            fn( Simple_Container $c ): Team_Match_Row_Presenter => new Team_Match_Row_Presenter(
                $c->get( Option_Gateway_Interface::class ),
                $c->get( Fixture_Relation_Loader::class ),
                $c->get( Fixture_Permission_Service::class ),
                $c->get( Fixture_Link_Service::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class )
            )
        );
        $container->alias( 'team_match_row_presenter', Team_Match_Row_Presenter::class );

        $container->set(
            Fixture_Presenter::class,
            fn( Simple_Container $c ): Fixture_Presenter => new Fixture_Presenter(
                $c->get( Fixture_Link_Service::class ),
                $c->get( Option_Gateway_Interface::class ),
                $c->get( View_Renderer_Interface::class ),
                $c->get( Team_Match_Row_Presenter::class )
            )
        );
        $container->alias( 'fixture_presenter', Fixture_Presenter::class );

        $container->set(
            Results_Checker_Presenter::class,
            fn( Simple_Container $c ): Results_Checker_Presenter => new Results_Checker_Presenter(
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( Fixture_Detail_Service::class )
            )
        );
        $container->alias( 'results_checker_presenter', Results_Checker_Presenter::class );

        $container->set(
            Matches_List_Presenter::class,
            fn( Simple_Container $c ): Matches_List_Presenter => new Matches_List_Presenter(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Team_Service_Interface::class ),
                $c->get( View_Renderer_Interface::class ),
                $c->get( Tournament_Presenter::class ),
                $c->get( Club_Presenter::class ),
                $c->get( Player_Presenter_Interface::class ),
                $c->get( Fixture_Link_Service::class ),
                $c->get( Fixture_Permission_Service::class ),
                $c->get( Rubber_Repository_Interface::class )
            )
        );
        $container->alias( 'matches_list_presenter', Matches_List_Presenter::class );

        $container->set(
            Match_Shortcode_Presenter::class,
            fn( Simple_Container $c ): Match_Shortcode_Presenter => new Match_Shortcode_Presenter(
                $c->get( Shortcode_View_Presenter_Interface::class ),
                $c->get( Fixture_Presenter::class ),
                $c->get( Player_Service_Interface::class )
            )
        );
        $container->alias( 'match_shortcode_presenter', Match_Shortcode_Presenter::class );

        $container->set(
            Template_Renderer_Interface::class,
            fn( Simple_Container $c ): Template_Renderer_Interface => new Shortcode_Template_Renderer(
                $c->get( View_Renderer_Interface::class ),
                $c->get( Club_Presenter::class ),
                $c->get( Player_Presenter_Interface::class ),
                $c->get( Favourite_Presenter::class )
            )
        );
        $container->alias( Shortcode_Template_Renderer::class, Template_Renderer_Interface::class );
        $container->alias( 'shortcode_template_renderer', Template_Renderer_Interface::class );

        $container->set(
            Shortcode_View_Presenter_Interface::class,
            fn( Simple_Container $c ): Shortcode_View_Presenter_Interface => new Shortcode_View_Presenter(
                $c->get( Template_Renderer_Interface::class )
            )
        );
        $container->alias( Shortcode_View_Presenter::class, Shortcode_View_Presenter_Interface::class );
        $container->alias( 'shortcode_view_presenter', Shortcode_View_Presenter_Interface::class );

        $container->set(
            Email_Shortcode_Presenter_Interface::class,
            fn( Simple_Container $c ): Email_Shortcode_Presenter_Interface => new Email_Shortcode_Presenter(
                tournament_service: $c->get( Tournament_Service_Interface::class ),
                competition_service: $c->get( Competition_Service_Interface::class ),
                team_service: $c->get( Team_Service_Interface::class ),
                fixture_link_service: $c->get( Fixture_Link_Service::class ),
                permission_service: $c->get( Fixture_Permission_Service::class ),
                site_url: $app?->site_url ?? '',
                site_name: $app?->site_name ?? '',
                rubber_repository: $c->get( Rubber_Repository_Interface::class ),
                option_gateway: $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( Email_Shortcode_Presenter::class, Email_Shortcode_Presenter_Interface::class );
        $container->alias( 'email_shortcode_presenter', Email_Shortcode_Presenter_Interface::class );
    }
}
