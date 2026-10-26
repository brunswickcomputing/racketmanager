<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support_Test;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Architecture_Test_Case;
use Racketmanager\Tests\Architecture\Support\Baseline;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;
use Racketmanager\Tests\Architecture\Support\Rule_Kind;
use Racketmanager\Tests\Architecture\Support\Rule_Mode;
use Racketmanager\Tests\Architecture\Support\Source_File;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;

/**
 * Exercises Architecture_Test_Case::assert_rule_holds() through a harness subclass that scans an
 * in-memory file set against a temporary baseline file.
 */
final class Architecture_Test_Case_Test extends TestCase {
    private string $baseline_path;
    private string|false $original_env = false;

    /** @var array<string, Source_File> */
    private array $files;

    protected function setUp(): void {
        parent::setUp();
        $this->baseline_path = tempnam( sys_get_temp_dir(), 'rm-arch-case-' ) . '.json';
        $this->original_env  = getenv( Baseline::ENV_UPDATE );
        putenv( Baseline::ENV_UPDATE );

        $domain = Source_Scanner::scan_source(
            "<?php\nnamespace Racketmanager\\Domain\\Club;\nclass Club {\n    public function load(): void {\n        global \$wpdb;\n        \$rows = \$wpdb->get_results( 'SELECT 1' );\n    }\n}\n",
            'src/php/Domain/Club/Club.php'
        );
        $service = Source_Scanner::scan_source(
            "<?php\ndeclare(strict_types=1);\nnamespace Racketmanager\\Services;\nfinal class Team_Service {\n    public function count(): int {\n        global \$wpdb;\n        return (int) \$wpdb->get_var( 'SELECT 1' );\n    }\n}\n",
            'src/php/Services/Team_Service.php'
        );
        $this->files = [ $domain->path => $domain, $service->path => $service ];
    }

    protected function tearDown(): void {
        foreach ( [ $this->baseline_path, substr( $this->baseline_path, 0, -5 ) ] as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
        putenv( false === $this->original_env ? Baseline::ENV_UPDATE : Baseline::ENV_UPDATE . '=' . $this->original_env );
        parent::tearDown();
    }

    private function harness(): Architecture_Test_Case {
        $harness = new class( 'harness' ) extends Architecture_Test_Case {
            /** @var array<string, Source_File> */
            public array $file_set = [];
            public string $path = '';
            /** @var array<int,string> */
            public array $warnings = [];

            protected function files(): array {
                return $this->file_set;
            }

            protected function baseline_path(): string {
                return $this->path;
            }

            protected function warn( string $message ): void {
                $this->warnings[] = $message;
            }

            public function run_rule( Layer_Rule $rule ): void {
                $this->assert_rule_holds( $rule );
            }
        };
        $harness->file_set = $this->files;
        $harness->path     = $this->baseline_path;

        return $harness;
    }

    private function wpdb_rule( string $id, Rule_Mode $mode, string $prefix ): Layer_Rule {
        return new Layer_Rule(
            $id,
            'no $wpdb in ' . $prefix,
            'Move the query into a repository (backlog item 6).',
            [ $prefix ],
            Rule_Kind::FORBIDDEN_VARIABLE,
            [ 'wpdb' ],
            $mode
        );
    }

    public function test_strict_rule_passes_when_there_are_no_violations(): void {
        $harness = $this->harness();

        $harness->run_rule( $this->wpdb_rule( 'application.no_wpdb', Rule_Mode::STRICT, 'src/php/Application/' ) );

        self::assertSame( [], $harness->warnings );
    }

    public function test_strict_rule_fails_on_any_violation_with_remediation(): void {
        $harness = $this->harness();

        try {
            $harness->run_rule( $this->wpdb_rule( 'domain.no_wpdb', Rule_Mode::STRICT, 'src/php/Domain/' ) );
            self::fail( 'A strict rule with violations must fail.' );
        } catch ( AssertionFailedError $failure ) {
            $message = $failure->getMessage();
            self::assertStringContainsString( 'Architecture rule `domain.no_wpdb` (no $wpdb in src/php/Domain/) has 2 violation(s):', $message );
            self::assertStringContainsString( 'src/php/Domain/Club/Club.php:5 — $wpdb', $message );
            self::assertStringContainsString( 'src/php/Domain/Club/Club.php:6 — $wpdb', $message );
            self::assertStringContainsString( 'Remediation: Move the query into a repository (backlog item 6).', $message );
            self::assertStringNotContainsString( 'Team_Service', $message, 'files outside the rule scope are not reported' );
        }
    }

    public function test_strict_rule_fails_when_baseline_contains_entries_for_it(): void {
        ( new Baseline( [ 'domain.no_wpdb' => [ 'src/php/Domain/Club/Club.php|$wpdb' => 2 ] ] ) )->write( $this->baseline_path );
        $harness = $this->harness();

        $this->expectException( AssertionFailedError::class );
        $this->expectExceptionMessage( 'is strict but has 1 baseline entries' );

        $harness->run_rule( $this->wpdb_rule( 'domain.no_wpdb', Rule_Mode::STRICT, 'src/php/Domain/' ) );
    }

    public function test_ratchet_rule_passes_when_violations_are_in_the_baseline(): void {
        ( new Baseline( [ 'services.no_wpdb' => [ 'src/php/Services/Team_Service.php|$wpdb' => 2 ] ] ) )->write( $this->baseline_path );
        $harness = $this->harness();

        $harness->run_rule( $this->wpdb_rule( 'services.no_wpdb', Rule_Mode::RATCHET, 'src/php/Services/' ) );

        self::assertSame( [], $harness->warnings );
    }

    public function test_ratchet_rule_fails_on_new_violation_and_lists_only_the_new_ones(): void {
        ( new Baseline( [ 'services.no_wpdb' => [ 'src/php/Services/Team_Service.php|$wpdb' => 2 ] ] ) )->write( $this->baseline_path );
        $harness = $this->harness();
        $rule    = $this->wpdb_rule( 'all.no_wpdb', Rule_Mode::RATCHET, 'src/php/' );

        try {
            $harness->run_rule( $rule );
            self::fail( 'A ratchet rule with a violation outside the baseline must fail.' );
        } catch ( AssertionFailedError $failure ) {
            $message = $failure->getMessage();
            self::assertStringContainsString( 'has 4 new violation(s)', $message, 'the rule id differs from the baselined one, so every violation is new' );
            self::assertStringContainsString( 'The baseline covers 0 of 4 current violation(s) of this rule.', $message );
            self::assertStringContainsString( 'Remediation: Move the query into a repository (backlog item 6).', $message );
            self::assertStringContainsString( 'Fix the code rather than regenerating the baseline', $message );
            self::assertStringContainsString( Baseline::ENV_UPDATE . '=1 vendor/bin/phpunit --testsuite Architecture', $message );
        }
    }

    public function test_ratchet_rule_fails_when_a_key_exceeds_its_tolerated_count(): void {
        ( new Baseline( [ 'services.no_wpdb' => [ 'src/php/Services/Team_Service.php|$wpdb' => 1 ] ] ) )->write( $this->baseline_path );
        $harness = $this->harness();

        try {
            $harness->run_rule( $this->wpdb_rule( 'services.no_wpdb', Rule_Mode::RATCHET, 'src/php/Services/' ) );
            self::fail( 'Exceeding a tolerated count must fail.' );
        } catch ( AssertionFailedError $failure ) {
            $message = $failure->getMessage();
            self::assertStringContainsString( 'has 2 new violation(s)', $message );
            self::assertStringContainsString( 'src/php/Services/Team_Service.php|$wpdb: 2 > 1 tolerated', $message );
            self::assertStringContainsString( 'The baseline covers 1 of 2 current violation(s) of this rule.', $message );
        }
    }

    public function test_stale_baseline_entries_warn_but_do_not_fail(): void {
        ( new Baseline( [
            'services.no_wpdb' => [
                'src/php/Services/Team_Service.php|$wpdb'    => 5,
                'src/php/Services/Removed_Service.php|$wpdb' => 1,
            ],
        ] ) )->write( $this->baseline_path );
        $harness = $this->harness();

        $harness->run_rule( $this->wpdb_rule( 'services.no_wpdb', Rule_Mode::RATCHET, 'src/php/Services/' ) );

        self::assertCount( 1, $harness->warnings );
        self::assertStringContainsString( 'rule `services.no_wpdb`: 2 stale baseline entries (4 occurrence(s) of debt no longer present)', $harness->warnings[0] );
        self::assertStringContainsString( 'src/php/Services/Removed_Service.php|$wpdb', $harness->warnings[0] );
        self::assertStringContainsString( Baseline::ENV_UPDATE . '=1', $harness->warnings[0] );
    }

    public function test_update_mode_rewrites_ratchet_entries_and_clears_strict_ones(): void {
        ( new Baseline( [
            'domain.no_wpdb'   => [ 'src/php/Domain/Club/Club.php|$wpdb' => 2 ],
            'services.no_wpdb' => [ 'src/php/Services/Removed_Service.php|$wpdb' => 1 ],
            'other.rule'       => [ 'src/php/Other.php|detail' => 1 ],
        ] ) )->write( $this->baseline_path );
        putenv( Baseline::ENV_UPDATE . '=1' );
        $harness = $this->harness();

        $harness->run_rule( $this->wpdb_rule( 'services.no_wpdb', Rule_Mode::RATCHET, 'src/php/Services/' ) );

        $failureMessage = null;
        try {
            $harness->run_rule( $this->wpdb_rule( 'domain.no_wpdb', Rule_Mode::STRICT, 'src/php/Domain/' ) );
        } catch ( AssertionFailedError $e ) {
            $failureMessage = $e->getMessage();
        }
        self::assertNotNull( $failureMessage, 'Update mode must not silence a strict rule.' );
        self::assertStringContainsString( 'violation', $failureMessage );

        self::assertSame(
            [
                'other.rule'       => [ 'src/php/Other.php|detail' => 1 ],
                'services.no_wpdb' => [ 'src/php/Services/Team_Service.php|$wpdb' => 2 ],
            ],
            Baseline::load( $this->baseline_path )->to_array()
        );
        self::assertTrue( Baseline::load( $this->baseline_path )->is_sorted_on_disk( $this->baseline_path ) );
    }

    public function test_update_mode_is_deterministic(): void {
        putenv( Baseline::ENV_UPDATE . '=1' );
        $rule = $this->wpdb_rule( 'all.no_wpdb', Rule_Mode::RATCHET, 'src/php/' );

        $this->harness()->run_rule( $rule );
        $first = file_get_contents( $this->baseline_path );
        $this->harness()->run_rule( $rule );
        $second = file_get_contents( $this->baseline_path );

        self::assertSame( $first, $second );
        self::assertSame(
            [ 'all.no_wpdb' => [ 'src/php/Domain/Club/Club.php|$wpdb' => 2, 'src/php/Services/Team_Service.php|$wpdb' => 2 ] ],
            Baseline::load( $this->baseline_path )->to_array()
        );
    }
}
