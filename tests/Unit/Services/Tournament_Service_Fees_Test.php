<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Request_DTO;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Entry_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Tournament_Service_Fees_Test extends TestCase {

    public function test_set_tournament_fees_creates_draft_charge_via_factory_when_none_exists(): void {
        $charge_repository = $this->createMock( Charge_Repository::class );
        $charge_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( '10_2026' )
            ->willReturn( null );

        $service = new Tournament_Service(
            $this->createMock( RacketManager::class ),
            $this->createMock( Tournament_Repository_Interface::class ),
            $charge_repository,
            $this->createMock( Event_Repository_Interface::class ),
            $this->createMock( Fixture_Service::class ),
            $this->createMock( League_Team_Repository_Interface::class ),
            $this->createMock( Tournament_Entry_Repository_Interface::class ),
            $this->createMock( Competition_Service::class ),
            $this->createMock( Player_Service::class ),
            $this->createMock( Player_Stats_Service::class ),
            $this->createMock( Club_Service::class ),
            $this->createMock( Finance_Service::class ),
            $this->createMock( League_Service::class )
        );

        $request = new Tournament_Request_DTO(
            array(
                'competition_id' => '10',
                'season'         => '2026',
                'dateStart'      => '2026-08-30',
                'feeCompetition' => '5',
                'feeEvent'       => '10',
            )
        );

        $charge = ( new \ReflectionMethod( $service, 'set_tournament_fees' ) )->invoke( $service, $request );

        self::assertInstanceOf( Charge::class, $charge );
        self::assertNull( $charge->get_id() );
        self::assertSame( 10, $charge->get_competition_id() );
        self::assertSame( '2026', $charge->get_season() );
        self::assertSame( 'draft', $charge->get_status() );
        self::assertSame( 5.0, $charge->get_fee_competition() );
        self::assertSame( 10.0, $charge->get_fee_event() );
        self::assertSame( '2026-08-30', $charge->get_date() );
    }
}
