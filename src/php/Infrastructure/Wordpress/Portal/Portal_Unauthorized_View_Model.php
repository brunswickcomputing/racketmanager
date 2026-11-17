<?php
declare( strict_types=1 );
/**
 * Portal Unauthorized View Model
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * View model representing an unauthorized 403 screen inside the portal shell.
 */
final readonly class Portal_Unauthorized_View_Model {

    /**
     * @param string $title         Error title.
     * @param string $message       Explanatory message.
     * @param string $dashboard_url Link to return to dashboard.
     * @param string $login_url     Link to log in or switch accounts.
     */
    public function __construct(
        public string $title = 'Access Denied',
        public string $message = 'You do not have sufficient permissions to access this management section.',
        public string $dashboard_url = '/manage/competitions',
        public string $login_url = '',
    ) {
    }
}
