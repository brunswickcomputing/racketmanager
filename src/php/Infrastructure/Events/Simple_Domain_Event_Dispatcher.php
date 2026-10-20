<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Events;

use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Events\Domain_Event_Interface;

/**
 * Lightweight, in-memory domain event dispatcher.
 */
final class Simple_Domain_Event_Dispatcher implements Domain_Event_Dispatcher_Interface {
    /**
     * @var array<string, list<callable>>
     */
    private array $listeners = [];

    public function dispatch( Domain_Event_Interface $event ): void {
        $event_class = $event::class;
        $event_name  = $event->get_event_name();

        if ( isset( $this->listeners[ $event_class ] ) ) {
            foreach ( $this->listeners[ $event_class ] as $listener ) {
                $listener( $event );
            }
        }

        if ( $event_name !== $event_class && isset( $this->listeners[ $event_name ] ) ) {
            foreach ( $this->listeners[ $event_name ] as $listener ) {
                $listener( $event );
            }
        }
    }

    public function dispatch_all( array $events ): void {
        foreach ( $events as $event ) {
            if ( $event instanceof Domain_Event_Interface ) {
                $this->dispatch( $event );
            }
        }
    }

    public function subscribe( string $event_class, callable $listener ): void {
        $this->listeners[ $event_class ][] = $listener;
    }

    public function clear_listeners(): void {
        $this->listeners = [];
    }
}
