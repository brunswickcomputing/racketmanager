<?php
/**
 * Options action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Options
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Options;

use Closure;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;

/**
 * Handles global plugin settings and options update actions for the admin options screen.
 */
readonly final class Options_Action_Dispatcher implements Options_Action_Dispatcher_Interface {

    /**
     * @var Closure(string, array):void
     */
    private Closure $scheduler;

    /**
     * @var Closure():array<int, string>
     */
    private Closure $competition_types_provider;

    /**
     * @var Closure():array<string, string>
     */
    private Closure $event_grades_provider;

    /**
     * @var Closure(array):void
     */
    private Closure $options_updater;

    /**
     * @var Closure():array
     */
    private Closure $options_provider;

    /**
     * @param Action_Guard_Interface $action_guard Security and capability guard.
     * @param RacketManager|null     $racketmanager Optional main plugin instance.
     * @param callable|null          $options_updater Optional callable to update options.
     * @param callable|null          $options_provider Optional callable to retrieve current options.
     * @param callable|null          $scheduler Optional callable to schedule result chase.
     * @param callable|null          $competition_types_provider Optional callable returning competition types.
     * @param callable|null          $event_grades_provider Optional callable returning event grades.
     */
    public function __construct(
        private Action_Guard_Interface $action_guard,
        ?RacketManager $racketmanager = null,
        ?callable $options_updater = null,
        ?callable $options_provider = null,
        ?callable $scheduler = null,
        ?callable $competition_types_provider = null,
        ?callable $event_grades_provider = null,
    ) {
        $app = $racketmanager ?? ( $GLOBALS['racketmanager'] ?? null );

        $updater = $options_updater ?? ( $app ? ( $app->update_plugin_options( ... ) ) : static function ( array $opts ): void {
            update_option( 'racketmanager', $opts );
            if ( isset( $GLOBALS['racketmanager'] ) && is_object( $GLOBALS['racketmanager'] ) ) {
                $GLOBALS['racketmanager']->options = $opts;
            }
        } );
        $this->options_updater = $updater( ... );

        $provider = $options_provider ?? ( $app ? ( $app->get_options( ... ) ) : static function (): array {
            return (array) get_option( 'racketmanager', array() );
        } );
        $this->options_provider = $provider( ... );

        $schedule        = $scheduler ?? ( Util::schedule_result_chase( ... ) );
        $this->scheduler = $schedule( ... );

        $types_fn                         = $competition_types_provider ?? ( Util_Lookup::get_competition_types( ... ) );
        $this->competition_types_provider = $types_fn( ... );

        $grades_fn                   = $event_grades_provider ?? ( Util_Lookup::get_event_grades( ... ) );
        $this->event_grades_provider = $grades_fn( ... );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( ! isset( $post['updateRacketManager'] ) ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_manage-global-league-options',
                'manage_racketmanager'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $tab = isset( $post['active-tab'] ) ? sanitize_text_field( wp_unslash( strval( $post['active-tab'] ) ) ) : 'club-players';

        $options = ( $this->options_provider )();
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $options = $this->build_options_from_post( $options, $post );

        // Cross-field validations
        if ( ! empty( $options['checks']['ratingCheck'] ) && ! empty( $options['checks']['wtn_check'] ) ) {
            return new Action_Result_DTO(
                message: $this->validation_error_message( __( 'Only one check can be set for ratings and wtn', 'racketmanager' ) ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'players',
                flash_data: array(
                    'tab'     => 'players',
                    'options' => $this->sanitize_flash_options( $options ),
                ),
            );
        }

        if ( ! empty( $options['billing']['stripe_is_live'] ) && ( empty( $options['billing']['api_publishable_key_live'] ) || empty( $options['billing']['api_secret_key_live'] ) ) ) {
            return new Action_Result_DTO(
                message: $this->validation_error_message( __( 'Live mode requires live keys to be set', 'racketmanager' ) ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'billing',
                flash_data: array(
                    'tab'     => 'billing',
                    'options' => $this->sanitize_flash_options( $options ),
                ),
            );
        }

        // Schedule cron result chases per competition type
        $competition_types = ( $this->competition_types_provider )();
        foreach ( $competition_types as $competition_type ) {
            ( $this->scheduler )( $competition_type, $options[ $competition_type ] ?? array() );
        }

        ( $this->options_updater )( $options );

        return new Action_Result_DTO(
            message: __( 'Settings saved', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: $tab,
        );
    }

    /**
     * Map posted values into the options array.
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $post
     *
     * @return array<string, mixed>
     */
    private function build_options_from_post( array $options, array $post ): array {
        // Rosters
        $options['rosters']['btm']                     = isset( $post['btmRequired'] );
        $options['rosters']['rosterEntry']             = isset( $post['clubPlayerEntry'] ) ? sanitize_text_field( wp_unslash( strval( $post['clubPlayerEntry'] ) ) ) : null;
        $options['rosters']['rosterConfirmation']      = isset( $post['confirmation'] ) ? sanitize_text_field( wp_unslash( strval( $post['confirmation'] ) ) ) : null;
        $options['rosters']['rosterConfirmationEmail'] = isset( $post['confirmationEmail'] ) ? sanitize_text_field( wp_unslash( strval( $post['confirmationEmail'] ) ) ) : null;
        $options['rosters']['ageLimitCheck']           = isset( $post['clubPlayerAgeLimitCheck'] );

        // Display
        $options['display']['wtn'] = isset( $post['wtnDisplay'] );

        // Player Checks
        $options['checks']['ageLimitCheck']  = isset( $post['ageLimitCheck'] );
        $options['checks']['leadTimeCheck']  = isset( $post['leadTimeCheck'] );
        $options['checks']['ratingCheck']    = isset( $post['ratingCheck'] );
        $options['checks']['wtn_check']      = isset( $post['wtnCheck'] );
        $options['checks']['rosterLeadTime'] = isset( $post['playerLeadTime'] ) ? intval( $post['playerLeadTime'] ) : null;
        $options['checks']['playedRounds']   = isset( $post['playedRounds'] ) ? intval( $post['playedRounds'] ) : null;
        $options['checks']['playerLocked']   = isset( $post['playerLocked'] ) ? sanitize_text_field( wp_unslash( strval( $post['playerLocked'] ) ) ) : null;

        // Competition Types
        $competition_types = ( $this->competition_types_provider )();
        foreach ( $competition_types as $competition_type ) {
            $comp_post                                               = isset( $post[ $competition_type ] ) && is_array( $post[ $competition_type ] ) ? $post[ $competition_type ] : array();
            $options[ $competition_type ]['matchCapability']         = isset( $comp_post['matchCapability'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['matchCapability'] ) ) ) : null;
            $options[ $competition_type ]['resultConfirmation']      = isset( $comp_post['resultConfirmation'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultConfirmation'] ) ) ) : null;
            $options[ $competition_type ]['resultEntry']             = isset( $comp_post['resultEntry'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultEntry'] ) ) ) : null;
            $options[ $competition_type ]['resultConfirmationEmail'] = isset( $comp_post['resultConfirmationEmail'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultConfirmationEmail'] ) ) ) : null;
            $options[ $competition_type ]['resultNotification']      = isset( $comp_post['resultNotification'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultNotification'] ) ) ) : null;
            $options[ $competition_type ]['resultPending']           = isset( $comp_post['resultPending'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultPending'] ) ) ) : null;
            $options[ $competition_type ]['resultTimeout']           = isset( $comp_post['resultTimeout'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultTimeout'] ) ) ) : null;
            $options[ $competition_type ]['resultPenalty']           = isset( $comp_post['resultPenalty'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['resultPenalty'] ) ) ) : null;
            $options[ $competition_type ]['confirmationPending']     = isset( $comp_post['confirmationPending'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['confirmationPending'] ) ) ) : null;
            $options[ $competition_type ]['confirmationTimeout']     = isset( $comp_post['confirmationTimeout'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['confirmationTimeout'] ) ) ) : null;
            $options[ $competition_type ]['confirmationPenalty']     = isset( $comp_post['confirmationPenalty'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['confirmationPenalty'] ) ) ) : null;
            $options[ $competition_type ]['confirmationRequired']    = isset( $comp_post['confirmationRequired'] );
            $options[ $competition_type ]['entry_level']             = isset( $comp_post['entryLevel'] ) ? sanitize_text_field( wp_unslash( strval( $comp_post['entryLevel'] ) ) ) : null;
        }

        // Championship
        $options['championship']['numRounds']      = isset( $post['numRounds'] ) ? intval( $post['numRounds'] ) : null;
        $options['championship']['open_lead_time'] = isset( $post['openLeadtime'] ) ? intval( $post['openLeadtime'] ) : null;

        $grades = ( $this->event_grades_provider )();
        foreach ( $grades as $grade => $grade_desc ) {
            $grade_post                                           = isset( $post[ $grade ] ) && is_array( $post[ $grade ] ) ? $post[ $grade ] : array();
            $options['championship']['date_closing'][ $grade ]    = isset( $grade_post['dateClose'] ) ? intval( $grade_post['dateClose'] ) : null;
            $options['championship']['date_withdrawal'][ $grade ] = isset( $grade_post['dateWithdraw'] ) ? intval( $grade_post['dateWithdraw'] ) : null;
        }

        // Billing
        $options['billing']['billingEmail']             = isset( $post['billingEmail'] ) ? sanitize_text_field( wp_unslash( strval( $post['billingEmail'] ) ) ) : null;
        $options['billing']['billingAddress']           = isset( $post['billingAddress'] ) ? sanitize_text_field( wp_unslash( strval( $post['billingAddress'] ) ) ) : null;
        $options['billing']['billingTelephone']         = isset( $post['billingTelephone'] ) ? sanitize_text_field( wp_unslash( strval( $post['billingTelephone'] ) ) ) : null;
        $options['billing']['billingCurrency']          = isset( $post['billingCurrency'] ) ? sanitize_text_field( wp_unslash( strval( $post['billingCurrency'] ) ) ) : null;
        $options['billing']['bankName']                 = isset( $post['bankName'] ) ? sanitize_text_field( wp_unslash( strval( $post['bankName'] ) ) ) : null;
        $options['billing']['sortCode']                 = isset( $post['sortCode'] ) ? sanitize_text_field( wp_unslash( strval( $post['sortCode'] ) ) ) : null;
        $options['billing']['accountNumber']            = isset( $post['accountNumber'] ) ? intval( $post['accountNumber'] ) : null;
        $options['billing']['invoiceNumber']            = isset( $post['invoiceNumber'] ) ? intval( $post['invoiceNumber'] ) : null;
        $options['billing']['paymentTerms']             = isset( $post['paymentTerms'] ) ? intval( $post['paymentTerms'] ) : null;
        $options['billing']['stripe_is_live']           = isset( $post['billingIsLive'] );
        $options['billing']['api_publishable_key_test'] = isset( $post['api_publishable_key_test'] ) ? sanitize_text_field( wp_unslash( strval( $post['api_publishable_key_test'] ) ) ) : null;
        $options['billing']['api_publishable_key_live'] = isset( $post['api_publishable_key_live'] ) ? sanitize_text_field( wp_unslash( strval( $post['api_publishable_key_live'] ) ) ) : null;
        $options['billing']['api_secret_key_test']      = $this->extract_secret_option( $options, $post, 'billing', 'api_secret_key_test', 'api_secret_key_test' );
        $options['billing']['api_secret_key_live']      = $this->extract_secret_option( $options, $post, 'billing', 'api_secret_key_live', 'api_secret_key_live' );
        $options['billing']['api_endpoint_key_test']    = $this->extract_secret_option( $options, $post, 'billing', 'api_endpoint_key_test', 'api_endpoint_key_test' );
        $options['billing']['api_endpoint_key_live']    = $this->extract_secret_option( $options, $post, 'billing', 'api_endpoint_key_live', 'api_endpoint_key_live' );

        // Keys
        $options['keys']['googleMapsKey']      = isset( $post['googleMapsKey'] ) ? sanitize_text_field( wp_unslash( strval( $post['googleMapsKey'] ) ) ) : null;
        $options['keys']['recaptchaSiteKey']   = isset( $post['recaptchaSiteKey'] ) ? sanitize_text_field( wp_unslash( strval( $post['recaptchaSiteKey'] ) ) ) : null;
        $options['keys']['recaptchaSecretKey'] = $this->extract_secret_option( $options, $post, 'keys', 'recaptchaSecretKey', 'recaptchaSecretKey' );

        // Player / Walkover / Missing / Share / Unregistered
        $options['player']['walkover']['female']     = isset( $post['walkoverFemale'] ) ? intval( $post['walkoverFemale'] ) : null;
        $options['player']['noplayer']['female']     = isset( $post['noPlayerFemale'] ) ? intval( $post['noPlayerFemale'] ) : null;
        $options['player']['share']['female']        = isset( $post['shareFemale'] ) ? intval( $post['shareFemale'] ) : null;
        $options['player']['unregistered']['female'] = isset( $post['unregisteredFemale'] ) ? intval( $post['unregisteredFemale'] ) : null;
        $options['player']['walkover']['male']       = isset( $post['walkoverMale'] ) ? intval( $post['walkoverMale'] ) : null;
        $options['player']['noplayer']['male']       = isset( $post['noPlayerMale'] ) ? intval( $post['noPlayerMale'] ) : null;
        $options['player']['share']['male']          = isset( $post['shareMale'] ) ? intval( $post['shareMale'] ) : null;
        $options['player']['unregistered']['male']   = isset( $post['unregisteredMale'] ) ? intval( $post['unregisteredMale'] ) : null;
        $options['player']['walkover']['rubber']     = isset( $post['walkoverPointsRubber'] ) ? intval( $post['walkoverPointsRubber'] ) : null;
        $options['player']['walkover']['match']      = isset( $post['walkoverPointsMatch'] ) ? intval( $post['walkoverPointsMatch'] ) : null;
        $options['player']['share']['rubber']        = isset( $post['sharePoints'] ) ? intval( $post['sharePoints'] ) : null;

        return $options;
    }

    /**
     * Extract a posted secret, preserving the stored value only when the field was not submitted.
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $post
     * @param string               $section
     * @param string               $key
     * @param string               $post_key
     *
     * @return string|null
     */
    private function extract_secret_option( array $options, array $post, string $section, string $key, string $post_key ): ?string {
        if ( ! array_key_exists( $post_key, $post ) ) {
            return $options[ $section ][ $key ] ?? null;
        }

        return sanitize_text_field( wp_unslash( strval( $post[ $post_key ] ) ) );
    }

    /**
     * Add guidance for secret values omitted from validation flash data.
     */
    private function validation_error_message( string $message ): string {
        return $message . ' ' . __( 'For security, secret fields are not retained after validation errors. Re-enter any new secret values before saving.', 'racketmanager' );
    }

    /**
     * Sanitize options for transient/flash storage to avoid exposing secret keys.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function sanitize_flash_options( array $options ): array {
        $safe = $options;
        unset(
            $safe['billing']['api_secret_key_live'],
            $safe['billing']['api_endpoint_key_live'],
            $safe['billing']['api_secret_key_test'],
            $safe['billing']['api_endpoint_key_test'],
            $safe['keys']['recaptchaSecretKey']
        );

        return $safe;
    }
}
