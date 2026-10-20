<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Player;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Persistence\Legacy_Fixture_Listing_Finder;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Matches_List_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Public\Shortcodes;
use Racketmanager\Public\Shortcodes_Match;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Matches_Shortcode_Adapter_Test extends TestCase {
    use Creates_Test_Container;
    use Creates_Team_Match_Row_Presenter;
    use Manages_Wordpress_Globals;

    /** @var array<int, array> Recorded get_matches() calls. */
    private array $get_matches_calls = [];

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        global $wp;
        $wp                    = (object) [ 'query_vars' => [] ];
        $GLOBALS['query_vars'] = [];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // [dailymatches]
    // ---------------------------------------------------------------------

    public function test_daily_matches_renders_teams_fixture_link_and_selected_date(): void {
        $adapter = $this->build_adapter( [ $this->fixture( 201, '1', '2', null ) ] );

        $output = $adapter->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertStringContainsString( 'Daily matches', $output );
        $this->assertStringContainsString( 'id="match_date" class="form-control match-date" value="2026-10-12"', $output );
        $this->assertStringContainsString( 'Red Club 1st Team', $output );
        $this->assertStringContainsString( 'Blue Club 2nd Team', $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $output );
        $this->assertSame( [ [ 'match_date' => '2026-10-12', 'competition_type' => 'league' ] ], $this->get_matches_calls );
    }

    public function test_daily_matches_renders_form_without_wp_query(): void {
        unset( $GLOBALS['wp_query'], $GLOBALS['post'] );

        $output = $this->build_adapter( [] )->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertStringContainsString( 'action="" id="racketmanager_daily_matches"', $output );
        $this->assertStringContainsString( 'name="page_id" value="0"', $output );
    }

    public function test_daily_matches_adapter_supplies_form_target(): void {
        $GLOBALS['wp_query'] = (object) [ 'post' => (object) [ 'ID' => 87 ] ];

        $output = $this->build_adapter( [] )->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertStringContainsString( 'action="https://example.test/page/87"', $output );
        $this->assertStringContainsString( 'name="page_id" value="87"', $output );
    }

    public function test_daily_matches_shows_result_link_only_when_update_allowed(): void {
        $allowed = $this->build_adapter( [ $this->fixture( 201, '1', '2', null ) ], true )->show_daily_matches( [ 'match_date' => '2026-10-12' ] );
        $this->assertStringContainsString( 'href="https://example.test/match/201/result/"', $allowed );
        $this->assertStringContainsString( 'class="btn match__btn"', $allowed );

        $denied = $this->build_adapter( [ $this->fixture( 201, '1', '2', null ) ], false )->show_daily_matches( [ 'match_date' => '2026-10-12' ] );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $denied );
        $this->assertStringNotContainsString( 'href="https://example.test/match/201/result/"', $denied );
        $this->assertStringNotContainsString( 'class="btn match__btn"', $denied );
    }

    public function test_daily_matches_prioritizes_query_vars_date(): void {
        $GLOBALS['query_vars']['match_date'] = '2025-01-04';
        $_GET['match_date']                  = '2025-02-08';

        $this->build_adapter( [] )->show_daily_matches( [] );

        $this->assertSame( '2025-01-04', $this->get_matches_calls[0]['match_date'] );
    }

    public function test_daily_matches_uses_get_parameter_when_query_vars_not_set(): void {
        $_GET['match_date'] = '2025-02-08';

        $output = $this->build_adapter( [] )->show_daily_matches( [] );

        $this->assertSame( '2025-02-08', $this->get_matches_calls[0]['match_date'] );
        $this->assertStringContainsString( 'value="2025-02-08"', $output );
        $this->assertStringContainsString( 'No Matches on selected day', $output );
    }

    public function test_daily_matches_defaults_to_today_and_rejects_invalid_dates(): void {
        $this->build_adapter( [] )->show_daily_matches( [] );
        $this->assertSame( gmdate( 'Y-m-d' ), $this->get_matches_calls[0]['match_date'] );

        $_GET['match_date'] = "2025-02-08' OR '1'='1";
        $output             = $this->build_adapter( [] )->show_daily_matches( [] );
        $this->assertSame( gmdate( 'Y-m-d' ), $this->get_matches_calls[0]['match_date'] );
        $this->assertStringNotContainsString( "OR '1'='1", $output );
    }

    public function test_daily_matches_competition_type_from_seo_query_var(): void {
        global $wp;
        $wp->query_vars['competition_type']        = 'cup';
        $GLOBALS['query_vars']['competition_type'] = 'Cup';

        $this->build_adapter( [] )->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertSame( 'cup', $this->get_matches_calls[0]['competition_type'] );
    }

    public function test_daily_matches_output_is_identical_to_legacy_shortcode(): void {
        $fixtures = [ $this->fixture( 201, '1', '2', null ), $this->fixture( 202, '3', '4', 10 ) ];
        $adapter  = $this->build_adapter( $fixtures, true );
        $legacy   = new Shortcodes( $GLOBALS['racketmanager'] );

        $this->assertSame(
            $legacy->show_daily_matches( [ 'match_date' => '2026-10-12' ] ),
            $adapter->show_daily_matches( [ 'match_date' => '2026-10-12' ] )
        );
    }

    // ---------------------------------------------------------------------
    // [latest_results]
    // ---------------------------------------------------------------------

    public function test_latest_results_groups_by_league_and_honours_header_level(): void {
        $adapter = $this->build_adapter( [ $this->fixture( 202, '3', '4', 10 ) ] );

        $output = $adapter->show_latest_results( [ 'days' => 7, 'header_level' => 2 ] );

        $this->assertStringContainsString( '<h2 class="module__title">Latest Results</h2>', $output );
        $this->assertStringContainsString( 'Division 2', $output );
        $this->assertStringContainsString( 'Green Team', $output );
        $this->assertStringContainsString( 'Yellow Team', $output );
        $this->assertSame( 'latest', $this->get_matches_calls[0]['time'] );
        $this->assertSame( 7, $this->get_matches_calls[0]['history'] );
    }

    public function test_latest_results_resolves_club_name_query_var(): void {
        global $wp;
        $wp->query_vars['club_name']        = 'leigh-park';
        $GLOBALS['query_vars']['club_name'] = 'leigh-park';
        $club                               = $this->createStub( Club::class );
        $club->id                           = 42;
        $clubs                              = $this->createMock( Club_Repository_Interface::class );
        $clubs->expects( $this->once() )->method( 'find_by_id' )->with( 'leigh park', 'shortcode' )->willReturn( $club );

        $this->build_adapter( [], false, $clubs )->show_latest_results( [] );

        $this->assertSame( 42, $this->get_matches_calls[0]['club'] );
    }

    public function test_latest_results_unknown_club_renders_empty_state_without_querying(): void {
        global $wp;
        $wp->query_vars['club_name']        = 'nowhere';
        $GLOBALS['query_vars']['club_name'] = 'nowhere';

        $output = $this->build_adapter( [ $this->fixture( 202, '3', '4', 10 ) ] )->show_latest_results( [] );

        $this->assertStringContainsString( 'No recent results', $output );
        $this->assertSame( [], $this->get_matches_calls );
    }

    public function test_latest_results_output_is_identical_to_legacy_shortcode(): void {
        $fixtures = [ $this->fixture( 202, '3', '4', 10 ) ];
        $adapter  = $this->build_adapter( $fixtures, true );
        $legacy   = new Shortcodes( $GLOBALS['racketmanager'] );

        $this->assertSame(
            $legacy->show_latest_results( [ 'days' => 7 ] ),
            $adapter->show_latest_results( [ 'days' => 7 ] )
        );
    }

    public function test_missing_template_renders_alert_and_restores_output_buffer(): void {
        $level  = ob_get_level();
        $output = $this->build_adapter( [] )->show_latest_results( [ 'template' => 'does-not-exist' ] );

        $this->assertStringContainsString( 'Could not load template matches-does-not-exist.php from templates directory', $output );
        $this->assertSame( $level, ob_get_level() );
    }

    // ---------------------------------------------------------------------
    // [score]
    // ---------------------------------------------------------------------

    public function test_show_score_returns_error_when_no_match_id(): void {
        $output = $this->build_native_adapter( $this->details_service( null ) )->show_score( [ 'id' => 0 ] );

        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'Match id not found', $output );
    }

    public function test_show_score_returns_error_when_match_not_found(): void {
        $output = $this->build_native_adapter( $this->details_service( null ) )->show_score( [ 'id' => 42 ] );

        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'Match not found', $output );
    }

    public function test_show_score_unplayed_match_without_home_away_is_blank_placeholder(): void {
        $dto = $this->details_dto( $this->fixture( 42, '1', '2', 10 ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )->show_score( [ 'id' => 42 ] );

        $this->assertSame( "<span class='unplayedMatch'>&nbsp;</span>", $output );
    }

    public function test_show_score_unplayed_match_with_home_away_shows_match_day_and_date(): void {
        $dto = $this->details_dto( $this->fixture( 42, '1', '2', 10 ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )->show_score( [ 'id' => 42, 'home_away' => true ] );

        $this->assertStringContainsString( 'unplayedMatch', $output );
        $this->assertStringContainsString( 'Match Day 1', $output );
        $this->assertStringContainsString( '<br/>', $output );
    }

    public function test_show_score_unplayed_match_without_date_shows_not_available(): void {
        $dto = $this->details_dto( $this->played_fixture( 42, '1', '2', null, null, null, null, '0000-00-00' ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )->show_score( [ 'id' => 42, 'home_away' => true ] );

        $this->assertStringContainsString( 'N/A', $output );
    }

    public function test_show_score_winner_at_home(): void {
        $dto = $this->details_dto( $this->played_fixture( 42, '1', '2', 1, 2, '6.0', '2.0' ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )
            ->show_score( [ 'id' => 42, 'team' => '1', 'opponent' => '2', 'home_away' => true ] );

        $this->assertStringContainsString( 'score winner', $output );
        $this->assertMatchesRegularExpression( '/is-team-1">6<\/span>/', $output );
        $this->assertMatchesRegularExpression( '/is-team-2">2<\/span>/', $output );
        $this->assertStringContainsString( 'title="Match Day 1"', $output );
        $this->assertStringContainsString( 'href="https://example.test/match/42/"', $output );
    }

    public function test_show_score_loser_at_home(): void {
        $dto = $this->details_dto( $this->played_fixture( 42, '1', '2', 2, 1, '1.0', '5.0' ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )
            ->show_score( [ 'id' => 42, 'team' => '1', 'opponent' => '2', 'home_away' => true ] );

        $this->assertStringContainsString( 'score loser', $output );
        $this->assertMatchesRegularExpression( '/is-team-1">1<\/span>/', $output );
        $this->assertMatchesRegularExpression( '/is-team-2">5<\/span>/', $output );
    }

    public function test_show_score_swaps_points_when_requested_team_is_away(): void {
        $dto = $this->details_dto( $this->played_fixture( 42, '1', '2', 1, 2, '6.0', '2.0' ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )
            ->show_score( [ 'id' => 42, 'team' => '2', 'opponent' => '1', 'home_away' => true ] );

        $this->assertStringContainsString( 'score loser', $output );
        $this->assertMatchesRegularExpression( '/is-team-1">2<\/span>/', $output );
        $this->assertMatchesRegularExpression( '/is-team-2">6<\/span>/', $output );
    }

    public function test_show_score_tie_is_classified_as_tie(): void {
        $dto = $this->details_dto( $this->played_fixture( 42, '1', '2', -1, -1, '4.0', '4.0' ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )
            ->show_score( [ 'id' => 42, 'team' => '1', 'opponent' => '2' ] );

        $this->assertStringContainsString( 'score tie', $output );
        $this->assertStringNotContainsString( 'score-separator', $output );
        $this->assertStringContainsString( 'title=""', $output );
    }

    public function test_show_score_resolves_fixture_by_attribute_id(): void {
        $service = $this->createMock( Fixture_Detail_Service::class );
        $service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 77 )->willReturn( null );

        $this->build_native_adapter( $service )->show_score( [ 'id' => '77' ] );
    }

    // ---------------------------------------------------------------------
    // [match-header]
    // ---------------------------------------------------------------------

    public function test_show_match_header_renders_via_handler_and_presenter(): void {
        $league        = $this->createStub( League::class );
        $league->title = 'Division 1 Men';
        $event         = $this->createStub( Event::class );
        $event->name   = 'Division 1';
        $competition   = $this->createStub( Competition::class );
        $competition->type = Competition_Type::LEAGUE;
        $dto           = $this->details_dto( $this->fixture( 42, '1', '2', 10 ), $league, $event, $competition );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )->show_match_header( [ 'id' => 42 ] );

        $this->assertStringContainsString( 'Division 1 Men', $output );
        $this->assertStringContainsString( 'Match Day 1', $output );
    }

    public function test_show_match_header_errors(): void {
        $adapter = $this->build_native_adapter( $this->details_service( null ) );

        $this->assertStringContainsString( 'Match id not found', $adapter->show_match_header( [] ) );
        $this->assertStringContainsString( 'Match not found', $adapter->show_match_header( [ 'id' => 5 ] ) );
    }

    // ---------------------------------------------------------------------
    // [match-detail]
    // ---------------------------------------------------------------------

    public function test_show_match_detail_errors(): void {
        $adapter = $this->build_native_adapter( $this->details_service( null ) );

        $this->assertStringContainsString( 'Match id not found', $adapter->show_match_detail( [] ) );
        $this->assertStringContainsString( 'Match not found', $adapter->show_match_detail( [ 'id' => 5 ] ) );
    }

    public function test_show_match_detail_passes_round_type_and_player_as_template_vars_without_mutating_fixture(): void {
        $fixture = \Racketmanager\Domain\Fixture\Fixture_Factory::from_array( [ 'id' => 42, 'final' => 'semi' ] );
        $rubber = ( new \ReflectionClass( \Racketmanager\Domain\Fixture\Rubber::class ) )->newInstanceWithoutConstructor();
        $rubber->id = 101;
        $rubber->players = [ 'home' => [ 1 => (object) [ 'user_id' => 9 ] ] ];
        $rubbers = $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class );
        $league              = $this->createStub( League::class );
        $league->num_rubbers = 4;
        $dto                 = $this->details_dto( $fixture, $league );

        $player     = $this->createStub( Player::class );
        $player->id = 9;
        $players    = $this->createStub( Player_Service::class );
        $players->method( 'get_player' )->willReturn( $player );

        $captured = null;
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            function ( string $path, $vm, array $vars ) use ( &$captured ): string {
                $captured = [ 'path' => $path, 'vars' => $vars ];

                return 'rendered-detail';
            }
        );

        $service = $this->createMock( Fixture_Detail_Service::class );
        $service->expects( $this->once() )->method( 'get_fixture_with_details' )
            ->with( 42, false, 9 )
            ->willReturnCallback( function () use ( $fixture, $rubber, $dto ) {
                $fixture->set_rubbers( [ $rubber ] );

                return $dto;
            } );
        $adapter = $this->build_native_adapter( $service, $players, $renderer, $rubbers );
        $output  = $adapter->show_match_detail( [ 'id' => 42, 'player' => '9' ] );

        $this->assertSame( 'rendered-detail', $output );
        $this->assertSame( 'match/detail-teams-scores', $captured['path'] );
        $this->assertSame( 'semi', $captured['vars']['match_round'] );
        $this->assertSame( 'tournament', $captured['vars']['match_type'] );
        $this->assertSame( $player, $captured['vars']['match_player'] );
        $this->assertSame( $fixture, $captured['vars']['match'] );
        $this->assertFalse( ( new \ReflectionClass( Fixture::class ) )->hasProperty( 'round' ), 'Fixture must not be given a dynamic round property' );
        $this->assertSame( [ $rubber ], $fixture->rubbers );
    }

    public function test_show_match_detail_league_fixture_uses_plain_template_and_ignores_unknown_player(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_final' )->willReturn( null );
        $fixture->method( 'get_rubbers' )->willReturn( [] );
        $dto = $this->details_dto( $fixture );

        $players = $this->createStub( Player_Service::class );
        $players->method( 'get_player' )->willThrowException( new Player_Not_Found_Exception( 'nope' ) );

        $captured = null;
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            function ( string $path, $vm, array $vars ) use ( &$captured ): string {
                $captured = [ 'path' => $path, 'vars' => $vars ];

                return 'ok';
            }
        );

        $this->build_native_adapter( $this->details_service( $dto ), $players, $renderer )
            ->show_match_detail( [ 'id' => 42, 'player' => '999', 'template' => 'custom' ] );

        $this->assertSame( 'match/detail-custom', $captured['path'] );
        $this->assertSame( '', $captured['vars']['match_round'] );
        $this->assertSame( 'league', $captured['vars']['match_type'] );
        $this->assertNull( $captured['vars']['match_player'] );
    }

    public function test_missing_match_template_renders_alert_naming_the_template(): void {
        $dto = $this->details_dto( $this->played_fixture( 42, '1', '2', 1, 2, '6.0', '2.0' ) );

        $output = $this->build_native_adapter( $this->details_service( $dto ) )
            ->show_score( [ 'id' => 42, 'team' => '1', 'template' => 'does-not-exist' ] );

        $this->assertStringContainsString( 'Could not load template score-does-not-exist.php', $output );
    }

    // ---------------------------------------------------------------------
    // Facade and architecture guards
    // ---------------------------------------------------------------------

    public function test_shortcodes_match_facade_delegates_to_adapter(): void {
        $adapter = $this->createMock( Matches_Shortcode_Adapter::class );
        $adapter->expects( $this->once() )->method( 'show_score' )->with( [ 'id' => 10 ] )->willReturn( 'facade-score' );
        $adapter->expects( $this->once() )->method( 'show_match_header' )->with( [ 'id' => 10 ] )->willReturn( 'facade-header' );
        $adapter->expects( $this->once() )->method( 'show_match_detail' )->with( [ 'id' => 10 ] )->willReturn( 'facade-detail' );

        $app = $this->create_app_with_services( [ 'matches_shortcode_adapter' => $adapter ] );

        $facade = new Shortcodes_Match( $app );
        $this->assertSame( 'facade-score', $facade->show_score( [ 'id' => 10 ] ) );
        $this->assertSame( 'facade-header', $facade->show_match_header( [ 'id' => 10 ] ) );
        $this->assertSame( 'facade-detail', $facade->show_match_detail( [ 'id' => 10 ] ) );
    }

    public function test_adapter_and_facade_have_no_legacy_cycle_or_service_locator(): void {
        $adapter = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Infrastructure/Wordpress/Shortcodes/Matches_Shortcode_Adapter.php' );
        $facade  = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Public/Shortcodes_Match.php' );

        $this->assertStringNotContainsString( 'Racketmanager\\Public\\', $adapter );
        $this->assertStringNotContainsString( 'match_handler', $adapter );
        $this->assertStringNotContainsString( 'container', $adapter );
        $this->assertSame( 0, substr_count( $facade, 'load_template' ) );
        $this->assertSame( 0, substr_count( $facade, 'fixture_detail_service' ) );
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function build_adapter( array $fixtures, ?bool $can_update = null, ?Club_Repository_Interface $clubs = null ): Matches_Shortcode_Adapter {
        $this->get_matches_calls = [];
        $calls                   = &$this->get_matches_calls;

        $container = $this->create_test_container( [
            '1' => [ 'name' => 'Red Club 1st Team', 'type' => 'T' ],
            '2' => [ 'name' => 'Blue Club 2nd Team', 'type' => 'T' ],
            '3' => [ 'name' => 'Green Team', 'type' => 'T' ],
            '4' => [ 'name' => 'Yellow Team', 'type' => 'T' ],
        ] );

        $this->configure_league_repository( $container, 10, 'Division 2' );

        $permission_service = null;
        if ( null !== $can_update ) {
            $permission_service = $this->createStub( Fixture_Permission_Service::class );
            $permission_service->method( 'is_update_allowed' )->willReturn( (object) [ 'user_can_update' => $can_update ] );
            $container->set( 'fixture_permission_service', fn() => $permission_service );
        }

        $competition_service = $container->get( 'competition_service' );
        $team_service        = $container->get( 'team_service' );
        $tournament_presenter = new Tournament_Presenter( team_match_row_presenter: $this->create_team_match_row_presenter( $can_update ) );
        $container->set( 'tournament_presenter', fn() => $tournament_presenter );

        $app = $this->createStub( RacketManager::class );
        $app->method( 'get_matches' )->willReturnCallback( static function ( array $args ) use ( &$calls, $fixtures ) {
            $calls[] = $args;
            return $fixtures;
        } );
        $app->container   = $container;
        $app->date_format = 'Y-m-d';

        global $racketmanager;
        $racketmanager = $app;

        $finder = new Legacy_Fixture_Listing_Finder( $app );
        if ( null === $clubs ) {
            $clubs = $this->createStub( Club_Repository_Interface::class );
            $clubs->method( 'find_by_id' )->willReturn( null );
        }
        $renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $this->assertInstanceOf( View_Renderer_Interface::class, $renderer );

        return $this->new_adapter(
            $renderer,
            $this->createStub( Fixture_Detail_Service::class ),
            $this->createStub( Player_Service::class ),
            new Get_Daily_Matches_Handler( $finder ),
            new Get_Latest_Results_Handler( $finder, $clubs, $this->createStub( Competition_Repository_Interface::class ) ),
            new Matches_List_Presenter( $competition_service, $team_service, $renderer, $tournament_presenter, null, $permission_service )
        );
    }

    private function build_native_adapter( Fixture_Detail_Service $service, ?Player_Service $players = null, ?View_Renderer_Interface $renderer = null, ?\Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface $rubbers = null ): Matches_Shortcode_Adapter {
        return $this->new_adapter(
            $renderer ?? new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ),
            $service,
            $players ?? $this->createStub( Player_Service::class ),
            $this->createStub( Get_Daily_Matches_Handler::class ),
            $this->createStub( Get_Latest_Results_Handler::class ),
            $this->createStub( Matches_List_Presenter::class ),
            $rubbers
        );
    }

    private function new_adapter(
        View_Renderer_Interface $renderer,
        Fixture_Detail_Service $service,
        Player_Service $players,
        Get_Daily_Matches_Handler $daily,
        Get_Latest_Results_Handler $latest,
        Matches_List_Presenter $list_presenter,
        ?\Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface $rubbers = null
    ): Matches_Shortcode_Adapter {
        return new Matches_Shortcode_Adapter(
            $daily,
            $latest,
            $list_presenter,
            new Get_Match_Score_Handler( $service ),
            new Get_Match_Header_Handler( $service ),
            new Get_Match_Detail_Handler( $service, $players, $rubbers ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class ) ),
            new Match_Shortcode_Presenter( new Shortcode_View_Presenter( new Shortcode_Template_Renderer( $renderer ) ), new Fixture_Presenter( new Fixture_Link_Service(), $this->createStub( Option_Gateway_Interface::class ) ), $players )
        );
    }

    private function details_service( ?Fixture_Details_DTO $dto ): Fixture_Detail_Service {
        $service = $this->createStub( Fixture_Detail_Service::class );
        $service->method( 'get_fixture_with_details' )->willReturn( $dto );

        return $service;
    }

    private function details_dto( Fixture $fixture, ?League $league = null, ?Event $event = null, ?Competition $competition = null ): Fixture_Details_DTO {
        return new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league ?? $this->createStub( League::class ),
            event: $event ?? $this->createStub( Event::class ),
            competition: $competition ?? $this->createStub( Competition::class ),
            link: 'https://example.test/match/' . $fixture->get_id() . '/'
        );
    }

    private function played_fixture( int $id, string $home, string $away, ?int $winner, ?int $loser, ?string $home_points, ?string $away_points, string $date = '2026-10-12' ): Fixture {
        return Fixture_Test_Factory::create(
            id: $id,
            home_team: $home,
            away_team: $away,
            winner_id: $winner,
            loser_id: $loser,
            home_points: $home_points,
            away_points: $away_points,
            date: $date,
            league_id: 10,
        );
    }

    private function fixture( int $id, string $home, string $away, ?int $league_id ): Fixture {
        return Fixture_Test_Factory::create(
            id: $id,
            home_team: $home,
            away_team: $away,
            league_id: $league_id,
        );
    }
}
