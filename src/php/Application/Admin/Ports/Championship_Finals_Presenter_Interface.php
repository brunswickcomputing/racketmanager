<?php
declare( strict_types=1 );
/**
 * Championship Finals Presenter Interface
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

/**
 * Admin-layer contract for presenting championship finals on the draw page.
 */
interface Championship_Finals_Presenter_Interface {
    /**
     * Present finals data.
     *
     * @param array<int|string, mixed> $finals     Finals data.
     * @param object|null              $tournament Tournament entity or null.
     * @param object                   $league     League entity.
     * @param string                   $season     Season name.
     * @return array<int|string, mixed>
     */
    public function present_finals( array $finals, ?object $tournament, object $league, string $season ): array;
}
