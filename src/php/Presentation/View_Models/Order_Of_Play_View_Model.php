<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing the complete Order of Play grid.
 */
final readonly class Order_Of_Play_View_Model {

    /**
     * @param array<string, array<string, Order_Of_Play_Match_View_Model[]>> $courts Map of court => time => match list.
     * @param string[] $times Sorted list of match times.
     * @param int $col_width Responsive column width for grid.
     * @param bool $is_expanded Whether the view is expanded.
     * @param array<string, array<string, Order_Of_Play_Match_View_Model>> $match_time Map of time => court => match.
     */
    public function __construct(
        public array $courts = array(),
        public array $times = array(),
        public int $col_width = 12,
        public bool $is_expanded = false,
        public array $match_time = array(),
    ) {
    }
}
