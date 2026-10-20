<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Events;

/**
 * Contract for dispatching domain events to registered subscribers.
 */
interface Domain_Event_Dispatcher_Interface {
    /**
     * Dispatch a domain event to all subscribers listening for it.
     *
     * @param Domain_Event_Interface $event The domain event to dispatch.
     * @return void
     */
    public function dispatch( Domain_Event_Interface $event ): void;

    /**
     * Dispatch a list of domain events sequentially.
     *
     * @param array<Domain_Event_Interface> $events List of domain events.
     * @return void
     */
    public function dispatch_all( array $events ): void;

    /**
     * Register an event listener callback for a specific event class or name.
     *
     * @param string $event_class FQCN or name of the domain event.
     * @param callable $listener Callback receiving the event object.
     * @return void
     */
    public function subscribe( string $event_class, callable $listener ): void;

    /**
     * Clear all registered event subscribers.
     *
     * @return void
     */
    public function clear_listeners(): void;
}
