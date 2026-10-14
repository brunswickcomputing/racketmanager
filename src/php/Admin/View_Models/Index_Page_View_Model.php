<?php
/**
 * Index (competitions) admin page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Admin\View_Models\Error_Bag;

readonly final class Index_Page_View_Model {

    /**
     * @param array<int, object>    $competitions
     * @param array<string, string> $age_groups
     * @param string                $age_group_select
     * @param string                $page_name
     * @param Error_Bag             $errors
     * @param ?string               $competition_name
     * @param ?string               $type
     * @param ?string               $age_group
     */
    public function __construct(
        public array $competitions = array(),
        public array $age_groups = array(),
        public string $age_group_select = '',
        public string $page_name = 'racketmanager',
        public Error_Bag $errors = new Error_Bag(),
        public ?string $competition_name = null,
        public ?string $type = null,
        public ?string $age_group = null,
    ) {
    }

    /**
     * Export variables expected by templates/admin/index.php.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competitions'     => $this->competitions,
            'age_groups'       => $this->age_groups,
            'age_group_select' => $this->age_group_select,
            'page_name'        => $this->page_name,
            'errors'           => $this->errors,
            'competition_name' => $this->competition_name,
            'type'             => $this->type,
            'age_group'        => $this->age_group,
        );
    }
}
