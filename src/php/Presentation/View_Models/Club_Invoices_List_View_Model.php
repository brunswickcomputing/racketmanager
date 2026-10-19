<?php
/**
 * Club Invoices List View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Invoice_Summary_View_Model>
 */
final readonly class Club_Invoices_List_View_Model implements IteratorAggregate, Countable {
    /**
     * @param Invoice_Summary_View_Model[] $items
     * @param string                       $total_amount_formatted
     * @param float                        $total_amount
     */
    public function __construct(
        public array $items = [],
        public string $total_amount_formatted = '',
        public float $total_amount = 0.0,
    ) {}

    public function getIterator(): Traversable {
        return new ArrayIterator( $this->items );
    }

    public function count(): int {
        return count( $this->items );
    }
}
