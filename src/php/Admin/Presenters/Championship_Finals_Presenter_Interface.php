<?php
declare( strict_types=1 );
/**
 * Championship Finals Presenter Interface
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

namespace Racketmanager\Admin\Presenters;

/**
 * Admin-layer contract for presenting championship finals on the draw page.
 */
interface Championship_Finals_Presenter_Interface {
    /**
     * @param array<int|string, mixed> $finals
     * @param object|null              $tournament
     * @param object                   $league
     * @param string                   $season
     * @return array<int|string, mixed>
     */
    public function present_finals( array $finals, ?object $tournament, object $league, string $season ): array;
}
