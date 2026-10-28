<?php
declare( strict_types=1 );
/**
 * Backward compatibility class aliases for renamed Admin View Models.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

if ( ! class_exists( Tournament_Teams_List_Page_View_Model::class, false ) ) {
    class_alias( Teams_List_Page_View_Model::class, Tournament_Teams_List_Page_View_Model::class );
}

if ( ! class_exists( Tournament_Contact_Page_View_Model::class, false ) ) {
    class_alias( Contact_Page_View_Model::class, Tournament_Contact_Page_View_Model::class );
}
