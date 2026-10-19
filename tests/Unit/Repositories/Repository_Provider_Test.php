<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use RuntimeException;

class Repository_Provider_Test extends TestCase {

    public function test_getters_return_injected_repository_instances(): void {
        $league_repo          = $this->createStub( League_Repository_Interface::class );
        $event_repo           = $this->createStub( Event_Repository_Interface::class );
        $competition_repo     = $this->createStub( Competition_Repository_Interface::class );
        $league_team_repo     = $this->createStub( League_Team_Repository_Interface::class );
        $team_repo            = $this->createStub( Team_Repository_Interface::class );
        $player_repo          = $this->createStub( Player_Repository_Interface::class );
        $rubber_repo          = $this->createStub( Rubber_Repository_Interface::class );
        $results_checker_repo = $this->createStub( Results_Checker_Repository_Interface::class );
        $results_report_repo  = $this->createStub( Results_Report_Repository_Interface::class );
        $result_repo          = $this->createStub( Result_Repository_Interface::class );
        $fixture_repo         = $this->createStub( Fixture_Repository_Interface::class );
        $club_repo            = $this->createStub( Club_Repository_Interface::class );

        $provider = new Repository_Provider(
            league_repository: $league_repo,
            event_repository: $event_repo,
            competition_repository: $competition_repo,
            league_team_repository: $league_team_repo,
            team_repository: $team_repo,
            player_repository: $player_repo,
            rubber_repository: $rubber_repo,
            results_checker_repository: $results_checker_repo,
            results_report_repository: $results_report_repo,
            result_repository: $result_repo,
            fixture_repository: $fixture_repo,
            club_repository: $club_repo
        );

        $this->assertSame( $league_repo, $provider->get_league_repository() );
        $this->assertSame( $event_repo, $provider->get_event_repository() );
        $this->assertSame( $competition_repo, $provider->get_competition_repository() );
        $this->assertSame( $league_team_repo, $provider->get_league_team_repository() );
        $this->assertSame( $team_repo, $provider->get_team_repository() );
        $this->assertSame( $player_repo, $provider->get_player_repository() );
        $this->assertSame( $rubber_repo, $provider->get_rubber_repository() );
        $this->assertSame( $results_checker_repo, $provider->get_results_checker_repository() );
        $this->assertSame( $results_report_repo, $provider->get_results_report_repository() );
        $this->assertSame( $result_repo, $provider->get_result_repository() );
        $this->assertSame( $fixture_repo, $provider->get_fixture_repository() );
        $this->assertSame( $club_repo, $provider->get_club_repository() );
    }

    #[DataProvider( 'provide_getters_and_expected_exception_messages' )]
    public function test_getter_throws_when_repository_is_unset( string $getter_name, string $expected_message ): void {
        $provider = new Repository_Provider();

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( $expected_message );

        $provider->$getter_name();
    }

    public static function provide_getters_and_expected_exception_messages(): array {
        return [
            [ 'get_event_repository', 'Event_Repository_Interface is not set.' ],
            [ 'get_competition_repository', 'Competition_Repository_Interface is not set.' ],
            [ 'get_league_repository', 'League_Repository_Interface is not set.' ],
            [ 'get_league_team_repository', 'League_Team_Repository_Interface is not set.' ],
            [ 'get_team_repository', 'Team_Repository_Interface is not set.' ],
            [ 'get_player_repository', 'Player_Repository_Interface is not set.' ],
            [ 'get_rubber_repository', 'Rubber_Repository_Interface is not set.' ],
            [ 'get_results_checker_repository', 'Results_Checker_Repository_Interface is not set.' ],
            [ 'get_results_report_repository', 'Results_Report_Repository_Interface is not set.' ],
            [ 'get_result_repository', 'Result_Repository_Interface is not set.' ],
            [ 'get_fixture_repository', 'Fixture_Repository_Interface is not set.' ],
            [ 'get_club_repository', 'Club_Repository_Interface is not set.' ],
        ];
    }
}
