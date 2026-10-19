<?php
/**
 * Messages List View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use Countable;

final readonly class Messages_List_View_Model implements Countable {
    /**
     * @param int $total
     * @param int $unread
     * @param int $read
     * @param Message_View_Model[] $detail
     */
    public function __construct(
        public int $total,
        public int $unread,
        public int $read,
        public array $detail,
    ) {}

    public function count(): int {
        return count( $this->detail );
    }
}
