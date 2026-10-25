<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Favourite_Presenter;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Infrastructure\Gateways\Account\Wp_User_Favourites_Gateway;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Team_List_Render_Regression_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use Manages_Wordpress_Globals;
    use Creates_Team_Match_Row_Presenter;

    private Shortcode_Template_Renderer $template_renderer;
    private Team_Details_Presenter $team_details_presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        unset( $GLOBALS['racketmanager'] );
        $GLOBALS['wp_stubs_user_meta'] = [];
        $GLOBALS['wp_stubs_get_current_user_id'] = 41;

        $view_renderer             = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $this->template_renderer       = new Shortcode_Template_Renderer(
            $view_renderer,
            favourite_presenter: new Favourite_Presenter( new Wp_User_Favourites_Gateway( new Security_Service() ) )
        );
        $this->team_details_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Helper to render a template and collect any PHP notices or warnings.
     */
    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    public function test_logged_in_league_teams_list_renders_contacts_and_favourites_without_warnings(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;

        $league_service = $this->createStub( League_Service::class );
        $team_service   = $this->createStub( Team_Service::class );
        $stats_service  = $this->createStub( Player_Stats_Service::class );

        $team1 = (object) [ 'id' => 101, 'title' => 'Westcliff 1' ];
        $team2 = (object) [ 'id' => 102, 'title' => 'Leigh TC 2' ];
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_league_teams' )->willReturn( [ $team1, $team2 ] );
        $league_query->method( 'get_team_dtls' )->willReturnCallback(
            static function ( object $league, int $team_id ): object {
                if ( 101 === $team_id ) {
                    return (object) [
                        'captain'              => 'John Smith',
                        'captain_contactno'    => '01234567890',
                        'captain_contactemail' => 'john@westcliff.test',
                        'match_day'            => 'Wednesday',
                        'match_time'           => '19:00:00',
                        'players'              => [
                            (object) [
                                'display_name' => 'Player Contact One',
                                'contactno'    => '01234111111',
                                'email'        => 'player.one@westcliff.test',
                            ],
                        ],
                    ];
                }
                return (object) [
                    'captain'              => 'Jane Doe',
                    'captain_contactno'    => '09876543210',
                    'captain_contactemail' => 'jane@leigh.test',
                    'match_day'            => 'Thursday',
                    'match_time'           => '19:30:00',
                ];
            }
        );

        $handler = new Get_League_Teams_Handler(
            $league_service,
            $team_service,
            $stats_service,
            new Tournament_Presenter( team_match_row_presenter: $this->create_team_match_row_presenter() ),
            $this->team_details_presenter,
            $league_query
        );

        $league = new class extends League {
            public function __construct() {
                $this->id             = 10;
                $this->title          = 'Premier Division';
                $this->current_season = [ 'name' => '2026' ];
                $this->event          = new class extends Event {
                    public function __construct() {
                        $this->id          = 20;
                        $this->name        = 'Men Premier';
                        $this->competition = new class extends Competition {
                            public function __construct() {
                                $this->type            = Competition_Type::LEAGUE;
                                $this->is_team_entry   = true;
                                $this->is_player_entry = false;
                            }
                        };
                    }
                };
            }
            public function set_template( string $type, string $template ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
        };

        $league_service->method( 'get_league' )->willReturn( $league );

        $vm = $handler->handle( new Get_League_Teams_Query( league_id: 10 ) );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'teams-list', $vm->template );
        $this->assertArrayNotHasKey( 'teams', $vm->vars );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering league teams-list must produce zero PHP warnings' );

        // Assert team 1 assertions
        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'W1', $output ); // initials
        $this->assertStringContainsString( 'href="/league/premier-division/2026/team/westcliff-1/"', $output );
        $this->assertStringContainsString( 'class="nav--link tabDataLink"', $output );
        $this->assertStringContainsString( 'data-type="league"', $output );
        $this->assertStringContainsString( 'data-type-id="10"', $output );
        $this->assertStringContainsString( 'data-season="2026"', $output );
        $this->assertStringContainsString( 'data-link-type="teams"', $output );
        $this->assertStringContainsString( 'data-link-id="101"', $output );
        $this->assertStringContainsString( 'John Smith', $output );
        $this->assertStringContainsString( 'href="tel:01234567890"', $output );
        $this->assertStringContainsString( 'id="captain-contact-no-20-101"', $output );
        $this->assertStringContainsString( 'href="mailto:john@westcliff.test"', $output );
        $this->assertStringContainsString( 'id="captain-contact-email-20-101"', $output );
        $this->assertStringContainsString( 'Wednesday', $output );
        $this->assertStringContainsString( '19:00', $output );
        $this->assertStringContainsString( 'calendar-add', $output );
        $this->assertStringContainsString( 'team_id=101', $output );
        $this->assertStringContainsString( 'league_id=10', $output );

        // Assert team 2 assertions
        $this->assertStringContainsString( 'Leigh TC 2', $output );
        $this->assertStringContainsString( 'LT2', $output ); // initials
        $this->assertStringContainsString( 'href="/league/premier-division/2026/team/leigh-tc-2/"', $output );
        $this->assertStringContainsString( 'Jane Doe', $output );
        $this->assertStringContainsString( 'Thursday', $output );
        $this->assertStringContainsString( '19:30', $output );
        $this->assertSame( 2, substr_count( $output, 'data-js="add-favourite"' ) );

        $league->event->competition->is_team_entry   = false;
        $league->event->competition->is_player_entry = true;
        $player_entry_vm = $handler->handle( new Get_League_Teams_Query( league_id: 10 ) );
        $this->assertFalse( $player_entry_vm->is_error() );
        $this->assertSame( 'teams-list', $player_entry_vm->template );

        $player_warnings = [];
        $player_output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $player_entry_vm->template, $player_entry_vm->vars, $player_entry_vm->template_type ),
            $player_warnings
        );

        $this->assertSame( [], $player_warnings, 'Rendering logged-in player-entry team rows must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Player Contact One', $player_output );
        $this->assertStringContainsString( 'href="tel:01234111111"', $player_output );
        $this->assertStringContainsString( 'href="mailto:player.one@westcliff.test"', $player_output );
    }

    public function test_championship_event_teams_list_renders_real_handler_output_with_modal_attrs_and_zero_warnings(): void {
        $comp_service  = $this->createStub( Competition_Service::class );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $team_service  = $this->createStub( Team_Service::class );

        $GLOBALS['wp_stubs_userdata'][700] = (object) [
            'ID'           => 700,
            'display_name' => 'David Miller',
            'user_email'   => 'david@thorpebay.test',
        ];

        $event_repository = $this->event_repository_with_results(
            [
                'query_teams'   => [
                    (object) [
                        'id'        => 201,
                        'team_id'   => 201,
                        'club_id'   => 0,
                        'name'      => 'Thorpe Bay 1',
                        'roster'    => null,
                        'team_type' => 'L',
                    ],
                ],
                'get_team_info' => (object) [
                    'captain'              => 700,
                    'captain_contactno'    => '01702123456',
                    'captain_contactemail' => 'david@thorpebay.test',
                    'match_day'            => 'Sunday',
                    'match_time'           => '10:00:00',
                ],
            ]
        );

        $handler = new Get_Event_Teams_Handler(
            $comp_service,
            $stats_service,
            new Tournament_Presenter( team_match_row_presenter: $this->create_team_match_row_presenter() ),
            $team_service,
            $this->team_details_presenter,
            new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ),
            $this->event_query_service( $event_repository )
        );

        $event = new class extends Event {
            public function __construct() {
                $this->id             = 40;
                $this->name           = 'Championship Cup Event';
                $this->current_season = [ 'name' => '2026' ];
                $this->competition    = new class extends Competition {
                    public function __construct() {
                        $this->id              = 5;
                        $this->name            = 'Championship Cup';
                        $this->type            = Competition_Type::CUP;
                        $this->is_championship = true;
                        $this->is_team_entry   = true;
                        $this->is_player_entry = false;
                        $this->current_season  = [ 'name' => '2026' ];
                    }
                    public function is_event_allowed( object|string|int $event, string|int|null $season = null, bool $for_entry = false, iterable $events = [] ): bool {
                        return true;
                    }
                };
            }
            public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {}
        };

        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $vm = $handler->handle( new Get_Event_Teams_Query( event_id: 40, season: '2026' ) );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'teams-list', $vm->template );
        $this->assertSame( 'event', $vm->template_type );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering championship event teams-list must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Thorpe Bay 1', $output );
        $this->assertStringContainsString( 'TB1', $output );
        $this->assertStringContainsString( 'href="/cup/championship-cup-event/2026/team/thorpe-bay-1/"', $output );
        $this->assertStringContainsString( 'data-type="event"', $output );
        $this->assertStringContainsString( 'data-type-id="40"', $output );
        $this->assertStringContainsString( 'data-season="2026"', $output );
        $this->assertStringContainsString( 'data-link-type="teams"', $output );
        $this->assertStringContainsString( 'David Miller', $output );
        $this->assertStringContainsString( 'Sunday', $output );
        $this->assertStringContainsString( '10:00', $output );

        unset( $GLOBALS['wp_stubs_userdata'][700] );
    }

    public function test_default_event_teams_renders_real_handler_output_with_table_links_and_zero_warnings(): void {
        $comp_service  = $this->createStub( Competition_Service::class );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $team_service  = $this->createStub( Team_Service::class );

        $hadleigh_club            = \Racketmanager\Tests\Helpers\Club_Test_Factory::create( 77, 'Hadleigh Tennis Club', 'Hadleigh TC' );
        $hadleigh_club_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class );
        $hadleigh_club_repository->method( 'find_by_id' )->willReturn( $hadleigh_club );

        $handler = new Get_Event_Teams_Handler(
            $comp_service,
            $stats_service,
            new Tournament_Presenter( team_match_row_presenter: $this->create_team_match_row_presenter() ),
            $team_service,
            $this->team_details_presenter,
            new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ),
            $this->event_query_service(
                $this->event_repository_with_results(
                    [
                        'query_teams'   => [
                            (object) [
                                'id'           => 301,
                                'name'         => 'Hadleigh 1',
                                'league_title' => 'Division 1',
                                'club_id'      => 77,
                                'team_id'      => 301,
                                'team_type'    => 'L',
                                'roster'       => null,
                            ],
                        ],
                        'query_players' => 6,
                    ]
                ),
                null,
                null,
                null,
                null,
                $hadleigh_club_repository,
                $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class )
            )
        );

        $event = new class extends Event {
            public function __construct() {
                $this->id             = 50;
                $this->name           = 'Division 1 Event';
                $this->is_box         = false;
                $this->current_season = [ 'name' => '2026' ];
                $this->competition    = new class extends Competition {
                    public function __construct() {
                        $this->id              = 8;
                        $this->name            = 'Summer League';
                        $this->type            = Competition_Type::LEAGUE;
                        $this->is_championship = false;
                        $this->current_season  = [ 'name' => '2026' ];
                    }
                    public function is_event_allowed( object|string|int $event, string|int|null $season = null, bool $for_entry = false, iterable $events = [] ): bool {
                        return true;
                    }
                };
            }
            public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {}
        };

        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $vm = $handler->handle( new Get_Event_Teams_Query( event_id: 50, season: '2026' ) );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'teams', $vm->template );
        $this->assertSame( 'event', $vm->template_type );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering default event teams must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Hadleigh 1', $output );
        $this->assertStringContainsString( 'Hadleigh TC', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'data-type="event"', $output );
        $this->assertStringContainsString( 'data-type-id="50"', $output );
        $this->assertStringContainsString( 'data-link-id="77"', $output );
        $this->assertStringContainsString( 'data-link-type="clubs"', $output );
        $this->assertStringContainsString( '6', $output ); // player count
    }

    public function test_club_competitions_renders_real_handler_output_with_modal_attrs_and_zero_warnings(): void {
        $club_service = $this->createStub( Club_Service::class );
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_repo    = $this->createStub( Competition_Repository_Interface::class );

        $handler = new Get_Club_Competitions_Handler(
            $club_service,
            $comp_service,
            $comp_repo,
            $this->team_details_presenter,
            new Club_Presenter(),
            $this->event_query_service()
        );

        $club = new class extends Club {
            public function __construct() {
                $this->id        = 12;
                $this->name      = 'Westcliff Hardcourt';
                $this->shortcode = 'Westcliff';
            }
            public function get_id(): int {
                return 12;
            }
        };

        $event1 = (object) [
            'id'             => 61,
            'name'           => 'Men Premier',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [
                'is_team_entry'   => true,
                'is_player_entry' => false,
            ],
            'teams'          => [
                (object) [
                    'id'    => 401,
                    'title' => 'Westcliff Men 1',
                    'info'  => (object) [
                        'captain'              => 'Mark Wood',
                        'captain_contactno'    => '01702987654',
                        'captain_contactemail' => 'mark@westcliff.test',
                    ],
                ],
            ],
        ];

        $comp1 = Competition_Test_Factory::create(
            id: 31,
            name: 'Summer League',
            current_season: [ 'name' => '2026' ]
        );

        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $club_service->method( 'can_user_update' )->willReturn( true );
        $comp_service->method( 'get_by_criteria' )->willReturnCallback(
            fn( array $criteria ) => 'league' === $criteria['type'] ? [ $comp1 ] : []
        );
        $comp_service->expects( $this->once() )
            ->method( 'get_events_for_competition' )
            ->with( 31 )
            ->willReturn( [ $event1 ] );

        $vm = $handler->handle( new Get_Club_Competitions_Query( club_name: 'Westcliff' ) );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'competitions', $vm->template );
        $this->assertSame( 'club', $vm->template_type );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering club competitions must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Westcliff Hardcourt', $output );
        $this->assertStringContainsString( 'Summer League', $output );
        $this->assertStringContainsString( 'Men Premier', $output );
        $this->assertStringContainsString( 'Westcliff Men 1', $output );
        $this->assertStringContainsString( 'WM1', $output );
        $this->assertStringContainsString( 'data-action="open-team-edit-modal"', $output );
        $this->assertStringContainsString( 'data-team-id="401"', $output );
        $this->assertStringContainsString( 'data-event-id="61"', $output );
        $this->assertStringContainsString( 'Mark Wood', $output );
    }

    public function test_club_competitions_fallback_to_empty_when_no_team_rows(): void {
        $club = (object) [
            'id'        => 15,
            'name'      => 'Empty Club',
            'shortcode' => 'Empty',
        ];

        $presenter   = new Club_Presenter();
        $event       = (object) [
            'id'             => 70,
            'name'           => 'Open Doubles',
            'current_season' => [ 'name' => '2026' ],
        ];
        $competition = (object) [
            'name'           => 'Winter Cup',
            'current_season' => [ 'name' => '2026' ],
        ];

        $vars = [
            'club'         => $club,
            'competitions' => [
                $presenter->present_club_competition( $competition, $club, [ $presenter->present_club_event( $event, $club ) ] ),
            ],
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'competitions', $vars, 'club' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Empty club event teams must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Club has no teams in this event', $output );
    }

    public function test_match_list_legacy_compatibility_renders_with_legacy_variables_and_zero_warnings(): void {
        $league = (object) [
            'title' => 'Division 1 League',
            'event' => (object) [
                'name'        => 'Division 1 Event',
                'competition' => (object) [
                    'type' => Competition_Type::LEAGUE,
                ],
            ],
        ];

        $matches = [
            (object) [
                'leg'  => 1,
                'link' => '/matches/101/',
                'date' => '2026-06-15 14:00:00',
            ],
            (object) [
                'leg'  => 2,
                'link' => '/matches/102/',
                'date' => '2026-06-22 14:00:00',
            ],
        ];

        $vars = [
            'league'    => $league,
            'season'    => '2026',
            'home_team' => 'Westcliff 1',
            'away_team' => 'Leigh 1',
            'matches'   => $matches,
            'round'     => 'final',
            'action'    => 'result',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-list', $vars ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-list with legacy variables must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Division 1 Event', $output );
        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'Leigh 1', $output );
        $this->assertStringContainsString( 'Final', $output );
        $this->assertStringContainsString( 'Leg 1', $output );
        $this->assertStringContainsString( 'href="/matches/101/result/"', $output );
        $this->assertStringContainsString( 'Leg 2', $output );
        $this->assertStringContainsString( 'href="/matches/102/result/"', $output );
        $this->assertStringContainsString( '2026-06-15', $output );
    }

    public function test_match_list_legacy_compatibility_renders_with_minimal_and_partial_variables(): void {
        $vars = [
            'league'    => (object) [ 'title' => 'Bare League' ],
            'season'    => '2026',
            'home_team' => (object) [ 'title' => 'Home Club' ],
            'away_team' => (object) [ 'name' => 'Away Club' ],
            'matches'   => [],
            'match_day' => '3',
            // No action, no round, no event on league
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-list', $vars ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-list with partial variables must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Home Club', $output );
        $this->assertStringContainsString( 'Away Club', $output );
        $this->assertStringContainsString( 'Match Day 3', $output );
    }

    public function test_separate_require_template_renders_allow_multiple_invocations(): void {
        $row_vm = $this->team_details_presenter->present_team_row(
            (object) [ 'id' => 99, 'title' => 'Repeat Team' ],
            (object) [
                'id'          => 1,
                'competition' => (object) [
                    'is_team_entry'   => false,
                    'is_player_entry' => true,
                ],
            ]
        );

        $vars = [
            'event'     => (object) [ 'id' => 1 ],
            'team_rows' => [ $row_vm ],
        ];

        // Render event/teams-list twice in a row
        $out1 = $this->template_renderer->render( 'teams-list', $vars, 'event' );
        $out2 = $this->template_renderer->render( 'teams-list', $vars, 'event' );

        $this->assertStringContainsString( 'Repeat Team', $out1 );
        $this->assertStringContainsString( 'Repeat Team', $out2 );
        $this->assertSame( $out1, $out2, 'Second template render must not be empty or suppressed by require_once' );
    }

    public function test_teams_list_does_not_pass_legacy_raw_teams_to_strict_row_template(): void {
        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render(
                'teams-list',
                [
                    'league' => (object) [ 'id' => 1 ],
                    'teams'  => [ (object) [ 'id' => 101, 'title' => 'Raw Legacy Team' ] ],
                ]
            ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Raw legacy team data must not reach the strict row template' );
        $this->assertStringContainsString( 'No teams found', $output );
        $this->assertStringNotContainsString( 'Raw Legacy Team', $output );
    }
}
