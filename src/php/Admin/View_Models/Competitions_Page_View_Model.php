<?php
declare( strict_types=1 );
/**
 * Competitions Page View Model
 *
 * View model for admin competitions list screen across leagues and cups.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Competitions_Page_View_Model {

    /**
     * @param string                $page_title
     * @param array                 $competitions
     * @param array<string, string> $age_groups
     * @param string                $age_group_select
     * @param string                $page_name
     * @param object|null           $tournament
     */
    public function __construct(
        public string $page_title,
        public array $competitions,
        public array $age_groups,
        public string $age_group_select,
        public string $page_name,
        public ?object $tournament = null,
    ) {
    }
}
