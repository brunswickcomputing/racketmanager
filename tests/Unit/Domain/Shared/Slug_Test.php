<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Shared\Slug;

final class Slug_Test extends TestCase {

    /**
     * Expected values were produced by WordPress core `sanitize_title()` (wp-includes/formatting.php,
     * `remove_accents()` + `sanitize_title_with_dashes( …, '', 'save' )`, locale en_GB).
     *
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function wordpress_sanitize_title_samples(): iterable {
        yield 'spaces'                 => [ 'Mixed Doubles', 'mixed-doubles' ];
        yield 'already a slug'         => [ 'mixed-doubles', 'mixed-doubles' ];
        yield 'apostrophe is dropped'  => [ "Men's Singles", 'mens-singles' ];
        yield 'curly apostrophe'       => [ 'Men’s Doubles', 'mens-doubles' ];
        yield 'accents'                => [ 'Café Été', 'cafe-ete' ];
        yield 'tilde'                  => [ 'Niño Ñandú', 'nino-nandu' ];
        yield 'ligatures'              => [ 'Æther Øst', 'aether-ost' ];
        yield 'polish'                 => [ 'Łódź Cup', 'lodz-cup' ];
        yield 'sharp s'                => [ 'Straße', 'strase' ];
        yield 'ampersand'              => [ "Ladies' Singles & Over-45s", 'ladies-singles-over-45s' ];
        yield 'entity'                 => [ 'Rock&amp;Roll', 'rockroll' ];
        yield 'html tags'              => [ '<b>Boys</b> U10', 'boys-u10' ];
        yield 'slash'                  => [ 'Boys U10/U12', 'boys-u10-u12' ];
        yield 'dot'                    => [ 'Girls U.14', 'girls-u-14' ];
        yield 'en dash'                => [ 'Group A – Division 1', 'group-a-division-1' ];
        yield 'underscore kept'        => [ 'Mixed_Doubles', 'mixed_doubles' ];
        yield 'percent'                => [ '100% Fun', '100-fun' ];
        yield 'repeated whitespace'    => [ 'Ladies  Doubles  ', 'ladies-doubles' ];
        yield 'tab is not whitespace'  => [ "Tab\tSeparated", 'tabseparated' ];
        yield 'mixed'                  => [ "Over-45s Men's Doubles 2026", 'over-45s-mens-doubles-2026' ];
        yield 'empty'                  => [ '', '' ];
    }

    #[DataProvider( 'wordpress_sanitize_title_samples' )]
    public function test_matches_wordpress_sanitize_title( string $input, string $expected ): void {
        $this->assertSame( $expected, Slug::normalise( $input ) );
    }

    public function test_name_and_slug_forms_compare_equal(): void {
        $this->assertSame( Slug::normalise( 'Mixed Doubles' ), Slug::normalise( 'mixed-doubles' ) );
        $this->assertSame( Slug::normalise( "Men's Singles" ), Slug::normalise( 'mens-singles' ) );
        $this->assertNotSame( Slug::normalise( 'Mixed Doubles' ), Slug::normalise( 'Ladies Doubles' ) );
    }

    public function test_is_idempotent(): void {
        $once = Slug::normalise( "Café & Men's U.14 Doubles" );

        $this->assertSame( $once, Slug::normalise( $once ) );
    }
}
