<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Handler;
use Racketmanager\Application\Matches\DTOs\Match_Detail_Result;
use Racketmanager\Application\Matches\DTOs\Match_Header_Result;
use Racketmanager\Application\Matches\DTOs\Match_Score_Result;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Handler;
use Racketmanager\Application\Matches\DTOs\Match_List_Result;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Presentation\View_Models\Matches_List_View_Model;
use Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Matches_List_Presenter;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

class Shortcode_Adapters_Bootstrap_Test extends TestCase {
    use Creates_Competition_Season_Service;
    private RacketManager $racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->racketmanager = $this->createStub( RacketManager::class );
        $this->racketmanager->container = new Simple_Container();
    }

    public function test_adapters_instantiate(): void {
        $club_adapter = $this->club_adapter();
        $this->assertInstanceOf( Club_Shortcode_Adapter::class, $club_adapter );

        $comp_adapter = $this->competition_adapter();
        $this->assertInstanceOf( Competition_Shortcode_Adapter::class, $comp_adapter );

        $event_adapter = $this->event_adapter();
        $this->assertInstanceOf( Event_Shortcode_Adapter::class, $event_adapter );

        $league_adapter = $this->league_adapter();
        $this->assertInstanceOf( League_Shortcode_Adapter::class, $league_adapter );

        $matches_adapter = $this->matches_adapter();
        $this->assertInstanceOf( Matches_Shortcode_Adapter::class, $matches_adapter );

        $email_adapter = $this->email_adapter();
        $this->assertInstanceOf( Email_Shortcode_Adapter::class, $email_adapter );

        $tournament_adapter = $this->tournament_adapter();
        $this->assertInstanceOf( Tournament_Shortcode_Adapter::class, $tournament_adapter );

        $login_adapter = $this->login_adapter();
        $this->assertInstanceOf( Login_Shortcode_Adapter::class, $login_adapter );

        $message_adapter = new Message_Shortcode_Adapter(
            $this->racketmanager,
            $this->createStub( Shortcode_View_Presenter::class ),
            $this->createStub( Get_Messages_Handler::class ),
            $this->createStub( Get_Message_Handler::class )
        );
        $this->assertInstanceOf( Message_Shortcode_Adapter::class, $message_adapter );

        $general_adapter = $this->general_adapter();
        $this->assertInstanceOf( General_Shortcode_Adapter::class, $general_adapter );
    }

    public function test_load_shortcodes_callbacks_are_callable(): void {
        $source = file_get_contents( dirname( __DIR__, 5 ) . '/src/php/RacketManager.php' );
        $this->assertNotFalse( $source );

        preg_match( '/function load_shortcodes\(\): void \{(.*?)\n    \}/s', $source, $method );
        $this->assertNotEmpty( $method[1] ?? '' );

        preg_match_all(
            '/add_shortcode\(\s*\'([^\']+)\'\s*,\s*\[\s*\$([a-z_]+)\s*,\s*\'([^\']+)\'\s*\]\s*\)/',
            $method[1],
            $matches,
            PREG_SET_ORDER
        );
        $this->assertNotEmpty( $matches );

        $raw_add_shortcode_count = substr_count( $method[1], 'add_shortcode(' );
        $this->assertSame( $raw_add_shortcode_count, count( $matches ), 'Mismatch between add_shortcode calls and parsed matches' );
        $this->assertSame( 86, count( $matches ), 'Expected 86 shortcode registrations' );

        $adapter_map = [
            'general'     => General_Shortcode_Adapter::class,
            'club'        => Club_Shortcode_Adapter::class,
            'competition' => Competition_Shortcode_Adapter::class,
            'email'       => Email_Shortcode_Adapter::class,
            'event'       => Event_Shortcode_Adapter::class,
            'league'      => League_Shortcode_Adapter::class,
            'matches'     => Matches_Shortcode_Adapter::class,
            'login'       => Login_Shortcode_Adapter::class,
            'message'     => Message_Shortcode_Adapter::class,
            'tournament'  => Tournament_Shortcode_Adapter::class,
            'fixture_controller' => Fixture_Shortcode_Controller::class,
        ];

        foreach ( $matches as $match ) {
            [ , $tag, $var, $method_name ] = $match;
            $this->assertArrayHasKey( $var, $adapter_map, "Unknown shortcode owner \${$var} for [{$tag}]" );
            $adapter_class = $adapter_map[ $var ];
            $this->assertTrue(
                method_exists( $adapter_class, $method_name ),
                "[{$tag}] callback {$adapter_class}::{$method_name} is not callable"
            );

            if ( 'fixture_controller' === $var ) {
                continue;
            }

            $legacy_method = $method_name;
            if ( 'login' === $var && 'login_form' === $method_name ) {
                $legacy_method = 'render_login_form';
            }

            if ( 'club' === $var && in_array( $method_name, [ 'show_club_event', 'show_club_team', 'show_team_edit_modal' ], true ) ) {
                // These resolve events/teams through lookups; covered by Club_Shortcode_Adapter_Test.
                continue;
            }

            if ( 'competition' === $var && in_array( $method_name, [ 'show_competition_entry', 'show_competition_entry_payment', 'show_competition_entry_payment_complete' ], true ) ) {
                // These require user session/payment context; covered by Competition_Shortcode_Adapter_Test.
                continue;
            }

            if ( 'email' === $var ) {
                // Email shortcodes require entity IDs and lookups; covered thoroughly by Email_Shortcode_Adapter_Test.
                continue;
            }

            $dummy_output = "rendered_output_for_{$tag}";
            $adapter = $this->create_adapter_with_mock_handler( $var, $method_name, $legacy_method, $dummy_output );
            $result = $adapter->$method_name( [] );
            $this->assertSame( $dummy_output, $result, "[{$tag}] adapter {$adapter_class}::{$method_name} failed to forward to its handler {$legacy_method}" );
        }
    }

    private function create_adapter_with_mock_handler( string $var, string $method_name, string $legacy_method, string $dummy_output ): object {
        $racketmanager = $this->createStub( RacketManager::class );
        $racketmanager->container = new Simple_Container();

        switch ( $var ) {
            case 'general':
                return $this->general_adapter_forwarding( $racketmanager, $method_name, $dummy_output );

            case 'club':
                return $this->club_adapter_forwarding( $method_name, $dummy_output );

            case 'competition':
                return $this->competition_adapter_forwarding( $method_name, $dummy_output );

            case 'email':
                return $this->email_adapter_forwarding( $method_name, $dummy_output );

            case 'event':
                return $this->event_adapter_forwarding( $method_name, $dummy_output );

            case 'league':
                return $this->league_adapter_forwarding( $method_name, $dummy_output );

            case 'login':
                return $this->login_adapter_forwarding( $racketmanager, $method_name, $dummy_output );

            case 'message':
                return $this->message_adapter_forwarding( $racketmanager, $method_name, $dummy_output );

            case 'tournament':
                return $this->tournament_adapter_forwarding( $method_name, $dummy_output );

            case 'matches':
                return $this->matches_adapter_forwarding( $method_name, $dummy_output );

            default:
                throw new \InvalidArgumentException( "Unknown adapter variable: {$var}" );
        }
    }

    private function login_adapter( ?RacketManager $racketmanager = null, array $overrides = [] ): Login_Shortcode_Adapter {
        return new Login_Shortcode_Adapter(
            $racketmanager ?? $this->racketmanager,
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides[ Get_Login_Form_Handler::class ] ?? $this->createStub( Get_Login_Form_Handler::class ),
            $overrides[ Get_Password_Lost_Form_Handler::class ] ?? $this->createStub( Get_Password_Lost_Form_Handler::class ),
            $overrides[ Get_Password_Reset_Form_Handler::class ] ?? $this->createStub( Get_Password_Reset_Form_Handler::class ),
            $overrides[ Get_Member_Account_Form_Handler::class ] ?? $this->createStub( Get_Member_Account_Form_Handler::class )
        );
    }

    private function login_adapter_forwarding( RacketManager $racketmanager, string $method_name, string $dummy_output ): Login_Shortcode_Adapter {
        $class = match ( $method_name ) {
            'login_form', 'render_login_form' => Get_Login_Form_Handler::class,
            'render_password_lost_form'       => Get_Password_Lost_Form_Handler::class,
            'render_password_reset_form'      => Get_Password_Reset_Form_Handler::class,
            'generate_member_account_form'    => Get_Member_Account_Form_Handler::class,
            default => throw new \InvalidArgumentException( "Unknown login method: {$method_name}" ),
        };
        $handler = $this->createMock( $class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( new Shortcode_View_Model( 'x' ) );
        $view_presenter = $this->createMock( Shortcode_View_Presenter::class );
        $view_presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return $this->login_adapter( $racketmanager, [ 'view_presenter' => $view_presenter, $class => $handler ] );
    }

    private function message_adapter_forwarding( RacketManager $racketmanager, string $method_name, string $dummy_output ): Message_Shortcode_Adapter {
        $class = match ( $method_name ) {
            'show_messages' => Get_Messages_Handler::class,
            'show_message'  => Get_Message_Handler::class,
            default => throw new \InvalidArgumentException( "Unknown message method: {$method_name}" ),
        };
        $handler = $this->createMock( $class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( new Shortcode_View_Model( 'x' ) );
        $view_presenter = $this->createMock( Shortcode_View_Presenter::class );
        $view_presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return new Message_Shortcode_Adapter(
            $racketmanager,
            $view_presenter,
            $class === Get_Messages_Handler::class ? $handler : $this->createStub( Get_Messages_Handler::class ),
            $class === Get_Message_Handler::class ? $handler : $this->createStub( Get_Message_Handler::class )
        );
    }

    private function general_adapter( ?RacketManager $racketmanager = null, array $overrides = [] ): General_Shortcode_Adapter {
        return new General_Shortcode_Adapter(
            $racketmanager ?? $this->racketmanager,
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides[ Get_Players_Handler::class ] ?? $this->createStub( Get_Players_Handler::class ),
            $overrides[ Get_Player_Handler::class ] ?? $this->createStub( Get_Player_Handler::class ),
            $overrides[ Get_Favourites_Handler::class ] ?? $this->createStub( Get_Favourites_Handler::class ),
            $overrides[ Get_Invoice_Handler::class ] ?? $this->createStub( Get_Invoice_Handler::class ),
            $overrides[ Get_Purchase_Order_Handler::class ] ?? $this->createStub( Get_Purchase_Order_Handler::class ),
            $overrides[ Get_Memberships_Handler::class ] ?? $this->createStub( Get_Memberships_Handler::class ),
            $overrides[ Get_Player_Search_Handler::class ] ?? $this->createStub( Get_Player_Search_Handler::class ),
            $overrides[ Get_Team_Order_Handler::class ] ?? $this->createStub( Get_Team_Order_Handler::class ),
            $overrides[ Get_Alert_Handler::class ] ?? $this->createStub( Get_Alert_Handler::class )
        );
    }

    private function general_adapter_forwarding( RacketManager $racketmanager, string $method_name, string $dummy_output ): General_Shortcode_Adapter {
        $class = match ( $method_name ) {
            'show_players'        => Get_Players_Handler::class,
            'show_player'         => Get_Player_Handler::class,
            'show_favourites'     => Get_Favourites_Handler::class,
            'show_invoice'        => Get_Invoice_Handler::class,
            'show_purchase_order' => Get_Purchase_Order_Handler::class,
            'show_memberships'    => Get_Memberships_Handler::class,
            'show_player_search'  => Get_Player_Search_Handler::class,
            'show_team_order'     => Get_Team_Order_Handler::class,
            'show_alert'          => Get_Alert_Handler::class,
            default => throw new \InvalidArgumentException( "Unknown general method: {$method_name}" ),
        };
        $handler = $this->createMock( $class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( new Shortcode_View_Model( 'x' ) );
        $view_presenter = $this->createMock( Shortcode_View_Presenter::class );
        $view_presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return $this->general_adapter( $racketmanager, [ 'view_presenter' => $view_presenter, $class => $handler ] );
    }

    private function club_adapter( array $overrides = [] ): Club_Shortcode_Adapter {
        return new Club_Shortcode_Adapter(
            $overrides['club_service'] ?? $this->createStub( Club_Service::class ),
            $overrides['get_club'] ?? $this->createStub( Get_Club_Handler::class ),
            $overrides['club_presenter'] ?? $this->createStub( Club_Shortcode_Presenter::class ),
            $overrides['players'] ?? $this->createStub( Get_Club_Players_Handler::class ),
            $overrides['roles'] ?? $this->createStub( Get_Club_Roles_Handler::class ),
            $overrides['role_modal'] ?? $this->createStub( Get_Club_Role_Modal_Handler::class ),
            $overrides['competitions'] ?? $this->createStub( Get_Club_Competitions_Handler::class ),
            $overrides['invoices'] ?? $this->createStub( Get_Club_Invoices_Handler::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['competition_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class )
        );
    }

    private function club_adapter_forwarding( string $method_name, string $dummy_output ): Club_Shortcode_Adapter {
        if ( 'show_club' === $method_name ) {
            $handler = $this->createMock( Get_Club_Handler::class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( new Club_View_Model( 'club' ) );
            $presenter = $this->createMock( Club_Shortcode_Presenter::class );
            $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

            return $this->club_adapter( [ 'get_club' => $handler, 'club_presenter' => $presenter ] );
        }

        $view_presenter = $this->createMock( Shortcode_View_Presenter::class );
        $view_presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );
        $overrides = [ 'view_presenter' => $view_presenter ];

        $handlers = [
            'show_club_players'      => [ 'players', Get_Club_Players_Handler::class ],
            'show_club_roles'        => [ 'roles', Get_Club_Roles_Handler::class ],
            'show_club_role_modal'   => [ 'role_modal', Get_Club_Role_Modal_Handler::class ],
            'show_club_competitions' => [ 'competitions', Get_Club_Competitions_Handler::class ],
            'show_club_invoices'     => [ 'invoices', Get_Club_Invoices_Handler::class ],
        ];
        if ( isset( $handlers[ $method_name ] ) ) {
            [ $key, $class ] = $handlers[ $method_name ];
            $handler         = $this->createMock( $class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( new Shortcode_View_Model( 'x' ) );
            $overrides[ $key ] = $handler;
        } else {
            $club_service = $this->createMock( Club_Service::class );
            $club_service->expects( $this->once() )->method( 'get_clubs_with_details' )->willReturn( [] );
            $overrides['club_service'] = $club_service;
        }

        return $this->club_adapter( $overrides );
    }

    private function competition_adapter( array $overrides = [] ): Competition_Shortcode_Adapter {
        $renderer       = $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class );
        $view_presenter = $overrides['view_presenter'] ?? new Shortcode_View_Presenter( $renderer );
        $comp_service   = $overrides['competition_service'] ?? $this->createStub( Competition_Service::class );
        $club_service   = $overrides['club_service'] ?? $this->createStub( Club_Service::class );
        $player_service = $overrides['player_service'] ?? $this->createStub( Player_Service::class );

        return new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            club_service: $club_service,
            team_service: $overrides['team_service'] ?? $this->createStub( Team_Service::class ),
            player_service: $player_service,
            tournament_service: $overrides['tournament_service'] ?? $this->createStub( Tournament_Service::class ),
            finance_service: $overrides['finance_service'] ?? $this->createStub( Finance_Service::class ),
            registration_service: $overrides['registration_service'] ?? $this->createStub( Registration_Service::class ),
            competition_entry_service: $overrides['competition_entry_service'] ?? $this->createStub( Competition_Entry_Service::class ),
            template_renderer: $renderer,
            get_competition_handler: $overrides['get_competition_handler'] ?? $this->createStub( Get_Competition_Handler::class ),
            competition_shortcode_presenter: $overrides['competition_shortcode_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class ),
            overview_handler: $overrides['overview_handler'] ?? new \Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler( $comp_service, $overrides['competition_shortcode_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class ), $this->createStub( \Racketmanager\Services\Competition\Competition_Season_Service::class ) ),
            events_handler: $overrides['events_handler'] ?? new \Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler( $comp_service, $overrides['competition_shortcode_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class ), $this->competition_season_service() ),
            teams_handler: $overrides['teams_handler'] ?? new \Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler( $comp_service, $overrides['competition_shortcode_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class ), $this->competition_season_service() ),
            clubs_handler: $overrides['clubs_handler'] ?? new \Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler( $comp_service, $club_service, $this->createStub( Player_Stats_Service::class ), new \Racketmanager\Presentation\Presenters\Tournament_Presenter( 'd/m/Y' ), $overrides['competition_shortcode_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class ), $this->competition_season_service() ),
            players_handler: $overrides['players_handler'] ?? new \Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler(
                $comp_service,
                $player_service,
                $this->createStub( Player_Query_Service::class ),
                $this->createStub( Player_Stats_Service::class ),
                new Player_Presenter(),
                $overrides['competition_shortcode_presenter'] ?? $this->createStub( Competition_Shortcode_Presenter::class ), $this->competition_season_service() ),
            winners_handler: $overrides['winners_handler'] ?? new \Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler( $comp_service, $this->competition_season_service() ),
            view_presenter: $view_presenter,
            racketmanager: $overrides['racketmanager'] ?? $this->racketmanager
        );
    }

    private function competition_adapter_forwarding( string $method_name, string $dummy_output ): Competition_Shortcode_Adapter {
        if ( 'show_competition' === $method_name ) {
            $handler = $this->createMock( Get_Competition_Handler::class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( new Competition_View_Model( 'competition' ) );
            $presenter = $this->createMock( Competition_Shortcode_Presenter::class );
            $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

            return $this->competition_adapter( [
                'get_competition_handler'         => $handler,
                'competition_shortcode_presenter' => $presenter,
            ] );
        }

        $renderer = $this->createStub( Shortcode_Template_Renderer::class );
        $renderer->method( 'render' )->willReturn( $dummy_output );
        $renderer->method( 'render_alert' )->willReturn( $dummy_output );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp = Competition_Test_Factory::create( id: 1, name: 'Test Comp', seasons: Competition_Test_Factory::season_list() );
        $comp_service->method( 'get_by_id' )->willReturn( $comp );
        $comp_service->method( 'get_by_criteria' )->willReturn( [ $comp ] );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [] );
        $comp_service->method( 'get_winners_for_competition' )->willReturn( [] );

        return $this->competition_adapter( [
            'template_renderer'   => $renderer,
            'competition_service' => $comp_service,
        ] );
    }

    private function matches_adapter( array $overrides = [] ): Matches_Shortcode_Adapter {
        return new Matches_Shortcode_Adapter(
            $overrides['daily'] ?? $this->createStub( Get_Daily_Matches_Handler::class ),
            $overrides['latest'] ?? $this->createStub( Get_Latest_Results_Handler::class ),
            $overrides['presenter'] ?? $this->createStub( Matches_List_Presenter::class ),
            $overrides['score'] ?? $this->createStub( Get_Match_Score_Handler::class ),
            $overrides['header'] ?? $this->createStub( Get_Match_Header_Handler::class ),
            $overrides['detail'] ?? $this->createStub( Get_Match_Detail_Handler::class ),
            $overrides['match_presenter'] ?? $this->createStub( Match_Shortcode_Presenter::class )
        );
    }

    private function matches_adapter_forwarding( string $method_name, string $dummy_output ): Matches_Shortcode_Adapter {
        if ( in_array( $method_name, [ 'show_daily_matches', 'show_latest_results' ], true ) ) {
            $daily_handler = $this->createMock( Get_Daily_Matches_Handler::class );
            $daily_handler->expects( 'show_daily_matches' === $method_name ? $this->once() : $this->never() )->method( 'handle' )->willReturn( new Match_List_Result() );
            $latest_handler = $this->createMock( Get_Latest_Results_Handler::class );
            $latest_handler->expects( 'show_latest_results' === $method_name ? $this->once() : $this->never() )->method( 'handle' )->willReturn( new Match_List_Result() );
            $presenter = $this->createMock( Matches_List_Presenter::class );
            $presenter->method( 'present_daily' )->willReturn( new Matches_List_View_Model( 'matches-daily' ) );
            $presenter->method( 'present_latest_results' )->willReturn( new Matches_List_View_Model( 'matches-results' ) );
            $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

            return $this->matches_adapter( [ 'daily' => $daily_handler, 'latest' => $latest_handler, 'presenter' => $presenter ] );
        }

        $map = [
            'show_score'        => [ 'score', Get_Match_Score_Handler::class, 'render_score', Match_Score_Result::class ],
            'show_match_header' => [ 'header', Get_Match_Header_Handler::class, 'render_header', Match_Header_Result::class ],
            'show_match_detail' => [ 'detail', Get_Match_Detail_Handler::class, 'render_detail', Match_Detail_Result::class ],
        ];
        [ $key, $handler_class, $render_method, $result_class ] = $map[ $method_name ];

        $handler = $this->createMock( $handler_class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( new $result_class() );
        $presenter = $this->createMock( Match_Shortcode_Presenter::class );
        $presenter->expects( $this->once() )->method( $render_method )->willReturn( $dummy_output );

        return $this->matches_adapter( [ $key => $handler, 'match_presenter' => $presenter ] );
    }

    private function league_adapter( array $overrides = [] ): League_Shortcode_Adapter {
        return new League_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->racketmanager,
            $overrides['get_league_standings_handler'] ?? $this->createStub( Get_League_Standings_Handler::class ),
            $overrides['league_standings_presenter'] ?? $this->createStub( League_Standings_Presenter::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['championship_handler'] ?? $this->createStub( Get_League_Championship_Handler::class ),
            $overrides['archive_handler'] ?? $this->createStub( Get_League_Archive_Handler::class ),
            $overrides['crosstable_handler'] ?? $this->createStub( Get_League_Crosstable_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_League_Matches_Handler::class ),
            $overrides['teams_handler'] ?? $this->createStub( Get_League_Teams_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_League_Players_Handler::class ),
            $overrides['season_dropdown_handler'] ?? $this->createStub( Get_League_Season_Dropdown_Handler::class ),
            $overrides['match_dropdown_handler'] ?? $this->createStub( Get_League_Match_Dropdown_Handler::class ),
            $overrides['last_5_handler'] ?? $this->createStub( Get_League_Last_5_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    private function league_adapter_forwarding( string $method_name, string $dummy_output ): League_Shortcode_Adapter {
        if ( 'show_standings' === $method_name ) {
            $handler = $this->createMock( Get_League_Standings_Handler::class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( new League_Standings_View_Model( 'standings' ) );
            $presenter = $this->createMock( League_Standings_Presenter::class );
            $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

            return $this->league_adapter( [
                'get_league_standings_handler' => $handler,
                'league_standings_presenter'   => $presenter,
            ] );
        }

        $map = [
            'show_championship'      => [ 'championship_handler', Get_League_Championship_Handler::class ],
            'show_archive'           => [ 'archive_handler', Get_League_Archive_Handler::class ],
            'show_crosstable'        => [ 'crosstable_handler', Get_League_Crosstable_Handler::class ],
            'show_matches'           => [ 'matches_handler', Get_League_Matches_Handler::class ],
            'show_teams'             => [ 'teams_handler', Get_League_Teams_Handler::class ],
            'show_league_players'    => [ 'players_handler', Get_League_Players_Handler::class ],
            'show_season_dropdown'   => [ 'season_dropdown_handler', Get_League_Season_Dropdown_Handler::class ],
            'show_match_dropdown'    => [ 'match_dropdown_handler', Get_League_Match_Dropdown_Handler::class ],
            'show_last_5'            => [ 'last_5_handler', Get_League_Last_5_Handler::class ],
        ];

        [ $key, $handler_class ] = $map[ $method_name ];

        $handler = $this->createMock( $handler_class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'test' ) );
        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return $this->league_adapter( [
            $key             => $handler,
            'view_presenter' => $presenter,
        ] );
    }

    private function email_adapter( array $overrides = [] ): Email_Shortcode_Adapter {
        return new Email_Shortcode_Adapter(
            $overrides['fixture_repository'] ?? $this->createStub( Fixture_Repository_Interface::class ),
            $overrides['presenter'] ?? $this->createStub( Email_Shortcode_Presenter::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    private function email_adapter_forwarding( string $method_name, string $dummy_output ): Email_Shortcode_Adapter {
        $fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
        $fixture      = Fixture_Factory::from_object( (object) [ 'id' => 123, 'league_id' => 1 ] );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );

        $renderer = $this->createMock( Shortcode_Template_Renderer::class );
        $renderer->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return $this->email_adapter( [
            'fixture_repository' => $fixture_repo,
            'template_renderer'  => $renderer,
        ] );
    }

    private function event_adapter( array $overrides = [] ): Event_Shortcode_Adapter {
        return new Event_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->racketmanager,
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['event_handler'] ?? $this->createStub( Get_Event_Handler::class ),
            $overrides['standings_handler'] ?? $this->createStub( Get_Event_Standings_Handler::class ),
            $overrides['draw_handler'] ?? $this->createStub( Get_Event_Draw_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_Event_Matches_Handler::class ),
            $overrides['clubs_handler'] ?? $this->createStub( Get_Event_Clubs_Handler::class ),
            $overrides['teams_handler'] ?? $this->createStub( Get_Event_Teams_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_Event_Players_Handler::class ),
            $overrides['partner_handler'] ?? $this->createStub( Get_Event_Partner_Handler::class ),
            $overrides['team_matches_handler'] ?? $this->createStub( Get_Event_Team_Matches_Handler::class ),
            $overrides['team_order_players_handler'] ?? $this->createStub( Get_Team_Order_Players_Handler::class ),
            $overrides['dropdown_handler'] ?? $this->createStub( Get_Event_Dropdown_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    private function event_adapter_forwarding( string $method_name, string $dummy_output ): Event_Shortcode_Adapter {
        $map = [
            'show_event'              => [ 'event_handler', Get_Event_Handler::class ],
            'show_event_standings'    => [ 'standings_handler', Get_Event_Standings_Handler::class ],
            'show_event_draw'         => [ 'draw_handler', Get_Event_Draw_Handler::class ],
            'show_event_matches'      => [ 'matches_handler', Get_Event_Matches_Handler::class ],
            'show_event_clubs'        => [ 'clubs_handler', Get_Event_Clubs_Handler::class ],
            'show_event_teams'        => [ 'teams_handler', Get_Event_Teams_Handler::class ],
            'show_event_players'      => [ 'players_handler', Get_Event_Players_Handler::class ],
            'show_event_partner'      => [ 'partner_handler', Get_Event_Partner_Handler::class ],
            'show_event_team_matches' => [ 'team_matches_handler', Get_Event_Team_Matches_Handler::class ],
            'show_team_order_players' => [ 'team_order_players_handler', Get_Team_Order_Players_Handler::class ],
            'show_dropdown'           => [ 'dropdown_handler', Get_Event_Dropdown_Handler::class ],
        ];

        [ $key, $handler_class ] = $map[ $method_name ];

        $handler = $this->createMock( $handler_class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'test' ) );
        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return $this->event_adapter( [
            $key             => $handler,
            'view_presenter' => $presenter,
        ] );
    }

    private function tournament_adapter( array $overrides = [] ): Tournament_Shortcode_Adapter {
        return new Tournament_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->racketmanager,
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['tournament_handler'] ?? $this->createStub( Get_Tournament_Handler::class ),
            $overrides['overview_handler'] ?? $this->createStub( Get_Tournament_Overview_Handler::class ),
            $overrides['events_handler'] ?? $this->createStub( Get_Tournament_Events_Handler::class ),
            $overrides['draws_handler'] ?? $this->createStub( Get_Tournament_Draws_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_Tournament_Players_Handler::class ),
            $overrides['winners_handler'] ?? $this->createStub( Get_Tournament_Winners_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_Tournament_Matches_Handler::class ),
            $overrides['match_handler'] ?? $this->createStub( Get_Tournament_Match_Handler::class ),
            $overrides['order_of_play_handler'] ?? $this->createStub( Get_Order_Of_Play_Handler::class ),
            $overrides['latest_tournament_handler'] ?? $this->createStub( Get_Latest_Tournament_Handler::class ),
            $overrides['withdrawal_handler'] ?? $this->createStub( Get_Tournament_Withdrawal_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    private function tournament_adapter_forwarding( string $method_name, string $dummy_output ): Tournament_Shortcode_Adapter {
        $map = [
            'show_tournament'                  => [ 'tournament_handler', Get_Tournament_Handler::class ],
            'show_tournament_overview'         => [ 'overview_handler', Get_Tournament_Overview_Handler::class ],
            'show_tournament_events'           => [ 'events_handler', Get_Tournament_Events_Handler::class ],
            'show_draws'                       => [ 'draws_handler', Get_Tournament_Draws_Handler::class ],
            'show_tournament_players'          => [ 'players_handler', Get_Tournament_Players_Handler::class ],
            'show_tournament_winners'          => [ 'winners_handler', Get_Tournament_Winners_Handler::class ],
            'show_tournament_matches'          => [ 'matches_handler', Get_Tournament_Matches_Handler::class ],
            'show_tournament_match'            => [ 'match_handler', Get_Tournament_Match_Handler::class ],
            'show_order_of_play'               => [ 'order_of_play_handler', Get_Order_Of_Play_Handler::class ],
            'show_latest_tournament'           => [ 'latest_tournament_handler', Get_Latest_Tournament_Handler::class ],
            'show_tournament_withdrawal_modal' => [ 'withdrawal_handler', Get_Tournament_Withdrawal_Handler::class ],
        ];

        [ $key, $handler_class ] = $map[ $method_name ];

        $handler = $this->createMock( $handler_class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'test' ) );
        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )->method( 'render' )->willReturn( $dummy_output );

        return $this->tournament_adapter( [
            $key             => $handler,
            'view_presenter' => $presenter,
        ] );
    }
}
