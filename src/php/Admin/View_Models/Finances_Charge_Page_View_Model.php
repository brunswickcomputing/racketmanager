<?php
/**
 * Finances charge edit page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Charge_Page_View_Model {

    /**
     * @param object|null           $charge
     * @param array<int, object>    $competitions
     * @param array<int, object>    $seasons
     * @param array<int, object>    $club_charges
     * @param bool                  $edit
     * @param string                $form_title
     * @param string                $form_action
     * @param Error_Bag             $errors
     */
    public function __construct(
        public ?object $charge,
        public array $competitions,
        public array $seasons,
        public array $club_charges = array(),
        public bool $edit = false,
        public string $form_title = '',
        public string $form_action = '',
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }
}
