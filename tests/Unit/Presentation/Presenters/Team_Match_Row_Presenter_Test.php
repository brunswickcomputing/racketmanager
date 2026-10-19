<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;

class Team_Match_Row_Presenter_Test extends TestCase {
    use Creates_Team_Match_Row_Presenter;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function loaded_fixture( int $id = 501, ?int $winner = null, ?int $loser = null, ?string $home_points = null, ?string $away_points = null, ?int $status = null ): Fixture {
        $fixture        = Fixture_Test_Factory::create(
            id: $id,
            link: 'https://example.test/match/' . $id . '/',
            home_team: 1,
            away_team: 2,
            winner_id: $winner,
            loser_id: $loser,
            home_points: $home_points,
            away_points: $away_points,
            status: $status,
        );
        $fixture->teams = [
            'home' => (object) [ 'id' => 1, 'title' => 'Team A', 'club' => (object) [ 'id' => 10 ] ],
            'away' => (object) [ 'id' => 2, 'title' => 'Team B', 'club' => (object) [ 'id' => 20 ] ],
        ];
        $fixture->league = (object) [ 'id' => 5, 'title' => 'Division 1' ];

        return $fixture;
    }

    public function test_unloaded_fixture_is_enriched_through_relation_loader(): void {
        $loader = $this->createMock( Fixture_Relation_Loader::class );
        $loader->expects( $this->once() )->method( 'load' )->willReturnCallback(
            static function ( Fixture $fixture ): Fixture {
                $fixture->teams  = [
                    'home' => (object) [ 'id' => 1, 'title' => 'Loaded Home', 'club' => (object) [ 'id' => 10 ] ],
                    'away' => (object) [ 'id' => 2, 'title' => 'Loaded Away', 'club' => (object) [ 'id' => 20 ] ],
                ];
                $fixture->league = (object) [ 'id' => 5, 'title' => 'Loaded League' ];

                return $fixture;
            }
        );
        $fixture = Fixture_Test_Factory::create( id: 502, home_team: 1, away_team: 2, league_id: 5 );

        $row = $this->create_team_match_row_presenter( relation_loader: $loader )->present( $fixture );

        $this->assertSame( 'Loaded Home', $row->home_name );
        $this->assertSame( 'Loaded Away', $row->away_name );
        $this->assertSame( 'Loaded League', $row->league_title );
        $this->assertSame( 'https://example.test/match/502/', $row->match_link );
    }

    public function test_fixture_without_resolved_teams_reports_unknown_not_a_fatal(): void {
        $fixture = Fixture_Test_Factory::create( id: 503, home_team: 1, away_team: 2 );

        $row = $this->create_team_match_row_presenter()->present( $fixture );

        $this->assertSame( 'Unknown', $row->home_name );
        $this->assertSame( 'Unknown', $row->away_name );
    }

    public function test_update_link_is_built_only_when_permission_allows(): void {
        $allowed = $this->create_team_match_row_presenter( true )->present( $this->loaded_fixture() );
        $denied  = $this->create_team_match_row_presenter( false )->present( $this->loaded_fixture() );
        $skipped = $this->create_team_match_row_presenter( true )->present( $this->loaded_fixture(), [ 'allow_user_update' => false ] );

        $this->assertTrue( $allowed->user_can_update );
        $this->assertSame( 'https://example.test/match/501/result/', $allowed->update_link );
        $this->assertFalse( $denied->user_can_update );
        $this->assertNull( $denied->update_link );
        $this->assertFalse( $skipped->user_can_update );
        $this->assertNull( $skipped->update_link );
    }

    public function test_final_round_keys_are_translated_and_unknown_rounds_are_preserved(): void {
        $presenter = $this->create_team_match_row_presenter();

        $final = $presenter->present( Fixture_Test_Factory::create( final: 'final' ) );
        $semi  = $presenter->present( Fixture_Test_Factory::create( final: 'semi' ) );
        $round = $presenter->present( Fixture_Test_Factory::create( final: 'round-8' ) );

        $this->assertSame( 'Final', $final->final_name );
        $this->assertSame( 'Semi Final', $semi->final_name );
        $this->assertSame( 'Round of 8', $round->final_name );
    }

    public function test_pending_match_uses_not_played_css_class_and_start_time(): void {
        $row = $this->create_team_match_row_presenter()->present( $this->loaded_fixture() );

        $this->assertTrue( $row->is_pending );
        $this->assertSame( 'is-not-played', $row->score_class );
        $this->assertSame( '18:00', $row->score_text );
    }

    public function test_club_highlight_reports_win_loss_and_tie_from_the_club_perspective(): void {
        $presenter = $this->create_team_match_row_presenter();
        $options   = [ 'current_club' => 10 ];

        $win  = $presenter->present( $this->loaded_fixture( 1, 1, 2, '4', '2' ), $options );
        $loss = $presenter->present( $this->loaded_fixture( 2, 2, 1, '1', '5' ), $options );
        $tie  = $presenter->present( $this->loaded_fixture( 3, null, -1, '3', '3' ), $options );

        $this->assertTrue( $win->is_highlighted );
        $this->assertSame( [ 'winner', 'W' ], [ $win->status_class, $win->status_text ] );
        $this->assertSame( [ 'loser', 'L' ], [ $loss->status_class, $loss->status_text ] );
        $this->assertSame( [ 'tie', 'T' ], [ $tie->status_class, $tie->status_text ] );
    }

    public function test_team_highlight_reports_result_for_that_team(): void {
        $presenter = $this->create_team_match_row_presenter();

        $win  = $presenter->present( $this->loaded_fixture( 4, 1, 2, '4', '2' ), [ 'current_team' => 1 ] );
        $loss = $presenter->present( $this->loaded_fixture( 5, 1, 2, '4', '2' ), [ 'current_team' => 2 ] );
        $none = $presenter->present( $this->loaded_fixture( 6, 1, 2, '4', '2' ), [ 'current_team' => 99 ] );

        $this->assertSame( 'W', $win->status_text );
        $this->assertSame( 'L', $loss->status_text );
        $this->assertFalse( $none->is_highlighted );
        $this->assertNull( $none->status_text );
    }

    public function test_non_zero_status_shows_status_message(): void {
        $row = $this->create_team_match_row_presenter()->present( $this->loaded_fixture( 7, 1, 2, '4', '2', 3 ) );

        $this->assertNotEmpty( $row->status_message );
        $plain = $this->create_team_match_row_presenter()->present( $this->loaded_fixture( 8, 1, 2, '4', '2', 0 ) );
        $this->assertNull( $plain->status_message );
    }

    public function test_withdrawn_team_title_uses_team_name(): void {
        $fixture                 = $this->loaded_fixture( 9 );
        $fixture->teams['home']->is_withdrawn = true;

        $row = $this->create_team_match_row_presenter()->present( $fixture );

        $this->assertTrue( $row->home_is_withdrawn );
        $this->assertSame( 'Team A has withdrawn', $row->home_withdrawn_title );
        $this->assertFalse( $row->away_is_withdrawn );
        $this->assertNull( $row->away_withdrawn_title );
    }

    public function test_logged_in_user_selection_comes_from_club_repository(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $GLOBALS['wp_stubs_current_user_id']   = 77;
        $GLOBALS['wp_stubs_get_current_user_id'] = 77;

        $clubs = $this->createMock( Club_Repository_Interface::class );
        $clubs->expects( $this->atLeastOnce() )->method( 'is_player_in_club' )
            ->willReturnCallback( static fn( array $args ): bool => 1 === $args['team'] && 77 === $args['player'] );

        $row = $this->create_team_match_row_presenter( club_repository: $clubs )->present( $this->loaded_fixture() );

        $this->assertTrue( $row->is_selected );
    }

    public function test_anonymous_user_is_never_selected(): void {
        $clubs = $this->createMock( Club_Repository_Interface::class );
        $clubs->expects( $this->never() )->method( 'is_player_in_club' );

        $row = $this->create_team_match_row_presenter( club_repository: $clubs )->present( $this->loaded_fixture() );

        $this->assertFalse( $row->is_selected );
    }

    public function test_group_header_formats_date_keys_and_match_days(): void {
        $presenter = $this->create_team_match_row_presenter( date_format: 'j F Y' );

        $by_date   = $presenter->present_group( '2026-06-01', [ $this->loaded_fixture() ] );
        $match_day = $presenter->present_group( 3, [ $this->loaded_fixture() ], null, [ 'matches_key' => 'match_day' ] );
        $league    = $presenter->present_group( 'Division 1', [ $this->loaded_fixture() ], null, [ 'matches_key' => 'league' ] );
        $explicit  = $presenter->present_group( 'x', [ $this->loaded_fixture() ], 'Custom' );

        $this->assertInstanceOf( Team_Matches_Group_View_Model::class, $by_date );
        $this->assertSame( '1 June 2026', $by_date->header );
        $this->assertSame( 'Match Day 3', $match_day->header );
        $this->assertSame( 'Division 1', $league->header );
        $this->assertSame( 'Custom', $explicit->header );
        $this->assertContainsOnlyInstancesOf( Team_Match_Row_View_Model::class, $by_date->matches );
    }

    public function test_group_keeps_tournament_cards_and_existing_rows_unchanged(): void {
        $presenter = $this->create_team_match_row_presenter();
        $card      = ( new ReflectionClass( Tournament_Match_Card_View_Model::class ) )->newInstanceWithoutConstructor();
        $row       = $presenter->present( $this->loaded_fixture( 10 ) );

        $group = $presenter->present_group( '2026-06-01', [ 'card' => $card, 'row' => $row ] );

        $this->assertSame( $card, $group->matches['card'] );
        $this->assertSame( $row, $group->matches['row'] );
    }

    public function test_unsupported_input_fails_loudly(): void {
        $this->expectException( InvalidArgumentException::class );

        $this->create_team_match_row_presenter()->present( (object) [ 'id' => 1 ] );
    }
}
