<?php
/**
 * Finances charge item view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Charge_Item_View_Model {

    /**
     * @param int                   $id
     * @param string                $name
     * @param string                $status
     * @param float|int|string|null $total
     * @param string                $total_formatted
     * @param string                $edit_url
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public float|int|string|null $total,
        public string $total_formatted,
        public string $edit_url,
    ) {
    }
}
