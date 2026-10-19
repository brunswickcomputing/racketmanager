<?php
/**
 * Finances invoice view/edit page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Invoice_Page_View_Model {

    /**
     * @param object    $invoice
     * @param string    $invoice_view Rendered invoice HTML table/breakdown
     * @param Error_Bag $errors
     * @param string    $view
     * @param string    $breadcrumb
     * @param string    $title
     * @param string    $subtitle
     * @param string    $invoice_status
     * @param string    $purchase_order
     * @param int|null  $invoice_id
     */
    public function __construct(
        public object $invoice,
        public string $invoice_view,
        public Error_Bag $errors,
        public string $view,
        public string $breadcrumb,
        public string $title,
        public string $subtitle,
        public string $invoice_status,
        public string $purchase_order,
        public ?int $invoice_id,
    ) {
    }
}
