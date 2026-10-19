<?php
declare( strict_types=1 );

/**
 * Tournament Plan Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Tournament_Plan_Page_View_Model {

    /**
     * @param object                       $tournament
     * @param array                        $final_matches  Raw final fixtures (count only; use $final_cards for display).
     * @param string                       $tab
     * @param array                        $order_of_play  Per-court order of play with defaults applied.
     * @param Error_Bag                    $errors
     * @param int                          $max_schedules  Number of time slots in the schedule grid.
     * @param int                          $column_width   Bootstrap column width of each court column.
     * @param int                          $fixture_length Fixture length used for time offsets.
     * @param array                        $schedule_slots Pre-computed schedule slots (time, court cells, clash names).
     * @param Plan_Final_Card_View_Model[] $final_cards    Draggable final fixture cards.
     * @param array{start_time:string,time_increment:string,num_courts:string} $config_values Values shown in the config form.
     */
    public function __construct(
        public object $tournament,
        public array $final_matches,
        public string $tab,
        public array $order_of_play,
        public Error_Bag $errors,
        public int $max_schedules,
        public int $column_width,
        public int $fixture_length,
        public array $schedule_slots,
        public array $final_cards,
        public array $config_values,
    ) {
    }
}
