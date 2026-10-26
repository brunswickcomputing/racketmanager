<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter;
use Racketmanager\Domain\Competition\Competition;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

final class Admin_League_Schedule_Presenter_Test extends TestCase {

    private Admin_League_Schedule_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Admin_League_Schedule_Presenter();
    }

    public function test_present_without_report_leaves_schedule_report_empty(): void {
        $vm = $this->presenter->present( $this->competition(), '2026', array() );

        $this->assertSame( array(), $vm->schedule_report );
        $this->assertFalse( $vm->has_schedule_report() );
        $this->assertArrayHasKey( 'schedule_report', $vm->to_template_vars() );
        $this->assertSame( array(), $vm->to_template_vars()['schedule_report'] );
    }

    public function test_present_normalises_flash_report(): void {
        $report = array(
            'retry'             => '7',
            'scheduled'         => '30',
            'conflicts'         => array(
                3 => array( 'week' => 2, 'division' => 'Div 1', 'court' => '5_Mon 18:00_C0', 'home' => 'Club A', 'with' => 'Club B' ),
            ),
            'alerts'            => array(
                array( 'type' => 'equity_compromise', 'team_name' => 'Club A', 'division' => 'Div 1', 'count' => 6, 'ideal' => 5 ),
            ),
            'resource_pairings' => array(
                '5_Mon 18:00_C0' => array(
                    array( 'id' => 1, 'name' => 'Club A', 'division' => 'Div 1' ),
                    array( 'id' => 2, 'name' => 'Club A Ladies', 'division' => 'Ladies 1' ),
                ),
                'broken'          => 'not-an-array',
            ),
        );

        $vm = $this->presenter->present( $this->competition(), '2026', array(), $report );

        $this->assertTrue( $vm->has_schedule_report() );
        $this->assertSame( 7, $vm->schedule_report['retry'] );
        $this->assertSame( 30, $vm->schedule_report['scheduled'] );
        $this->assertSame( array( 0 ), array_keys( $vm->schedule_report['conflicts'] ) );
        $this->assertSame( 'Club B', $vm->schedule_report['conflicts'][0]['with'] );
        $this->assertCount( 1, $vm->schedule_report['alerts'] );
        $this->assertSame( array( '5_Mon 18:00_C0' ), array_keys( $vm->schedule_report['resource_pairings'] ) );
        $this->assertCount( 2, $vm->schedule_report['resource_pairings']['5_Mon 18:00_C0'] );
    }

    public function test_present_ignores_unrelated_flash_data(): void {
        $vm = $this->presenter->present( $this->competition(), '2026', array(), array( 'form_data' => array( 'x' => 1 ) ) );

        $this->assertSame( array(), $vm->schedule_report );
        $this->assertFalse( $vm->has_schedule_report() );
    }

    public function test_report_with_only_counts_is_kept_but_not_flagged_for_display(): void {
        $vm = $this->presenter->present( $this->competition(), '2026', array(), array( 'retry' => 1, 'scheduled' => 10 ) );

        $this->assertSame( 1, $vm->schedule_report['retry'] );
        $this->assertSame( 10, $vm->schedule_report['scheduled'] );
        $this->assertFalse( $vm->has_schedule_report() );
    }

    private function competition(): Competition {
        return new Competition( name: 'Leagues', type: 'league', age_group: 'open', id: 10 );
    }
}
