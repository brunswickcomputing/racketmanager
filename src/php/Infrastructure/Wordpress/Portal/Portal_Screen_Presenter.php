<?php
declare( strict_types=1 );
/**
 * Portal Screen Presenter
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

use Closure;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Message_Service;

/**
 * Class Portal_Screen_Presenter
 *
 * Renders frontend management screens wrapped inside the portal shell layout.
 */
readonly class Portal_Screen_Presenter {

    /**
     * @param View_Renderer_Interface       $renderer         View renderer instance.
     * @param Admin_Message_Service         $message_service  Flash message service.
     * @param Portal_Section_Registry       $section_registry Section registry.
     * @param Manage_Url_Resolver_Interface $url_resolver     Contextual manage URL resolver.
     * @param Portal_Auth_Guard             $auth_guard       Portal authentication guard.
     */
    public function __construct(
        private View_Renderer_Interface $renderer,
        private Admin_Message_Service $message_service,
        private Portal_Section_Registry $section_registry,
        private Manage_Url_Resolver_Interface $url_resolver,
        private Portal_Auth_Guard $auth_guard,
    ) {
    }

    /**
     * Pop and apply any pending flash messages.
     *
     * @return array{message?:string,message_type?:bool|string}
     */
    public function apply_flash(): array {
        $flash = $this->message_service->pop_flash_message();
        if ( ! empty( $flash['message'] ) ) {
            $this->message_service->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        return $flash;
    }

    /**
     * Build navigation items view model for the current user.
     *
     * @param string $active_section Active section slug.
     */
    public function build_navigation( string $active_section ): Portal_Navigation_View_Model {
        $items = [];

        foreach ( $this->section_registry->all() as $config ) {
            if ( ! $config->in_navigation ) {
                continue;
            }

            if ( ! $this->auth_guard->user_can( $config->capability ) ) {
                continue;
            }

            $is_active = ( $config->slug === $active_section );
            if ( $config->is_ported ) {
                $url = $this->url_resolver->get_url( $config->slug );
            } elseif ( function_exists( 'admin_url' ) ) {
                $url = admin_url( 'admin.php?page=' . $config->admin_page );
            } else {
                $url = '/wp-admin/admin.php?page=' . $config->admin_page;
            }

            $items[] = new Portal_Navigation_Item(
                $config->slug,
                $config->title,
                $url,
                $is_active,
                $config->is_ported,
                $config->group
            );
        }

        return new Portal_Navigation_View_Model( $items );
    }

    /**
     * Render an inner view wrapped in the portal layout shell.
     *
     * @param string $title          Page title.
     * @param string $content_html   Pre-rendered inner view HTML.
     * @param string $active_section Current section slug.
     */
    public function render_layout( string $title, string $content_html, string $active_section = '' ): void {
        $navigation = $this->build_navigation( $active_section );
        $user_name  = '';
        if ( function_exists( 'wp_get_current_user' ) ) {
            $current_user = wp_get_current_user();
            $user_name    = $current_user->display_name ?? '';
        }

        $logout_redirect = function_exists( 'home_url' ) ? home_url() : '/';
        $logout_url      = function_exists( 'wp_logout_url' )
            ? wp_logout_url( $logout_redirect )
            : '';

        $flash = $this->apply_flash();

        $site_name = function_exists( 'get_bloginfo' ) ? strval( get_bloginfo( 'name' ) ) : 'RacketManager';
        if ( '' === $site_name ) {
            $site_name = 'RacketManager';
        } elseif ( function_exists( 'wp_specialchars_decode' ) ) {
            $site_name = wp_specialchars_decode( $site_name, ENT_QUOTES );
        } else {
            $site_name = html_entity_decode( $site_name, ENT_QUOTES, 'UTF-8' );
        }

        $site_url        = function_exists( 'home_url' ) ? home_url( '/' ) : '/';
        $portal_home_url = $this->url_resolver->get_url( 'overview' );

        $layout_vm = new Portal_Layout_View_Model(
            $title,
            $content_html,
            $active_section,
            $navigation,
            $user_name,
            $logout_url,
            $flash,
            $site_name,
            $site_url,
            $portal_home_url
        );

        $this->renderer->render( 'portal/layout', $layout_vm );
    }

    /**
     * Present a controller result view model inside the portal shell.
     *
     * @param array{view_model?:object,message?:string,message_type?:bool|string} $result           Controller result.
     * @param class-string                                                        $view_model_class Expected view model class.
     * @param string|Closure(object):string                                       $template         Template path or resolver.
     * @param string                                                              $active_section   Current section slug.
     * @param string                                                              $title            Optional explicit title.
     * @param bool                                                                $strict           Throw exception if view model is missing.
     *
     * @throws Invalid_Status_Exception When strict and view model is missing or invalid.
     */
    public function present(
        array $result,
        string $view_model_class,
        string|Closure $template,
        string $active_section = '',
        string $title = '',
        bool $strict = true
    ): void {
        if ( ! empty( $result['message'] ) ) {
            $this->message_service->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof $view_model_class ) ) {
            if ( $strict ) {
                throw new Invalid_Status_Exception( esc_html__( 'Invalid view model', 'racketmanager' ) );
            }
            return;
        }

        $template_path = $template instanceof Closure ? $template( $vm ) : $template;
        $content_html  = $this->renderer->render_to_string( $template_path, $vm );

        $resolved_title = '' !== $title ? $title : ucfirst( $active_section );
        $this->render_layout( $resolved_title, $content_html, $active_section );
    }

    /**
     * Present an unauthorised 403 access denied view inside the portal shell.
     *
     * @param string $active_section Section that was attempted.
     */
    public function present_unauthorized( string $active_section = '' ): void {
        $dashboard_url = $this->url_resolver->get_url( 'overview' );
        $redirect_to   = function_exists( 'home_url' )
            ? home_url( '/manage/' . $active_section )
            : '/manage/' . $active_section;
        $login_url     = $this->auth_guard->get_login_url( $redirect_to );

        $vm = new Portal_Unauthorized_View_Model(
            __( 'Access Denied', 'racketmanager' ),
            __( 'You do not have sufficient permissions to access this management section.', 'racketmanager' ),
            $dashboard_url,
            $login_url
        );

        $content_html = $this->renderer->render_to_string( 'portal/unauthorized', $vm );
        $this->render_layout( __( 'Access Denied', 'racketmanager' ), $content_html, $active_section );
    }
}
