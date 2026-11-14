<?php
/**
 * Email shortcode presenter port
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Presentation/Ports
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Presentation\Ports;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Interface for preparing view data for email and notification shortcodes.
 */
interface Email_Shortcode_Presenter_Interface {

    /**
     * Prepare presentation variables for match notification.
     *
     * @param Fixture $fixture Fixture entity.
     * @param array   $args    Shortcode arguments.
     *
     * @return array
     */
    public function present_match_notification( Fixture $fixture, array $args ): array;

    /**
     * Prepare presentation variables for result notification.
     *
     * @param Fixture $fixture Fixture entity.
     * @param array   $args    Shortcode arguments.
     *
     * @return array
     */
    public function present_result_notification( Fixture $fixture, array $args ): array;

    /**
     * Prepare presentation variables for captain result notification.
     *
     * @param Fixture $fixture Fixture entity.
     * @param array   $args    Shortcode arguments.
     *
     * @return array
     */
    public function present_captain_result_notification( Fixture $fixture, array $args ): array;

    /**
     * Prepare presentation variables for outstanding result notification.
     *
     * @param Fixture $fixture Fixture entity.
     * @param array   $args    Shortcode arguments.
     *
     * @return array
     */
    public function present_result_outstanding_notification( Fixture $fixture, array $args ): array;

    /**
     * Prepare presentation variables for club player notification.
     *
     * @param array $args Shortcode arguments.
     *
     * @return array
     */
    public function present_club_player_notification( array $args ): array;

    /**
     * Prepare presentation variables for match date change notification.
     *
     * @param Fixture $fixture Fixture entity.
     * @param array   $args    Shortcode arguments.
     *
     * @return array
     */
    public function present_match_date_change_notification( Fixture $fixture, array $args ): array;

    /**
     * Prepare presentation variables for team withdrawn notification.
     *
     * @param object $team   Team object.
     * @param object $league League object.
     * @param string $season Season identifier.
     * @param array  $args   Shortcode arguments.
     *
     * @return array
     */
    public function present_team_withdrawn_email( object $team, object $league, string $season, array $args ): array;

    /**
     * Prepare presentation variables for withdrawn team match notification.
     *
     * @param array $args Shortcode arguments.
     *
     * @return array
     */
    public function present_withdrawn_team_match( array $args ): array;

    /**
     * Prepare presentation variables for event constitution notification.
     *
     * @param object $event Event object.
     *
     * @return array
     */
    public function present_event_constitution( object $event ): array;
}
