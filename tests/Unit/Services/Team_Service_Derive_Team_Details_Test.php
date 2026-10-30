<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Team_Service_Derive_Team_Details_Test extends TestCase {

    private function service(): Team_Service {
        return new Team_Service(
            $this->createMock( Team_Repository::class ),
            $this->createMock( Club_Repository::class ),
            $this->createMock( Event_Repository::class ),
            $this->createMock( Player_Service::class ),
            $this->createMock( Fixture_Repository_Interface::class )
        );
    }

    public function test_rank_reference_derives_team_named_by_rank(): void {
        $details = $this->service()->derive_team_details( '3_rank' );

        self::assertInstanceOf( Team_Details_DTO::class, $details );
        self::assertSame( 'Team rank 3', $details->team->get_name() );
        self::assertNull( $details->team->get_id() );
    }

    public function test_winner_reference_derives_winner_of_round_and_match_number(): void {
        $details = $this->service()->derive_team_details( '1_semi_2' );

        self::assertInstanceOf( Team_Details_DTO::class, $details );
        self::assertSame( 'Winner Semi Final 2', $details->team->get_name() );
    }

    public function test_loser_reference_derives_loser_of_round_and_match_number(): void {
        $details = $this->service()->derive_team_details( '2_final_1' );

        self::assertInstanceOf( Team_Details_DTO::class, $details );
        self::assertSame( 'Loser Final 1', $details->team->get_name() );
    }
}
