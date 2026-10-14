<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Domain\Competition\Competition_Type;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

final class Admin_League_Matches_Presenter_Test extends TestCase {

    private Admin_League_Matches_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );
        $this->presenter             = new Admin_League_Matches_Presenter( new Admin_Date_Presenter() );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        parent::tearDown();
    }

    public function test_present_collection_skips_non_objects_and_presents_matches(): void {
        $league            = new stdClass();
        $league->id        = 10;
        $league->event     = (object) array(
            'competition' => (object) array(
                'type' => Competition_Type::LEAGUE,
            ),
        );

        $match1          = new stdClass();
        $match1->id      = 1;
        $match1->date    = '2026-05-01 14:00:00';
        $match1->hour    = '14';
        $match1->minutes = '00';

        $match2          = new stdClass();
        $match2->id      = 2;
        $match2->date    = '2026-05-02 16:30:00';
        $match2->hour    = '16';
        $match2->minutes = '30';

        $rows = $this->presenter->present_collection( array( $match1, null, 'invalid', $match2 ), $league, '2026', 'Group A' );

        $this->assertCount( 2, $rows );
        $this->assertSame( 1, $rows[0]->id );
        $this->assertSame( '1 May 2026', $rows[0]->formatted_date );
        $this->assertSame( '14:00', $rows[0]->formatted_time );
        $this->assertSame( 2, $rows[1]->id );
        $this->assertSame( '2 May 2026', $rows[1]->formatted_date );
        $this->assertSame( '16:30', $rows[1]->formatted_time );
    }

    public function test_present_with_enum_competition_type_and_group_url_encoding(): void {
        $league        = new stdClass();
        $league->id    = 42;
        $league->event = (object) array(
            'competition' => (object) array(
                'type' => Competition_Type::LEAGUE,
            ),
        );

        $match           = new stdClass();
        $match->id       = 105;
        $match->date     = '2026-06-15 19:00:00';
        $match->hour     = '19';
        $match->minutes  = '00';
        $match->location = 'Court 3';

        $vm = $this->presenter->present( $match, $league, '2026/2027', 'Men & Mixed' );

        $this->assertSame( '15 Jun 2026', $vm->formatted_date );
        $this->assertSame( '19:00', $vm->formatted_time );
        $this->assertSame( 'Court 3', $vm->formatted_location );
        $this->assertSame(
            '/wp-admin/admin.php?page=racketmanager-leagues&view=match&league_id=42&edit=105&season=2026%2F2027&group=Men%20%26%20Mixed',
            $vm->edit_url
        );
    }

    public function test_present_with_string_competition_type(): void {
        $league        = new stdClass();
        $league->id    = 7;
        $league->event = (object) array(
            'competition' => (object) array(
                'type' => 'cup',
            ),
        );

        $match       = new stdClass();
        $match->id   = 22;
        $match->date = '2026-07-20 11:00:00';
        $match->hour = '11';

        $vm = $this->presenter->present( $match, $league, '2026' );

        $this->assertSame(
            '/wp-admin/admin.php?page=racketmanager-cups&view=match&league_id=7&edit=22&season=2026',
            $vm->edit_url
        );
    }

    public function test_time_and_date_edge_cases(): void {
        $league        = new stdClass();
        $league->id    = 1;
        $league->event = (object) array(
            'competition' => (object) array(
                'type' => Competition_Type::LEAGUE,
            ),
        );

        // Case 1: 00:00 should return N/A
        $match1          = new stdClass();
        $match1->date    = '2026-05-01 00:00:00';
        $match1->hour    = '00';
        $match1->minutes = '00';
        $vm1             = $this->presenter->present( $match1, $league, '2026' );
        $this->assertSame( 'N/A', $vm1->formatted_time );

        // Case 2: hour '0' and minutes '30' should format time
        $match2          = new stdClass();
        $match2->date    = '2026-05-01 00:30:00';
        $match2->hour    = '0';
        $match2->minutes = '30';
        $vm2             = $this->presenter->present( $match2, $league, '2026' );
        $this->assertSame( '00:30', $vm2->formatted_time );

        // Case 3: missing/empty hour should return N/A
        $match3       = new stdClass();
        $match3->date = '2026-05-01 15:00:00';
        $match3->hour = null;
        $vm3          = $this->presenter->present( $match3, $league, '2026' );
        $this->assertSame( 'N/A', $vm3->formatted_time );

        // Case 4: 0000-00-00 date should return N/A
        $match4       = new stdClass();
        $match4->date = '0000-00-00';
        $vm4          = $this->presenter->present( $match4, $league, '2026' );
        $this->assertSame( 'N/A', $vm4->formatted_date );

        // Case 5: empty location should return N/A
        $match5           = new stdClass();
        $match5->location = '';
        $vm5              = $this->presenter->present( $match5, $league, '2026' );
        $this->assertSame( 'N/A', $vm5->formatted_location );
    }
}
