<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Listeners;

use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Withdrawn_Event;

/**
 * Listener for Tournament_Entry_Withdrawn_Event.
 *
 * Provides a decoupled hook for audit logging, entry quota recalculations,
 * and asynchronous post-withdrawal workflows.
 */
final class Tournament_Entry_Withdrawn_Listener {
    /**
     * Handle the event.
     *
     * @param Tournament_Entry_Withdrawn_Event $event Withdrawn entry event.
     * @return void
     */
    public function __invoke( Tournament_Entry_Withdrawn_Event $event ): void {
        do_action(
            'racketmanager_tournament_entry_withdrawn',
            $event->entry_id,
            $event->tournament_id,
            $event->player_id,
            $event->reason
        );
    }
}
