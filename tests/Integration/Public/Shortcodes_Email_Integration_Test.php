<?php
declare( strict_types=1 );

namespace Racketmanager\Public {
    function shortcode_atts($pairs, $atts) {
        return array_merge($pairs, $atts);
    }
}

namespace Racketmanager\Tests\Integration\Public {

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team;
use Racketmanager\Public\Shortcodes_Email;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Shortcodes_Email_Integration_Test extends TestCase {

    private $plugin;
    private $container;
    private $shortcode;

    protected function setUp(): void {
        parent::setUp();

        $this->plugin            = $this->createMock( RacketManager::class );
        $this->plugin->site_name = 'Test Tennis League';
        $this->plugin->site_url  = 'https://example.com';
        $this->container         = new Simple_Container();
        $this->plugin->container = $this->container;
        $GLOBALS['racketmanager'] = $this->plugin;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $league_repo  = $this->createMock( League_Repository_Interface::class );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $team_service = $this->createMock( Team_Service::class );

        $this->container->set( 'fixture_repository', $fixture_repo );
        $this->container->set( 'competition_repository', $this->createMock( Competition_Repository_Interface::class ) );
        $this->container->set( 'competition_service', $comp_service );
        $this->container->set( 'team_service', $team_service );
        $this->container->set( 'tournament_service', $this->createMock( Tournament_Service::class ) );
        $this->container->set( 'club_service', $this->createMock( \Racketmanager\Services\Club_Service::class ) );
        $this->container->set( 'finance_service', $this->createMock( \Racketmanager\Services\Finance_Service::class ) );
        $this->container->set( 'player_service', $this->createMock( \Racketmanager\Services\Player_Service::class ) );
        $this->container->set( 'registration_service', $this->createMock( \Racketmanager\Services\Registration_Service::class ) );
        $this->container->set( 'competition_entry_service', $this->createMock( \Racketmanager\Services\Competition_Entry_Service::class ) );
        $this->container->set( 'fixture_service', $this->createMock( \Racketmanager\Services\Fixture_Service::class ) );
        $this->container->set( 'fixture_detail_service', $this->createMock( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class ) );

        $fixture = new Fixture( (object) [
            'id'        => 555,
            'league_id' => 10,
            'home_team' => '1',
            'away_team' => '2',
            'date'      => '2026-06-01 19:00:00',
            'season'    => '2026',
            'host'      => 'home',
        ] );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $league->method( 'get_name' )->willReturn( 'Division 1' );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $event = $this->createMock( Event::class );
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_competition_id' )->willReturn( 30 );
        $event->method( 'get_name' )->willReturn( 'Summer League' );
        $event->method( 'get_type' )->willReturn( 'league' );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $competition = $this->createMock( Competition::class );
        $competition->method( 'get_id' )->willReturn( 30 );
        $competition->method( 'get_name' )->willReturn( 'Summer Competition' );
        $competition->type = Competition_Type::LEAGUE;
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

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

        $this->shortcode = $this->getMockBuilder( Shortcodes_Email::class )
            ->setConstructorArgs( [ $this->plugin ] )
            ->onlyMethods( [ 'load_template' ] )
            ->getMock();
    }

    public function test_show_match_notification_renders_template_with_adapted_data(): void {
        $this->shortcode->expects( $this->once() )
            ->method( 'load_template' )
            ->with(
                $this->equalTo( 'match-notification' ),
                $this->callback( function ( array $args ) {
                    return isset( $args['match'] )
                        && $args['match']->league->title === 'Division 1'
                        && $args['match']->teams['home']->title === 'Home Team'
                        && $args['match']->teams['away']->title === 'Away Team';
                } ),
                $this->equalTo( 'email' )
            )
            ->willReturn( 'email_rendered_html' );

        $output = $this->shortcode->show_match_notification( [
            'match'            => 555,
            'competition_type' => 'league',
        ] );

        $this->assertSame( 'email_rendered_html', $output );
    }

    public function test_show_result_notification_renders_template_with_adapted_data(): void {
        $this->shortcode->expects( $this->once() )
            ->method( 'load_template' )
            ->with(
                $this->equalTo( 'result-notification' ),
                $this->callback( function ( array $args ) {
                    return isset( $args['match'] )
                        && $args['match']->league->title === 'Division 1'
                        && $args['match']->teams['home']->title === 'Home Team';
                } ),
                $this->equalTo( 'email' )
            )
            ->willReturn( 'result_email_rendered_html' );

        $output = $this->shortcode->show_result_notification( [
            'match'            => 555,
            'league'           => '10',
            'competition_type' => 'league',
        ] );

        $this->assertSame( 'result_email_rendered_html', $output );
    }
}
}
