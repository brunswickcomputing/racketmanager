<?php
/**
 * Tournament contact page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Tournament_Contact_Page_View_Model {

    public function __construct(
        public object $tournament,
        public string $object_name,
        public int $object_id,
        public string $season,
        public string $tab,
        public string $email_title,
        public string $email_intro,
        public array $email_body,
        public string $email_close,
        public string $email_message,
    ) {
    }

}
