<?php
/**
 * Tournament Modify Page ViewModel
 *
 * @package RacketManager
 * @subpackage Admin/ViewModels
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Services\Validator\Validator_Tournament;

final readonly class Tournament_Modify_Page_View_Model {

    public function __construct(
        public bool $edit,
        public string $form_title,
        public string $form_action,
        public object $tournament,
        public object $fees,
        public array $clubs,
        public array $competitions,
        public array $seasons,
        public Validator_Tournament $validator,
    ) {
    }

    /**
     * Export legacy template variables for templates/admin/tournament-edit.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'edit'         => $this->edit,
            'form_title'   => $this->form_title,
            'form_action'  => $this->form_action,
            'tournament'   => $this->tournament,
            'fees'         => $this->fees,
            'clubs'        => $this->clubs,
            'competitions' => $this->competitions,
            'seasons'      => $this->seasons,
            'validator'    => $this->validator,
        );
    }
}
