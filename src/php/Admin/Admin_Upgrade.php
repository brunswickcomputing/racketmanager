<?php
/**
 * RacketManager-Admin API: RacketManager-upgrade class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Upgrade
 */

namespace Racketmanager\Admin;

use Racketmanager\Services\Validator\Validator;

/**
 * RacketManager Upgrade functions
 * Class to implement RacketManager Admin Upgrade
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Upgrade
 */
class Admin_Upgrade extends Admin_Display {
    /**
     * Function to handle administration displays
     *
     * @return void
     */
    public function handle_display(): void {
        $this->display_upgrade_page();
    }
    /**
     * Display upgrade page
     */
    private function display_upgrade_page(): void {
        global $racketmanager;
        $validator = new Validator();
        $validator->capability( 'manage_racketmanager' );
        if (! empty( $validator->error )) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $options   = $racketmanager->options;
        $installed = $options['dbversion'] ?? null;
        if ( isset($_POST['doUpgrade'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_upgrade' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
                return;
            }
            require_once RACKETMANAGER_PATH . 'templates/admin/upgrade/do-upgrade.php';
        } else {
            require_once RACKETMANAGER_PATH . 'templates/admin/show-upgrade.php';
        }
    }
}
