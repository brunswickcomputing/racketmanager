<?php
declare( strict_types=1 );
/**
 * Notification Presenter Interface
 *
 * @package RacketManager
 * @subpackage Application/Notification/Ports
 */

namespace Racketmanager\Application\Notification\Ports;

use Racketmanager\Domain\Fixture\Fixture;

interface Notification_Presenter_Interface {
    /**
     * Present match notification payload.
     *
     * @param Fixture              $fixture Match fixture.
     * @param array<string, mixed> $args    Notification arguments.
     * @return array<string, mixed>
     */
    public function present_match_notification( Fixture $fixture, array $args ): array;

    /**
     * Present result notification payload.
     *
     * @param Fixture              $fixture      Match fixture.
     * @param array<string, mixed> $message_args Notification message arguments.
     * @return array<string, mixed>
     */
    public function present_result_notification( Fixture $fixture, array $message_args ): array;

    /**
     * Present result outstanding notification payload.
     *
     * @param Fixture              $fixture Match fixture.
     * @param array<string, mixed> $args    Notification arguments.
     * @return array<string, mixed>
     */
    public function present_result_outstanding_notification( Fixture $fixture, array $args ): array;

    /**
     * Present captain result approval notification payload.
     *
     * @param Fixture              $fixture Match fixture.
     * @param array<string, mixed> $args    Notification arguments.
     * @return array<string, mixed>
     */
    public function present_captain_result_approval_notification( Fixture $fixture, array $args ): array;

    /**
     * Present date change notification payload.
     *
     * @param Fixture              $fixture      Match fixture.
     * @param array<string, mixed> $message_args Notification message arguments.
     * @return array<string, mixed>
     */
    public function present_date_change_notification( Fixture $fixture, array $message_args ): array;

    /**
     * Present team withdrawn notification payload.
     *
     * @param Fixture              $fixture      Match fixture.
     * @param array<string, mixed> $message_args Notification message arguments.
     * @return array<string, mixed>
     */
    public function present_team_withdrawn_notification( Fixture $fixture, array $message_args ): array;

    /**
     * Present send fixtures match object.
     *
     * @param Fixture $fixture Match fixture.
     * @param int     $team_id Team ID.
     * @return object
     */
    public function present_send_fixtures_match( Fixture $fixture, int $team_id ): object;
}
