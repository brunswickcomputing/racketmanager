<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\View_Models;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Plan_Final_Card_View_Model;
use Racketmanager\Admin\View_Models\League_Event_Page_View_Model;
use Racketmanager\Presentation\View_Models\League_Archive_View_Model;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Application\League\DTOs\League_View_State;

final class ViewModel_Compatibility_Test extends TestCase {

    public function test_plan_final_card_vm_compatibility(): void {
        $vm = new Plan_Final_Card_View_Model( id: 1, league_name: 'Div 1', btn_type: 'a', home_title: 'H', away_title: 'A', is_home_pending: false, is_away_pending: false );
        $this->assertSame( 'Div 1', $vm->league_name );
        $this->assertSame( 'Div 1', $vm->league_title );
    }

    public function test_league_archive_vm_compatibility(): void {
        $vm = new League_Archive_View_Model( league_id: 1, league_name: 'Div 1', league_seo_name: 'div-1', competition_type: 'league', icon_path: '', icon_url: '', calendar_icon_url: '', event_name: '', event_url: '', competition_name: '', competition_url: '', current_season_name: '' );
        $this->assertSame( 'Div 1', $vm->league_name );
        $this->assertSame( 'Div 1', $vm->league_title );
    }

    public function test_team_match_row_vm_compatibility(): void {
        $vm = new Team_Match_Row_View_Model( league_name: 'Div 1' );
        $this->assertSame( 'Div 1', $vm->league_name );
        $this->assertSame( 'Div 1', $vm->league_title );
    }

    public function test_tournament_match_card_vm_compatibility(): void {
        $vm = new Tournament_Match_Card_View_Model( league_name: 'Div 1' );
        $this->assertSame( 'Div 1', $vm->league_name );
        $this->assertSame( 'Div 1', $vm->league_title );
    }

    public function test_league_event_page_vm_compatibility(): void {
        $vm = new League_Event_Page_View_Model( event: (object)[], season: '2026', league_name: 'Div 1' );
        $this->assertSame( 'Div 1', $vm->league_name );
        $this->assertSame( 'Div 1', $vm->league_title );
    }

    public function test_plan_final_card_vm_requires_args(): void {
        $this->expectException( \ArgumentCountError::class );
        new Plan_Final_Card_View_Model( id: 1 );
    }
}
