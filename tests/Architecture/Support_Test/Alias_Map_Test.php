<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support_Test;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Alias_Map;
use RuntimeException;

final class Alias_Map_Test extends TestCase {
    private string $tmp = '';

    protected function tearDown(): void {
        if ( '' !== $this->tmp && is_file( $this->tmp ) ) {
            unlink( $this->tmp );
        }
        Alias_Map::reset_cache();
    }

    public function test_reads_the_production_alias_map_without_executing_it(): void {
        $map = Alias_Map::load();

        self::assertGreaterThanOrEqual( 29, count( $map ) );
        self::assertSame( 'Racketmanager\Domain\Player\User', $map['Racketmanager\Domain\User'] );
        self::assertSame( 'Racketmanager\Domain\Club\Club', $map['Racketmanager\Domain\Club'] );
        self::assertSame( 'Racketmanager\Application\Club\Commands\Update_Club_Command', $map['Racketmanager\Domain\DTO\Club\Update_Club_Command'] );
        self::assertFalse( class_exists( 'Racketmanager\Domain\Nonexistent_Alias_Probe', false ) );
        self::assertSame( array_keys( $map ), Alias_Map::alias_names() );
        self::assertSame( Alias_Map::alias_names(), array_values( array_unique( Alias_Map::alias_names() ) ) );
    }

    public function test_alias_names_are_sorted_and_stripped_of_leading_backslash(): void {
        $this->tmp = tempnam( sys_get_temp_dir(), 'aliases' );
        file_put_contents(
            $this->tmp,
            "<?php\nnamespace Racketmanager;\n\$aliases = [\n    \\Foo\\Bar\\Target::class => '\\\\Foo\\\\Zeta',\n    'Foo\\\\Other\\\\Target' => 'Foo\\\\Alpha',\n];\nforeach ( \$aliases as \$t => \$a ) { class_alias( \$t, \$a ); }\n"
        );

        self::assertSame( [ 'Foo\Alpha' => 'Foo\Other\Target', 'Foo\Zeta' => 'Foo\Bar\Target' ], Alias_Map::load( $this->tmp ) );
        self::assertSame( [ 'Foo\Alpha', 'Foo\Zeta' ], Alias_Map::alias_names( $this->tmp ) );
    }

    public function test_file_without_alias_array_is_rejected(): void {
        $this->tmp = tempnam( sys_get_temp_dir(), 'aliases' );
        file_put_contents( $this->tmp, "<?php\n\$other = [ 'a' => 'b' ];\n" );

        $this->expectException( RuntimeException::class );
        Alias_Map::load( $this->tmp );
    }
}
