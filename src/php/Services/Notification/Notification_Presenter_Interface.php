<?php
declare( strict_types=1 );
/**
 * Notification Presenter Interface
 *
 * @package RacketManager
 * @subpackage Services/Notification
 */

namespace Racketmanager\Services\Notification;

use Racketmanager\Domain\Fixture\Fixture;

interface Notification_Presenter_Interface {
    /**
     * @param Fixture $fixture
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function present_match_notification( Fixture $fixture, array $args ): array;

    /**
     * @param Fixture $fixture
     * @param array<string, mixed> $message_args
     * @return array<string, mixed>
     */
    public function present_result_notification( Fixture $fixture, array $message_args ): array;

    /**
     * @param Fixture $fixture
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function present_result_outstanding_notification( Fixture $fixture, array $args ): array;

    /**
     * @param Fixture $fixture
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function present_captain_result_approval_notification( Fixture $fixture, array $args ): array;

    /**
     * @param Fixture $fixture
     * @param array<string, mixed> $message_args
     * @return array<string, mixed>
     */
    public function present_date_change_notification( Fixture $fixture, array $message_args ): array;

    /**
     * @param Fixture $fixture
     * @param array<string, mixed> $message_args
     * @return array<string, mixed>
     */
    public function present_team_withdrawn_notification( Fixture $fixture, array $message_args ): array;

    /**
     * @param Fixture $fixture
     * @param int $team_id
     * @return object
     */
    public function present_send_fixtures_match( Fixture $fixture, int $team_id ): object;
}
