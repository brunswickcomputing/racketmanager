<?php
/**
 * Options admin page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

readonly final class Options_Page_View_Model {

    /**
     * @param array<string, mixed> $options Current or flash-preserved plugin options.
     * @param string|int           $tab     Active settings tab name or index.
     * @param Error_Bag            $errors  Validation errors bag.
     */
    public function __construct(
        public array $options = array(),
        public string|int $tab = 'club-players',
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }

    /**
     * Export variables expected by templates/admin/show-settings.php.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'options' => $this->options,
            'tab'     => $this->tab,
            'errors'  => $this->errors,
        );
    }
}
