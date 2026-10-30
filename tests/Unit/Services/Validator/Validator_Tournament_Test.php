<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class Validator_Tournament_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['racketmanager'] = new stdClass();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_partner_rejects_partner_already_in_another_event_team_using_query_service(): void {
        $event = Event::from_object( (object) [ 'id' => 8, 'name' => 'Doubles', 'type' => 'doubles', 'competition_id' => 1, 'settings' => [ 'age_limit' => 'open' ] ] );
        $query_service = $this->createMock( Event_Query_Service::class );
        $query_service->expects( $this->once() )
            ->method( 'get_teams' )
            ->with( $event, [ 'player' => 20, 'season' => '2026' ] )
            ->willReturn( [ (object) [ 'player_id' => [ 20, 30 ] ] ] );

        $validator = new Validator_Tournament();
        $result = $validator->partner( 20, '8', 'Doubles', $event, '2026', 10, '2026-08-30', $query_service );

        $this->assertSame( $validator, $result );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'partner-8', $validator->err_flds );
        $this->assertContains( 'Partner is in another team in this event', $validator->err_msgs );
    }
}
