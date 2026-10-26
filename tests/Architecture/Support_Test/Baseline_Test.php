<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support_Test;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Baseline;
use Racketmanager\Tests\Architecture\Support\Violation;
use RuntimeException;

final class Baseline_Test extends TestCase {
    private string $path;
    private string|false $original_env = false;

    protected function setUp(): void {
        parent::setUp();
        $this->path         = tempnam( sys_get_temp_dir(), 'rm-arch-baseline-' ) . '.json';
        $this->original_env = getenv( Baseline::ENV_UPDATE );
        putenv( Baseline::ENV_UPDATE );
    }

    protected function tearDown(): void {
        foreach ( [ $this->path, substr( $this->path, 0, -5 ) ] as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
        putenv( false === $this->original_env ? Baseline::ENV_UPDATE : Baseline::ENV_UPDATE . '=' . $this->original_env );
        parent::tearDown();
    }

    private function violation( string $file, string $detail, int $line = 1, string $rule = 'r.one' ): Violation {
        return new Violation( $rule, $file, $line, $detail );
    }

    public function test_missing_file_loads_as_empty_baseline(): void {
        $baseline = Baseline::load( $this->path . '.missing' );

        self::assertSame( [], $baseline->rule_ids() );
        self::assertSame( [], $baseline->entries_for( 'r.one' ) );
        self::assertFalse( $baseline->has_rule( 'r.one' ) );
    }

    public function test_invalid_json_is_rejected_loudly(): void {
        file_put_contents( $this->path, '{ not json' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'not valid JSON' );

        Baseline::load( $this->path );
    }

    public function test_known_violation_is_accepted_and_nothing_is_new(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'a.php|$wpdb' => 2 ] ] );

        $diff = $baseline->compare( 'r.one', [ $this->violation( 'a.php', '$wpdb', 10 ), $this->violation( 'a.php', '$wpdb', 20 ) ] );

        self::assertFalse( $diff->has_new_violations() );
        self::assertFalse( $diff->has_stale_entries() );
        self::assertSame( 2, $diff->accepted );
        self::assertSame( 2, $diff->total );
    }

    public function test_violation_in_another_file_or_with_other_detail_is_new(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'a.php|$wpdb' => 1 ] ] );

        $diff = $baseline->compare( 'r.one', [
            $this->violation( 'a.php', '$wpdb' ),
            $this->violation( 'b.php', '$wpdb', 5 ),
            $this->violation( 'a.php', '$_POST', 7 ),
        ] );

        self::assertTrue( $diff->has_new_violations() );
        self::assertSame( [ 'a.php|$_POST', 'b.php|$wpdb' ], array_keys( $diff->new ) );
        self::assertSame( 0, $diff->new['b.php|$wpdb']['allowed'] );
        self::assertSame( 2, $diff->new_violation_count() );
        self::assertSame( 1, $diff->accepted );
    }

    public function test_exceeding_the_tolerated_count_for_a_key_is_new(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'a.php|$wpdb' => 2 ] ] );

        $diff = $baseline->compare( 'r.one', [
            $this->violation( 'a.php', '$wpdb', 1 ),
            $this->violation( 'a.php', '$wpdb', 2 ),
            $this->violation( 'a.php', '$wpdb', 3 ),
        ] );

        self::assertTrue( $diff->has_new_violations() );
        self::assertSame( [ 'count' => 3, 'allowed' => 2 ], array_intersect_key( $diff->new['a.php|$wpdb'], [ 'count' => 1, 'allowed' => 1 ] ) );
        self::assertCount( 3, $diff->new['a.php|$wpdb']['violations'], 'every occurrence is reported because the new one cannot be identified' );
    }

    public function test_removed_debt_is_stale_not_failing(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'a.php|$wpdb' => 3, 'gone.php|$wpdb' => 1 ] ] );

        $diff = $baseline->compare( 'r.one', [ $this->violation( 'a.php', '$wpdb' ) ] );

        self::assertFalse( $diff->has_new_violations() );
        self::assertTrue( $diff->has_stale_entries() );
        self::assertSame(
            [
                'a.php|$wpdb'    => [ 'allowed' => 3, 'count' => 1 ],
                'gone.php|$wpdb' => [ 'allowed' => 1, 'count' => 0 ],
            ],
            $diff->stale
        );
        self::assertSame( 1, $diff->accepted );
    }

    public function test_baseline_keys_are_line_independent(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'a.php|$wpdb' => 1 ] ] );

        $moved = $baseline->compare( 'r.one', [ $this->violation( 'a.php', '$wpdb', 999 ) ] );

        self::assertFalse( $moved->has_new_violations() );
        self::assertFalse( $moved->has_stale_entries() );
    }

    public function test_rules_are_isolated_from_each_other(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'a.php|$wpdb' => 1 ] ] );

        $diff = $baseline->compare( 'r.two', [ $this->violation( 'a.php', '$wpdb', 1, 'r.two' ) ] );

        self::assertTrue( $diff->has_new_violations() );
    }

    public function test_with_rule_violations_replaces_only_that_rule_and_counts_duplicates(): void {
        $baseline = new Baseline( [ 'r.one' => [ 'old.php|x' => 1 ], 'r.two' => [ 'keep.php|y' => 1 ] ] );

        $updated = $baseline->with_rule_violations( 'r.one', [
            $this->violation( 'a.php', '$wpdb', 1 ),
            $this->violation( 'a.php', '$wpdb', 2 ),
            $this->violation( 'b.php', '$wpdb', 3 ),
        ] );

        self::assertSame( [ 'r.one' => [ 'a.php|$wpdb' => 2, 'b.php|$wpdb' => 1 ], 'r.two' => [ 'keep.php|y' => 1 ] ], $updated->to_array() );
        self::assertSame( [ 'old.php|x' => 1 ], $baseline->entries_for( 'r.one' ), 'the original baseline is immutable' );
        self::assertSame( [ 'r.two' => [ 'keep.php|y' => 1 ] ], $updated->without_rule( 'r.one' )->to_array() );
        self::assertSame( [ 'r.two' => [ 'keep.php|y' => 1 ] ], $updated->with_rule_violations( 'r.one', [] )->to_array(), 'an empty list removes the rule' );
    }

    public function test_write_is_sorted_pretty_printed_and_deterministic(): void {
        $baseline = new Baseline( [
            'z.rule' => [ 'b.php|detail' => 1, 'a.php|detail' => 2 ],
            'a.rule' => [ 'src/php/X.php|missing declare(strict_types=1)' => 1 ],
        ] );

        $baseline->write( $this->path );
        $first = file_get_contents( $this->path );
        Baseline::load( $this->path )->write( $this->path );
        $second = file_get_contents( $this->path );

        self::assertSame( $first, $second, 'writing a loaded baseline again must not change the file' );
        self::assertSame(
            <<<'JSON'
            {
                "a.rule": {
                    "src/php/X.php|missing declare(strict_types=1)": 1
                },
                "z.rule": {
                    "a.php|detail": 2,
                    "b.php|detail": 1
                }
            }

            JSON,
            $first
        );
        self::assertTrue( $baseline->is_sorted_on_disk( $this->path ) );
    }

    public function test_unsorted_file_on_disk_is_detected(): void {
        file_put_contents( $this->path, "{\"z\": {\"k|d\": 1}, \"a\": {\"k|d\": 1}}\n" );

        $baseline = Baseline::load( $this->path );

        self::assertSame( [ 'a', 'z' ], $baseline->rule_ids() );
        self::assertFalse( $baseline->is_sorted_on_disk( $this->path ) );
    }

    public function test_empty_baseline_serialises_to_empty_object(): void {
        self::assertSame( "{}\n", ( new Baseline() )->to_json() );
    }

    public function test_compact_list_form_and_invalid_counts_are_normalised(): void {
        $baseline = new Baseline( [
            'r.one' => [ 'a.php|x', 'a.php|x', 'b.php|y' ],
            'r.two' => [ 'c.php|z' => 0, 'd.php|w' => '3', 'e.php|v' => 'nope' ],
            'r.bad' => 'not an array',
            'r.empty' => [],
        ] );

        self::assertSame(
            [ 'r.one' => [ 'a.php|x' => 2, 'b.php|y' => 1 ], 'r.two' => [ 'd.php|w' => 3 ] ],
            $baseline->to_array()
        );
        self::assertSame( 3, $baseline->count_for( 'r.one' ) );
    }

    public function test_update_requested_reads_the_environment_flag(): void {
        self::assertFalse( Baseline::update_requested() );

        putenv( Baseline::ENV_UPDATE . '=1' );
        self::assertTrue( Baseline::update_requested() );

        putenv( Baseline::ENV_UPDATE . '=0' );
        self::assertFalse( Baseline::update_requested() );

        putenv( Baseline::ENV_UPDATE . '=false' );
        self::assertFalse( Baseline::update_requested() );

        putenv( Baseline::ENV_UPDATE . '=yes' );
        self::assertTrue( Baseline::update_requested() );
    }

    public function test_default_path_is_the_committed_baseline(): void {
        self::assertStringEndsWith( '/tests/Architecture/baseline.json', str_replace( '\\', '/', Baseline::default_path() ) );
    }
}
