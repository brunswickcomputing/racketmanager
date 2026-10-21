<?php
/**
 * Asset Manager
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Assets
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Assets;

/**
 * Manages registration, enqueuing, localization, and URL resolution for scripts and styles.
 */
class Asset_Manager {

    private ?string $base_url;
    private ?string $base_path;
    private ?string $version;

    /**
     * Constructor
     *
     * @param ?string $base_url  Plugin base URL. If null, falls back to RACKETMANAGER_URL constant.
     * @param ?string $base_path Plugin base filesystem path. If null, falls back to RACKETMANAGER_PATH constant.
     * @param ?string $version   Plugin version. If null, falls back to RACKETMANAGER_VERSION constant.
     */
    public function __construct( ?string $base_url = null, ?string $base_path = null, ?string $version = null ) {
        $this->base_url  = $base_url;
        $this->base_path = $base_path;
        $this->version   = $version;
    }

    /**
     * Resolve an asset URL, preferring a `.min` variant when not in debug mode and when the file exists.
     *
     * @param string $relative_path Plugin-relative path, e.g. 'dist/js/racketmanager.js'.
     * @param bool   $prefer_min    Whether to prefer minified variant in non-debug mode.
     * @return string
     */
    public function get_asset_url( string $relative_path, bool $prefer_min = true ): string {
        $is_debug  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG );
        $base_url  = $this->get_base_url();
        $full_url  = rtrim( $base_url, '/' ) . '/' . ltrim( $relative_path, '/' );

        if ( $prefer_min && ! $is_debug ) {
            $dot_pos = strrpos( $relative_path, '.' );
            if ( false !== $dot_pos ) {
                $min_relative = substr( $relative_path, 0, $dot_pos ) . '.min' . substr( $relative_path, $dot_pos );
                $base_path    = $this->get_base_path();
                $min_path     = rtrim( $base_path, '/' ) . '/' . ltrim( $min_relative, '/' );
                if ( file_exists( $min_path ) ) {
                    return rtrim( $base_url, '/' ) . '/' . ltrim( $min_relative, '/' );
                }
            }
        }

        return $full_url;
    }

    /**
     * Enqueue and register frontend scripts, inline configs, and localization.
     *
     * @param string $currency_code Currency code for JavaScript localization.
     */
    public function load_frontend_scripts( string $currency_code = 'GBP' ): void {
        $javascript_locale = str_replace( '_', '-', function_exists( 'get_locale' ) ? get_locale() : 'en_US' );
        $module_handle     = 'racketmanager-module';
        $module_src        = $this->get_asset_url( 'dist/js/racketmanager.js' );
        $version           = $this->get_version();

        // Enqueue jQuery UI dependencies FIRST
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-effects-core' );
        wp_enqueue_script( 'jquery-effects-slide' );

        // Register a regular script JUST for the inline config
        wp_register_script(
            'racketmanager-config',
            false,
            [],
            $version,
            false
        );
        wp_enqueue_script( 'racketmanager-config' );

        // Add ajax_var to the config script
        wp_add_inline_script(
            'racketmanager-config',
            'window.ajax_var = ' . wp_json_encode( array(
                'url'        => admin_url( 'admin-ajax.php' ),
                'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
            ) ) . ';'
        );

        // Register and enqueue JS module AFTER the config
        wp_register_script_module( $module_handle, $module_src, array(), $version );
        wp_enqueue_script_module( $module_handle );

        // Add locale config
        $config = array(
            'currency' => $currency_code,
            'locale'   => $javascript_locale,
        );
        wp_add_inline_script( 'racketmanager-config', 'window.locale_var = ' . wp_json_encode( $config ) . ';' );

        wp_enqueue_script( 'password-strength-meter' );
        wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
            'empty'    => __( 'But... it\'s empty!', 'theme-domain' ),
            'short'    => __( 'Too short!', 'theme-domain' ),
            'bad'      => __( 'Not even close!', 'theme-domain' ),
            'good'     => __( 'You are getting closer...', 'theme-domain' ),
            'strong'   => __( 'Now, that\'s a password!', 'theme-domain' ),
            'mismatch' => __( 'They are completely different, come on!', 'theme-domain' ),
        ) );
        ?>
    <script type="text/javascript">
    //<![CDATA[
    RacketManagerAjaxL10n = {
        blogUrl: "<?php bloginfo( 'wpurl' ); ?>",
        pluginUrl: "<?php echo esc_url( $this->get_base_url() ); ?>",
        requestUrl: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
        Edit: "<?php esc_html_e( 'Edit', 'racketmanager' ); ?>",
        Post: "<?php esc_html_e( 'Post', 'racketmanager' ); ?>",
        Save: "<?php esc_html_e( 'Save', 'racketmanager' ); ?>",
        Cancel: "<?php esc_html_e( 'Cancel', 'racketmanager' ); ?>",
        pleaseWait: "<?php esc_html_e( 'Please wait...', 'racketmanager' ); ?>",
        Revisions: "<?php esc_html_e( 'Page Revisions', 'racketmanager' ); ?>",
        Time: "<?php esc_html_e( 'Insert time', 'racketmanager' ); ?>",
        Options: "<?php esc_html_e( 'Options', 'racketmanager' ); ?>",
        Delete: "<?php esc_html_e( 'Delete', 'racketmanager' ); ?>"
    }
    //]]>
    </script>
        <?php
    }

    /**
     * Enqueue and register frontend styles.
     */
    public function load_frontend_styles(): void {
        $version  = $this->get_version();
        $base_url = rtrim( $this->get_base_url(), '/' ) . '/';

        wp_enqueue_style( 'racketmanager-print', $this->get_asset_url( 'dist/css/print.css' ), false, $version, 'print' );
        wp_enqueue_style( 'racketmanager-modal', $this->get_asset_url( 'dist/css/modal.css' ), false, $version, 'screen' );
        wp_enqueue_style( 'racketmanager', $this->get_asset_url( 'dist/css/style.css' ), false, $version, 'screen' );

        $jquery_ui_version = '1.13.2';
        wp_register_style( 'jquery-ui', $base_url . 'css/jquery/jquery-ui.min.css', false, $jquery_ui_version );
        wp_register_style( 'jquery-ui-structure', $base_url . 'css/jquery/jquery-ui.structure.min.css', array( 'jquery-ui' ), $jquery_ui_version );
        wp_register_style( 'jquery-ui-theme', $base_url . 'css/jquery/jquery-ui.theme.min.css', array( 'jquery-ui', 'jquery-ui-structure' ), $jquery_ui_version );
        wp_register_style( 'jquery-ui-autocomplete', $base_url . 'css/jquery/jquery-ui.autocomplete.min.css', array( 'jquery-ui', 'jquery-ui-autocomplete' ), $jquery_ui_version );

        wp_enqueue_style( 'jquery-ui-structure' );
        wp_enqueue_style( 'jquery-ui-theme' );
    }

    /**
     * Enqueue and register admin scripts, widgets, and localization.
     *
     * @param ?string $hook Optional current admin page hook.
     */
    public function load_admin_scripts( ?string $hook = null ): void {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $page = ( isset( $_GET['page'] ) && is_string( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_rm_page   = ( '' !== $page && str_starts_with( $page, 'racketmanager' ) );
        $is_post_edit = $screen && in_array( $screen->base ?? '', array( 'post', 'post-new', 'post.php', 'post-new.php' ), true );

        // If not a RacketManager admin page or post editor (for metabox), do nothing
        if ( ! $is_rm_page && ! $is_post_edit ) {
            return;
        }

        // Common localization data
        $l10n = array(
            'url'        => admin_url( 'admin-ajax.php' ),
            'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
        );

        // Enqueue base deps used by admin pages
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-effects-core' );
        wp_enqueue_script( 'jquery-effects-slide' );
        wp_enqueue_script( 'jquery-effects-explode' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'iris' );

        $version = $this->get_version();

        // Bootstrap only on plugin pages
        if ( $is_rm_page ) {
            wp_register_script( 'racketmanager-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), $version, false );
            wp_enqueue_script( 'racketmanager-bootstrap' );
        }

        // Admin AJAX helpers – required by metabox and many pages
        wp_register_script(
            'racketmanager-admin-ajax',
            $this->get_asset_url( 'dist/js/admin/ajax.js' ),
            array(),
            $version,
            false
        );
        wp_enqueue_script( 'racketmanager-admin-ajax' );
        wp_localize_script( 'racketmanager-admin-ajax', 'ajax_var', $l10n );

        // Functions bundle – only needed on plugin pages (UI widgets, handlers)
        if ( $is_rm_page ) {
            wp_register_script(
                'racketmanager-admin-functions',
                $this->get_asset_url( 'dist/js/admin/functions.js' ),
                array( 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-effects-core', 'jquery-effects-slide', 'jquery-effects-explode', 'jquery-ui-autocomplete', 'iris', 'racketmanager-admin-ajax' ),
                $version,
                false
            );
            wp_enqueue_script( 'racketmanager-admin-functions' );
            wp_localize_script( 'racketmanager-admin-functions', 'ajax_var', $l10n );

            // Small utilities used by many admin templates (e.g. Racketmanager.checkAll)
            wp_register_script(
                'racketmanager-admin-utils',
                $this->get_asset_url( 'dist/js/admin/utils.js' ),
                array(),
                $version,
                false
            );
            wp_enqueue_script( 'racketmanager-admin-utils' );
        }

        // Draggable interactions – only on tournaments / cups page
        if ( $is_rm_page && ( 'racketmanager-tournaments' === $page || 'racketmanager-cups' === $page ) ) {
            wp_register_script(
                'racketmanager-admin-draggable',
                $this->get_asset_url( 'dist/js/admin/draggable.js' ),
                array( 'jquery' ),
                $version,
                true
            );
            wp_enqueue_script( 'racketmanager-admin-draggable' );
        }

        // Keep legacy inline i18n for specific strings
        ?>
        <script type='text/javascript'>
        //<!--<![CDATA[-->
        window.RacketManagerAjaxL10n = {
            requestUrl: "<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php",
            manualPointRuleDescription: "<?php esc_html_e( 'Order: win, win overtime, tie, loss, loss overtime', 'racketmanager' ); ?>",
            pluginUrl: "<?php echo esc_url( rtrim( $this->get_base_url(), '/' ) . '/assets' ); ?>",
            Edit: "<?php esc_html_e( 'Edit', 'racketmanager' ); ?>",
            Post: "<?php esc_html_e( 'Post', 'racketmanager' ); ?>",
            Save: "<?php esc_html_e( 'Save', 'racketmanager' ); ?>",
            Cancel: "<?php esc_html_e( 'Cancel', 'racketmanager' ); ?>",
            pleaseWait: "<?php esc_html_e( 'Please wait...', 'racketmanager' ); ?>",
            Delete: "<?php esc_html_e( 'Delete', 'racketmanager' ); ?>",
            Yellow: "<?php esc_html_e( 'Yellow', 'racketmanager' ); ?>",
            Red: "<?php esc_html_e( 'Red', 'racketmanager' ); ?>",
            Yellow_Red: "<?php esc_html_e( 'Yellow/Red', 'racketmanager' ); ?>",
            Insert: "<?php esc_html_e( 'Insert', 'racketmanager' ); ?>",
            InsertPlayer: "<?php esc_html_e( 'Insert Player', 'racketmanager' ); ?>"
        }
        //<!--]]>-->
        </script>
        <?php
    }

    /**
     * Enqueue and register admin styles.
     */
    public function load_admin_styles(): void {
        $version  = $this->get_version();
        $base_url = rtrim( $this->get_base_url(), '/' ) . '/';

        wp_enqueue_style( 'racketmanager-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', false, $version, 'screen' );
        wp_enqueue_style( 'racketmanager', $this->get_asset_url( 'dist/css/admin.css' ), false, $version, 'screen' );
        wp_enqueue_style( 'racketmanager-modal', $this->get_asset_url( 'dist/css/modal.css' ), false, $version, 'screen' );

        $jquery_ui_version = '1.13.2';
        wp_register_style( 'jquery-ui', $base_url . 'css/jquery/jquery-ui.min.css', false, $jquery_ui_version );
        wp_register_style( 'jquery-ui-structure', $base_url . 'css/jquery/jquery-ui.structure.min.css', array( 'jquery-ui' ), $jquery_ui_version );
        wp_register_style( 'jquery-ui-theme', $base_url . 'css/jquery/jquery-ui.theme.min.css', array( 'jquery-ui', 'jquery-ui-structure' ), $jquery_ui_version );

        wp_enqueue_style( 'jquery-ui-structure' );
        wp_enqueue_style( 'jquery-ui-theme' );

        wp_enqueue_style( 'thickbox' );
    }

    /**
     * Get plugin base URL.
     *
     * @return string
     */
    public function get_base_url(): string {
        if ( null !== $this->base_url ) {
            return $this->base_url;
        }

        return defined( 'RACKETMANAGER_URL' ) ? RACKETMANAGER_URL : '';
    }

    /**
     * Get plugin base filesystem path.
     *
     * @return string
     */
    public function get_base_path(): string {
        if ( null !== $this->base_path ) {
            return $this->base_path;
        }

        return defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH : '';
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    public function get_version(): string {
        if ( null !== $this->version ) {
            return $this->version;
        }

        return defined( 'RACKETMANAGER_VERSION' ) ? RACKETMANAGER_VERSION : '1.0.0';
    }
}
