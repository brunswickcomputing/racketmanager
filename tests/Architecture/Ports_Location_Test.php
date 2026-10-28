<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Source_File;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;

/**
 * Architecture guardrail preventing introduction of files or declarations in legacy port locations.
 *
 * Canonical locations for hexagonal ports are:
 * - Domain ports: src/php/Domain/Ports/
 * - Application ports: src/php/Application/Ports/ and src/php/Application/<Context>/Ports/
 *
 * Legacy locations src/php/Gateways/ and src/php/Services/Contracts/ have been consolidated and deleted.
 * No new files or class/interface declarations may exist under these locations.
 */
final class Ports_Location_Test extends TestCase {
    private const array FORBIDDEN_PATH_PREFIXES = [
        'src/php/Gateways/',
        'src/php/Services/Contracts/',
    ];

    private const string FORBIDDEN_NAMESPACE_PATTERN = '/^Racketmanager\\\\(Gateways|Services\\\\Contracts)(\\\\|$)/i';

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

        return $violations;
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

        $clean_violations = self::collect_violations( [
            $valid_source_1->path => $valid_source_1,
            $valid_source_2->path => $valid_source_2,
            $valid_source_3->path => $valid_source_3,
        ] );

        self::assertSame( [], $clean_violations, 'Valid canonical ports must not produce violations.' );
    }
}
