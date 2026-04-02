<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Has_Matches_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\League_Service;
use stdClass;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class League_Service_Test extends TestCase {

    private League_Service $service;
    private $league_repository;
    private $event_repository;
    private $league_team_repository;
    private $team_repository;

    protected function setUp(): void {
        parent::setUp();
        $this->league_repository      = $this->createMock( League_Repository_Interface::class );
        $this->event_repository       = $this->createMock( Event_Repository_Interface::class );
        $this->league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $this->team_repository        = $this->createMock( Team_Repository_Interface::class );
        $plugin_instance              = $this->createMock( RacketManager::class );

        $this->service = new League_Service(
            $plugin_instance,
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );
    }

    public function test_add_team_to_league_returns_existing_if_already_exists(): void {
        $team_id   = 1;
        $league_id = 2;
        $season    = 2024;

        $team = $this->createMock( Team::class );
        $this->team_repository->method( 'find_by_id' )->willReturn( $team );

        $league = $this->createMock( League::class );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $existing_league_team = $this->createMock( League_Team::class );

        $this->league_team_repository->expects( self::once() )
                                     ->method( 'find_by_team_league_and_season' )
                                     ->with( $team_id, $league_id, $season )
                                     ->willReturn( $existing_league_team );

        $this->league_team_repository->expects( self::never() )
                                     ->method( 'save' );

        $result = $this->service->add_team_to_league( $team_id, $league_id, $season );

        self::assertSame( $existing_league_team, $result );
    }

    public function test_remove_team_from_league_throws_exception_if_matches_exist(): void {
        $team_id   = 1;
        $league_id = 2;
        $season    = 2024;

        $league_team = $this->createMock( League_Team::class );
        $this->league_team_repository->method( 'find_by_team_league_and_season' )
                                     ->willReturn( $league_team );

        $league = $this->createMock( League::class );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $league->expects( self::once() )
               ->method( 'get_matches' )
               ->with( [
                   'team_id' => $team_id,
                   'season'  => $season,
                   'final'   => 'all',
               ] )
               ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $this->expectException( Team_Has_Matches_Exception::class );

        $this->service->remove_team_from_league( $team_id, $league_id, $season );
    }

    public function test_league_team_constructor_handles_string_profile(): void {
        $data = new stdClass();
        $data->profile = '1'; // ACTIVE
        $data->id = 123;
        $data->team_id = 456;
        $data->league_id = 1;
        $data->season = '2024';
        $data->add_points = 0;
        $data->points_plus = 0;
        $data->points_minus = 0;
        $data->points_2_plus = 0;
        $data->points_2_minus = 0;
        $data->diff = 0;
        $data->done_matches = 0;
        $data->won_matches = 0;
        $data->lost_matches = 0;
        $data->draw_matches = 0;

        $league_team = new League_Team( $data );

        self::assertInstanceOf( Team_Profile::class, $league_team->get_profile_enum() );
        self::assertEquals( Team_Profile::ACTIVE, $league_team->get_profile_enum() );
        self::assertEquals( 1, $league_team->get_profile() );
    }

    public function test_remove_teams_from_league_collects_messages_and_errors(): void {
        $league_id = 1;
        $season = 2024;
        $ids = [101, 102];

        $league_team1 = $this->createMock( League_Team::class );
        $league_team1->method('get_team_id')->willReturn(456);
        $league_team1->method('get_id')->willReturn(101);

        $league_team2 = $this->createMock( League_Team::class );
        $league_team2->method('get_team_id')->willReturn(789);
        $league_team2->method('get_id')->willReturn(102);

        $this->league_team_repository->method('find_by_id')
            ->willReturnMap([
                [101, $league_team1],
                [102, $league_team2]
            ]);

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->willReturnMap([
                [456, $league_id, $season, $league_team1],
                [789, $league_id, $season, $league_team2]
            ]);

        $league = $this->createMock( League::class );
        $this->league_repository->method('find_by_id')->willReturn($league);

        // First team has matches
        $league->method('get_matches')
            ->willReturnCallback(function($args) use ($season) {
                if ($args['team_id'] == 456 && $args['season'] == $season) {
                    return [(object)['id' => 1]];
                }
                return [];
            });

        $result = $this->service->remove_teams_from_league($ids, $league_id, $season);

        self::assertTrue($result['any_error']);
        self::assertCount(2, $result['messages']);
        self::assertStringContainsString('cannot be deleted', $result['messages'][0]);
        self::assertStringContainsString('deleted', $result['messages'][1]);
    }

    public function test_rank_teams_by_points(): void {
        $team1 = new stdClass();
        $team1->points = ['plus' => 10];
        $team1->sets_won = 5;
        $team1->sets_allowed = 2;
        $team1->games_won = 30;
        $team1->games_allowed = 20;
        $team1->title = 'Team B';

        $team2 = new stdClass();
        $team2->points = ['plus' => 10];
        $team2->sets_won = 6;
        $team2->sets_allowed = 2;
        $team2->games_won = 35;
        $team2->games_allowed = 20;
        $team2->title = 'Team A';

        $teams = [$team1, $team2];
        $ranked = $this->service->rank_teams_by_points($teams);

        self::assertSame($team2, $ranked[0]); // More sets won
        self::assertSame($team1, $ranked[1]);
    }

    public function test_rank_teams_manual(): void {
        $league = $this->createMock(League::class);
        $team1_id = 101;
        $team2_id = 102;

        $team1_data = new stdClass();
        $team1_data->id = $team1_id;
        $team1_data->team_id = 456;
        $team1_data->league_id = 1;
        $team1_data->season = '2024';
        $team1_data->profile = '1';
        $team1 = new League_Team($team1_data);

        $team2_data = new stdClass();
        $team2_data->id = $team2_id;
        $team2_data->team_id = 789;
        $team2_data->league_id = 1;
        $team2_data->season = '2024';
        $team2_data->profile = '1';
        $team2 = new League_Team($team2_data);

        $this->league_team_repository->method('find_by_id')
            ->willReturnMap([
                [$team1_id, $team1],
                [$team2_id, $team2]
            ]);

        $post = [
            'rank' => [2, 1],
            'table_id' => [$team1_id, $team2_id]
        ];

        $this->league_team_repository->expects(self::exactly(2))
            ->method('save');

        $result = $this->service->rank_teams($league, 'manual', $post, [$team1_id, $team2_id]);

        self::assertTrue($result);
        self::assertEquals(2, $team1->get_rank());
        self::assertEquals(1, $team2->get_rank());
    }
}
