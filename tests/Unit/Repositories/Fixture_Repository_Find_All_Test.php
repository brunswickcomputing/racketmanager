<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Repository_Find_All_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private wpdb $wpdb;
    private Fixture_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = array();

        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

        $this->repository = new Fixture_Repository( $this->wpdb );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_all_default_query_structure(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $result = $this->repository->find_all();
        $this->assertSame( array(), $result );
        $this->assertNotNull( $captured_sql );
        $this->assertStringStartsWith( 'SELECT m.`final` AS final_round', $captured_sql );
        $this->assertStringContainsString( 'FROM wp_racketmanager_matches AS m, wp_racketmanager_leagues AS l', $captured_sql );
        $this->assertStringContainsString( 'WHERE m.`league_id` = l.`id`', $captured_sql );
        $this->assertStringEndsWith( 'ORDER BY `league_id` ASC,`id` ASC', $captured_sql );
    }

    public function test_find_all_count_with_no_filters(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_var' )
            ->with( 'SELECT COUNT(*) FROM wp_racketmanager_matches' )
            ->willReturn( '12' );

        $count = $this->repository->find_all( array( 'count' => true ) );
        $this->assertSame( 12, $count );
    }

    public function test_find_all_count_with_filters(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_var' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): string {
                    $captured_sql = $sql;
                    return '5';
                }
            );

        $count = $this->repository->find_all(
            array(
                'count'    => true,
                'leagueId' => 3,
                'season'   => '2026',
            )
        );

        $this->assertSame( 5, $count );
        $this->assertSame( "SELECT COUNT(*) FROM wp_racketmanager_matches WHERE `league_id`  = '3' AND `season`  = '2026'", $captured_sql );
    }

    public function test_find_all_competition_and_event_filters(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'match_date'       => '2026-06-15',
                'competition_name' => 'Premier League',
                'competition_id'   => 4,
                'event_id'         => 9,
                'league_name'      => 'Division 1',
                'season'           => '2026',
            )
        );

        $this->assertNotNull( $captured_sql );
        $this->assertStringContainsString( "AND DATEDIFF('2026-06-15', `date`) = 0", $captured_sql );
        $this->assertStringContainsString( "WHERE c.`name` = 'Premier League' AND e.`competition_id` = c.`id`", $captured_sql );
        $this->assertStringContainsString( "WHERE `competition_id` = '4'", $captured_sql );
        $this->assertStringContainsString( "WHERE `event_id` = '9'", $captured_sql );
        $this->assertStringContainsString( "WHERE `title` = 'Division 1'", $captured_sql );
        $this->assertStringContainsString( "AND `season`  = '2026'", $captured_sql );
    }

    public function test_find_all_final_rounds_and_competition_type_and_tournament(): void {
        $captured_sql_all = null;
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql_all, &$captured_sql_specific ): array {
                    if ( null === $captured_sql_all ) {
                        $captured_sql_all = $sql;
                    } else {
                        $captured_sql_specific = $sql;
                    }
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'final'            => 'all',
                'competition_type' => 'cup',
                'tournament_id'    => 42,
            )
        );

        $this->assertNotNull( $captured_sql_all );
        $this->assertStringContainsString( "AND `final` != ''", $captured_sql_all );
        $this->assertStringContainsString( "AND c.`type` = 'cup'", $captured_sql_all );
        $this->assertStringContainsString( 'AND t.`id` = 42', $captured_sql_all );

        $this->repository->find_all(
            array(
                'final' => 'semi',
            )
        );

        $this->assertNotNull( $captured_sql_specific );
        $this->assertStringContainsString( "AND `final`  = 'semi'", $captured_sql_specific );
    }

    public function test_find_all_time_offsets_and_pending_fields(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'timeOffset'          => 3,
                'status'              => 'C',
                'confirmed'           => true,
                'confirmationPending' => 2,
                'resultPending'       => 4,
            )
        );

        $this->assertNotNull( $captured_sql );
        $this->assertStringContainsString( "AND `confirmed` = 'C'", $captured_sql );
        $this->assertStringContainsString( "AND `confirmed` in ('P','A','C')", $captured_sql );
        $this->assertStringContainsString( "AND ADDTIME(`updated`,'3:00:00') <= NOW()", $captured_sql );
        $this->assertStringContainsString( "ADDTIME(`updated`,'2:00:00') as confirmation_overdue_date", $captured_sql );
        $this->assertStringContainsString( "ADDTIME(`date`,'4:00:00') as result_overdue_date", $captured_sql );
    }

    public function test_find_all_time_modes_and_history(): void {
        $captured_sqls = array();
        $this->wpdb->expects( $this->exactly( 3 ) )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sqls ): array {
                    $captured_sqls[] = $sql;
                    return array();
                }
            );

        $this->repository->find_all( array( 'time' => 'latest' ) );
        $this->repository->find_all( array( 'time' => 'outstanding', 'timeOffset' => 1 ) );
        $this->repository->find_all( array( 'time' => '14', 'history' => 7 ) );

        $this->assertStringContainsString( "AND (`home_points` != '' OR `away_points` != '')", $captured_sqls[0] );
        $this->assertStringContainsString( "AND ADDTIME(`date`,'1:00:00') <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL", $captured_sqls[1] );
        $this->assertStringContainsString( 'AND m.`date` > now() - INTERVAL 14 DAY', $captured_sqls[2] );
        $this->assertStringContainsString( 'AND `updated` >= NOW() - INTERVAL 7 DAY', $captured_sqls[2] );
    }

    public function test_find_all_club_and_team_and_day_filters(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'club'      => 10,
                'team'      => 20,
                'home_club' => 11,
                'home_team' => 21,
                'away_team' => 22,
                'team_name' => 'Hawks',
                'match_day' => 5,
            )
        );

        $this->assertNotNull( $captured_sql );
        $this->assertStringContainsString( 'SELECT `id` FROM wp_racketmanager_teams WHERE `club_id` = 10', $captured_sql );
        $this->assertStringContainsString( 'AND (`home_team` = 20 OR `away_team` = 20)', $captured_sql );
        $this->assertStringContainsString( 'AND `home_team` IN (SELECT `id` FROM wp_racketmanager_teams WHERE `club_id` = 11)', $captured_sql );
        $this->assertStringContainsString( 'AND `home_team` = 21 ', $captured_sql );
        $this->assertStringContainsString( 'AND `away_team` = 22 ', $captured_sql );
        $this->assertStringContainsString( "SELECT `id` FROM wp_racketmanager_teams WHERE `title` LIKE '%Hawks%'", $captured_sql );
        $this->assertStringContainsString( 'AND `match_day` = 5 ', $captured_sql );
    }

    public function test_find_all_player_joins_for_league_and_tournament(): void {
        $captured_league     = null;
        $captured_tournament = null;
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_league, &$captured_tournament ): array {
                    if ( null === $captured_league ) {
                        $captured_league = $sql;
                    } else {
                        $captured_tournament = $sql;
                    }
                    return array();
                }
            );

        // Player without tournament: joins rubbers and rubber_players.
        $this->repository->find_all( array( 'player' => 99 ) );
        $this->assertNotNull( $captured_league );
        $this->assertStringContainsString( ',wp_racketmanager_rubbers r, wp_racketmanager_rubber_players rp', $captured_league );
        $this->assertStringContainsString( "AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = '99'", $captured_league );

        // Player with tournament: joins team_players.
        $this->repository->find_all( array( 'player' => 99, 'tournament_id' => 7 ) );
        $this->assertNotNull( $captured_tournament );
        $this->assertStringContainsString( ',wp_racketmanager_team_players tp', $captured_tournament );
        $this->assertStringContainsString( "AND ((m.`home_team` = tp.`team_id` AND tp.`player_id` = '99') OR (m.`away_team` = tp.`team_id` AND tp.`player_id` = '99'))", $captured_tournament );
    }

    public function test_find_all_type_complete_and_age_group(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'type'      => 'tennis',
                'complete'  => true,
                'age_group' => 'Veterans',
            )
        );

        $this->assertNotNull( $captured_sql );
        $this->assertStringContainsString( "WHERE e.`type` like '%%tennis%%'", $captured_sql );
        $this->assertStringContainsString( 'AND m.`winner_id` != 0', $captured_sql );
        $this->assertStringContainsString( "WHERE e.`competition_id` = c.`id` and `age_group` = 'Veterans'", $captured_sql );
    }

    public function test_find_all_cache_hit_bypasses_database_query(): void {
        $this->wpdb->method( 'get_row' )
            ->willReturn( (object) array( 'id' => 101, 'league_id' => 1, 'season' => '2026', 'home_team' => 1, 'away_team' => 2, 'date' => '2026-05-01' ) );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( array( (object) array( 'id' => 101 ) ) );

        $first_run = $this->repository->find_all( array( 'season' => '2026' ) );
        $this->assertCount( 1, $first_run );

        // Second run with same parameters: get_results is NOT called again.
        $second_run = $this->repository->find_all( array( 'season' => '2026' ) );
        $this->assertCount( 1, $second_run );
    }

    public function test_find_all_hydrates_fixtures_and_attaches_rubbers_for_player(): void {
        $mock_fixture_row = (object) array(
            'id'          => 77,
            'league_id'   => 1,
            'season'      => '2026',
            'home_team'   => 10,
            'away_team'   => 20,
            'home_points' => 4,
            'away_points' => 2,
            'match_day'   => 1,
            'date'        => '2026-05-01 10:00:00',
        );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( array( (object) array( 'id' => 77 ) ) );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $mock_fixture_row );

        $rubber_repo = $this->createMock( Rubber_Repository_Interface::class );
        $mock_rubber = $this->createMock( Rubber::class );
        $rubber_repo->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 77, 88 )
            ->willReturn( array( $mock_rubber ) );

        $repo = new Fixture_Repository( $this->wpdb, null, $rubber_repo );
        $results = $repo->find_all( array( 'player' => 88 ) );

        $this->assertCount( 1, $results );
        $this->assertInstanceOf( Fixture::class, $results[0] );
        $this->assertSame( 77, $results[0]->get_id() );
        $this->assertSame( array( $mock_rubber ), $results[0]->get_rubbers() );
    }

    public function test_find_all_multi_filter_row_sql_is_exact(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'leagueId' => 3,
                'season'   => '2026',
                'final'    => 'semi',
                'status'   => 'C',
            )
        );

        $expected_sql = "SELECT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, m.`confirmed`, m.`home_captain`, m.`away_captain`, m.`comments`, m.`updated`, `event_id`, m.`status`, `leg`, `winner_id_tie`, `loser_id_tie`"
            . ' FROM wp_racketmanager_matches AS m, wp_racketmanager_leagues AS l'
            . ' WHERE m.`league_id` = l.`id`'
            . " AND `league_id`  = '3' AND `season`  = '2026' AND `final`  = 'semi'"
            . " AND `confirmed` = 'C'"
            . ' ORDER BY `league_id` ASC,`id` ASC';

        $this->assertSame( $expected_sql, $captured_sql );
    }

    public function test_find_all_multi_filter_row_sql_is_exact_all_helpers(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'leagueId'      => 3,
                'season'        => '2026',
                'status'        => 'C',
                'team'          => 20,
                'player'        => 99,
                'tournament_id' => 7,
                'complete'      => true,
            )
        );

        $expected_sql = "SELECT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, m.`confirmed`, m.`home_captain`, m.`away_captain`, m.`comments`, m.`updated`, `event_id`, m.`status`, `leg`, `winner_id_tie`, `loser_id_tie`"
            . ' FROM wp_racketmanager_matches AS m, wp_racketmanager_leagues AS l ,wp_racketmanager_team_players tp'
            . ' WHERE m.`league_id` = l.`id`'
            . " AND `league_id`  = '3' AND `season`  = '2026' AND `league_id` in (select `id` from wp_racketmanager_leagues WHERE `event_id` in (SELECT e.`id` FROM wp_racketmanager_events e, wp_racketmanager_competitions c, wp_racketmanager_tournaments t WHERE e.`competition_id` = c.`id` AND c.`id` = t.`competition_id` AND t.`id` = 7))"
            . " AND `confirmed` = 'C'"
            . ' AND (`home_team` = 20 OR `away_team` = 20)'
            . " AND ((m.`home_team` = tp.`team_id` AND tp.`player_id` = '99') OR (m.`away_team` = tp.`team_id` AND tp.`player_id` = '99'))"
            . ' AND m.`winner_id` != 0'
            . ' ORDER BY `league_id` ASC,`id` ASC';

        $this->assertSame( $expected_sql, $captured_sql );
    }

    public function test_find_all_treats_empty_cached_array_as_cache_miss(): void {
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_results' )
            ->willReturn( array() );

        $first_run = $this->repository->find_all( array( 'season' => '2026' ) );
        $this->assertSame( array(), $first_run );

        $second_run = $this->repository->find_all( array( 'season' => '2026' ) );
        $this->assertSame( array(), $second_run );
    }

    public function test_find_all_preserves_result_keys_when_a_fixture_is_dropped(): void {
        $row_one   = (object) array(
            'id'          => 1,
            'league_id'   => 1,
            'season'      => '2026',
            'home_team'   => 10,
            'away_team'   => 20,
            'home_points' => 0,
            'away_points' => 0,
            'match_day'   => 1,
            'date'        => '2026-05-01 10:00:00',
        );
        $row_three = (object) array(
            'id'          => 3,
            'league_id'   => 1,
            'season'      => '2026',
            'home_team'   => 30,
            'away_team'   => 40,
            'home_points' => 0,
            'away_points' => 0,
            'match_day'   => 2,
            'date'        => '2026-05-08 10:00:00',
        );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( array( (object) array( 'id' => 1 ), (object) array( 'id' => 2 ), (object) array( 'id' => 3 ) ) );

        $this->wpdb->expects( $this->exactly( 3 ) )
            ->method( 'get_row' )
            ->willReturnOnConsecutiveCalls( $row_one, null, $row_three );

        $results = $this->repository->find_all( array( 'season' => '2026' ) );

        $this->assertSame( array( 0, 2 ), array_keys( $results ) );
        $this->assertSame( 1, $results[0]->get_id() );
        $this->assertSame( 3, $results[2]->get_id() );
    }

    public function test_find_all_confirmed_without_time_offset_uses_zero_offset(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all( array( 'confirmed' => true ) );

        $this->assertNotNull( $captured_sql );
        $this->assertStringContainsString( "AND ADDTIME(`updated`,'00:00:00') <= NOW()", $captured_sql );
        $this->assertStringNotContainsString( "ADDTIME(`updated`,'0:00:00')", $captured_sql );
    }

    public function test_find_all_uses_expected_md5_cache_key(): void {
        $captured_sql = null;
        $database_rows = array( (object) array( 'id' => 101 ) );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql, $database_rows ): array {
                    $captured_sql = $sql;
                    return $database_rows;
                }
            );

        $this->wpdb->method( 'get_row' )
            ->willReturn( (object) array( 'id' => 101, 'league_id' => 1, 'season' => '2026', 'home_team' => 1, 'away_team' => 2, 'date' => '2026-05-01' ) );

        $first_run = $this->repository->find_all();

        $this->assertNotNull( $captured_sql );
        $this->assertStringEndsWith( 'ORDER BY `league_id` ASC,`id` ASC', $captured_sql );

        $expected_key = md5( $captured_sql );
        $this->assertArrayHasKey( $expected_key, $GLOBALS['wp_stubs_cache']['matches'] );
        $this->assertEquals( $database_rows, $GLOBALS['wp_stubs_cache']['matches'][ $expected_key ] );
        $this->assertCount( 1, $first_run );

        $second_run = $this->repository->find_all();

        $this->assertCount( 1, $second_run );
        $this->assertSame( 101, $second_run[0]->get_id() );
    }
}
