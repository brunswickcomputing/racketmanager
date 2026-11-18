<?php
declare( strict_types=1 );
/**
 * Portal Layout View Model
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * View model representing the outer portal application shell.
 */
final readonly class Portal_Layout_View_Model {

    /**
     * @param string                                           $title           Page or section title.
     * @param string                                           $content         Rendered HTML content of the inner view.
     * @param string                                           $active_section  Current section slug.
     * @param Portal_Navigation_View_Model                     $navigation      Navigation view model.
     * @param string                                           $user_name       Current user display name.
     * @param string                                           $logout_url      Logout link URL.
     * @param array{message?:string,message_type?:bool|string} $flash           Flash message data.
     * @param string                                           $site_name       WordPress site title for header branding.
     * @param string                                           $site_url        URL to main frontend site.
     * @param string                                           $portal_home_url URL to portal dashboard/competitions root.
     */
    public function __construct(
        public string $title,
        public string $content,
        public string $active_section,
        public Portal_Navigation_View_Model $navigation,
        public string $user_name = '',
        public string $logout_url = '',
        public array $flash = [],
        public string $site_name = '',
        public string $site_url = '',
        public string $portal_home_url = '',
    ) {
    }
}
