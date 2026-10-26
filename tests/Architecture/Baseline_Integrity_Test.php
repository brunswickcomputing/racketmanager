<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Rules\Layer_Rules;
use Racketmanager\Tests\Architecture\Support\Alias_Map;
use Racketmanager\Tests\Architecture\Support\Baseline;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;

/**
 * Sanity checks on the rule catalogue and the committed ratchet baseline: the baseline is canonical
 * and only names known ratchet rules, every rule is wired into exactly one test class, and no rule
 * is vacuous because its path scope matches nothing.
 */
final class Baseline_Integrity_Test extends TestCase {
    /** Test classes that run rules, with the id prefixes they own. */
    private const array RULE_OWNERS = [
        Domain_Layer_Test::class                    => [ 'domain.' ],
        Application_Layer_Test::class               => [ 'application.' ],
        Services_And_Repositories_Layer_Test::class => [ 'services.', 'repositories.' ],
        Cross_Cutting_Test::class                   => [ 'all.', 'templates.', 'admin.', 'scanner.' ],
    ];

    public function test_baseline_file_exists_and_is_canonically_sorted(): void {
        if ( Baseline::update_requested() ) {
            self::markTestSkipped( 'Baseline is being regenerated in this run.' );
        }

        $path = Baseline::default_path();
        self::assertFileExists( $path, 'Generate it with ' . Baseline::ENV_UPDATE . '=1 vendor/bin/phpunit --testsuite Architecture' );
        self::assertTrue(
            Baseline::load( $path )->is_sorted_on_disk( $path ),
            sprintf( '%s is not in canonical form (sorted keys, pretty-printed). Regenerate with %s=1 vendor/bin/phpunit --testsuite Architecture.', $path, Baseline::ENV_UPDATE )
        );
    }

    public function test_baseline_only_names_known_rules(): void {
        $unknown = array_diff( Baseline::load()->rule_ids(), Layer_Rules::ids() );

        self::assertSame( [], array_values( $unknown ), 'Baseline contains entries for rule ids that no longer exist in Layer_Rules: ' . implode( ', ', $unknown ) );
    }

    public function test_strict_rules_have_no_baseline_entries(): void {
        $baseline = Baseline::load();
        $offending = [];
        foreach ( Layer_Rules::all() as $rule ) {
            if ( $rule->is_strict() && $baseline->has_rule( $rule->id ) ) {
                $offending[] = sprintf( '%s (%d entries)', $rule->id, count( $baseline->entries_for( $rule->id ) ) );
            }
        }

        self::assertSame( [], $offending, 'Strict rules must not carry baseline debt: ' . implode( ', ', $offending ) );
    }

    public function test_every_rule_is_owned_by_exactly_one_test_class(): void {
        foreach ( Layer_Rules::ids() as $id ) {
            $owners = [];
            foreach ( self::RULE_OWNERS as $class => $prefixes ) {
                foreach ( $prefixes as $prefix ) {
                    if ( str_starts_with( $id, $prefix ) ) {
                        $owners[] = $class;
                        break;
                    }
                }
            }
            self::assertCount( 1, $owners, sprintf( 'Rule `%s` must be provided by exactly one test class, found: %s', $id, implode( ', ', $owners ) ?: 'none' ) );
        }
    }

    public function test_rule_ids_are_unique_and_well_formed(): void {
        $ids = Layer_Rules::ids();

        self::assertSame( $ids, array_values( array_unique( $ids ) ) );
        foreach ( $ids as $id ) {
            self::assertMatchesRegularExpression( '/^[a-z]+\.[a-z0-9_]+$/', $id, "Rule id `$id` must look like `<layer>.<snake_case>`" );
        }
        self::assertGreaterThanOrEqual( 25, count( $ids ) );
    }

    public function test_every_rule_scope_matches_scanned_files(): void {
        $files = Source_Scanner::scan_plugin();
        $paths = array_keys( $files );

        foreach ( Layer_Rules::all() as $rule ) {
            foreach ( [ ...$rule->path_prefixes, ...$rule->exclude_prefixes ] as $prefix ) {
                $hit = false;
                foreach ( $paths as $path ) {
                    if ( str_starts_with( $path, $prefix ) ) {
                        $hit = true;
                        break;
                    }
                }
                self::assertTrue( $hit, sprintf( 'Rule `%s` names path prefix `%s` which matches no scanned file (typo or stale scope would make the rule vacuous).', $rule->id, $prefix ) );
            }

            $in_scope = count( array_filter( $paths, static fn( string $path ): bool => $rule->applies_to( $path ) ) );
            self::assertGreaterThan( 0, $in_scope, sprintf( 'Rule `%s` applies to no scanned file.', $rule->id ) );
        }
    }

    public function test_every_rule_has_description_and_remediation(): void {
        foreach ( Layer_Rules::all() as $rule ) {
            self::assertInstanceOf( Layer_Rule::class, $rule );
            self::assertNotSame( '', trim( $rule->description ), $rule->id );
            self::assertNotSame( '', trim( $rule->remediation ), $rule->id );
        }
    }

    public function test_alias_rule_tracks_the_production_alias_map(): void {
        $aliases = Alias_Map::alias_names();

        self::assertNotEmpty( $aliases );
        self::assertSame( $aliases, Layer_Rules::get( 'all.no_alias_class_references' )->arguments );
        self::assertContains( 'Racketmanager\Domain\User', $aliases );
    }
}
