<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\League_Query_Service;

class Competition_Notification_Service_Test extends TestCase {

    public function test_contact_teams_queries_a_real_event_through_the_injected_repository(): void {
        $competition = \Racketmanager\Tests\Helpers\Competition_Test_Factory::create( id: 10, name: 'Summer League' );
        $competition->is_player_entry = false;
        $event = Event::from_object( (object) [ 'id' => 93, 'name' => 'Mens Doubles', 'seasons' => '{"2026":{"name":"2026"}}' ] );
        $event->set_season( '2026' );
        $repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_competition_id' )->with( 10 )->willReturn( [ $event ] );
        $repository->expects( $this->once() )->method( 'query_teams' )
            ->with( 93, $this->callback( static fn( array $args ): bool => '2026' === $args['season'] ) )
            ->willReturn( [] );
        $mailer = $this->createMock( Mailer_Interface::class );
        $mailer->expects( $this->once() )->method( 'send' )
            ->with( [], 'Leigh and Westcliff Tennis - Summer League 2026 - Important Message', 'Hello Teams', $this->isArray() )
            ->willReturn( true );

        $service = new Competition_Notification_Service( $mailer, $repository );
        $this->assertTrue( $service->contact_teams( $competition, '2026', 'Hello Teams' ) );
    }

    protected function setUp(): void {
        parent::setUp();
        $racketmanager = new \stdClass();
        $racketmanager->site_name = 'Leigh and Westcliff Tennis';
        $racketmanager->get_confirmation_email = fn( $type ) => 'secretary@example.com';
        $racketmanager->get_clubs = fn( $args ) => [];
        $GLOBALS['racketmanager'] = $racketmanager;
    }

    public function test_contact_league_teams_sends_email_to_team_captains(): void {
        $sent_calls = [];
        $mock_mailer = $this->createMock( Mailer_Interface::class );
        $mock_mailer->expects( $this->once() )
            ->method( 'send' )
            ->willReturnCallback( function ( $to, $subject, $message, $headers ) use ( &$sent_calls ) {
                $sent_calls[] = [
                    'to'      => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                ];
                return true;
            } );

        $team_dtls = (object) [
            'captain'      => 'John Smith',
            'contactemail' => 'john@example.com',
            'club'         => (object) [
                'match_secretary' => (object) [
                    'display_name' => 'Jane Secretary',
                    'email'        => 'jane@example.com',
                ],
            ],
        ];
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_league_teams' )->willReturn( [ (object) [ 'id' => 1 ] ] );
        $league_query->method( 'get_team_dtls' )->willReturn( $team_dtls );

        $service = new Competition_Notification_Service( $mock_mailer, null, $league_query );

        $event = new class extends Event {
            public function __construct() {
                $this->competition = (object) [ 'type' => 'league' ];
            }
        };

        $league = new class extends League {
            public function __construct() {
                $this->id = 10;
                $this->title = 'Premier Division';
            }
        };

        $league->event = $event;

        $result = $service->contact_league_teams( $league, '2026', 'Hello Teams' );

        $this->assertTrue( $result );
        $this->assertCount( 1, $sent_calls );
        $this->assertSame( 'Leigh and Westcliff Tennis - Premier Division 2026 - Important Message', $sent_calls[0]['subject'] );
        $this->assertSame( 'Hello Teams', $sent_calls[0]['message'] );
        $this->assertContains( 'John Smith <john@example.com>', $sent_calls[0]['to'] );
    }

    public function test_notify_withdrawn_team_formats_and_sends_email(): void {
        $sent_calls = [];
        $mock_mailer = $this->createMock( Mailer_Interface::class );
        $mock_mailer->expects( $this->once() )
            ->method( 'send' )
            ->willReturnCallback( function ( $to, $subject, $message, $headers ) use ( &$sent_calls ) {
                $sent_calls[] = compact( 'to', 'subject', 'message', 'headers' );
                return true;
            } );

        $team_dtls = (object) [
            'title'        => 'Westcliff 1',
            'captain'      => 'Captain Bob',
            'contactemail' => 'bob@example.com',
            'club'         => (object) [
                'match_secretary' => (object) [
                    'display_name' => '',
                    'email'        => '',
                ],
            ],
        ];
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_league_teams' )->willReturn( [ (object) [ 'id' => 2 ] ] );
        $league_query->method( 'get_team_dtls' )->willReturn( $team_dtls );

        $service = new Competition_Notification_Service( $mock_mailer, null, $league_query );

        $event = new class extends Event {
            public function __construct() {
                $this->competition = (object) [ 'type' => 'league' ];
            }
        };

        $league = new class extends League {
            public function __construct() {
                $this->id = 10;
                $this->title = 'Division 1';
            }
        };

        $league->event = $event;

        $result = $service->notify_withdrawn_team( $league, 2, '2026' );

        $this->assertTrue( $result );
        $this->assertCount( 1, $sent_calls );
        $this->assertStringContainsString( 'Withdrawn team', $sent_calls[0]['subject'] );
    }

    public function test_send_constitution_sends_email_to_club_secretaries(): void {
        $sent_calls = [];
        $mock_mailer = $this->createMock( Mailer_Interface::class );
        $mock_mailer->expects( $this->once() )
            ->method( 'send' )
            ->willReturnCallback( function ( $to, $subject, $message, $headers ) use ( &$sent_calls ) {
                $sent_calls[] = compact( 'to', 'subject', 'message', 'headers' );
                return true;
            } );

        $GLOBALS['racketmanager']->get_clubs = function ( $args ) {
            return [
                (object) [
                    'match_secretary' => (object) [
                        'display_name' => 'Sec Alice',
                        'email'        => 'alice@example.com',
                    ],
                ],
            ];
        };

        $service = new Competition_Notification_Service( $mock_mailer );

        $event = new class extends Event {
            public function __construct() {
                $this->id = 5;
                $this->name = 'Mixed League';
                $this->competition = (object) [ 'type' => 'league' ];
            }
        };

        $service->send_constitution( $event, [ 'name' => '2026' ] );

        $this->assertCount( 1, $sent_calls );
        $this->assertSame( 'secretary@example.com', $sent_calls[0]['to'] );
        $this->assertSame( 'Leigh and Westcliff Tennis - Mixed League 2026 - Constitution', $sent_calls[0]['subject'] );
    }

    public function test_contact_teams_reads_events_from_injected_event_repository(): void {
        $event = new class extends Event {
            public function __construct() {
                $this->id = 5;
                $this->current_season = [ 'name' => '2026' ];
            }
        };

        $event_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )
            ->method( 'find_by_competition_id' )
            ->with( 31 )
            ->willReturn( [ $event ] );
        $event_repository->expects( $this->once() )
            ->method( 'query_teams' )
            ->with( 5, [ 'season' => '2026' ] )
            ->willReturn( [] );

        $mailer = $this->createMock( Mailer_Interface::class );
        $mailer->expects( $this->once() )->method( 'send' )->willReturn( true );

        $service     = new Competition_Notification_Service( $mailer, $event_repository );
        $competition = \Racketmanager\Tests\Helpers\Competition_Test_Factory::create( id: 31 );

        $this->assertTrue( $service->contact_teams( $competition, '2026', 'Hello' ) );
    }

    public function test_contact_teams_requires_event_repository(): void {
        $service     = new Competition_Notification_Service( $this->createStub( Mailer_Interface::class ) );
        $competition = \Racketmanager\Tests\Helpers\Competition_Test_Factory::create( id: 31 );

        $this->expectException( \LogicException::class );
        $service->contact_teams( $competition, '2026', 'Hello' );
    }
}
