<?php
/**
 * Activator API: Activator class (PSR-4)
 *
 * This is the PSR-4 canonical location for the Activator.
 */

namespace Racketmanager;

use Racketmanager\Util\Util;

defined('ABSPATH') || die('Access denied !');

/**
 * Class for tasks to do during plugin activation and deactivation phases
 */
class Activator {
    /**
     * Activate plugin
     */
    public static function activate(): void {
        $options = get_option('racketmanager');
        if (!$options) {
            $color_white                  = '#FFFFFF';
            $options                      = array();
            $options['version']           = RACKETMANAGER_VERSION;
            $options['dbversion']         = RACKETMANAGER_DBVERSION;
            $options['textdomain']        = 'racketmanager';
            $options['colors']['headers'] = '#dddddd';
            $options['colors']['rows']    = array(
                'main'      => $color_white,
                'alternate' => '#efefef',
                'ascend'    => $color_white,
                'descend'   => $color_white,
                'relegate'  => $color_white,
            );

            add_option('racketmanager', $options, '', 'yes');
        }

        // create directory.
        wp_mkdir_p(Util::get_file_path());

        /*
        * Set Capabilities
        */
        $role = get_role('administrator');
        if (null !== $role) {
            $role->add_cap('view_leagues');
            $role->add_cap('racketmanager_settings');
            $role->add_cap('edit_leagues');
            $role->add_cap('edit_league_settings');
            $role->add_cap('del_leagues');
            $role->add_cap('edit_seasons');
            $role->add_cap('del_seasons');
            $role->add_cap('edit_teams');
            $role->add_cap('del_teams');
            $role->add_cap('edit_matches');
            $role->add_cap('del_matches');
            $role->add_cap('update_results');
            $role->add_cap('export_leagues');
            $role->add_cap('import_leagues');
            $role->add_cap('manage_racketmanager');

            // old roles.
            $role->add_cap('racketmanager');
            $role->add_cap('racket_manager');
        }

        $role = get_role('editor');
        $role?->add_cap('racket_manager');

        static::create_login_pages();
        static::create_basic_pages();

        static::install();
    }

    /**
     * Create login pages
     */
    public static function create_login_pages(): void {
        // Information needed for creating the plugin's login/account pages.
        $no_title         = 'No title';
        $page_definitions = array(
            'member-login'          => array(
                'title'         => __('Sign In', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[custom-login-form]',
            ),
            'member-account'        => array(
                'title'         => __('Your Account', 'racketmanager'),
                'page_template' => 'Member account',
                'content'       => '[account-info]',
            ),
            'member-password-lost'  => array(
                'title'         => __('Forgot Your Password?', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[custom-password-lost-form]',
            ),
            'member-password-reset' => array(
                'title'         => __('Pick a New Password', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[custom-password-reset-form]',
            ),
        );
        Util::add_racketmanager_pages( $page_definitions );
    }

    /**
     * Create basic pages
     */
    public static function create_basic_pages(): void {
        $no_title = 'No title';
        $pages    = array(
            'racketmanager-seasons'      => array(
                'title'         => __('Seasons', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[seasons]'
            ),
            'racketmanager-competitions' => array(
                'title'         => __('Competitions', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[competitions]'
            ),
            'racketmanager-events'       => array(
                'title'         => __('Events', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[events]'
            ),
            'racketmanager-tournaments'  => array(
                'title'         => __('Tournaments', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[tournaments]'
            ),
            'racketmanager-clubs'        => array(
                'title'         => __('Clubs', 'racketmanager'),
                'page_template' => $no_title,
                'content'       => '[clubs]'
            ),
        );
        Util::add_racketmanager_pages($pages);
    }

    /**
     * Install/update database tables
     */
    public static function install(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Create/update tables here as needed. Kept identical to legacy implementation.
        $sql[] = "CREATE TABLE {$wpdb->racketmanager} (\n            id mediumint(11) NOT NULL AUTO_INCREMENT,\n            name varchar(100) NOT NULL,\n            PRIMARY KEY  (id)\n        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    /**
     * Deactivate plugin
     */
    public static function deactivate(): void {
        // No-op for now, kept for API parity.
    }
}
