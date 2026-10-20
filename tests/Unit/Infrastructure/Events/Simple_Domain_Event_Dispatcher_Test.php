<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Events;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Events\Abstract_Domain_Event;
use Racketmanager\Domain\Events\Domain_Event_Interface;
use Racketmanager\Infrastructure\Events\Simple_Domain_Event_Dispatcher;

final readonly class Test_User_Registered_Event extends Abstract_Domain_Event {
    public function __construct(
        public int $user_id,
        ?DateTimeImmutable $occurred_on = null
    ) {
        parent::__construct( $occurred_on );
    }
}

final class Test_Named_Event implements Domain_Event_Interface {
    private DateTimeImmutable $occurred_on;

    public function __construct(
        private string $name,
        ?DateTimeImmutable $occurred_on = null
    ) {
        $this->occurred_on = $occurred_on ?? new DateTimeImmutable();
    }

    public function get_occurred_on(): DateTimeImmutable {
        return $this->occurred_on;
    }

    public function get_event_name(): string {
        return $this->name;
    }
}

final class Simple_Domain_Event_Dispatcher_Test extends TestCase {
    public function test_dispatches_event_to_subscribed_listener_by_class(): void {
        $dispatcher = new Simple_Domain_Event_Dispatcher();
        $received   = [];

        $dispatcher->subscribe( Test_User_Registered_Event::class, function ( Test_User_Registered_Event $event ) use ( &$received ) {
            $received[] = $event->user_id;
        } );

        $event = new Test_User_Registered_Event( 42 );
        $dispatcher->dispatch( $event );

        $this->assertSame( [ 42 ], $received );
        $this->assertInstanceOf( DateTimeImmutable::class, $event->get_occurred_on() );
        $this->assertSame( Test_User_Registered_Event::class, $event->get_event_name() );
    }

    public function test_dispatches_event_to_subscribed_listener_by_name(): void {
        $dispatcher = new Simple_Domain_Event_Dispatcher();
        $received   = [];

        $dispatcher->subscribe( 'custom.event.name', function ( Test_Named_Event $event ) use ( &$received ) {
            $received[] = $event->get_event_name();
        } );

        $event = new Test_Named_Event( 'custom.event.name' );
        $dispatcher->dispatch( $event );

        $this->assertSame( [ 'custom.event.name' ], $received );
    }

    public function test_dispatch_all_dispatches_all_events(): void {
        $dispatcher = new Simple_Domain_Event_Dispatcher();
        $received   = [];

        $dispatcher->subscribe( Test_User_Registered_Event::class, function ( Test_User_Registered_Event $event ) use ( &$received ) {
            $received[] = $event->user_id;
        } );

        $events = [
            new Test_User_Registered_Event( 1 ),
            new Test_User_Registered_Event( 2 ),
            new Test_User_Registered_Event( 3 ),
        ];

        $dispatcher->dispatch_all( $events );

        $this->assertSame( [ 1, 2, 3 ], $received );
    }

    public function test_clear_listeners_removes_all_subscribers(): void {
        $dispatcher = new Simple_Domain_Event_Dispatcher();
        $received   = [];

        $dispatcher->subscribe( Test_User_Registered_Event::class, function ( Test_User_Registered_Event $event ) use ( &$received ) {
            $received[] = $event->user_id;
        } );

        $dispatcher->clear_listeners();
        $dispatcher->dispatch( new Test_User_Registered_Event( 99 ) );

        $this->assertEmpty( $received );
    }
}
