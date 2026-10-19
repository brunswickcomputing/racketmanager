<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Query;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use stdClass;

class Entry_And_Match_Templates_Test extends TestCase {
    use Manages_Wordpress_Globals;
    use Creates_Team_Match_Row_Presenter;

    private Php_View_Renderer $view_renderer;
    private Shortcode_Template_Renderer $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $template_path           = defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH . 'templates/' : dirname( __DIR__, 3 ) . '/templates/';
        $this->view_renderer     = new Php_View_Renderer( $template_path );
        $this->template_renderer = new Shortcode_Template_Renderer( $this->view_renderer );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

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

    public function test_entry_league_template_renders_with_real_handler_output_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $club_service         = $this->createStub( Club_Service::class );
        $player_service       = $this->createStub( Player_Service::class );
        $comp_service         = $this->createStub( Competition_Service::class );
        $tournament_service   = $this->createStub( Tournament_Service::class );
        $team_service         = $this->createStub( Team_Service::class );
        $registration_service = $this->createStub( Registration_Service::class );
        $comp_entry_service   = $this->createStub( Competition_Entry_Service::class );
        $finance_service      = $this->createStub( Finance_Service::class );

        $club_entry_selection = new Club_Entry_Selection_Service( $club_service, $player_service );

        $handler = new Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $comp_service,
            tournament_service: $tournament_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $registration_service,
            competition_entry_service: $comp_entry_service,
            finance_service: $finance_service,
            competition_presenter: new Competition_Shortcode_Presenter( $this->view_renderer ),
            tournament_presenter: new Tournament_Presenter()
        );

        $competition = new class extends Competition {
            public function __construct() {
                $this->id             = 12;
                $this->name           = 'Summer League';
                $this->type           = Competition_Type::LEAGUE;
                $this->is_open        = true;
                $this->seasons        = new Season_Collection( [
                    '2025' => [
                        'name' => '2025',
                    ],
                    '2026' => [
                        'name'         => '2026',
                        'date_closing' => '2026-05-01',
                    ],
                ] );
                $this->current_season = [
                    'name'         => '2026',
                    'date_closing' => '2026-05-01',
                ];
            }
            public function get_events( array $args = [] ): array {
                $event = new class extends Event {
                    public function __construct() {
                        $this->id          = 101;
                        $this->name        = 'Men Premier';
                        $this->type        = 'MD';
                        $this->status      = '';
                        $this->teams       = [];
                        $this->event_teams = [];
                    }
                    public function get_id(): int {
                        return 101;
                    }
                    public function get_teams( array $args = [] ): array {
                        return [];
                    }
                    public function get_team_info( int $team_id ): ?object {
                        return null;
                    }
                };
                return [ $event ];
            }
        };

        $club = new class extends Club {
            public function __construct() {
                $this->id        = 5;
                $this->name      = 'Leigh Tennis Club';
                $this->shortcode = 'Leigh TC';
                $this->entry     = [];
            }
            public function get_id(): int {
                return 5;
            }
        };

        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $club_service->method( 'get_clubs' )->willReturn( 1 );

        $query = new Get_Competition_Entry_Query(
            competition_name: 'Summer League',
            season: '2025',
            club_name: 'Leigh TC',
            user_id: 1,
            is_admin: true,
            entry_level: 'captain'
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'entry-league', $vm->template );
        $this->assertSame( 'Monday', $vm->vars['match_days']['0'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering entry-league template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Summer League', $output );
        $this->assertStringNotContainsString( 'Entry deadline', $output, 'The 2025 entry form must not inherit the current 2026 deadline' );
        $this->assertStringNotContainsString( '01/05/2026', $output );
        $this->assertMatchesRegularExpression( '/<option[^>]*value="0"[^>]*>Monday<\/option>/', $output );
        $this->assertStringContainsString( 'Men Premier', $output );

        $current_season_query = new Get_Competition_Entry_Query(
            competition_name: 'Summer League',
            season: '2026',
            club_name: 'Leigh TC',
            user_id: 1,
            is_admin: true,
            entry_level: 'captain'
        );
        $current_season_vm = $handler->handle( $current_season_query );
        $current_warnings  = [];
        $current_output    = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $current_season_vm->template, $current_season_vm->vars, $current_season_vm->template_type ),
            $current_warnings
        );

        $this->assertSame( [], $current_warnings, 'Rendering the 2026 league entry template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Entry deadline', $current_output );
        $this->assertStringContainsString( '01/05/2026', $current_output );
    }

    public function test_entry_cup_template_renders_with_real_handler_output_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $club_service         = $this->createStub( Club_Service::class );
        $player_service       = $this->createStub( Player_Service::class );
        $comp_service         = $this->createStub( Competition_Service::class );
        $tournament_service   = $this->createStub( Tournament_Service::class );
        $team_service         = $this->createStub( Team_Service::class );
        $registration_service = $this->createStub( Registration_Service::class );
        $comp_entry_service   = $this->createStub( Competition_Entry_Service::class );
        $finance_service      = $this->createStub( Finance_Service::class );

        $club_entry_selection = new Club_Entry_Selection_Service( $club_service, $player_service );

        $handler = new Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $comp_service,
            tournament_service: $tournament_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $registration_service,
            competition_entry_service: $comp_entry_service,
            finance_service: $finance_service,
            competition_presenter: new Competition_Shortcode_Presenter( $this->view_renderer ),
            tournament_presenter: new Tournament_Presenter()
        );

        $competition = new class extends Competition {
            public function __construct() {
                $this->id             = 14;
                $this->name           = 'Championship Cup';
                $this->type           = Competition_Type::CUP;
                $this->is_open        = true;
                $this->seasons        = new Season_Collection( [
                    '2026' => [
                        'name'         => '2026',
                        'date_closing' => '2026-06-01',
                    ],
                ] );
                $this->current_season = [
                    'name'         => '2026',
                    'date_closing' => '2026-06-01',
                ];
            }
            public function get_events( array $args = [] ): array {
                $event = new class extends Event {
                    public function __construct() {
                        $this->id     = 201;
                        $this->name   = 'Men Cup Event';
                        $this->type   = 'MD';
                        $this->status = '';
                        $this->teams  = [];
                    }
                    public function get_id(): int {
                        return 201;
                    }
                    public function get_teams( array $args = [] ): array {
                        return [];
                    }
                    public function get_team_info( int $team_id ): ?object {
                        return null;
                    }
                };
                return [ $event ];
            }
        };

        $club = new class extends Club {
            public function __construct() {
                $this->id        = 5;
                $this->name      = 'Leigh Tennis Club';
                $this->shortcode = 'Leigh TC';
                $this->entry     = [];
            }
            public function get_id(): int {
                return 5;
            }
        };

        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $club_service->method( 'get_clubs' )->willReturn( 1 );
        $team_service->method( 'get_teams_for_club' )->willReturn( [] );

        $query = new Get_Competition_Entry_Query(
            competition_name: 'Championship Cup',
            season: '2026',
            club_name: 'Leigh TC',
            user_id: 1,
            is_admin: true,
            entry_level: 'captain'
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'entry-cup', $vm->template );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering entry-cup template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Championship Cup', $output );
        $this->assertStringContainsString( 'Entry deadline', $output );
        $this->assertStringContainsString( '01/06/2026', $output );
        $this->assertStringContainsString( 'Men Cup Event', $output );

        $competition->seasons        = new Season_Collection( [ '2026' => [ 'name' => '2026' ] ] );
        $competition->current_season = [ 'name' => '2026' ];
        $missing_date_vm             = $handler->handle( $query );
        $missing_date_warnings       = [];
        $missing_date_output         = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $missing_date_vm->template, $missing_date_vm->vars, $missing_date_vm->template_type ),
            $missing_date_warnings
        );

        $this->assertSame( [], $missing_date_warnings, 'Rendering cup entry without a closing date must produce zero PHP warnings' );
        $this->assertStringNotContainsString( 'Entry deadline', $missing_date_output );
    }

    public function test_entry_tournament_template_renders_with_real_handler_output_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $GLOBALS['wp_stubs_clubs'][1]         = (object) [ 'id' => 1, 'name' => 'Leigh Tennis Club' ];

        $club_service         = $this->createStub( Club_Service::class );
        $player_service       = $this->createStub( Player_Service::class );
        $comp_service         = $this->createStub( Competition_Service::class );
        $tournament_service   = $this->createStub( Tournament_Service::class );
        $team_service         = $this->createStub( Team_Service::class );
        $registration_service = $this->createStub( Registration_Service::class );
        $comp_entry_service   = $this->createStub( Competition_Entry_Service::class );
        $finance_service      = $this->createStub( Finance_Service::class );

        $club_entry_selection = new Club_Entry_Selection_Service( $club_service, $player_service );

        $handler = new Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $comp_service,
            tournament_service: $tournament_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $registration_service,
            competition_entry_service: $comp_entry_service,
            finance_service: $finance_service,
            competition_presenter: new Competition_Shortcode_Presenter( $this->view_renderer ),
            tournament_presenter: new Tournament_Presenter( 'd/m/Y' )
        );

        $today        = new \DateTimeImmutable( 'today', new \DateTimeZone( 'UTC' ) );
        $date_open    = $today->modify( '-5 days' )->format( 'Y-m-d' );
        $date_closing = $today->modify( '+10 days' )->format( 'Y-m-d' );
        $date_start   = $today->modify( '+30 days' )->format( 'Y-m-d' );
        $date_end     = $today->modify( '+37 days' )->format( 'Y-m-d' );

        $competition = new class( $date_closing ) extends Competition {
            public function __construct( string $date_closing ) {
                $this->id             = 8;
                $this->name           = 'Summer Open';
                $this->type           = Competition_Type::TOURNAMENT;
                $this->is_tournament  = true;
                $this->is_open        = true;
                $this->seasons        = new Season_Collection( [
                    '2026' => [
                        'name'         => '2026',
                        'date_closing' => $date_closing,
                    ],
                ] );
                $this->current_season = [
                    'name'         => '2026',
                    'date_closing' => $date_closing,
                ];
            }
        };

        $events = [
            new class extends Event {
                public ?string $gender = 'M';
                public ?bool $is_singles = true;
                public function __construct() {
                    $this->id   = 301;
                    $this->name = "Men's Singles";
                    $this->type = 'MS';
                }
                public function get_id(): int {
                    return 301;
                }
            },
        ];

        $tournament = new Tournament( (object) [
            'id'             => 1,
            'competition_id' => 8,
            'name'           => 'Summer Open',
            'season'         => '2026',
            'date_open'    => $date_open,
            'date_start'   => $date_start,
            'date_end'     => $date_end,
            'date_closing' => $date_closing,
            'fees'         => (object) [
                'competition' => 5,
                'event'       => 10,
            ],
        ] );

        $player = Player_Test_Factory::create_sparse( [
            'id'            => 42,
            'firstname'     => 'Roger',
            'surname'       => 'Federer',
            'display_name'  => 'Roger Federer',
            'gender'        => 'M',
            'year_of_birth' => 1986,
            'age'           => 40,
            'contactno'     => '07123456789',
            'user_email'    => 'roger@example.com',
            'btm'           => '12345678',
        ] );

        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $tournament_service->method( 'get_fees' )->willReturn( (object) [
            'competition' => 5,
            'event'       => 10,
        ] );
        $tournament_service->method( 'get_events_for_tournament' )->willReturn( $events );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $player_service->method( 'get_player' )->willReturn( $player );

        $club_dto = new Club_Player_DTO(
            $player,
            Club_Test_Factory::create( id: 1, name: 'Leigh Tennis Club', shortcode: 'LTC' ),
            new Club_Player()
        );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [ $club_dto ] );
        $comp_entry_service->method( 'get_tournament_entry_by_tournament_and_player' )->willReturn( new Tournament_Entry( (object) [
            'id'            => 10,
            'tournament_id' => 1,
            'player_id'     => 42,
            'status'        => 1,
            'fee'           => 15,
            'club_id'       => 1,
        ] ) );

        $query = new Get_Competition_Entry_Query(
            tournament_name: 'Summer Open',
            user_id: 42
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'entry-tournament', $vm->template );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering entry-tournament must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Summer Open', $output );
        $this->assertStringContainsString( 'Leigh Tennis Club', $output );
        $this->assertStringContainsString( 'Entry deadline', $output );
        $this->assertStringContainsString( $today->modify( '+10 days' )->format( 'd/m/Y' ), $output );
        $this->assertStringContainsString( "Men's Singles", $output );

        $tournament->date_closing = null;
        $missing_date_vm          = $handler->handle( $query );
        $missing_date_warnings   = [];
        $missing_date_output     = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $missing_date_vm->template, $missing_date_vm->vars, $missing_date_vm->template_type ),
            $missing_date_warnings
        );

        $this->assertSame( [], $missing_date_warnings, 'Rendering tournament entry without a closing date must produce zero PHP warnings' );
        $this->assertStringNotContainsString( 'Entry deadline', $missing_date_output, 'A missing tournament closing date must not display the presenter fallback N/A' );
    }

    public function test_two_row_differing_favourite_buttons_do_not_leak_state(): void {
        unset( $GLOBALS['racketmanager'] );
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;

        $GLOBALS['racketmanager'] = new class {
            public string $date_format = 'Y-m-d';
            public function is_user_favourite( string $type, int|string $id ): bool {
                return in_array( (int) $id, [ 102, 202 ], true );
            }
        };

        $league_service = $this->createStub( League_Service::class );
        $team_service   = $this->createStub( Team_Service::class );
        $stats_service  = $this->createStub( Player_Stats_Service::class );

        $handler = new Get_League_Teams_Handler(
            $league_service,
            $team_service,
            $stats_service,
            new Tournament_Presenter( team_match_row_presenter: $this->create_team_match_row_presenter() ),
            new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() )
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
            public function get_league_teams( array $query_args = [] ): array {
                $team1 = (object) [ 'id' => 101, 'title' => 'Westcliff 1' ];
                $team2 = (object) [ 'id' => 102, 'title' => 'Leigh TC 2' ];
                return [ $team1, $team2 ];
            }
            public function get_team_dtls( int $team_id, ?string $season = null ): object {
                return (object) [
                    'captain'              => 'John Smith',
                    'captain_contactno'    => '01234567890',
                    'captain_contactemail' => 'john@test.local',
                    'match_day'            => 'Wednesday',
                    'match_time'           => '19:00:00',
                ];
            }
        };

        $league_service->method( 'get_league' )->willReturn( $league );

        $vm = $handler->handle( new Get_League_Teams_Query( league_id: 10 ) );
        $this->assertFalse( $vm->is_error() );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering two-row teams list must produce zero PHP warnings' );

        // Row 1 (id 101) must NOT be marked as favourite
        $this->assertStringContainsString( 'title="Add favourite" id="fav-101" data-js="add-favourite" data-type="team" data-favourite="101" data-status=""', $output );
        $this->assertDoesNotMatchRegularExpression(
            '/class="[^"]*btn-favourite[^"]*is-favourite[^"]*"[^>]*id="fav-101"/',
            $output,
            'Row 1 must not have is-favourite class'
        );

        // Row 2 (id 102) MUST be marked as favourite
        $this->assertStringContainsString( 'title="Remove favourite" id="fav-102" data-js="add-favourite" data-type="team" data-favourite="102" data-status="1"', $output );
        $this->assertMatchesRegularExpression(
            '/class="[^"]*btn-favourite[^"]*is-favourite[^"]*"[^>]*id="fav-102"/',
            $output,
            'Row 2 must have is-favourite class'
        );

        // Also test favourite icon template in loop (competition/events)
        $events_vars = [
            'competition' => new class extends Competition {
                public function __construct() {
                    $this->type           = Competition_Type::LEAGUE;
                    $this->current_season = [ 'name' => '2026' ];
                }
            },
            'events'      => [
                (object) [
                    'event_id'    => 201,
                    'event_name'  => 'Men Div 1',
                    'num_leagues' => 1,
                    'num_teams'   => 8,
                    'num_players' => null,
                ],
                (object) [
                    'event_id'    => 202,
                    'event_name'  => 'Ladies Div 1',
                    'num_leagues' => 1,
                    'num_teams'   => 8,
                    'num_players' => null,
                ],
            ],
        ];

        $output_events = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'events', $events_vars, 'competition' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering competition event favourite icons must produce zero PHP warnings' );
        $this->assertMatchesRegularExpression(
            '/id="fav-201"[^>]*title="Add favourite"[^>]*data-favourite="201"/',
            $output_events,
            'Event 201 icon must have "Add favourite" title'
        );
        $this->assertMatchesRegularExpression(
            '/id="fav-202"[^>]*title="Remove favourite"[^>]*data-favourite="202"/',
            $output_events,
            'Event 202 icon must have "Remove favourite" title'
        );
    }

    public function test_email_competition_entry_open_renders_with_correct_punctuation_and_dates(): void {
        $GLOBALS['racketmanager'] = (object) [
            'date_format' => 'd/m/Y',
            'site_url'    => 'https://example.local',
        ];

        $vars = [
            'email_subject'   => 'Competition Entry Open',
            'competition'     => 'summer league',
            'season_dtls'     => (object) [
                'date_start'   => '2026-05-01',
                'date_end'     => '2026-08-31',
                'date_closing' => '2026-04-15',
            ],
            'addressee'       => 'Jane Doe',
            'salutation'      => RACKETMANAGER_PATH . 'templates/email/components/salutation.php',
            'paragraph'       => RACKETMANAGER_PATH . 'templates/email/components/paragraph.php',
            'contact'         => RACKETMANAGER_PATH . 'templates/email/components/contact.php',
            'closing'         => RACKETMANAGER_PATH . 'templates/email/components/closing.php',
            'action_link'     => RACKETMANAGER_PATH . 'templates/email/components/action-link.php',
            'from_email'      => 'secretary@example.local',
            'action_url'      => 'https://example.local/entry',
            'site_url'        => 'https://example.local',
            'organisation'    => 'Tennis Association',
            'days_remaining'  => null,
            'is_championship' => false,
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'competition-entry-open', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering competition-entry-open email must produce zero PHP warnings' );
        $this->assertStringContainsString( 'The entry form for the Summer league is now available. The closing date for entries is 15/04/2026.', $output );
        $this->assertStringContainsString( 'The competition will run from 01/05/2026 until 31/08/2026.', $output );
        $this->assertStringNotContainsString( '..', $output, 'Email body must not contain double periods' );
    }

    public function test_single_match_legacy_template_renders_with_match_date_and_zero_warnings(): void {
        $match = new class {
            public int $id = 88;
            public string $score = '6-2, 6-4';
            public string $date = '2026-06-15';
            public string $start_time = '19:00:00';
            public string $location = 'Court 1';
            public int $match_day = 3;
            public int $post_id = 0;
            public function match_title( int $id, bool $full ): string {
                return 'Westcliff 1 vs Leigh TC 1';
            }
        };

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match', [ 'match' => $match ] ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Westcliff 1 vs Leigh TC 1', $output );
        $this->assertStringContainsString( '6-2, 6-4', $output );
        $this->assertStringContainsString( 'Match Day 3', $output );
        $this->assertStringContainsString( '15/06/2026', $output );
        $this->assertStringContainsString( 'Court 1', $output );
    }

    public function test_single_match_legacy_template_renders_with_missing_date_and_zero_warnings(): void {
        $match = new class {
            public int $id = 89;
            public string $score = '0:0';
            public ?string $date = null;
            public string $start_time = '10:00:00';
            public string $location = 'Court 2';
            public ?int $match_day = null;
            public int $post_id = 0;
            public function match_title( int $id, bool $full ): string {
                return 'Finals Match';
            }
        };

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match', [ 'match' => $match ] ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match template with missing date must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Finals Match', $output );
        $this->assertStringContainsString( 'Court 2', $output );
    }

    public function test_player_clubs_template_renders_with_presenter_formatted_dates_and_zero_warnings(): void {
        $option_gateway = new class implements \Racketmanager\Gateways\Option\Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'd/m/Y';
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
                return true;
            }
            public function delete_option( string $option ): bool {
                return true;
            }
        };

        $player_presenter = new Player_Presenter( option_gateway: $option_gateway );

        $active_club = (object) [
            'club_name'         => 'Westcliff Hardcourt',
            'club_display_name' => 'Westcliff Hardcourt Club',
            'role_name'         => 'Member',
            'approval_date'     => '2026-01-10',
            'contactno'         => '0123456789',
            'email'             => 'player@test.com',
            'is_current'        => true,
            'is_pending'        => false,
            'is_expired'        => false,
        ];

        $archived_club = (object) [
            'club_name'         => 'Leigh Tennis Club',
            'club_display_name' => 'Leigh Tennis Club',
            'role_name'         => 'Member',
            'approval_date'     => '2025-02-01',
            'removal_date'      => '2026-05-20',
            'contactno'         => '0123456789',
            'email'             => 'player@test.com',
            'is_current'        => false,
            'is_pending'        => false,
            'is_expired'        => true,
        ];

        $presented_active   = $player_presenter->present_club_membership( $active_club );
        $presented_archived = $player_presenter->present_club_membership( $archived_club );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render(
                'player-clubs',
                [
                    'clubs'         => [ $presented_active ],
                    'clubs_archive' => [ $presented_archived ],
                ],
                'account'
            ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering player-clubs must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Westcliff Hardcourt', $output );
        $this->assertStringContainsString( 'Added: 10/01/2026', $output );
        $this->assertStringContainsString( 'Previous memberships', $output );
        $this->assertStringContainsString( 'Leigh Tennis Club', $output );
        $this->assertStringContainsString( 'Removed: 20/05/2026', $output );
        $this->assertStringContainsString( 'Added: 01/02/2025', $output );
    }

    public function test_player_clubs_template_renders_with_missing_dates_and_zero_warnings(): void {
        $option_gateway = new class implements \Racketmanager\Gateways\Option\Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'd/m/Y';
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
                return true;
            }
            public function delete_option( string $option ): bool {
                return true;
            }
        };

        $player_presenter = new Player_Presenter( option_gateway: $option_gateway );

        $club = (object) [
            'club_name'         => 'Hadleigh TC',
            'club_display_name' => 'Hadleigh Tennis Club',
            'approval_date'     => null,
            'removal_date'      => null,
        ];

        $presented_club = $player_presenter->present_club_membership( $club );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'player-clubs', [ 'clubs' => [ $presented_club ] ], 'account' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering player-clubs with missing dates must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Hadleigh TC', $output );
        $this->assertStringNotContainsString( 'Added:', $output );
        $this->assertStringNotContainsString( 'Removed:', $output );
    }

    public function test_get_memberships_handler_full_flow_renders_player_clubs_template(): void {
        $player = $this->createStub( Player::class );
        $player->method( 'get_id' )->willReturn( 42 );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $option_gateway = new class implements \Racketmanager\Gateways\Option\Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'd/m/Y';
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
                return true;
            }
            public function delete_option( string $option ): bool {
                return true;
            }
        };

        $player_presenter = new Player_Presenter( option_gateway: $option_gateway );

        $reg_service = $this->createStub( Registration_Service::class );
        $reg_service->method( 'get_clubs_for_player' )->willReturnCallback( function ( $id, $status = 'active' ) {
            if ( 'removed' === $status ) {
                return [
                    (object) [
                        'club_name'         => 'Old Club',
                        'club_display_name' => 'Old Club Display',
                        'approval_date'     => '2024-03-01',
                        'removal_date'      => '2025-04-15',
                    ],
                ];
            }
            return [
                (object) [
                    'club_name'         => 'Current Club',
                    'club_display_name' => 'Current Club Display',
                    'approval_date'     => '2026-02-14',
                    'removal_date'      => null,
                ],
            ];
        } );

        $handler = new Get_Memberships_Handler( $player_service, $reg_service, $player_presenter );
        $vm      = $handler->handle( new Get_Memberships_Query( is_logged_in: true, user_id: 42 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'player-clubs', $vm->template );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering full handler memberships flow must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Current Club', $output );
        $this->assertStringContainsString( 'Added: 14/02/2026', $output );
        $this->assertStringContainsString( 'Previous memberships', $output );
        $this->assertStringContainsString( 'Old Club', $output );
        $this->assertStringContainsString( 'Removed: 15/04/2025', $output );
    }
}
