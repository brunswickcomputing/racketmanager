<?php
declare( strict_types=1 );

namespace Racketmanager\Public {
    if ( ! function_exists( 'Racketmanager\Public\shortcode_atts' ) ) {
        function shortcode_atts( array $pairs, array $atts, string $shortcode = '' ): array {
            return array_merge( $pairs, $atts );
        }
    }
}

namespace Racketmanager\Services\Fixture {
    if ( ! function_exists( 'Racketmanager\Services\Fixture\home_url' ) ) {
        function home_url( string $path = '' ): string {
            return 'https://example.com' . $path;
        }
    }
}

namespace Racketmanager\Tests\Integration\Public {

    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use PHPUnit\Framework\TestCase;
    use Racketmanager\Domain\Competition\Competition;
    use Racketmanager\Domain\Competition\Event;
    use Racketmanager\Domain\Competition\League;
    use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
    use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
    use Racketmanager\Domain\Fixture\Fixture;
    use Racketmanager\Domain\Team;
    use Racketmanager\Public\Shortcodes_Match;
    use Racketmanager\RacketManager;
    use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
    use Racketmanager\Repositories\Repository_Provider;
    use Racketmanager\Services\Club_Service;
    use Racketmanager\Services\Competition_Entry_Service;
    use Racketmanager\Services\Competition_Service;
    use Racketmanager\Services\Container\Simple_Container;
    use Racketmanager\Services\Finance_Service;
    use Racketmanager\Services\Fixture\Fixture_Detail_Service;
    use Racketmanager\Services\Fixture\Fixture_Permission_Service;
    use Racketmanager\Services\Fixture_Service;
    use Racketmanager\Services\Player_Service;
    use Racketmanager\Services\Registration_Service;
    use Racketmanager\Services\Team_Service;
    use Racketmanager\Services\Tournament_Service;

    #[AllowMockObjectsWithoutExpectations]
    class Shortcodes_Match_Integration_Test extends TestCase {

        private $plugin;
        private $container;
        private $shortcode;

        public function test_shortcode_flow_with_real_detail_service(): void {
            $this->shortcode->expects( $this->once() )->method( 'load_template' )->with( $this->equalTo( 'match-header' ), $this->callback( function ( $args ) {
                    return $args['dto'] instanceof Fixture_Details_DTO && $args['match'] instanceof Fixture && $args['dto']->league->title === 'Premier League';
                } ), $this->equalTo( 'match' ) )->willReturn( 'header_html' );

            $result = $this->shortcode->show_match_header( [ 'id' => 123 ] );
            $this->assertEquals( 'header_html', $result );
        }

        protected function setUp(): void {
            $this->plugin            = $this->createMock( RacketManager::class );
            $this->container         = new Simple_Container();
            $this->plugin->container = $this->container;

            $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
            $league_repo  = $this->createMock( League_Repository_Interface::class );
            $team_repo    = $this->createMock( Team_Repository_Interface::class );

            $repo_provider = new Repository_Provider( league_repository: $league_repo, team_repository: $team_repo, rubber_repository: $this->createMock( Rubber_Repository_Interface::class ), fixture_repository: $fixture_repo, club_repository: $this->createMock( Club_Repository_Interface::class ) );

            $competition_service = $this->createStub( Competition_Service::class );
            $team_service        = $this->createStub( Team_Service::class );

            $home_team = $this->createMock( Team::class );
            $home_team->method( 'get_name' )->willReturn( 'Home Team' );
            $home_team_dto = new Team_Details_DTO( $home_team, null, null );

            $away_team = $this->createMock( Team::class );
            $away_team->method( 'get_name' )->willReturn( 'Away Team' );
            $away_team_dto = new Team_Details_DTO( $away_team, null, null );

            $team_service->method( 'get_team_details' )->willReturnMap( [
                [ 1, $home_team_dto ],
                [ 2, $away_team_dto ],
            ] );

            $registration_service = $this->createStub( Registration_Service::class );
            $permission_service   = new Fixture_Permission_Service( $repo_provider, $registration_service );

            $detail_service = new Fixture_Detail_Service( $repo_provider, $competition_service, $team_service, $permission_service );

            $this->container->set( 'fixture_detail_service', $detail_service );
            $this->container->set( 'competition_service', $competition_service );
            $this->container->set( 'club_service', $this->createMock( Club_Service::class ) );
            $this->container->set( 'finance_service', $this->createMock( Finance_Service::class ) );
            $this->container->set( 'player_service', $this->createMock( Player_Service::class ) );
            $this->container->set( 'registration_service', $registration_service );
            $this->container->set( 'team_service', $team_service );
            $this->container->set( 'tournament_service', $this->createMock( Tournament_Service::class ) );
            $this->container->set( 'competition_entry_service', $this->createMock( Competition_Entry_Service::class ) );
            $this->container->set( 'fixture_service', $this->createMock( Fixture_Service::class ) );

            $fixture = new Fixture( (object) [
                'id'        => 123,
                'league_id' => 10,
                'home_team' => '1',
                'away_team' => '2',
                'date'      => '2026-04-11 03:30',
                'season'    => '2026'
            ] );
            $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );

            $league = $this->createMock( League::class );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $league->title = 'Premier League';
            $league_repo->method( 'find_by_id' )->willReturn( $league );

            $event = $this->createMock( Event::class );
            $event->method( 'get_id' )->willReturn( 20 );
            $event->method( 'get_competition_id' )->willReturn( 30 );
            $event->name = 'Men\'s Singles';
            $competition_service->method( 'get_event_by_id' )->willReturn( $event );

            $competition = $this->createMock( Competition::class );
            $competition->method( 'get_id' )->willReturn( 30 );
            $competition->type          = 'league';
            $competition->is_tournament = false;
            $competition_service->method( 'get_by_id' )->willReturn( $competition );

            $this->shortcode = $this->getMockBuilder( Shortcodes_Match::class )->setConstructorArgs( [ $this->plugin ] )->onlyMethods( [ 'load_template' ] )->getMock();
        }
    }
}
