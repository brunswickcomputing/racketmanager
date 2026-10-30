<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\DTOs\League_Championship_Result;
use Racketmanager\Application\League\DTOs\League_Crosstable_Result;
use Racketmanager\Application\League\DTOs\League_Last_5_Result;
use Racketmanager\Application\League\DTOs\League_Match_Dropdown_Result;
use Racketmanager\Application\League\DTOs\League_Matches_Result;
use Racketmanager\Application\League\DTOs\League_Players_Result;
use Racketmanager\Application\League\DTOs\League_Season_Dropdown_Result;
use Racketmanager\Application\League\DTOs\League_Teams_Result;
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
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Team_Details_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * Mapping tests for League_Shortcode_Adapter::present_*() with populated result DTOs.
 *
 * Each test pins the template filename, template type, view vars and presenter calls
 * that the League query handlers produced before the Application/Presentation split.
 */
class League_Shortcode_Adapter_Mapping_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        global $wp;
        $wp = (object) [ 'query_vars' => [] ];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_adapter( array $overrides = [] ): League_Shortcode_Adapter {
        $settings = $this->createStub( Settings_Service::class );
        $settings->method( 'get_category' )->willReturn( [] );

        return new League_Shortcode_Adapter(
            $this->createStub( RacketManager::class ),
            $this->createStub( Get_League_Standings_Handler::class ),
            $this->createStub( League_Standings_Presenter::class ),
            $this->createStub( Shortcode_View_Presenter::class ),
            $this->createStub( Get_League_Championship_Handler::class ),
            $this->createStub( Get_League_Archive_Handler::class ),
            $this->createStub( Get_League_Crosstable_Handler::class ),
            $this->createStub( Get_League_Matches_Handler::class ),
            $this->createStub( Get_League_Teams_Handler::class ),
            $this->createStub( Get_League_Players_Handler::class ),
            $this->createStub( Get_League_Season_Dropdown_Handler::class ),
            $this->createStub( Get_League_Match_Dropdown_Handler::class ),
            $this->createStub( Get_League_Last_5_Handler::class ),
            template_renderer: $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class ),
            tournament_presenter: $overrides['tournament_presenter'] ?? $this->createStub( Tournament_Presenter::class ),
            team_details_presenter: new Team_Details_Presenter( $this->createStub( Option_Gateway_Interface::class ), $settings ),
            fixture_presenter: $overrides['fixture_presenter'] ?? $this->createStub( Fixture_Presenter::class ),
            competition_service: $overrides['competition_service'] ?? null,
            team_service: $overrides['team_service'] ?? null
        );
    }

    private function league( array $competition = [], array $props = [] ): object {
        return (object) array_merge(
            [
                'sport' => 'tennis',
                'event' => (object) [ 'competition' => (object) array_merge( [ 'type' => 'league' ], $competition ) ],
            ],
            $props
        );
    }

    public function test_matches_non_team_entry_renders_match_cards(): void {
        $raw        = [ (object) [ 'id' => 1 ], (object) [ 'id' => 2 ] ];
        $tournament = $this->createMock( Tournament_Presenter::class );
        $tournament->method( 'is_tournament_competition' )->willReturn( true );
        $tournament->expects( self::once() )->method( 'present_match_cards' )
            ->with( $raw, null, null, '', false, true )
            ->willReturn( [ 'cards' ] );
        $tournament->expects( self::never() )->method( 'present_match_list' );

        $league = $this->league( [ 'is_team_entry' => false ] );
        $vm     = $this->create_adapter( [ 'tournament_presenter' => $tournament ] )->present_matches(
            new League_Matches_Result(
                league: $league,
                raw_matches: $raw,
                is_team_entry: false,
                teams: [ (object) [ 'id' => 5 ] ],
                season: '2026',
                post_id: 12,
                form_action: 'https://example.test/page/'
            )
        );

        $this->assertSame( 'matches', $vm->template );
        $this->assertFalse( $vm->template_type );
        $this->assertSame( $league, $vm->vars['league'] );
        $this->assertSame( [ 'cards' ], $vm->vars['matches'] );
        $this->assertSame( 12, $vm->vars['post_id'] );
        $this->assertSame( '2026', $vm->vars['season'] );
        $this->assertSame( 'https://example.test/page/', $vm->vars['form_action'] );
        $this->assertSame( [ 'league', 'matches', 'teams', 'season', 'post_id', 'form_action' ], array_keys( $vm->vars ) );
    }

    public function test_matches_grouped_wraps_match_days_in_group_view_models(): void {
        $day1       = [ (object) [ 'id' => 1, 'match_day' => 1 ] ];
        $day2       = [ (object) [ 'id' => 2, 'match_day' => 2 ] ];
        $tournament = $this->createMock( Tournament_Presenter::class );
        $tournament->expects( self::never() )->method( 'present_match_cards' );
        $tournament->expects( self::exactly( 2 ) )->method( 'present_match_list' )
            ->willReturnCallback( function ( iterable $matches, ?bool $is_tournament = null, array $options = [] ) {
                $this->assertNull( $is_tournament );
                $this->assertSame( [ 'show_header' => false, 'matches_key' => 'match_day' ], $options );
                return [ 'list-' . $matches[0]->id ];
            } );

        $vm = $this->create_adapter( [ 'tournament_presenter' => $tournament ] )->present_matches(
            new League_Matches_Result(
                league: $this->league(),
                raw_matches: array_merge( $day1, $day2 ),
                is_grouped: true,
                grouped_matches: [ 1 => $day1, 2 => $day2 ]
            )
        );

        $groups = $vm->vars['matches'];
        $this->assertSame( [ 1, 2 ], array_keys( $groups ) );
        $this->assertInstanceOf( Team_Matches_Group_View_Model::class, $groups[1] );
        $this->assertSame( '1', $groups[1]->key );
        $this->assertSame( 'Match Day 1', $groups[1]->header );
        $this->assertSame( [ 'list-1' ], $groups[1]->matches );
        $this->assertSame( 'Match Day 2', $groups[2]->header );
        $this->assertSame( [ 'list-2' ], $groups[2]->matches );
    }

    public function test_matches_ungrouped_renders_flat_match_list(): void {
        $raw        = [ (object) [ 'id' => 1 ] ];
        $tournament = $this->createMock( Tournament_Presenter::class );
        $tournament->expects( self::once() )->method( 'present_match_list' )
            ->with( $raw, null, [ 'show_header' => false ] )
            ->willReturn( [ 'flat' ] );

        $vm = $this->create_adapter( [ 'tournament_presenter' => $tournament ] )->present_matches(
            new League_Matches_Result( league: $this->league(), raw_matches: $raw )
        );

        $this->assertSame( [ 'flat' ], $vm->vars['matches'] );
    }

    public function test_matches_filename_lookup_matches_legacy_order(): void {
        $renderer = $this->createStub( Shortcode_Template_Renderer::class );
        $renderer->method( 'template_exists' )->willReturnCallback(
            static fn( string $name, false|string $type = false ) => false === $type && in_array( $name, [ 'matches-tennis', 'matches-by_matchday-tennis' ], true )
        );
        $adapter = $this->create_adapter( [ 'template_renderer' => $renderer ] );

        $this->assertSame( 'matches-tennis', $adapter->present_matches( new League_Matches_Result( league: $this->league() ) )->template );
        $this->assertSame( 'matches-by_matchday-tennis', $adapter->present_matches( new League_Matches_Result( league: $this->league(), template: 'by_matchday' ) )->template );
        $this->assertSame( 'matches-other', $adapter->present_matches( new League_Matches_Result( league: $this->league(), template: 'other' ) )->template );
    }

    public function test_team_details_presents_match_list_and_team_details_view_model(): void {
        $team         = (object) [ 'id' => 10, 'title' => 'Leigh 1' ];
        $team_details = (object) [ 'team' => $team ];
        $raw          = [ (object) [ 'id' => 3 ] ];
        $tournament   = $this->createMock( Tournament_Presenter::class );
        $tournament->expects( self::never() )->method( 'present_match_cards' );
        $tournament->expects( self::once() )->method( 'present_match_list' )
            ->with( $raw, null, [ 'current_team' => 10, 'show_header' => false ] )
            ->willReturn( [ 'rows' ] );

        $league = $this->league( [ 'is_player_entry' => false ], [ 'title' => 'Division 1' ] );
        $vm     = $this->create_adapter( [ 'tournament_presenter' => $tournament ] )->present_teams(
            new League_Teams_Result(
                league: $league,
                is_team_details: true,
                team: $team,
                team_details: $team_details,
                raw_matches: $raw,
                user_can_edit_team: true,
                team_id: 10
            )
        );

        $this->assertSame( 'team-details', $vm->template );
        $this->assertFalse( $vm->template_type );
        $this->assertSame( [ 'league', 'teams', 'object', 'team_details', 'vm' ], array_keys( $vm->vars ) );
        $this->assertSame( [], $vm->vars['teams'] );
        $this->assertSame( $league, $vm->vars['object'] );
        $this->assertSame( $team_details, $vm->vars['team_details'] );
        $this->assertInstanceOf( Team_Details_View_Model::class, $vm->vars['vm'] );
        $this->assertSame( [ 'rows' ], $team->matches );
    }

    public function test_team_details_player_entry_presents_match_cards(): void {
        $team       = (object) [ 'id' => 10 ];
        $raw        = [ (object) [ 'id' => 3 ] ];
        $tournament = $this->createMock( Tournament_Presenter::class );
        $tournament->method( 'is_tournament_competition' )->willReturn( false );
        $tournament->expects( self::once() )->method( 'present_match_cards' )
            ->with( $raw, null, null, '', false, false )
            ->willReturn( [ 'cards' ] );
        $tournament->expects( self::never() )->method( 'present_match_list' );

        $this->create_adapter( [ 'tournament_presenter' => $tournament ] )->present_teams(
            new League_Teams_Result(
                league: $this->league( [ 'is_player_entry' => true ] ),
                is_team_details: true,
                team: $team,
                team_details: (object) [ 'team' => $team ],
                raw_matches: $raw
            )
        );

        $this->assertSame( [ 'cards' ], $team->matches );
    }

    public function test_last_5_builds_tooltips_with_team_titles_for_object_and_array_matches(): void {
        $object_match = (object) [ 'id' => 7, 'home_team' => 1, 'away_team' => 2 ];
        $array_match  = [ 'id' => 8, 'home_team' => 2, 'away_team' => 3 ];
        $no_id_match  = (object) [ 'home_team' => 1 ];
        $fixture      = $this->createMock( Fixture_Presenter::class );
        $fixture->expects( self::exactly( 2 ) )->method( 'present_last_5_tooltip' )
            ->willReturnCallback( static fn( $match, ?string $home, ?string $away ) => $home . ' v ' . ( $away ?? 'null' ) );

        $renderer = $this->createStub( Shortcode_Template_Renderer::class );
        $renderer->method( 'template_exists' )->willReturn( false );

        $vm = $this->create_adapter( [ 'fixture_presenter' => $fixture, 'template_renderer' => $renderer ] )->present_last_5(
            new League_Last_5_Result(
                league: $this->league(),
                matches: [ $object_match, $array_match, $no_id_match ],
                team_id: 1,
                team_titles: [ '1' => 'Leigh', '2' => 'Westcliff' ],
                template: 'compact'
            )
        );

        $this->assertSame( 'last-5-compact', $vm->template );
        $this->assertSame( 'league', $vm->template_type );
        $this->assertSame( [ 'matches', 'team_id', 'match_tooltips' ], array_keys( $vm->vars ) );
        $this->assertSame( 1, $vm->vars['team_id'] );
        $this->assertSame( [ 7 => 'Leigh v Westcliff', 8 => 'Westcliff v null' ], $vm->vars['match_tooltips'] );
    }

    public function test_crosstable_passes_mode_and_season(): void {
        $vm = $this->create_adapter()->present_crosstable(
            new League_Crosstable_Result( league: $this->league(), mode: 'popup', season: '2026', template: 'x' )
        );

        $this->assertSame( 'crosstable-x', $vm->template );
        $this->assertSame( 'league', $vm->template_type );
        $this->assertSame( 'popup', $vm->vars['mode'] );
        $this->assertSame( '2026', $vm->vars['season'] );
    }

    public function test_template_suffixes_are_always_prefixed(): void {
        $adapter = $this->create_adapter();

        $this->assertSame( 'championship-championship', $adapter->present_championship( new League_Championship_Result( league: $this->league(), template: 'championship' ) )->template );
        $this->assertSame( 'players-players', $adapter->present_players( new League_Players_Result( league: $this->league(), template: 'players' ) )->template );
        $this->assertSame( 'season-dropdown-x', $adapter->present_season_dropdown( new League_Season_Dropdown_Result( league: $this->league(), template: 'x' ) )->template );
        $this->assertSame( 'match-dropdown-x', $adapter->present_match_dropdown( new League_Match_Dropdown_Result( league: $this->league(), template: 'x' ) )->template );
    }

    public function test_championship_tournament_presents_match_cards_and_list(): void {
        $fixture_a  = Fixture_Factory::from_object( (object) [ 'id' => 11 ] );
        $fixture_b  = Fixture_Factory::from_object( (object) [ 'id' => 12 ] );
        $card       = new Tournament_Match_Card_View_Model( id: 11 );
        $tournament = $this->createMock( Tournament_Presenter::class );
        $tournament->method( 'is_tournament_competition' )->willReturn( true );
        $tournament->expects( self::exactly( 2 ) )->method( 'present_match_card' )
            ->with( self::anything(), null, null, 'list', false, true )
            ->willReturn( $card );
        $tournament->expects( self::once() )->method( 'present_match_list' )
            ->willReturnCallback( function ( iterable $matches, ?bool $is_tournament, array $options ) {
                $this->assertSame( [ 1, 2 ], array_keys( $matches ) );
                $this->assertTrue( $is_tournament );
                $this->assertSame( [ 'show_header' => false ], $options );
                return [ 'final-list' ];
            } );

        $data          = (object) [ 'key' => 'final', 'name' => 'Final', 'matches' => [ 1 => $fixture_a, 2 => $fixture_b ] ];
        $empty         = (object) [ 'key' => 'semi', 'name' => 'Semi', 'matches' => [] ];
        $league        = $this->league( [ 'type' => 'tournament' ] );
        $vm            = $this->create_adapter( [ 'tournament_presenter' => $tournament ] )->present_championship(
            new League_Championship_Result( league: $league, finals: [ 0 => $data, 1 => $empty ] )
        );

        $this->assertSame( 'championship', $vm->template );
        $this->assertFalse( $vm->template_type );
        $this->assertSame( [ 'league', 'finals' ], array_keys( $vm->vars ) );
        $this->assertSame( [ 0 ], array_keys( $vm->vars['finals'] ) );
        $this->assertSame( [ 'final-list' ], $vm->vars['finals'][0]->matches );
        $this->assertSame( 'Final', $vm->vars['finals'][0]->name );
        // The handler's result data is not mutated by presentation.
        $this->assertSame( [ 1 => $fixture_a, 2 => $fixture_b ], $data->matches );
    }

    public function test_championship_non_tournament_adapts_fixtures_without_mutating_domain(): void {
        $fixture    = Fixture_Factory::from_object( (object) [ 'id' => 21, 'location' => '' ] );
        $tournament = $this->createMock( Tournament_Presenter::class );
        $tournament->method( 'is_tournament_competition' )->willReturn( false );
        $tournament->expects( self::never() )->method( 'present_match_card' );
        $captured = null;
        $tournament->expects( self::once() )->method( 'present_match_list' )
            ->willReturnCallback( function ( iterable $matches, ?bool $is_tournament, array $options ) use ( &$captured ) {
                $captured = $matches;
                $this->assertFalse( $is_tournament );
                return [ 'list' ];
            } );

        $vm = $this->create_adapter( [
            'tournament_presenter' => $tournament,
            'competition_service'  => $this->createStub( Competition_Service::class ),
            'team_service'         => $this->createStub( Team_Service::class ),
        ] )->present_championship(
            new League_Championship_Result(
                league: $this->league(),
                finals: [ 0 => (object) [ 'key' => 'final', 'matches' => [ 1 => $fixture ] ] ],
                num_rubbers: 0,
                num_sets: 3
            )
        );

        $this->assertSame( [ 'list' ], $vm->vars['finals'][0]->matches );
        $this->assertSame( [ 1 ], array_keys( $captured ) );
        $this->assertInstanceOf( Fixture_Presentation_Adapter::class, $captured[1] );
        $this->assertSame( 0, $captured[1]->num_rubbers );
        $this->assertSame( 3, $captured[1]->num_sets );
        $this->assertSame( '', $captured[1]->location );
        $this->assertSame( ' - ', $captured[1]->title );
        $this->assertFalse( property_exists( $fixture, 'num_sets' ) && isset( $fixture->num_sets ) );
    }
}
