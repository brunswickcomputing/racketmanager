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
            unlink( $this->tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        }
        Alias_Map::reset_cache();
    }

    public function test_reads_the_production_alias_map_without_executing_it(): void {
        $map = Alias_Map::load();

        self::assertCount( 63, $map );
        self::assertSame( 'Racketmanager\Domain\Player\User', $map['Racketmanager\Domain\User'] );
        self::assertSame( 'Racketmanager\Domain\Club\Club', $map['Racketmanager\Domain\Club'] );
        self::assertSame( 'Racketmanager\Application\Club\Commands\Update_Club_Command', $map['Racketmanager\Domain\DTO\Club\Update_Club_Command'] );
        self::assertSame( 'Racketmanager\Presentation\View\Shortcode_Template_Renderer', $map['Racketmanager\Services\View\Shortcode_Template_Renderer'] );
        self::assertSame( 'Racketmanager\Application\Mail\Mailer_Interface', $map['Racketmanager\Gateways\Mail\Mailer_Interface'] );
        self::assertSame( 'Racketmanager\Application\Export\Ports\Data_Exporter_Interface', $map['Racketmanager\Services\Contracts\Data_Exporter_Interface'] );
        self::assertFalse( class_exists( 'Racketmanager\Domain\Nonexistent_Alias_Probe', false ) );
        self::assertSame(
            [ ...array_keys( $map ), ...array_keys( Alias_Map::RETIRED_VIEW_MODEL_ALIASES ) ],
            Alias_Map::alias_names()
        );
        self::assertSame( Alias_Map::alias_names(), array_values( array_unique( Alias_Map::alias_names() ) ) );

        self::assertCount( 2, Alias_Map::RETIRED_VIEW_MODEL_ALIASES );
        self::assertSame( 'Racketmanager\Admin\View_Models\Contact_Page_View_Model', Alias_Map::RETIRED_VIEW_MODEL_ALIASES['Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model'] );
        self::assertSame( 'Racketmanager\Admin\View_Models\Teams_List_Page_View_Model', Alias_Map::RETIRED_VIEW_MODEL_ALIASES['Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model'] );
    }

    public function test_alias_names_are_sorted_and_stripped_of_leading_backslash(): void {
        $this->tmp = tempnam( sys_get_temp_dir(), 'aliases' );
        file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            $this->tmp,
            "<?php\nnamespace Racketmanager;\n\$aliases = [\n    \\Foo\\Bar\\Target::class => '\\\\Foo\\\\Zeta',\n    'Foo\\\\Other\\\\Target' => 'Foo\\\\Alpha',\n];\nforeach ( \$aliases as \$t => \$a ) { class_alias( \$t, \$a ); }\n"
        );

        self::assertSame( [ 'Foo\Alpha' => 'Foo\Other\Target', 'Foo\Zeta' => 'Foo\Bar\Target' ], Alias_Map::load( $this->tmp ) );
        self::assertSame( [ 'Foo\Alpha', 'Foo\Zeta' ], Alias_Map::alias_names( $this->tmp ) );
    }

    public function test_file_without_alias_array_is_rejected(): void {
        $this->tmp = tempnam( sys_get_temp_dir(), 'aliases' );
        file_put_contents( $this->tmp, "<?php\n\$other = [ 'a' => 'b' ];\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        $this->expectException( RuntimeException::class );
        Alias_Map::load( $this->tmp );
    }
}
