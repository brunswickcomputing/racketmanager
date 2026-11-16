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
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Application\Ports\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use function is_array;
use function is_object;

/**
 * Adapts login-related shortcode rendering to the corresponding form handlers and view presenter.
 *
 * Normalizes shortcode attributes, gathers request and query context, delegates form preparation
 * to dedicated handlers, and renders either raw HTML or template-backed view models.
 *
 * Supports login, password lost, password reset, and member account form output. Uses injected
 * security and query-variable services when available, with WordPress functions and request
 * superglobals as fallbacks where applicable.
 */
class Login_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    /**
     * Initializes the shortcode controller with the required form handlers, presenter, and optional services.
     *
     * @param RacketManager|null $racketmanager Racket manager instance used to access plugin functionality, or null when unavailable.
     * @param Shortcode_View_Presenter_Interface $view_presenter Presenter used to prepare shortcode view data.
     * @param Get_Login_Form_Handler $login_form_handler Handler responsible for generating the login form.
     * @param Get_Password_Lost_Form_Handler $password_lost_form_handler Handler responsible for generating the password lost form.
     * @param Get_Password_Reset_Form_Handler $password_reset_form_handler Handler responsible for generating the password reset form.
     * @param Get_Member_Account_Form_Handler $member_account_form_handler Handler responsible for generating the member account form.
     * @param Template_Renderer_Interface|null $template_renderer Optional template renderer used to render shortcode output.
     * @param Security_Service_Interface|null $security_service Optional security service used for validation and access checks.
     * @param Query_Vars_Interface|null $query_vars Optional query variables provider used to read request context.
     *
     * @return void
     */
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter_Interface $view_presenter,
        private readonly Get_Login_Form_Handler $login_form_handler,
        private readonly Get_Password_Lost_Form_Handler $password_lost_form_handler,
        private readonly Get_Password_Reset_Form_Handler $password_reset_form_handler,
        private readonly Get_Member_Account_Form_Handler $member_account_form_handler,
        private readonly ?Template_Renderer_Interface $template_renderer = null,
        private readonly ?Security_Service_Interface $security_service = null,
        private readonly ?Query_Vars_Interface $query_vars = null
    ) {}

    /**
     * Renders the login form using the provided attributes and optional action override.
     *
     * Builds a login form query from shortcode attributes, request context, server data,
     * and current authentication state, then returns either raw rendered HTML or a
     * rendered template-based view.
     *
     * @param array|string $vars Attributes or serialized attribute data used to configure the login form.
     * @param string|null $action_override Optional form action URL to use instead of the resolved default.
     *
     * @return string Rendered login form markup.
     */
    public function render_login_form( array|string $vars = [], ?string $action_override = null ): string {
        $is_logged_in    = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $action_override = ( null !== $action_override && '' !== trim( $action_override ) ) ? $action_override : null;

        $query = Get_Login_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_request_params(),
            $this->extract_server_params(),
            $action_override,
            $is_logged_in
        );

        $result = $this->login_form_handler->handle( $query );
        if ( null !== $result->raw_html ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_html( $result->raw_html ) );
        }

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $result->template ?? 'form-login', $result->vars, $result->template_type ?? 'form' ) );
    }

    /**
     * Renders the login form using the provided variables.
     *
     * @param array|string $vars Variables or data used to customize the rendered login form.
     *
     * @return string The rendered login form markup.
     */
    public function login_form( array|string $vars = [] ): string {
        return $this->render_login_form( $vars );
    }

    /**
     * Renders the password lost form using the provided variables.
     *
     * @param array|string $vars Variables or data used to customize the rendered password lost form.
     *
     * @return string The rendered password lost form markup.
     */
    public function render_password_lost_form( array|string $vars = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );

        $query = Get_Password_Lost_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_request_params(),
            $is_logged_in
        );

        $result = $this->password_lost_form_handler->handle( $query );
        if ( null !== $result->raw_html ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_html( $result->raw_html ) );
        }

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $result->template ?? 'form-password-lost', $result->vars, $result->template_type ?? 'form' ) );
    }

    /**
     * Renders the password reset form using the provided variables and request context.
     *
     * @param array|string $vars Variables or data used to customize the rendered password reset form.
     *
     * @return string The rendered password reset form markup.
     */
    public function render_password_reset_form( array|string $vars = [] ): string {
        $query = Get_Password_Reset_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $this->extract_request_params()
        );

        $result = $this->password_reset_form_handler->handle( $query );
        if ( null !== $result->raw_html ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_html( $result->raw_html ) );
        }

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $result->template ?? 'form-password-reset', $result->vars, $result->template_type ?? 'form' ) );
    }

    /**
     * Generates the member account form based on the provided variables and current user state.
     *
     * @param array|string $vars Variables or attributes used to customize the member account form.
     *
     * @return string The rendered member account form markup.
     */
    public function generate_member_account_form( array|string $vars = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null ) ) : null;

        $query = Get_Member_Account_Form_Query::from_atts(
            $this->normalize_atts( $vars ),
            $is_logged_in,
            $user_id
        );

        $result = $this->member_account_form_handler->handle( $query );
        if ( null !== $result->raw_html ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_html( $result->raw_html ) );
        }

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $result->template ?? 'form-member-account', $result->vars, $result->template_type ?? 'form' ) );
    }

    public function load_translations(): void {
        // Translation loading hook
    }

    /**
     * Retrieves all available query variables.
     *
     * @return array The query variables, or an empty array when none are available.
     */
    private function extract_query_vars(): array {
        return $this->query_vars?->all() ?? [];
    }

    /**
     * Retrieves the current GET request parameters.
     *
     * @return array The GET parameters from the current request, or an empty array if none are available.
     */
    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    /**
     * Retrieves the current request parameters.
     *
     * @return array The request parameters from the global request data, or an empty array if unavailable.
     */
    private function extract_request_params(): array {
        return $_REQUEST ?? [];
    }

    /**
     * Extracts the current server parameters.
     *
     * @return array Server parameters from the global server environment.
     */
    private function extract_server_params(): array {
        return $_SERVER ?? [];
    }

    /**
     * Normalizes the provided attributes into an array.
     *
     * @param array|string $atts Attributes to normalize.
     *
     * @return array The normalized attributes array, or an empty array when the input is not an array.
     */
    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }
}
