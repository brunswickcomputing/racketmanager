<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition_Service;

final class Event_Allowlist_Guard {

    /**
     * Check if an event is permitted for viewing in its parent competition for the specified season.
     *
     * @param object|Event $event Event instance.
     * @param int|string|null $season Season identifier (or null to use the event's current season).
     * @param Competition_Service|null $competition_service Competition service.
     * @return bool True if allowed; false if restricted or competition invalid/missing.
     */
    public static function is_event_allowed(
        object $event,
        int|string|null $season = null,
        ?Competition_Service $competition_service = null
    ): bool {
        $season_name = null !== $season && '' !== $season ? (string) $season : null;
        if ( empty( $season_name ) && isset( $event->current_season['name'] ) ) {
            $season_name = (string) $event->current_season['name'];
        }

        $competition = null;

        if ( isset( $event->competition ) && $event->competition instanceof Competition ) {
            $competition = $event->competition;
        } elseif ( isset( $event->competition_id ) && (int) $event->competition_id > 0 && null !== $competition_service ) {
            try {
                $competition = $competition_service->get_by_id( (int) $event->competition_id );
            } catch ( Competition_Not_Found_Exception ) {
                $competition = null;
            }
        }

        if ( ! $competition instanceof Competition ) {
            return false;
        }

        return $competition->is_event_allowed( $event, $season_name, false );
    }
}
