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

/**
 * @property int                  $total total number of messages.
 * @property int                  $unread number of unread messages.
 * @property int                  $read number of read messages.
 * @property Message_View_Model[] $detail message details array.
 */
final readonly class Messages_List_View_Model implements Countable {
    /**
     * @param int                  $total total number of messages.
     * @param int                  $unread number of unread messages.
     * @param int                  $read number of read messages.
     * @param Message_View_Model[] $detail message details array.
     */
    public function __construct(
        public int $total,
        public int $unread,
        public int $read,
        public array $detail,
    ) {}

    /**
     * @return int
     */
    public function count(): int {
        return count( $this->detail );
    }
}
