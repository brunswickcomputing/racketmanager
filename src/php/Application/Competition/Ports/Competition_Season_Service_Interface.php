<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Ports;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;

/**
 * Port interface for competition season lifecycle and query operations.
 */
interface Competition_Season_Service_Interface {
    /**
     * Resolves the request-specific current season of a competition and applies it to the entity.
     *
     * @param Competition $competition The competition entity.
     * @param string      $season      Optional specific season name.
     * @param bool        $force       Whether the given season takes precedence over the request.
     * @return array The resolved season data.
     */
    public function resolve_current_season( Competition $competition, string $season = '', bool $force = false ): array;

    /**
     * Calculate competition phase from season data.
     *
     * @param array $season_data Season data.
     * @return string
     */
    public function calculate_phase( array $season_data ): string;

    /**
     * Cascades a new season to all events within a competition.
     *
     * @param Competition $competition The competition entity.
     * @param object      $season      The new season data object.
     * @return void
     */
    public function cascade_season_to_events( Competition $competition, object $season ): void;

    /**
     * Add season to event.
     *
     * @param Event $event  Event entity.
     * @param array $season Season data.
     * @return void
     */
    public function add_season_to_event( Event $event, array $season ): void;

    /**
     * Build constitution array for an event.
     *
     * @param int   $event_id Event ID.
     * @param array $args     Query arguments.
     * @return array
     */
    public function build_event_constitution( int $event_id, array $args = array() ): array;

    /**
     * Normalise constitution rows for a new season.
     *
     * @param array $constitutions Raw constitution rows.
     * @return array
     */
    public static function normalize_constitution_rows( array $constitutions ): array;
}
