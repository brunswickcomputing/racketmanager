<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Options;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Options_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_does_not_check_guard_or_save(): void {
        $guard    = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );
        $saved    = false;
        $updater  = function () use ( &$saved ): void {
            $saved = true;
        };
        $scheduled = false;
        $scheduler = function () use ( &$scheduled ): void {
            $scheduled = true;
        };

        $dispatcher = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: $updater,
            options_provider: fn(): array => array(),
            scheduler: $scheduler
        );

        $result = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertFalse( $saved );
        self::assertFalse( $scheduled );
    }

    public function test_guard_failure_returns_error_dto_and_does_not_save_or_schedule(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-global-league-options', 'manage_racketmanager' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized settings update' ) );

        $saved     = false;
        $scheduled = false;
        $dispatcher = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function () use ( &$saved ): void {
                $saved = true;
            },
            options_provider: fn(): array => array(),
            scheduler: function () use ( &$scheduled ): void {
                $scheduled = true;
            }
        );

        $result = $dispatcher->handle( array(), array( 'updateRacketManager' => '1' ) );

        self::assertSame( 'Unauthorized settings update', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertFalse( $saved );
        self::assertFalse( $scheduled );
    }

    public function test_cross_field_validation_rating_and_wtn_check_conflict(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $saved     = false;
        $scheduled = false;
        $dispatcher = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function () use ( &$saved ): void {
                $saved = true;
            },
            options_provider: fn(): array => array(),
            scheduler: function () use ( &$scheduled ): void {
                $scheduled = true;
            }
        );

        $post = array(
            'updateRacketManager' => '1',
            'ratingCheck'         => '1',
            'wtnCheck'            => '1',
            'active-tab'          => 'players',
        );

        $result = $dispatcher->handle( array(), $post );

        self::assertStringContainsString( 'Only one check can be set for ratings and wtn', $result->message );
        self::assertStringContainsString( 'For security, secret fields are not retained after validation errors. Re-enter any new secret values before saving.', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'players', $result->tab_override );
        self::assertSame( 'players', $result->flash_data['tab'] );
        self::assertTrue( $result->flash_data['options']['checks']['ratingCheck'] );
        self::assertTrue( $result->flash_data['options']['checks']['wtn_check'] );
        self::assertFalse( $saved );
        self::assertFalse( $scheduled );
    }

    public function test_cross_field_validation_stripe_live_without_keys(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $saved     = false;
        $scheduled = false;
        $dispatcher = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function () use ( &$saved ): void {
                $saved = true;
            },
            options_provider: fn(): array => array(),
            scheduler: function () use ( &$scheduled ): void {
                $scheduled = true;
            }
        );

        $post = array(
            'updateRacketManager' => '1',
            'billingIsLive'       => '1',
            'active-tab'          => 'billing',
        );

        $result = $dispatcher->handle( array(), $post );

        self::assertStringContainsString( 'Live mode requires live keys to be set', $result->message );
        self::assertStringContainsString( 'For security, secret fields are not retained after validation errors. Re-enter any new secret values before saving.', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'billing', $result->tab_override );
        self::assertSame( 'billing', $result->flash_data['tab'] );
        self::assertFalse( $saved );
        self::assertFalse( $scheduled );
    }

    public function test_successful_save_persists_options_and_calls_cron_scheduler(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $persisted_options = null;
        $scheduled_types   = array();

        $dispatcher = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function ( array $opts ) use ( &$persisted_options ): void {
                $persisted_options = $opts;
            },
            options_provider: fn(): array => array(
                'rosters' => array( 'btm' => false ),
            ),
            scheduler: function ( string $comp_type, array $opts ) use ( &$scheduled_types ): void {
                $scheduled_types[ $comp_type ] = $opts;
            },
            competition_types_provider: fn(): array => array( 'league', 'tournament' ),
            event_grades_provider: fn(): array => array( 'grade1' => 'Grade 1' )
        );

        $post = array(
            'updateRacketManager'      => '1',
            'active-tab'               => 'championship',
            'btmRequired'              => '1',
            'clubPlayerEntry'          => 'manual',
            'confirmation'             => 'admin',
            'confirmationEmail'        => 'notify@example.com',
            'clubPlayerAgeLimitCheck'  => '1',
            'wtnDisplay'               => '1',
            'ageLimitCheck'            => '1',
            'leadTimeCheck'            => '1',
            'ratingCheck'              => '1',
            'playerLeadTime'           => '14',
            'playedRounds'             => '2',
            'playerLocked'             => 'locked',
            'league'                   => array(
                'matchCapability'         => 'edit_matches',
                'resultConfirmation'      => 'both',
                'resultEntry'             => 'captain',
                'resultConfirmationEmail' => 'confirm@example.com',
                'resultNotification'      => 'notify@example.com',
                'resultPending'           => '3',
                'resultTimeout'           => '7',
                'resultPenalty'           => '1',
                'confirmationPending'     => '2',
                'confirmationTimeout'     => '5',
                'confirmationPenalty'     => '0',
                'confirmationRequired'    => '1',
                'entryLevel'              => 'club',
            ),
            'tournament'               => array(
                'resultPending' => '1',
            ),
            'numRounds'                => '4',
            'openLeadtime'             => '30',
            'grade1'                   => array(
                'dateClose'    => '7',
                'dateWithdraw' => '3',
            ),
            'billingEmail'             => 'billing@example.com',
            'billingAddress'           => '123 Tennis Lane',
            'billingTelephone'         => '0123456789',
            'billingCurrency'          => 'GBP',
            'bankName'                 => 'Tennis Bank',
            'sortCode'                 => '12-34-56',
            'accountNumber'            => '12345678',
            'invoiceNumber'            => '1001',
            'paymentTerms'             => '30',
            'billingIsLive'            => '1',
            'api_publishable_key_live' => 'pk_live_123',
            'api_secret_key_live'      => 'sk_live_456',
            'googleMapsKey'            => 'gmap_key_123',
            'recaptchaSiteKey'         => 'rc_site_key',
            'recaptchaSecretKey'       => 'rc_secret_key',
            'walkoverFemale'           => '1',
            'walkoverMale'             => '2',
            'walkoverPointsRubber'     => '10',
            'walkoverPointsMatch'      => '20',
            'noPlayerFemale'           => '3',
            'noPlayerMale'             => '4',
            'shareFemale'              => '5',
            'shareMale'                => '6',
            'sharePoints'              => '1',
            'unregisteredFemale'       => '7',
            'unregisteredMale'         => '8',
        );

        $result = $dispatcher->handle( array(), $post );

        self::assertSame( 'Settings saved', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'championship', $result->tab_override );

        self::assertNotNull( $persisted_options );
        self::assertTrue( $persisted_options['rosters']['btm'] );
        self::assertSame( 'manual', $persisted_options['rosters']['rosterEntry'] );
        self::assertSame( 'admin', $persisted_options['rosters']['rosterConfirmation'] );
        self::assertSame( 'notify@example.com', $persisted_options['rosters']['rosterConfirmationEmail'] );
        self::assertTrue( $persisted_options['rosters']['ageLimitCheck'] );
        self::assertTrue( $persisted_options['display']['wtn'] );
        self::assertTrue( $persisted_options['checks']['ageLimitCheck'] );
        self::assertTrue( $persisted_options['checks']['leadTimeCheck'] );
        self::assertTrue( $persisted_options['checks']['ratingCheck'] );
        self::assertFalse( $persisted_options['checks']['wtn_check'] );
        self::assertSame( 14, $persisted_options['checks']['rosterLeadTime'] );
        self::assertSame( 2, $persisted_options['checks']['playedRounds'] );
        self::assertSame( 'locked', $persisted_options['checks']['playerLocked'] );

        self::assertSame( 'edit_matches', $persisted_options['league']['matchCapability'] );
        self::assertSame( 'both', $persisted_options['league']['resultConfirmation'] );
        self::assertSame( 'captain', $persisted_options['league']['resultEntry'] );
        self::assertTrue( $persisted_options['league']['confirmationRequired'] );
        self::assertSame( 'club', $persisted_options['league']['entry_level'] );

        self::assertSame( 4, $persisted_options['championship']['numRounds'] );
        self::assertSame( 30, $persisted_options['championship']['open_lead_time'] );
        self::assertSame( 7, $persisted_options['championship']['date_closing']['grade1'] );
        self::assertSame( 3, $persisted_options['championship']['date_withdrawal']['grade1'] );

        self::assertSame( 'billing@example.com', $persisted_options['billing']['billingEmail'] );
        self::assertSame( 'GBP', $persisted_options['billing']['billingCurrency'] );
        self::assertSame( 12345678, $persisted_options['billing']['accountNumber'] );
        self::assertSame( 1001, $persisted_options['billing']['invoiceNumber'] );
        self::assertSame( 30, $persisted_options['billing']['paymentTerms'] );
        self::assertTrue( $persisted_options['billing']['stripe_is_live'] );
        self::assertSame( 'pk_live_123', $persisted_options['billing']['api_publishable_key_live'] );
        self::assertSame( 'sk_live_456', $persisted_options['billing']['api_secret_key_live'] );

        self::assertSame( 'gmap_key_123', $persisted_options['keys']['googleMapsKey'] );
        self::assertSame( 'rc_site_key', $persisted_options['keys']['recaptchaSiteKey'] );
        self::assertSame( 'rc_secret_key', $persisted_options['keys']['recaptchaSecretKey'] );

        self::assertSame( 1, $persisted_options['player']['walkover']['female'] );
        self::assertSame( 2, $persisted_options['player']['walkover']['male'] );
        self::assertSame( 10, $persisted_options['player']['walkover']['rubber'] );
        self::assertSame( 20, $persisted_options['player']['walkover']['match'] );
        self::assertSame( 3, $persisted_options['player']['noplayer']['female'] );
        self::assertSame( 4, $persisted_options['player']['noplayer']['male'] );
        self::assertSame( 5, $persisted_options['player']['share']['female'] );
        self::assertSame( 6, $persisted_options['player']['share']['male'] );
        self::assertSame( 1, $persisted_options['player']['share']['rubber'] );
        self::assertSame( 7, $persisted_options['player']['unregistered']['female'] );
        self::assertSame( 8, $persisted_options['player']['unregistered']['male'] );

        self::assertArrayHasKey( 'league', $scheduled_types );
        self::assertArrayHasKey( 'tournament', $scheduled_types );
    }

    public function test_save_clears_explicitly_blank_secrets_and_preserves_unrelated_options(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $persisted_options = null;
        $dispatcher        = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function ( array $opts ) use ( &$persisted_options ): void {
                $persisted_options = $opts;
            },
            options_provider: fn(): array => array(
                'version'       => '2.4.0',
                'custom_module' => array( 'enabled' => true, 'feature' => 'pro' ),
                'billing'       => array(
                    'api_secret_key_live'   => 'sk_live_existing_secret',
                    'api_endpoint_key_live' => 'whsec_existing_secret',
                    'api_secret_key_test'   => 'sk_test_existing_secret',
                    'api_endpoint_key_test' => 'whsec_test_existing_secret',
                ),
                'keys'          => array(
                    'recaptchaSecretKey' => 'existing_recaptcha_secret',
                ),
            ),
            scheduler: fn() => null,
            competition_types_provider: fn(): array => array(),
            event_grades_provider: fn(): array => array()
        );

        $post = array(
            'updateRacketManager'      => '1',
            'api_publishable_key_live' => 'pk_live_new',
            'api_secret_key_live'      => '',
            'api_endpoint_key_live'    => ' ',
            'api_secret_key_test'      => '',
            'api_endpoint_key_test'    => '',
            'recaptchaSecretKey'       => '',
            'bankName'                 => 'Updated Bank',
        );

        $result = $dispatcher->handle( array(), $post );

        self::assertSame( 'Settings saved', $result->message );
        self::assertNotNull( $persisted_options );
        self::assertSame( '2.4.0', $persisted_options['version'] );
        self::assertSame( array( 'enabled' => true, 'feature' => 'pro' ), $persisted_options['custom_module'] );
        self::assertSame( 'Updated Bank', $persisted_options['billing']['bankName'] );
        self::assertSame( 'pk_live_new', $persisted_options['billing']['api_publishable_key_live'] );
        self::assertSame( '', $persisted_options['billing']['api_secret_key_live'] );
        self::assertSame( '', $persisted_options['billing']['api_endpoint_key_live'] );
        self::assertSame( '', $persisted_options['billing']['api_secret_key_test'] );
        self::assertSame( '', $persisted_options['billing']['api_endpoint_key_test'] );
        self::assertSame( '', $persisted_options['keys']['recaptchaSecretKey'] );
    }

    public function test_save_preserves_existing_secret_when_secret_field_is_absent(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $persisted_options = null;
        $dispatcher        = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function ( array $opts ) use ( &$persisted_options ): void {
                $persisted_options = $opts;
            },
            options_provider: fn(): array => array(
                'billing' => array( 'api_secret_key_live' => 'sk_live_existing_secret' ),
            ),
            scheduler: fn() => null,
            competition_types_provider: fn(): array => array(),
            event_grades_provider: fn(): array => array()
        );

        $dispatcher->handle( array(), array( 'updateRacketManager' => '1' ) );

        self::assertSame( 'sk_live_existing_secret', $persisted_options['billing']['api_secret_key_live'] );
    }

    public function test_empty_string_integer_fields_convert_to_zero_matching_legacy(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $persisted_options = null;
        $dispatcher        = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: function ( array $opts ) use ( &$persisted_options ): void {
                $persisted_options = $opts;
            },
            options_provider: fn(): array => array(),
            scheduler: fn() => null,
            competition_types_provider: fn(): array => array(),
            event_grades_provider: fn(): array => array( 'grade1' => 'Grade 1' )
        );

        $post = array(
            'updateRacketManager'  => '1',
            'playerLeadTime'       => '',
            'playedRounds'         => '',
            'numRounds'            => '',
            'openLeadtime'         => '',
            'grade1'               => array(
                'dateClose'    => '',
                'dateWithdraw' => '',
            ),
            'accountNumber'        => '',
            'invoiceNumber'        => '',
            'paymentTerms'         => '',
            'walkoverFemale'       => '',
            'walkoverMale'         => '',
            'walkoverPointsRubber' => '',
            'walkoverPointsMatch'  => '',
            'sharePoints'          => '',
        );

        $result = $dispatcher->handle( array(), $post );

        self::assertSame( 'Settings saved', $result->message );
        self::assertSame( 0, $persisted_options['checks']['rosterLeadTime'] );
        self::assertSame( 0, $persisted_options['checks']['playedRounds'] );
        self::assertSame( 0, $persisted_options['championship']['numRounds'] );
        self::assertSame( 0, $persisted_options['championship']['open_lead_time'] );
        self::assertSame( 0, $persisted_options['championship']['date_closing']['grade1'] );
        self::assertSame( 0, $persisted_options['championship']['date_withdrawal']['grade1'] );
        self::assertSame( 0, $persisted_options['billing']['accountNumber'] );
        self::assertSame( 0, $persisted_options['billing']['invoiceNumber'] );
        self::assertSame( 0, $persisted_options['billing']['paymentTerms'] );
        self::assertSame( 0, $persisted_options['player']['walkover']['female'] );
        self::assertSame( 0, $persisted_options['player']['walkover']['male'] );
        self::assertSame( 0, $persisted_options['player']['walkover']['rubber'] );
        self::assertSame( 0, $persisted_options['player']['walkover']['match'] );
        self::assertSame( 0, $persisted_options['player']['share']['rubber'] );
    }

    public function test_validation_failure_sanitizes_all_secrets_from_flash_data(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Options_Action_Dispatcher(
            action_guard: $guard,
            options_updater: fn() => null,
            options_provider: fn(): array => array(),
            scheduler: fn() => null,
            competition_types_provider: fn(): array => array(),
            event_grades_provider: fn(): array => array()
        );

        $post = array(
            'updateRacketManager'      => '1',
            'ratingCheck'              => '1',
            'wtnCheck'                 => '1',
            'api_secret_key_live'      => 'sk_live_secret',
            'api_endpoint_key_live'    => 'whsec_live_secret',
            'api_secret_key_test'      => 'sk_test_secret',
            'api_endpoint_key_test'    => 'whsec_test_secret',
            'recaptchaSecretKey'       => 're_secret_key',
            'api_publishable_key_live' => 'pk_live_pub',
        );

        $result = $dispatcher->handle( array(), $post );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'secret fields are not retained after validation errors', $result->message );
        self::assertStringNotContainsString( 'sk_live_secret', $result->message );
        self::assertArrayNotHasKey( 'api_secret_key_live', $result->flash_data['options']['billing'] );
        self::assertArrayNotHasKey( 'api_endpoint_key_live', $result->flash_data['options']['billing'] );
        self::assertArrayNotHasKey( 'api_secret_key_test', $result->flash_data['options']['billing'] );
        self::assertArrayNotHasKey( 'api_endpoint_key_test', $result->flash_data['options']['billing'] );
        self::assertArrayNotHasKey( 'recaptchaSecretKey', $result->flash_data['options']['keys'] );
        self::assertSame( 'pk_live_pub', $result->flash_data['options']['billing']['api_publishable_key_live'] );
    }
}
