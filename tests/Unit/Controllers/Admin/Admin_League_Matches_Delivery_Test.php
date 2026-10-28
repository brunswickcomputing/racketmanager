<?php
/**
 * Admin league matches delivery test
 *
 * Verifies that the championship finals presenter hydrates league rounds into preformatted fixture view models.
 *
 * @package RacketManager
 * @subpackage Unit/Controllers/Admin
 */

declare( strict_types=1 );

namespace Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\View_Models\Admin_Championship_Final_Fixture_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Admin_League_Matches_Delivery_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Admin_Championship_Finals_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );
        $this->presenter = new Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_present_league_finals_builds_cup_finals_with_presented_fixtures(): void {
        $competition       = $this->createStub( Competition::class );
        $competition->id   = 10;
        $competition->type = Competition_Type::CUP;
        $competition->is_cup = true;

        $fixture_league     = $this->createStub( League::class );
        $fixture_league->id = 4;

        $raw            = new Fixture();
        $raw->id        = 77;
        $raw->league_id = 4;
        $raw->final     = 'final';
        $raw->season    = '2026';
        $raw->date      = '2026-05-09 11:00:00';
        $raw->location  = 'Club Court';

        $home = $this->createStub( Team::class );
        $home->method( 'get_name' )->willReturn( 'Cup Home' );
        $away = $this->createStub( Team::class );
        $away->method( 'get_name' )->willReturn( 'Cup Away' );

        $details = new Fixture_Details_DTO(
            $raw,
            $fixture_league,
            $this->createStub( Event::class ),
            $competition,
            new Team_Details_DTO( $home ),
            new Team_Details_DTO( $away ),
        );

        $detail_service = $this->createMock( Fixture_Detail_Service::class );
        $detail_service->expects( $this->once() )
            ->method( 'get_fixture_with_details' )
            ->with( $raw )
            ->willReturn( $details );
        $league               = new \stdClass();
        $league->id           = 4;
        $league->championship = new class() {
            public function get_finals(): array {
                return array(
                    array( 'key' => 'semi', 'name' => 'Semi Final', 'num_matches' => 2 ),
                    array( 'key' => 'final', 'name' => 'Final', 'num_matches' => 1 ),
                );
            }
        };

        $queries       = array();
        $query_service = $this->createStub( League_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturnCallback(
            static function ( $queried_league, array $args ) use ( &$queries, $raw ): array {
                $queries[] = $args;
                return 'final' === $args['final'] ? array( $raw ) : array();
            }
        );

        $finals = $this->presenter->present_league_finals( $league, '2026', $query_service, $detail_service );

        $this->assertCount( 2, $queries );
        $this->assertSame( 'final', $queries[0]['final'], 'rounds are queried in reversed (earliest-last) order' );
        $this->assertSame( '2026', $queries[0]['season'] );
        $this->assertCount( 1, $finals, 'rounds without fixtures are omitted' );
        $this->assertSame( 'final', $finals[0]->key );
        $this->assertSame( 1, $finals[0]->num_matches );
        $this->assertCount( 1, $finals[0]->fixtures );

        $fixture = $finals[0]->fixtures[0];
        $this->assertInstanceOf( Admin_Championship_Final_Fixture_View_Model::class, $fixture );
        $this->assertSame( '9 May 2026', $fixture->formatted_date );
        $this->assertSame( 'Club Court', $fixture->formatted_location );
        $this->assertSame( 'Cup Home - Cup Away', $fixture->teams_label );
        $this->assertSame(
            'admin.php?page=racketmanager-cups&view=match&competition_id=10&league=4&edit=77&season=2026&final=final',
            $fixture->match_link
        );
    }

    public function test_present_league_finals_skips_fixtures_that_cannot_be_hydrated(): void {
        $raw     = new Fixture();
        $raw->id = 78;

        $detail_service = $this->createStub( Fixture_Detail_Service::class );
        $detail_service->method( 'get_fixture_with_details' )->willThrowException( new \Racketmanager\Exceptions\Fixture_Not_Found_Exception( 'missing' ) );

        $query_service = $this->createStub( League_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( array( $raw ) );

        $league               = new \stdClass();
        $league->championship = new class() {
            public function get_finals(): array {
                return array( array( 'key' => 'final', 'name' => 'Final', 'num_matches' => 1 ) );
            }
        };

        $finals = $this->presenter->present_league_finals( $league, '2026', $query_service, $detail_service );

        $this->assertCount( 1, $finals, 'the round is kept but without fixtures' );
        $this->assertSame( array(), $finals[0]->fixtures );
    }

    public function test_present_league_finals_returns_empty_list_without_championship(): void {
        $league = new \stdClass();

        $this->assertSame(
            array(),
            $this->presenter->present_league_finals( $league, '2026', $this->createStub( League_Query_Service::class ), $this->createStub( Fixture_Detail_Service::class ) )
        );
    }
}
