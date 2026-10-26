<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Racketmanager\Util\Util_Lookup;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Util_Lookup_Event_Types_Test extends TestCase {

    public function test_event_types_include_team_tennis_labels(): void {
        $types = Util_Lookup::get_event_types();

        $this->assertSame( 'Male Team Tennis', $types['MT'] );
        $this->assertSame( 'Female Team Tennis', $types['WT'] );
        $this->assertSame( 'Mixed Team Tennis', $types['XT'] );
        $this->assertSame( 'Open Team Tennis', $types['OT'] );
        // Existing entries are unchanged.
        $this->assertSame( 'Mixed Doubles', $types['XD'] );
        $this->assertSame( 'The League', $types['LD'] );
    }

    public function test_get_event_type_resolves_team_types(): void {
        $this->assertSame( 'Mixed Team Tennis', Util_Lookup::get_event_type( 'XT' ) );
        $this->assertSame( 'Unknown', Util_Lookup::get_event_type( 'ZZ' ) );
    }
}
