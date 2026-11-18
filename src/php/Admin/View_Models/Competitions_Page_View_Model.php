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

/**
 * Competitions page view model.
 *
 * Provides immutable data for rendering an admin competitions page, including
 * competition listings, age group options, selected filters, tournament context,
 * form action details, and validation errors.
 *
 * @package RacketManager
 * @subpackage Admin\View_Models
 */
final readonly class Competitions_Page_View_Model {

    /**
     * Initialises the view data for the competitions page.
     *
     * @param string         $page_title The title displayed for the page.
     * @param array          $competitions The list of competitions to display.
     * @param array          $age_groups The available age groups.
     * @param string         $age_group_select The age group selection value or markup.
     * @param string         $page_name The current page name.
     * @param object|null    $tournament The related tournament, if available.
     * @param string         $competition_type The selected competition type.
     * @param Error_Bag|null $errors Validation or processing errors, if any.
     * @param string|null    $competition_name The selected or entered competition name.
     * @param string|null    $selected_age_group The selected age group identifier or name.
     * @param string         $action_url The form or page action URL.
     * @param string         $view_name The view template name.
     *
     * @return void
     */
    public function __construct(
        public string $page_title,
        public array $competitions,
        public array $age_groups,
        public string $age_group_select,
        public string $page_name,
        public ?object $tournament = null,
        public string $competition_type = '',
        public ?Error_Bag $errors = null,
        public ?string $competition_name = null,
        public ?string $selected_age_group = null,
        public string $action_url = '',
        public string $view_name = '',
    ) {
    }
}
