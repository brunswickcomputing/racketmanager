<?php
/**
 * Invoice Item View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Invoice_Item_View_Model {
    public function __construct(
        public string $description = '',
        public int|string $quantity = 1,
        public string $unit_price_formatted = '',
        public string $net_price_formatted = '',
    ) {}
}
