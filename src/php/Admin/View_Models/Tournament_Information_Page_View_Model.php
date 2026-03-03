<?php
/**
 * Tournament information page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Tournament_Information_Page_View_Model {

    public function __construct(
        public object $tournament,
        public Error_Bag $errors,
    ) {
    }

}
