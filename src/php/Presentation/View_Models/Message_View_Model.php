<?php
/**
 * Message View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Message_View_Model {
    /**
     * @param int $id
     * @param string|null $from_name
     * @param string|null $from_email
     * @param string $subject
     * @param string $date_formatted
     * @param string $message_object
     * @param string $status
     * @param string $status_class
     * @param string $date_month
     * @param string $date_day
     * @param string $date_year
     * @param string|null $date
     * @param int|null $userid
     */
    public function __construct(
        public int $id,
        public ?string $from_name,
        public ?string $from_email,
        public string $subject,
        public string $date_formatted,
        public string $message_object,
        public string $status = '0',
        public string $status_class = 'read',
        public string $date_month = '',
        public string $date_day = '',
        public string $date_year = '',
        public ?string $date = null,
        public ?int $userid = null,
    ) {}
}
