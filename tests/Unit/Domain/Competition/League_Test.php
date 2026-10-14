<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class League_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wpdb'] = new \wpdb();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_constructs_league_with_competition_settings_object(): void {
        $league_raw = (object) [
            'id'       => 1,
            'title'    => 'Division 1',
            'event_id' => 10,
            'settings' => [],
            'seasons'  => [ '2024-25' ],
        ];

        $league = new League( $league_raw );

        $this->assertSame( 1, $league->get_id() );
        $this->assertSame( 'Division 1', $league->get_name() );
        $this->assertSame( 'league', $league->mode );
        $this->assertSame( 'three', $league->point_rule );
        $this->assertSame( 'tennis', $league->sport );
    }
}
