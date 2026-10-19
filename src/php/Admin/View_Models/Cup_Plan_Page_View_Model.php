<?php
declare( strict_types=1 );

/**
 * Cup Plan Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Cup_Plan_Page_View_Model {

    /**
     * @param object                         $competition
     * @param object                         $cup_season
     * @param string                         $season
     * @param array                          $final_matches  Raw final fixtures (count only; use $final_cards for display).
     * @param string                         $tab
     * @param int                            $num_events
     * @param array                          $order_of_play  Per-court order of play with defaults applied.
     * @param Error_Bag                      $errors
     * @param int                            $max_schedules  Number of time slots in the schedule grid.
     * @param int                            $column_width   Bootstrap column width of each court column.
     * @param int                            $match_length   Match length used for time offsets.
     * @param array                          $schedule_slots Pre-computed schedule slots (time, court cells, clash names).
     * @param Plan_Final_Card_View_Model[]   $final_cards    Draggable final match cards.
     */
    public function __construct(
        public object $competition,
        public object $cup_season,
        public string $season,
        public array $final_matches,
        public string $tab,
        public int $num_events,
        public array $order_of_play,
        public Error_Bag $errors,
        public int $max_schedules,
        public int $column_width,
        public int $match_length,
        public array $schedule_slots,
        public array $final_cards,
    ) {
    }
}
