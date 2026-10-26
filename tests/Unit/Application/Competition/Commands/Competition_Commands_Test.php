<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Competition\Commands;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Commands\Submit_Cup_Entry_Command;
use Racketmanager\Application\Competition\Commands\Submit_Cup_Entry_Handler;
use Racketmanager\Application\Competition\Commands\Submit_League_Entry_Command;
use Racketmanager\Application\Competition\Commands\Submit_League_Entry_Handler;
use Racketmanager\Infrastructure\Wordpress\Request\Competition\Cup_Entry_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Competition\League_Entry_Request_DTO;
use Racketmanager\Services\Competition_Entry_Service;
use WP_Error;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

final class Competition_Commands_Test extends TestCase {

    public function test_submit_cup_entry_command_from_post_and_to_request_dto(): void {
        $post = [
            'competitionId'  => '12',
            'season'         => '2026',
            'clubId'         => '45',
            'commentDetails' => 'Cup entry notes',
            'acceptance'     => '1',
            'event'          => [ '101' ],
            'team'           => [ '101' => '501' ],
            'captain'        => [ '101' => 'John Doe' ],
            'captainId'      => [ '101' => '701' ],
            'contactno'      => [ '101' => '0123456789' ],
            'contactemail'   => [ '101' => 'john@example.com' ],
            'matchday'       => [ '101' => '2' ],
            'matchtime'      => [ '101' => '18:30' ],
        ];

        $command = Submit_Cup_Entry_Command::from_post( $post );

        $this->assertSame( 12, $command->competition_id );
        $this->assertSame( '2026', $command->season );
        $this->assertSame( 45, $command->club_id );
        $this->assertSame( 'Cup entry notes', $command->comments );
        $this->assertTrue( $command->acceptance );
        $this->assertArrayHasKey( 101, $command->events_entered );
        $this->assertSame( 501, $command->events_entered[101]->team_id );
        $this->assertSame( 'John Doe', $command->events_entered[101]->captain );
        $this->assertSame( 701, $command->events_entered[101]->captain_id );
        $this->assertSame( '0123456789', $command->events_entered[101]->phone );
        $this->assertSame( 'john@example.com', $command->events_entered[101]->email );
        $this->assertSame( 2, $command->events_entered[101]->match_day );
        $this->assertSame( '18:30', $command->events_entered[101]->match_time );

        $dto = $command->to_request_dto();
        $this->assertInstanceOf( Cup_Entry_Request_DTO::class, $dto );
        $this->assertSame( 12, $dto->competition_id );
        $this->assertSame( '2026', $dto->season );
        $this->assertSame( 45, $dto->club_id );
        $this->assertSame( 'Cup entry notes', $dto->comments );
        $this->assertTrue( $dto->acceptance );
        $this->assertSame( $command->events_entered, $dto->events_entered );
    }

    public function test_submit_cup_entry_command_pure_instantiation(): void {
        $entry_row = (object) [ 'team_id' => 99 ];
        $command   = new Submit_Cup_Entry_Command(
            competition_id: 5,
            season: '2025',
            club_id: 10,
            acceptance: false,
            comments: 'Direct command',
            events_entered: [ 1 => $entry_row ]
        );

        $this->assertSame( 5, $command->competition_id );
        $this->assertSame( '2025', $command->season );
        $this->assertSame( 10, $command->club_id );
        $this->assertFalse( $command->acceptance );
        $this->assertSame( 'Direct command', $command->comments );
        $this->assertSame( [ 1 => $entry_row ], $command->events_entered );

        $dto = $command->to_request_dto();
        $this->assertSame( 5, $dto->competition_id );
        $this->assertSame( '2025', $dto->season );
        $this->assertSame( 10, $dto->club_id );
        $this->assertFalse( $dto->acceptance );
        $this->assertSame( 'Direct command', $dto->comments );
        $this->assertSame( [ 1 => $entry_row ], $dto->events_entered );
    }

    public function test_submit_cup_entry_handler_delegates_to_service(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $service->expects( $this->once() )
            ->method( 'request_cup_entry' )
            ->with( $this->isInstanceOf( Cup_Entry_Request_DTO::class ) )
            ->willReturn( true );

        $handler = new Submit_Cup_Entry_Handler( $service );
        $command = new Submit_Cup_Entry_Command(
            competition_id: 1,
            season: '2026',
            club_id: 2,
            acceptance: true,
            comments: 'Cup test'
        );

        $result = $handler->handle( $command );
        $this->assertTrue( $result );
    }

    public function test_submit_cup_entry_handler_returns_wp_error(): void {
        $error   = new WP_Error( 'invalid_entry', 'Cup entry closed' );
        $service = $this->createMock( Competition_Entry_Service::class );
        $service->expects( $this->once() )
            ->method( 'request_cup_entry' )
            ->willReturn( $error );

        $handler = new Submit_Cup_Entry_Handler( $service );
        $command = new Submit_Cup_Entry_Command(
            competition_id: 1,
            season: '2026',
            club_id: 2,
            acceptance: true,
            comments: null
        );

        $result = $handler->handle( $command );
        $this->assertSame( $error, $result );
    }

    public function test_submit_league_entry_command_from_post_and_to_request_dto(): void {
        $post = [
            'competitionId'      => '20',
            'season'             => '2026',
            'clubId'             => '30',
            'commentDetails'     => 'League comments',
            'acceptance'         => '1',
            'numCourtsAvailable' => '4',
            'competition_events' => '10,20,30',
            'event'              => [ '10', '20' ],
            'event_teams'        => [
                '10' => '101,102',
                '20' => '201',
            ],
            'teamEvent'          => [
                '10' => [ '101' ],
                '20' => [ '201' ],
            ],
            'teamEventTitle'     => [
                '10' => [ '101' => 'Team 1' ],
                '20' => [ '201' => 'Team 2' ],
            ],
            'teamEventLeague'    => [
                '10' => [ '101' => '1' ],
                '20' => [ '201' => '2' ],
            ],
            'captain'            => [
                '10' => [ '101' => 'Cap 1' ],
                '20' => [ '201' => 'Cap 2' ],
            ],
            'captainId'          => [
                '10' => [ '101' => '11' ],
                '20' => [ '201' => '22' ],
            ],
            'contactno'          => [
                '10' => [ '101' => '1111' ],
                '20' => [ '201' => '2222' ],
            ],
            'contactemail'       => [
                '10' => [ '101' => 'c1@example.com' ],
                '20' => [ '201' => 'c2@example.com' ],
            ],
            'matchday'           => [
                '10' => [ '101' => '6' ],
                '20' => [ '201' => '0' ],
            ],
            'matchtime'          => [
                '10' => [ '101' => '10:00' ],
                '20' => [ '201' => '14:00' ],
            ],
        ];

        $command = Submit_League_Entry_Command::from_post( $post );

        $this->assertSame( 20, $command->competition_id );
        $this->assertSame( '2026', $command->season );
        $this->assertSame( 30, $command->club_id );
        $this->assertSame( 'League comments', $command->comments );
        $this->assertTrue( $command->acceptance );
        $this->assertSame( 4, $command->num_courts_available );
        $this->assertArrayHasKey( 10, $command->events_entered );
        $this->assertArrayHasKey( 20, $command->events_entered );
        $this->assertSame( [ 2 => 30 ], $command->missed_event_ids ); // event 30 not selected
        $this->assertSame( [ 10 => [ 102 ] ], $command->missed_team_ids ); // team 102 missed in event 10

        $dto = $command->to_request_dto();
        $this->assertInstanceOf( League_Entry_Request_DTO::class, $dto );
        $this->assertSame( 20, $dto->competition_id );
        $this->assertSame( '2026', $dto->season );
        $this->assertSame( 30, $dto->club_id );
        $this->assertSame( 'League comments', $dto->comments );
        $this->assertTrue( $dto->acceptance );
        $this->assertSame( 4, $dto->num_courts_available );
        $this->assertSame( $command->events_entered, $dto->events_entered );
        $this->assertSame( $command->missed_event_ids, $dto->missed_event_ids );
        $this->assertSame( $command->missed_team_ids, $dto->missed_team_ids );
    }

    public function test_submit_league_entry_handler_delegates_to_service(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $service->expects( $this->once() )
            ->method( 'request_league_entry' )
            ->with( $this->isInstanceOf( League_Entry_Request_DTO::class ) )
            ->willReturn( true );

        $handler = new Submit_League_Entry_Handler( $service );
        $command = new Submit_League_Entry_Command(
            competition_id: 10,
            season: '2026',
            club_id: 20,
            comments: 'League test',
            acceptance: true,
            num_courts_available: 3
        );

        $result = $handler->handle( $command );
        $this->assertTrue( $result );
    }
}
