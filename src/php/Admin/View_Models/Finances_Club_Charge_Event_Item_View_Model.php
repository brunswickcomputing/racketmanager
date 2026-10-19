<?php
/**
 * Finances club charge event item view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Club_Charge_Event_Item_View_Model {

    /**
     * @param int|string            $type
     * @param string                $type_name
     * @param int|string            $count
     * @param float|int|string|null $fee
     * @param string                $fee_formatted
     */
    public function __construct(
        public int|string $type,
        public string $type_name,
        public int|string $count,
        public float|int|string|null $fee,
        public string $fee_formatted,
    ) {
    }
}
