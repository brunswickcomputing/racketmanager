<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Domain\Shared\Slug;
use Racketmanager\Util\Util;

final class Seo_Slug_Test extends TestCase {

    /**
     * Verbatim copy of the legacy `Racketmanager\seo_url()` (functions.php, pre P2-7).
     */
    private static function legacy_seo_url( string $string_field ): string {
        $string_field = strtolower( $string_field );
        $string_field = preg_replace( '/[^a-z0-9_\s-]/', '', $string_field );
        $string_field = preg_replace( '/\s+/', ' ', $string_field );
        $string_field = str_replace( '-', '_', $string_field );
        return preg_replace( '/\s/', '-', $string_field );
    }

    /**
     * Verbatim copy of the legacy `Racketmanager\un_seo_url()` (functions.php, pre P2-7).
     */
    private static function legacy_un_seo_url( string $string_field ): string {
        $string_field = str_replace( '-', ' ', $string_field );
        $string_field = str_replace( '_', '-', $string_field );
        return strtolower( $string_field );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function encode_samples(): iterable {
        yield 'spaces'                   => [ 'Mixed Doubles', 'mixed-doubles' ];
        yield 'apostrophe is dropped'    => [ "Men's Singles", 'mens-singles' ];
        yield 'curly apostrophe'         => [ 'Men’s Doubles', 'mens-doubles' ];
        yield 'dash becomes underscore'  => [ 'Over-45 Mixed', 'over_45-mixed' ];
        yield 'existing underscore kept' => [ 'ALREADY_under', 'already_under' ];
        yield 'leading/trailing spaces'  => [ '  Two   spaces ', '-two-spaces-' ];
        yield 'tab and newline'          => [ "Tab\tNew\nLine", 'tab-new-line' ];
        yield 'ampersand'                => [ 'Café & Bar', 'caf-bar' ];
        yield 'accents stripped'         => [ 'Niño Ñandú', 'nio-and' ];
        yield 'non-latin stripped'       => [ 'Группа A', '-a' ];
        yield 'symbols only'             => [ '!!!', '' ];
        yield 'slash and dot'            => [ 'Boys U10/U12 v.2', 'boys-u10u12-v2' ];
        yield 'html tags kept as text'   => [ '<b>Boys</b> U10', 'bboysb-u10' ];
        yield 'percent'                  => [ '100% Fun', '100-fun' ];
        yield 'digits'                   => [ 'Division 1', 'division-1' ];
        yield 'empty'                    => [ '', '' ];
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function decode_samples(): iterable {
        yield 'dashes to spaces'       => [ 'mixed-doubles', 'mixed doubles' ];
        yield 'underscore to dash'     => [ 'over_45-mixed', 'over-45 mixed' ];
        yield 'lower-cased'            => [ 'Mixed-DOUBLES', 'mixed doubles' ];
        yield 'leading/trailing dash'  => [ '-two-spaces-', ' two spaces ' ];
        yield 'no separators'          => [ 'singles', 'singles' ];
        yield 'empty'                  => [ '', '' ];
    }

    #[DataProvider( 'encode_samples' )]
    public function test_encode_matches_expected_and_legacy_algorithm( string $input, string $expected ): void {
        $this->assertSame( $expected, Seo_Slug::encode( $input ) );
        $this->assertSame( self::legacy_seo_url( $input ), Seo_Slug::encode( $input ) );
    }

    #[DataProvider( 'decode_samples' )]
    public function test_decode_matches_expected_and_legacy_algorithm( string $input, string $expected ): void {
        $this->assertSame( $expected, Seo_Slug::decode( $input ) );
        $this->assertSame( self::legacy_un_seo_url( $input ), Seo_Slug::decode( $input ) );
    }

    #[DataProvider( 'encode_samples' )]
    public function test_util_helpers_delegate_to_the_codec( string $input, string $expected ): void {
        $this->assertSame( $expected, Util::seo_url( $input ) );
        $this->assertSame( Seo_Slug::encode( $input ), Util::seo_url( $input ) );
        $this->assertSame( Seo_Slug::decode( $input ), Util::un_seo_url( $input ) );
    }

    public function test_round_trip_restores_lower_case_name_with_single_spaces(): void {
        foreach ( [ 'Mixed Doubles', 'Over-45 Mixed', 'Division 1', 'CLUB-A' ] as $name ) {
            $this->assertSame( strtolower( $name ), Seo_Slug::decode( Seo_Slug::encode( $name ) ) );
        }
    }

    public function test_round_trip_is_lossy_for_stripped_characters(): void {
        $this->assertSame( 'mens singles', Seo_Slug::decode( Seo_Slug::encode( "Men's Singles" ) ) );
        $this->assertSame( 'caf bar', Seo_Slug::decode( Seo_Slug::encode( 'Café & Bar' ) ) );
    }

    public function test_is_a_different_codec_from_wordpress_slug_normalise(): void {
        $this->assertSame( 'over_45-mixed', Seo_Slug::encode( 'Over-45 Mixed' ) );
        $this->assertSame( 'over-45-mixed', Slug::normalise( 'Over-45 Mixed' ) );
        $this->assertSame( 'caf-bar', Seo_Slug::encode( 'Café & Bar' ) );
        $this->assertSame( 'cafe-bar', Slug::normalise( 'Café & Bar' ) );
    }
}
