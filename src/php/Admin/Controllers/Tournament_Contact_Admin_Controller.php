<?php
declare( strict_types=1 );
/**
 * Tournament Contact Admin Controller (BC alias stub)
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

if ( ! class_exists( Tournament_Contact_Admin_Controller::class, false ) ) {
    class_alias( Contact_Admin_Controller::class, Tournament_Contact_Admin_Controller::class );
}
