<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Listeners;

use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Submitted_Event;

/**
 * Listener for Tournament_Entry_Submitted_Event.
 *
 * Provides a decoupled hook for audit logging, entry quota recalculations,
 * and asynchronous post-submission workflows.
 */
final class Tournament_Entry_Audit_Listener {
    /**
     * Handle the event.
     *
     * @param Tournament_Entry_Submitted_Event $event Submitted entry event.
     * @return void
     */
    public function __invoke( Tournament_Entry_Submitted_Event $event ): void {
        do_action(
            'racketmanager_tournament_entry_submitted',
            $event->entry_id,
            $event->tournament_id,
            $event->player_id,
            $event->club_id,
            $event->fee_cents
        );
    }
}
