<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require $autoload;
}

require_once __DIR__ . '/wp-stubs.php';

// Fallback (and also a supplement) for when:
// - composer hasn't been run in this plugin folder, OR
// - an autoloader exists but doesn't include this plugin's src/php PSR-4 mapping.
if ( ! enum_exists( 'Racketmanager\\Domain\\DTO\\Admin\\Admin_Message_Type' ) ) {
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Admin_Message_Type.php';
}

if ( ! class_exists( 'Racketmanager\\Domain\\DTO\\Admin\\Action_Result_DTO' ) ) {
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Action_Result_DTO.php';
}

if ( ! class_exists( 'Racketmanager\\Domain\\DTO\\Admin\\Championship\\Draw_Action_Request_DTO' ) ) {
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Championship/Draw_Action_Request_DTO.php';
}

if ( ! class_exists( 'Racketmanager\\Domain\\DTO\\Admin\\Championship\\Draw_Action_Response_DTO' ) ) {
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Championship/Draw_Action_Response_DTO.php';
}

if ( ! interface_exists( 'Racketmanager\\Services\\Admin\\Security\\Action_Guard_Interface' ) ) {
    require_once __DIR__ . '/../src/php/Services/Admin/Security/Wp_Action_Guard.php';
}

if ( ! class_exists( 'Racketmanager\\Services\\Admin\\Championship\\Draw_Action_Resolver' ) ) {
    require_once __DIR__ . '/../src/php/Services/Admin/Championship/Draw_Action_Resolver.php';
}

if ( ! interface_exists( 'Racketmanager\\Services\\Admin\\Championship\\Draw_Action_Handler_Interface' ) ) {
    require_once __DIR__ . '/../src/php/Services/Admin/Championship/Draw_Action_Handler_Interface.php';
}

if ( ! class_exists( 'Racketmanager\\Services\\Admin\\Championship\\Draw_Action_Dispatcher' ) ) {
    require_once __DIR__ . '/../src/php/Services/Admin/Championship/Draw_Action_Dispatcher.php';
}

if ( ! class_exists( 'Racketmanager\\Admin\\Controllers\\Admin_Redirect_Url_Builder' ) ) {
    require_once __DIR__ . '/../src/php/Admin/Controllers/Admin_Redirect_Url_Builder.php';
}

if ( ! class_exists( 'Racketmanager\\Domain\\DTO\\Admin\\Tournament_Contact_Action_Result_DTO' ) ) {
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Tournament_Contact_Action_Result_DTO.php';
}

if ( ! class_exists( 'Racketmanager\\Admin\\View_Models\\Tournament_Contact_Page_View_Model' ) ) {
    require_once __DIR__ . '/../src/php/Admin/View_Models/Tournament_Contact_Page_View_Model.php';
}

if ( ! class_exists( 'Racketmanager\\Admin\\Presenters\\Admin_Message_Mapper' ) ) {
    require_once __DIR__ . '/../src/php/Admin/Presenters/Admin_Message_Mapper.php';
}

if ( ! class_exists( 'Racketmanager\\Services\\Admin\\Tournament\\Tournament_Contact_Action_Dispatcher' ) ) {
    require_once __DIR__ . '/../src/php/Services/Admin/Tournament/Tournament_Contact_Action_Dispatcher.php';
}

if ( ! class_exists( 'Racketmanager\\Admin\\Controllers\\Tournament_Contact_Admin_Controller' ) ) {
    require_once __DIR__ . '/../src/php/Admin/Controllers/Tournament_Contact_Admin_Controller.php';
}