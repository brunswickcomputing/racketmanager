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
if ( ! interface_exists( 'Racketmanager\\Services\\Admin\\Security\\Action_Guard_Interface' ) ) {
    // Keep this list minimal: only what unit tests need, and in dependency order.
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Admin_Message_Type.php';
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Action_Result_DTO.php';
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Championship/Draw_Action_Request_DTO.php';
    require_once __DIR__ . '/../src/php/Domain/DTO/Admin/Championship/Draw_Action_Response_DTO.php';

    require_once __DIR__ . '/../src/php/Services/Admin/Security/Wp_Action_Guard.php'; // also defines Action_Guard_Interface

    require_once __DIR__ . '/../src/php/Services/Admin/Championship/Draw_Action_Resolver.php';
    require_once __DIR__ . '/../src/php/Services/Admin/Championship/Draw_Action_Handler_Interface.php';
    require_once __DIR__ . '/../src/php/Services/Admin/Championship/Draw_Action_Dispatcher.php';
}

if ( ! class_exists( 'Racketmanager\\Admin\\Controllers\\Admin_Redirect_Url_Builder' ) ) {
    require_once __DIR__ . '/../src/php/Admin/Controllers/Admin_Redirect_Url_Builder.php';
}
