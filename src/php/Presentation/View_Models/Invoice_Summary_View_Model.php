<?php
/**
 * Invoice Summary View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Invoice_Summary_View_Model {
    public function __construct(
        public int $id,
        public string|int $invoice_number,
        public string $charge_name,
        public ?float $amount,
        public string $amount_formatted,
        public string $status,
        public string $date_due,
        public string $date_due_formatted = '',
        public ?string $link = null,
    ) {}
}
