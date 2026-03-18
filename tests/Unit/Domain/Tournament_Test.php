<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO;
use Racketmanager\Domain\View_Models\Tournament_Email_View_Model;
use stdClass;

    require_once __DIR__ . '/../../wp-stubs.php';

    use function Racketmanager\seo_url;

final class Tournament_Test extends TestCase {

    public function test_set_tournament_info_sets_correct_data(): void {
        global $racketmanager;
        // Do not overwrite global $racketmanager if it is already set by stubs with necessary methods
        if ( ! isset( $racketmanager ) || ! method_exists( $racketmanager, 'get_options' ) ) {
            $racketmanager = new stdClass();
        }
        $racketmanager->site_url = 'http://example.com';
        $racketmanager->date_format = 'Y-m-d';

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Test Tournament';
        $data->date_end = '2026-12-31';
        $data->date_start = '2026-12-01';
        $data->date_open = '2026-11-01';
        $data->date_closing = '2026-11-30';
        $data->date_withdrawal = '2026-12-05';
        $data->season = '2026';

        $tournament = new Tournament($data);
        $view_model = new Tournament_Email_View_Model($tournament);

        $this->assertEquals('/tournament/test-tournament/', $tournament->link);
        $this->assertEquals('http://example.com/entry-form/test-tournament-tournament/', $tournament->entry_link);
        $this->assertEquals('2026-12-31', $view_model->date_display);
        $this->assertEquals('2026-11-30', $view_model->date_closing_display);
        $this->assertEquals('2026-12-05', $view_model->date_withdrawal_display);
        $this->assertEquals('2026-11-01', $view_model->date_open_display);
        $this->assertEquals('2026-12-01', $view_model->date_start_display);
        
        // $this->assertNotEmpty($tournament->current_phase);
        $this->assertIsArray($tournament->finals);
        $this->assertCount(6, $tournament->finals);
    }

    public function test_update_from_request(): void {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Original Name';
        $data->date = '2026-12-31';
        $tournament = new Tournament($data);

        $request_data = [
            'tournamentName'   => 'Updated Name',
            'competition_id'   => 10,
            'season'           => '2027',
            'venue'            => 5,
            'dateEnd'          => '2027-12-31',
            'dateClose'        => '2027-11-30',
            'dateWithdraw'     => '2027-12-05',
            'dateOpen'         => '2027-11-01',
            'dateStart'        => '2027-12-01',
            'competition_code' => 'COMP123',
            'grade'            => 'A',
            'num_entries'      => 32,
        ];
        $request = new Tournament_Request_DTO($request_data);

        $tournament->update_from_request($request);

        $this->assertEquals('Updated Name', $tournament->name);
        $this->assertEquals(10, $tournament->competition_id);
        $this->assertEquals('2027', $tournament->season);
        $this->assertEquals(5, $tournament->venue);
        $this->assertEquals('2027-12-31', $tournament->date_end);
        $this->assertEquals('2027-11-30', $tournament->date_closing);
        $this->assertEquals('2027-12-05', $tournament->date_withdrawal);
        $this->assertEquals('2027-11-01', $tournament->date_open);
        $this->assertEquals('2027-12-01', $tournament->date_start);
        $this->assertEquals('COMP123', $tournament->competition_code);
        $this->assertEquals('A', $tournament->grade);
        $this->assertEquals(32, $tournament->num_entries);
    }

    public function test_calculate_default_match_dates(): void {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Test';
        $data->date_end = '2026-12-31';
        $tournament = new Tournament($data);

        // finals[R1] = round 6
        // finals[R2] = round 5
        // finals[R4] = round 4
        // finals[QF] = round 3
        // finals[SF] = round 2
        // finals[F]  = round 1

        $match_dates = $tournament->calculate_default_match_dates(7);
        ksort($match_dates);

        // Final (round 1 - 1 = 0) should be date_end
        $this->assertEquals('2026-12-31', $match_dates[0]);
        // Semi-final (round 2 - 1 = 1) should be date_end - 7 days
        $this->assertEquals('2026-12-24', $match_dates[1]);
        // Quarter-final (round 3 - 1 = 2) should be previous - 7 days
        $this->assertEquals('2026-12-17', $match_dates[2]);
    }
}
