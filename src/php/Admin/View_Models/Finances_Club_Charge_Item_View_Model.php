<?php
/**
 * Finances club charge item view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Club_Charge_Item_View_Model {

    /**
     * @param string                                                     $name
     * @param int|string                                                 $num_teams
     * @param float|int|string|null                                      $fee
     * @param string                                                     $fee_formatted
     * @param array<int, Finances_Club_Charge_Event_Item_View_Model>     $events
     */
    public function __construct(
        public string $name,
        public int|string $num_teams,
        public float|int|string|null $fee,
        public string $fee_formatted,
        public array $events = array(),
    ) {
    }
}
