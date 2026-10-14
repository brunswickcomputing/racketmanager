<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Query;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function is_array;
use function is_object;

/**
 * Driving adapter for Login and Account form shortcodes.
 */
class Login_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_Login_Form_Handler $login_form_handler,
        private readonly Get_Password_Lost_Form_Handler $password_lost_form_handler,
        private readonly Get_Password_Reset_Form_Handler $password_reset_form_handler,
        private readonly Get_Member_Account_Form_Handler $member_account_form_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function render_login_form( array|string $vars = [], ?string $action_override = null ): string {
        $query = Get_Login_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_request_params(),
            $this->extract_server_params(),
            $action_override
        );

        return $this->view_presenter->render( $this->login_form_handler->handle( $query ) );
    }

    public function login_form( array|string $vars = [] ): string {
        return $this->render_login_form( $vars );
    }

    public function render_password_lost_form( array|string $vars = [] ): string {
        $query = Get_Password_Lost_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_request_params()
        );

        return $this->view_presenter->render( $this->password_lost_form_handler->handle( $query ) );
    }

    public function render_password_reset_form( array|string $vars = [] ): string {
        $query = Get_Password_Reset_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_request_params()
        );

        return $this->view_presenter->render( $this->password_reset_form_handler->handle( $query ) );
    }

    public function generate_member_account_form( array|string $vars = [] ): string {
        $query = Get_Member_Account_Form_Query::from_atts(
            $this->normalize_atts( $vars )
        );

        return $this->view_presenter->render( $this->member_account_form_handler->handle( $query ) );
    }

    public function load_translations(): void {
        // Translation loading hook
    }

    private function extract_query_vars(): array {
        global $wp;
        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
            return $wp->query_vars;
        }

        return [];
    }

    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    private function extract_request_params(): array {
        return $_REQUEST ?? [];
    }

    private function extract_server_params(): array {
        return $_SERVER ?? [];
    }

    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }
}
