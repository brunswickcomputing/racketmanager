<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

/**
 * Regression: get_league_teams( [ 'get_details' => true ] ) used to assign the captain display name
 * to League_Team::$captain (?int, the persisted captain user id) and fatal on real data.
 */
final class League_Team_Details_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_league_teams_with_details_keeps_captain_id_and_exposes_captain_name(): void {
        $league_team = new League_Team( (object) [
            'id'             => 940,
            'table_id'       => 77,
            'title'          => 'Leigh A',
            'captain'        => '55',
            'points_plus'    => 0,
            'points_minus'   => 0,
            'points_2_plus'  => 0,
            'points_2_minus' => 0,
            'custom'         => '',
            'roster'         => '',
        ] );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 77 )->willReturn( $league_team );

        $repository = $this->createStub( League_Repository_Interface::class );
        $repository->method( 'query_league_teams' )->willReturnCallback(
            static fn( int $league_id, array $args, bool $count = false ): array|int => $count ? 1 : [ (object) [ 'table_id' => 77 ] ]
        );
        $repository->method( 'get_team_details' )->willReturn( (object) [
            'id'             => 940,
            'title'          => 'Leigh A',
            'captain'        => '55',
            'club_id'        => '',
            'stadium'        => '',
            'roster'         => '',
            'team_type'      => 'S',
            'match_day'      => 'Friday',
            'match_time'     => '19:30:00',
            'league_status'  => 'A',
            'points_plus'    => 0,
            'points_minus'   => 0,
            'points_2_plus'  => 0,
            'points_2_minus' => 0,
        ] );

        $league = new League( (object) [
            'id'       => 2397,
            'title'    => 'Division 1',
            'event_id' => 10,
            'settings' => [],
            'seasons'  => [ '2026' => [ 'name' => '2026' ] ],
        ] );
        $league->current_season = [ 'name' => '2026' ];

        $captain               = new Player();
        $captain->id           = 55;
        $captain->display_name = 'User 55';
        $captain->user_email   = 'user55@example.test';
        $captain->contactno    = '01234 567890';
        $player_repository     = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )->method( 'find' )->with( 55 )->willReturn( $captain );

        $service = new League_Query_Service(
            $repository,
            league_team_repository: $league_team_repository,
            club_repository: $this->createStub( Club_Repository_Interface::class ),
            player_repository: $player_repository
        );

        $teams = $service->get_league_teams( $league, [ 'get_details' => true ] );

        $this->assertCount( 1, $teams );
        $team = $teams[0];
        $this->assertInstanceOf( League_Team::class, $team );
        $this->assertSame( 940, $team->id );
        $this->assertSame( 55, $team->captain, 'captain keeps the persisted captain user id' );
        $this->assertSame( 55, $team->captain_id );
        $this->assertSame( 'User 55', $team->captain_name );
        $this->assertSame( 'user55@example.test', $team->contactemail );
        $this->assertSame( '01234 567890', $team->contactno );
    }
}
