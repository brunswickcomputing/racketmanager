<?php
/**
 * Finances charges page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Charges_Page_View_Model {

    /**
     * @param array<int, object>    $charges
     * @param array<int, object>    $competitions
     * @param array<int, object>    $seasons
     * @param int|string|null       $competition_id
     * @param string|null           $season
     * @param string                $racketmanager_tab
     * @param Error_Bag             $errors
     */
    public function __construct(
        public array $charges,
        public array $competitions,
        public array $seasons,
        public int|string|null $competition_id = null,
        public ?string $season = null,
        public string $racketmanager_tab = 'charges',
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }
}
