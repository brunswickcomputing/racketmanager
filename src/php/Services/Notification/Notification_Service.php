<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Notification;

use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;
use Racketmanager\Util\Util;
use function Racketmanager\get_club;
use function Racketmanager\get_match;
use function Racketmanager\captain_result_notification;
use function Racketmanager\result_notification;

/**
 * Service for handling fixture-related notifications.
 */
class Notification_Service {
    /**
     * Send result notification for a fixture.
     *
     * @param Fixture $fixture
     * @param string $match_status
     * @param string $match_message
     * @param string|false $match_updated_by
     */
    public function send_result_notification( Fixture $fixture, string $match_status, string $match_message, string|false $match_updated_by = false ): void {
        global $racketmanager;

        // For now, we delegate to the legacy Racketmanager_Match to avoid duplicating the complex logic
        // in get_confirmation_email, captain_result_notification, etc.
        // This is a bridge until those parts are also migrated.
        $match = get_match( $fixture->get_id() );
        if ( $match ) {
            $match->result_notification( $match_status, $match_message, $match_updated_by );
        }
    }

    /**
     * Notify opponents when a team is withdrawn.
     *
     * @param Fixture $fixture
     * @param int $team_id The withdrawn team ID
     */
    public function notify_team_withdrawal( Fixture $fixture, int $team_id ): void {
        $match = get_match( $fixture->get_id() );
        if ( $match ) {
            $match->notify_team_withdrawal( $team_id );
        }
    }
}
