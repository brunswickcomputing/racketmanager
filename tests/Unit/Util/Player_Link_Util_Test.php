<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Racketmanager\Util\Player_Link_Util;
use Racketmanager\Util\Util;

final class Player_Link_Util_Test extends TestCase {
    public function test_generate_link_simple_name_without_btm(): void {
        $link = Player_Link_Util::generate_link( 'John Smith' );
        $this->assertSame( '/player/john-smith/', $link );
        $this->assertSame( 'john smith', Util::un_seo_url( 'john-smith' ) );
    }

    public function test_generate_link_simple_name_with_btm(): void {
        $link = Player_Link_Util::generate_link( 'John Smith', '123456' );
        $this->assertSame( '/player/john-smith/123456/', $link );
    }

    public function test_generate_link_hyphenated_name_preserves_reversible_slug(): void {
        $link = Player_Link_Util::generate_link( 'Mary-Jane Smith' );
        $this->assertSame( '/player/mary_jane-smith/', $link );

        // Must round-trip correctly via un_seo_url
        $slug = trim( str_replace( '/player/', '', $link ), '/' );
        $this->assertSame( 'mary-jane smith', Util::un_seo_url( $slug ) );
    }

    public function test_generate_link_hyphenated_name_with_btm(): void {
        $link = Player_Link_Util::generate_link( 'Mary-Jane Smith', '987654' );
        $this->assertSame( '/player/mary_jane-smith/987654/', $link );
    }

    public function test_generate_link_accented_and_apostrophe_names(): void {
        $link = Player_Link_Util::generate_link( "Seán O'Brien" );
        $this->assertSame( '/player/sen-obrien/', $link );
    }

    public function test_generate_link_multiple_spaces_and_underscores(): void {
        $link = Player_Link_Util::generate_link( 'Smi_th  Two' );
        $this->assertSame( '/player/smi_th-two/', $link );
    }

    public function test_generate_link_handles_null_or_empty_btm(): void {
        $this->assertSame( '/player/jane-doe/', Player_Link_Util::generate_link( 'Jane Doe', null ) );
        $this->assertSame( '/player/jane-doe/', Player_Link_Util::generate_link( 'Jane Doe', '' ) );
    }
}
