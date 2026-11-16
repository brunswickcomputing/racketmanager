<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Source_File;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;

/**
 * Architecture guardrail preventing introduction of files or declarations in legacy port locations,
 * and enforcing that interfaces outside Domain/ and Application/ must be explicitly allow-listed.
 *
 * Canonical locations for hexagonal ports are:
 * - Domain ports: src/php/Domain/Ports/
 * - Application ports: src/php/Application/Ports/ and src/php/Application/<Context>/Ports/
 *
 * Legacy locations src/php/Gateways/ and src/php/Services/Contracts/ have been consolidated and deleted.
 * Outer layers (Services/, Infrastructure/, Presentation/, Admin/) may not declare interfaces unless
 * they are legitimate framework/infrastructure contracts explicitly allow-listed in ALLOWED_OUTER_INTERFACES.
 */
final class Ports_Location_Test extends TestCase {
    public const array FORBIDDEN_PATH_PREFIXES = [
        'src/php/Gateways/',
        'src/php/Services/Contracts/',
    ];

    public const string FORBIDDEN_NAMESPACE_PATTERN = '/^Racketmanager\\\\(Gateways|Services\\\\Contracts)(\\\\|$)/i';

    public const array OUTER_LAYER_PATH_PREFIXES = [
        'src/php/Services/',
        'src/php/Infrastructure/',
        'src/php/Presentation/',
        'src/php/Admin/',
    ];

    public const array ALLOWED_OUTER_INTERFACES = [
        'src/php/Services/Container/Providers/Service_Provider_Interface.php' => 'Racketmanager\Services\Container\Providers\Service_Provider_Interface',
        'src/php/Services/Export/Formatters/Export_Formatter_Interface.php' => 'Racketmanager\Services\Export\Formatters\Export_Formatter_Interface',
        'src/php/Infrastructure/Wordpress/Component/Component_Coordinator_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator_Interface',
        'src/php/Infrastructure/Wordpress/Shortcodes/Shortcode_Adapter_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Shortcodes\Shortcode_Adapter_Interface',
        'src/php/Infrastructure/Wordpress/Presentation/Ports/Email_Shortcode_Presenter_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Email_Shortcode_Presenter_Interface',
        'src/php/Infrastructure/Wordpress/Presentation/Ports/Invoice_Presenter_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Invoice_Presenter_Interface',
        'src/php/Infrastructure/Wordpress/Presentation/Ports/Message_Presenter_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Message_Presenter_Interface',
        'src/php/Infrastructure/Wordpress/Presentation/Ports/Player_Presenter_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Player_Presenter_Interface',
        'src/php/Infrastructure/Wordpress/Presentation/Ports/Shortcode_View_Presenter_Interface.php' => 'Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface',
    ];

    /**
     * Evaluates a collection of Source_File objects against the outer layer interface rules.
     * Interfaces declared in Services/, Infrastructure/, Presentation/, or Admin/ must be explicitly allow-listed.
     *
     * @param array<string, Source_File> $files
     * @return array<int, string> List of violation descriptions.
     */
    public static function collect_outer_interface_violations( array $files ): array {
        $violations = [];

        foreach ( $files as $path => $file ) {
            if ( empty( $file->declared_interfaces ) ) {
                continue;
            }

            foreach ( self::OUTER_LAYER_PATH_PREFIXES as $prefix ) {
                if ( str_starts_with( $path, $prefix ) ) {
                    $allowed_interface = self::ALLOWED_OUTER_INTERFACES[ $path ] ?? null;
                    foreach ( $file->declared_interfaces as $interface ) {
                        if ( $interface !== $allowed_interface ) {
                            $violations[] = sprintf(
                                'Misplaced interface `%s` in `%s`: interfaces in `%s` are prohibited; ports belong in Domain/Ports/ or Application/Ports/.',
                                $interface,
                                $path,
                                $prefix
                            );
                        }
                    }
                    break;
                }
            }
        }

        return $violations;
    }

    /**
     * Evaluates a collection of Source_File objects against the port location rules.
     *
     * @param array<string, Source_File> $files
     * @return array<int, string> List of violation descriptions.
     */
    public static function collect_violations( array $files ): array {
        $violations = [];

        foreach ( $files as $path => $file ) {
            foreach ( self::FORBIDDEN_PATH_PREFIXES as $prefix ) {
                if ( str_starts_with( $path, $prefix ) ) {
                    $violations[] = sprintf( 'Forbidden path `%s`: port contracts belong in Domain/Ports/ or Application/Ports/, not `%s`.', $path, $prefix );
                }
            }

            if ( null !== $file->namespace && 1 === preg_match( self::FORBIDDEN_NAMESPACE_PATTERN, $file->namespace ) ) {
                $violations[] = sprintf( 'Forbidden namespace `%s` in `%s`: legacy gateway/contracts namespaces must not be used.', $file->namespace, $path );
            }

            foreach ( $file->declared_classes as $declared ) {
                if ( 1 === preg_match( self::FORBIDDEN_NAMESPACE_PATTERN, $declared ) ) {
                    $violations[] = sprintf( 'Forbidden declaration `%s` in `%s`: declarations in Gateways or Services\\Contracts are prohibited.', $declared, $path );
                }
            }
        }

        return array_merge( $violations, self::collect_outer_interface_violations( $files ) );
    }

    public function test_no_legacy_port_directories_or_files_exist_on_disk(): void {
        $plugin_root = Source_Scanner::plugin_root();

        foreach ( [ 'src/php/Gateways', 'src/php/Services/Contracts' ] as $forbidden_dir ) {
            $full_path = $plugin_root . '/' . $forbidden_dir;
            self::assertDirectoryDoesNotExist(
                $full_path,
                sprintf( 'Directory `%s` must not exist on disk; hexagonal ports belong in Domain/Ports/ or Application/Ports/.', $forbidden_dir )
            );
        }
    }

    public function test_no_scanned_files_or_declarations_in_legacy_port_locations(): void {
        $files      = Source_Scanner::scan_plugin();
        $violations = self::collect_violations( $files );

        self::assertSame(
            [],
            $violations,
            "Found misplaced port files or forbidden declarations:\n" . implode( "\n", $violations )
        );
    }

    public function test_no_misplaced_interfaces_outside_allow_list(): void {
        $files      = Source_Scanner::scan_plugin();
        $violations = self::collect_outer_interface_violations( $files );

        self::assertSame(
            [],
            $violations,
            "Found unapproved interfaces in outer layers (Services, Infrastructure, Presentation, Admin):\n" . implode( "\n", $violations )
        );
    }

    public function test_all_allow_listed_outer_interfaces_exist_on_disk_and_declare_expected_interface(): void {
        $plugin_root = Source_Scanner::plugin_root();

        foreach ( self::ALLOWED_OUTER_INTERFACES as $relative_path => $expected_interface ) {
            $full_path = $plugin_root . '/' . $relative_path;
            self::assertFileExists(
                $full_path,
                sprintf( 'Allow-listed outer interface file `%s` must exist on disk.', $relative_path )
            );

            $source_file = Source_Scanner::scan_file( $full_path, $relative_path );
            self::assertContains(
                $expected_interface,
                $source_file->declared_interfaces,
                sprintf( 'File `%s` must declare expected allow-listed interface `%s`.', $relative_path, $expected_interface )
            );
            self::assertCount(
                1,
                $source_file->declared_interfaces,
                sprintf( 'File `%s` must declare exactly one interface (`%s`).', $relative_path, $expected_interface )
            );
        }
    }

    public function test_guardrail_catches_synthetic_misplaced_files(): void {
        // 1. Synthetic file placed in forbidden path src/php/Gateways/
        $synthetic_gateway_file = new Source_File(
            path: 'src/php/Gateways/Mail/Mailer_Interface.php',
            namespace: 'Racketmanager\Gateways\Mail',
            declared_classes: [ 'Racketmanager\Gateways\Mail\Mailer_Interface' ]
        );

        $violations = self::collect_violations( [ 'src/php/Gateways/Mail/Mailer_Interface.php' => $synthetic_gateway_file ] );
        self::assertNotEmpty( $violations, 'Should catch synthetic file in src/php/Gateways/' );
        self::assertStringContainsString( 'Forbidden path `src/php/Gateways/Mail/Mailer_Interface.php`', $violations[0] );

        // 2. Synthetic file placed in forbidden path src/php/Services/Contracts/
        $synthetic_contract_file = new Source_File(
            path: 'src/php/Services/Contracts/Custom_Interface.php',
            namespace: 'Racketmanager\Services\Contracts',
            declared_classes: [ 'Racketmanager\Services\Contracts\Custom_Interface' ]
        );

        $violations = self::collect_violations( [ 'src/php/Services/Contracts/Custom_Interface.php' => $synthetic_contract_file ] );
        self::assertNotEmpty( $violations, 'Should catch synthetic file in src/php/Services/Contracts/' );
        self::assertStringContainsString( 'Forbidden path `src/php/Services/Contracts/Custom_Interface.php`', $violations[0] );

        // 3. Synthetic file in another path declaring forbidden namespace
        $synthetic_misplaced_ns = new Source_File(
            path: 'src/php/Services/Misplaced.php',
            namespace: 'Racketmanager\Services\Contracts',
            declared_classes: []
        );

        $violations = self::collect_violations( [ 'src/php/Services/Misplaced.php' => $synthetic_misplaced_ns ] );
        self::assertNotEmpty( $violations, 'Should catch file declaring forbidden namespace' );
        self::assertStringContainsString( 'Forbidden namespace `Racketmanager\Services\Contracts`', $violations[0] );

        // 4. Synthetic file in another path declaring forbidden class
        $synthetic_misplaced_class = new Source_File(
            path: 'src/php/Services/Misplaced_Class.php',
            namespace: 'Racketmanager\Services',
            declared_classes: [ 'Racketmanager\Gateways\Payment\Payment_Gateway_Interface' ]
        );

        $violations = self::collect_violations( [ 'src/php/Services/Misplaced_Class.php' => $synthetic_misplaced_class ] );
        self::assertNotEmpty( $violations, 'Should catch file declaring class in forbidden namespace' );
        self::assertStringContainsString( 'Forbidden declaration `Racketmanager\Gateways\Payment\Payment_Gateway_Interface`', $violations[0] );

        // 5. Valid canonical ports produce zero violations
        $valid_source_1 = Source_Scanner::scan_source(
            '<?php declare(strict_types=1); namespace Racketmanager\Application\Mail; interface Mailer_Interface {}',
            'src/php/Application/Mail/Mailer_Interface.php'
        );
        $valid_source_2 = Source_Scanner::scan_source(
            '<?php declare(strict_types=1); namespace Racketmanager\Domain\Ports; interface Rating_Gateway_Interface {}',
            'src/php/Domain/Ports/Rating_Gateway_Interface.php'
        );
        $valid_source_3 = Source_Scanner::scan_source(
            '<?php declare(strict_types=1); namespace Racketmanager\Application\Ports; interface Option_Gateway_Interface {}',
            'src/php/Application/Ports/Option_Gateway_Interface.php'
        );

        $clean_violations = self::collect_violations(
            [
                $valid_source_1->path => $valid_source_1,
                $valid_source_2->path => $valid_source_2,
                $valid_source_3->path => $valid_source_3,
            ]
        );

        self::assertSame( [], $clean_violations, 'Valid canonical ports must not produce violations.' );
    }

    public function test_guardrail_catches_synthetic_unapproved_outer_layer_interfaces(): void {
        // 1. Synthetic interface in Services/
        $synthetic_services_interface = new Source_File(
            path: 'src/php/Services/Reporting/Report_Service_Interface.php',
            namespace: 'Racketmanager\Services\Reporting',
            declared_classes: [ 'Racketmanager\Services\Reporting\Report_Service_Interface' ],
            declared_interfaces: [ 'Racketmanager\Services\Reporting\Report_Service_Interface' ],
        );
        $violations = self::collect_outer_interface_violations(
            [ $synthetic_services_interface->path => $synthetic_services_interface ]
        );
        self::assertNotEmpty( $violations, 'Should catch synthetic interface in src/php/Services/' );
        self::assertStringContainsString( 'Misplaced interface `Racketmanager\Services\Reporting\Report_Service_Interface`', $violations[0] );
        self::assertStringContainsString( 'src/php/Services/', $violations[0] );

        // 2. Synthetic interface in Infrastructure/
        $synthetic_infra_interface = new Source_File(
            path: 'src/php/Infrastructure/Cache/Cache_Adapter_Interface.php',
            namespace: 'Racketmanager\Infrastructure\Cache',
            declared_classes: [ 'Racketmanager\Infrastructure\Cache\Cache_Adapter_Interface' ],
            declared_interfaces: [ 'Racketmanager\Infrastructure\Cache\Cache_Adapter_Interface' ],
        );
        $violations = self::collect_outer_interface_violations(
            [ $synthetic_infra_interface->path => $synthetic_infra_interface ]
        );
        self::assertNotEmpty( $violations, 'Should catch synthetic interface in src/php/Infrastructure/' );
        self::assertStringContainsString( 'Misplaced interface `Racketmanager\Infrastructure\Cache\Cache_Adapter_Interface`', $violations[0] );
        self::assertStringContainsString( 'src/php/Infrastructure/', $violations[0] );

        // 3. Synthetic interface in Presentation/
        $synthetic_pres_interface = new Source_File(
            path: 'src/php/Presentation/Theme/Theme_Renderer_Interface.php',
            namespace: 'Racketmanager\Presentation\Theme',
            declared_classes: [ 'Racketmanager\Presentation\Theme\Theme_Renderer_Interface' ],
            declared_interfaces: [ 'Racketmanager\Presentation\Theme\Theme_Renderer_Interface' ],
        );
        $violations = self::collect_outer_interface_violations(
            [ $synthetic_pres_interface->path => $synthetic_pres_interface ]
        );
        self::assertNotEmpty( $violations, 'Should catch synthetic interface in src/php/Presentation/' );
        self::assertStringContainsString( 'Misplaced interface `Racketmanager\Presentation\Theme\Theme_Renderer_Interface`', $violations[0] );
        self::assertStringContainsString( 'src/php/Presentation/', $violations[0] );

        // 4. Synthetic interface in Admin/
        $synthetic_admin_interface = new Source_File(
            path: 'src/php/Admin/Menu/Menu_Builder_Interface.php',
            namespace: 'Racketmanager\Admin\Menu',
            declared_classes: [ 'Racketmanager\Admin\Menu\Menu_Builder_Interface' ],
            declared_interfaces: [ 'Racketmanager\Admin\Menu\Menu_Builder_Interface' ],
        );
        $violations = self::collect_outer_interface_violations(
            [ $synthetic_admin_interface->path => $synthetic_admin_interface ]
        );
        self::assertNotEmpty( $violations, 'Should catch synthetic interface in src/php/Admin/' );
        self::assertStringContainsString( 'Misplaced interface `Racketmanager\Admin\Menu\Menu_Builder_Interface`', $violations[0] );
        self::assertStringContainsString( 'src/php/Admin/', $violations[0] );

        // 5. Concrete classes in outer layers are not interfaces and produce zero violations
        $synthetic_outer_class = new Source_File(
            path: 'src/php/Services/Reporting/Report_Service.php',
            namespace: 'Racketmanager\Services\Reporting',
            declared_classes: [ 'Racketmanager\Services\Reporting\Report_Service' ],
            declared_interfaces: [],
        );
        self::assertSame(
            [],
            self::collect_outer_interface_violations( [ $synthetic_outer_class->path => $synthetic_outer_class ] ),
            'Concrete classes in outer layers must not be flagged by the interface guardrail.'
        );

        // 6. Explicitly allow-listed outer interfaces produce zero violations when declaring expected interface
        $allowed_files = [];
        foreach ( self::ALLOWED_OUTER_INTERFACES as $allowed_path => $expected_interface ) {
            $allowed_files[ $allowed_path ] = new Source_File(
                path: $allowed_path,
                declared_interfaces: [ $expected_interface ],
            );
        }
        self::assertSame(
            [],
            self::collect_outer_interface_violations( $allowed_files ),
            'Allow-listed infrastructure and presentation contracts must produce zero violations.'
        );

        // 7. Allow-listed file declaring a second, unapproved interface
        $sneaky_source = Source_Scanner::scan_source(
            '<?php declare(strict_types=1); namespace Racketmanager\Services\Container\Providers; interface Service_Provider_Interface {} interface Sneaky_Interface {}',
            'src/php/Services/Container/Providers/Service_Provider_Interface.php'
        );
        $violations = self::collect_outer_interface_violations(
            [ $sneaky_source->path => $sneaky_source ]
        );
        self::assertCount( 1, $violations, 'A second unapproved interface inside an allow-listed file must be caught.' );
        self::assertStringContainsString( 'Misplaced interface `Racketmanager\Services\Container\Providers\Sneaky_Interface`', $violations[0] );

        // 8. Allow-listed file declaring an unexpected interface name
        $wrong_interface_file = new Source_File(
            path: 'src/php/Services/Container/Providers/Service_Provider_Interface.php',
            declared_interfaces: [ 'Racketmanager\Services\Container\Providers\Other_Interface' ],
        );
        $violations = self::collect_outer_interface_violations(
            [ $wrong_interface_file->path => $wrong_interface_file ]
        );
        self::assertCount( 1, $violations, 'An unexpected interface in an allow-listed path must be caught.' );
        self::assertStringContainsString( 'Misplaced interface `Racketmanager\Services\Container\Providers\Other_Interface`', $violations[0] );
    }
}
