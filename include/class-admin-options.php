<?php
/**
 * RacketManager-Admin API: Admin_Options class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Options
 */

namespace Racketmanager;

use stdClass;

/**
 * RacketManager Season Options functions
 * Class to implement RacketManager Options Result
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Options
 */
class Admin_Options extends Admin_Display {
    /**
     * Function to handle administration club displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->display_options_page();
    }
    /**
     * Display global settings page (e.g. color scheme options)
     */
    public function display_options_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'manage_racketmanager' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
        } else {
            $options = $racketmanager->options;
            $tab = 0;
            if ( isset( $_POST['updateRacketManager'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-global-league-options' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->show_message();
                    return;
                }
                // Set active tab.
                $tab                                           = isset( $_POST['active-tab'] ) ? sanitize_text_field( wp_unslash( $_POST['active-tab'] ) ) : null;
                $valid                                         = true;
                $options['rosters']['btm']                     = isset( $_POST['btmRequired'] );
                $options['rosters']['rosterEntry']             = isset( $_POST['clubPlayerEntry'] ) ? sanitize_text_field( wp_unslash( $_POST['clubPlayerEntry'] ) ) : null;
                $options['rosters']['rosterConfirmation']      = isset( $_POST['confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation'] ) ) : null;
                $options['rosters']['rosterConfirmationEmail'] = isset( $_POST['confirmationEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmationEmail'] ) ) : null;
                $options['rosters']['ageLimitCheck']           = isset( $_POST['clubPlayerAgeLimitCheck'] );
                $options['display']['wtn']                     = isset( $_POST['wtnDisplay'] );
                $options['checks']['ageLimitCheck']            = isset( $_POST['ageLimitCheck'] );
                $options['checks']['leadTimeCheck']            = isset( $_POST['leadTimeCheck'] );
                $options['checks']['ratingCheck']              = isset( $_POST['ratingCheck'] );
                $options['checks']['wtn_check']                = isset( $_POST['wtnCheck'] );
                $options['checks']['rosterLeadTime']           = isset( $_POST['playerLeadTime'] ) ? intval( $_POST['playerLeadTime'] ) : null;
                $options['checks']['playedRounds']             = isset( $_POST['playedRounds'] ) ? intval( $_POST['playedRounds'] ) : null;
                $options['checks']['playerLocked']             = isset( $_POST['playerLocked'] ) ? sanitize_text_field( wp_unslash( $_POST['playerLocked'] ) ) : null;
                $competition_types                             = Util::get_competition_types();
                foreach ( $competition_types as $competition_type ) {
                    $options[ $competition_type ]['matchCapability']         = isset( $_POST[ $competition_type ]['matchCapability'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['matchCapability'] ) ) : null;
                    $options[ $competition_type ]['resultConfirmation']      = isset( $_POST[ $competition_type ]['resultConfirmation'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultConfirmation'] ) ) : null;
                    $options[ $competition_type ]['resultEntry']             = isset( $_POST[ $competition_type ]['resultEntry'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultEntry'] ) ) : null;
                    $options[ $competition_type ]['resultConfirmationEmail'] = isset( $_POST[ $competition_type ]['resultConfirmationEmail'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultConfirmationEmail'] ) ) : null;
                    $options[ $competition_type ]['resultNotification']      = isset( $_POST[ $competition_type ]['resultNotification'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultNotification'] ) ) : null;
                    $options[ $competition_type ]['resultPending']           = isset( $_POST[ $competition_type ]['resultPending'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultPending'] ) ) : null;
                    $options[ $competition_type ]['resultTimeout']           = isset( $_POST[ $competition_type ]['resultTimeout'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultTimeout'] ) ) : null;
                    $options[ $competition_type ]['resultPenalty']           = isset( $_POST[ $competition_type ]['resultPenalty'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultPenalty'] ) ) : null;
                    $options[ $competition_type ]['confirmationPending']     = isset( $_POST[ $competition_type ]['confirmationPending'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationPending'] ) ) : null;
                    $options[ $competition_type ]['confirmationTimeout']     = isset( $_POST[ $competition_type ]['confirmationTimeout'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationTimeout'] ) ) : null;
                    $options[ $competition_type ]['confirmationPenalty']     = isset( $_POST[ $competition_type ]['confirmationPenalty'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationPenalty'] ) ) : null;
                    $options[ $competition_type ]['confirmationRequired']    = isset( $_POST[ $competition_type ]['confirmationRequired'] );
                    $options[ $competition_type ]['entry_level']             = isset( $_POST[ $competition_type ]['entryLevel'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['entryLevel'] ) ) : null;
                    Util::schedule_result_chase( $competition_type, $options[ $competition_type ] );
                }
                $options['championship']['numRounds']           = isset( $_POST['numRounds'] ) ? intval( $_POST['numRounds'] ) : null;
                $options['championship']['open_lead_time']      = isset( $_POST['openLeadtime'] ) ? intval( $_POST['openLeadtime'] ) : null;
                $grades = Util::get_event_grades();
                foreach ( $grades as $grade => $grade_desc ) {
                    $options['championship']['date_closing'][ $grade ]    = isset( $_POST[ $grade ]['dateClose'] ) ? intval( $_POST[ $grade ]['dateClose'] ) : null;
                    $options['championship']['date_withdrawal'][ $grade ] = isset( $_POST[ $grade ]['dateWithdraw'] ) ? intval( $_POST[ $grade ]['dateWithdraw'] ) : null;
                }
                $options['billing']['billingEmail']             = isset( $_POST['billingEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['billingEmail'] ) ) : null;
                $options['billing']['billingAddress']           = isset( $_POST['billingAddress'] ) ? sanitize_text_field( wp_unslash( $_POST['billingAddress'] ) ) : null;
                $options['billing']['billingTelephone']         = isset( $_POST['billingTelephone'] ) ? sanitize_text_field( wp_unslash( $_POST['billingTelephone'] ) ) : null;
                $options['billing']['billingCurrency']          = isset( $_POST['billingCurrency'] ) ? sanitize_text_field( wp_unslash( $_POST['billingCurrency'] ) ) : null;
                $options['billing']['bankName']                 = isset( $_POST['bankName'] ) ? sanitize_text_field( wp_unslash( $_POST['bankName'] ) ) : null;
                $options['billing']['sortCode']                 = isset( $_POST['sortCode'] ) ? sanitize_text_field( wp_unslash( $_POST['sortCode'] ) ) : null;
                $options['billing']['accountNumber']            = isset( $_POST['accountNumber'] ) ? intval( $_POST['accountNumber'] ) : null;
                $options['billing']['invoiceNumber']            = isset( $_POST['invoiceNumber'] ) ? intval( $_POST['invoiceNumber'] ) : null;
                $options['billing']['paymentTerms']             = isset( $_POST['paymentTerms'] ) ? intval( $_POST['paymentTerms'] ) : null;
                $options['billing']['stripe_is_live']           = isset( $_POST['billingIsLive'] );
                $options['billing']['api_publishable_key_test'] = isset( $_POST['api_publishable_key_test'] ) ? sanitize_text_field( wp_unslash( $_POST['api_publishable_key_test'] ) ) : null;
                $options['billing']['api_publishable_key_live'] = isset( $_POST['api_publishable_key_live'] ) ? sanitize_text_field( wp_unslash( $_POST['api_publishable_key_live'] ) ) : null;
                $options['billing']['api_secret_key_test']      = isset( $_POST['api_secret_key_test'] ) ? sanitize_text_field( wp_unslash( $_POST['api_secret_key_test'] ) ) : null;
                $options['billing']['api_secret_key_live']      = isset( $_POST['api_secret_key_live'] ) ? sanitize_text_field( wp_unslash( $_POST['api_secret_key_live'] ) ) : null;
                $options['billing']['api_endpoint_key_test']    = isset( $_POST['api_endpoint_key_test'] ) ? sanitize_text_field( wp_unslash( $_POST['api_endpoint_key_test'] ) ) : null;
                $options['billing']['api_endpoint_key_live']    = isset( $_POST['api_endpoint_key_live'] ) ? sanitize_text_field( wp_unslash( $_POST['api_endpoint_key_live'] ) ) : null;
                $options['keys']['googleMapsKey']               = isset( $_POST['googleMapsKey'] ) ? sanitize_text_field( wp_unslash( $_POST['googleMapsKey'] ) ) : null;
                $options['keys']['recaptchaSiteKey']            = isset( $_POST['recaptchaSiteKey'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptchaSiteKey'] ) ) : null;
                $options['keys']['recaptchaSecretKey']          = isset( $_POST['recaptchaSecretKey'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptchaSecretKey'] ) ) : null;
                $options['player']['walkover']['female']        = isset( $_POST['walkoverFemale'] ) ? intval( $_POST['walkoverFemale'] ) : null;
                $options['player']['noplayer']['female']        = isset( $_POST['noPlayerFemale'] ) ? intval( $_POST['noPlayerFemale'] ) : null;
                $options['player']['share']['female']           = isset( $_POST['shareFemale'] ) ? intval( $_POST['shareFemale'] ) : null;
                $options['player']['unregistered']['female']    = isset( $_POST['unregisteredFemale'] ) ? intval( $_POST['unregisteredFemale'] ) : null;
                $options['player']['walkover']['male']          = isset( $_POST['walkoverMale'] ) ? intval( $_POST['walkoverMale'] ) : null;
                $options['player']['noplayer']['male']          = isset( $_POST['noPlayerMale'] ) ? intval( $_POST['noPlayerMale'] ) : null;
                $options['player']['share']['male']             = isset( $_POST['shareMale'] ) ? intval( $_POST['shareMale'] ) : null;
                $options['player']['unregistered']['male']      = isset( $_POST['unregisteredMale'] ) ? intval( $_POST['unregisteredMale'] ) : null;
                $options['player']['walkover']['rubber']        = isset( $_POST['walkoverPointsRubber'] ) ? intval( $_POST['walkoverPointsRubber'] ) : null;
                $options['player']['walkover']['match']         = isset( $_POST['walkoverPointsMatch'] ) ? intval( $_POST['walkoverPointsMatch'] ) : null;
                $options['player']['share']['rubber']           = isset( $_POST['sharePoints'] ) ? intval( $_POST['sharePoints'] ) : null;
                if ( $options['checks']['ratingCheck'] && $options['checks']['wtn_check'] ) {
                    $this->set_message( __( 'Only one check can be set for ratings and wtn', 'racketmanager' ), true );
                    $valid = false;
                    $tab   = 'players';
                }
                if ( $options['billing']['stripe_is_live'] && ( empty( $options['billing']['api_publishable_key_live'] ) || empty( $options['billing']['api_secret_key_live'] ) ) ) {
                    $this->set_message( __( 'Live mode requires live keys to be set', 'racketmanager' ), true );
                    $valid = false;
                    $tab   = 'billing';
                }
                if ( $valid ) {
                    update_option( 'racketmanager', $options );
                    $this->set_message( __( 'Settings saved', 'racketmanager' ) );
                }
                $this->show_message();
            }

            require_once RACKETMANAGER_PATH . '/admin/show-settings.php';
        }
    }
}
