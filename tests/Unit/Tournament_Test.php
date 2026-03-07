<?php
declare(strict_types=1);

namespace Racketmanager;
function seo_url(string $name): string {
    return strtolower(str_replace(' ', '-', $name));
}

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament;
use stdClass;

require_once __DIR__ . '/../wp-stubs.php';

final class Tournament_Test extends TestCase {

    public function test_set_tournament_info_sets_correct_data(): void {
        global $racketmanager;
        $racketmanager = new stdClass();
        $racketmanager->site_url = 'http://example.com';
        $racketmanager->date_format = 'Y-m-d';

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Test Tournament';
        $data->date = '2026-12-31';
        $data->date_start = '2026-12-01';
        $data->date_open = '2026-11-01';
        $data->date_closing = '2026-11-30';
        $data->date_withdrawal = '2026-12-05';
        $data->season = '2026';

        $tournament = new Tournament($data);

        $this->assertEquals('/tournament/test-tournament/', $tournament->link);
        $this->assertEquals('http://example.com/entry-form/test-tournament-tournament/', $tournament->entry_link);
        $this->assertEquals('2026-12-31', $tournament->date_display);
        $this->assertEquals('2026-11-30', $tournament->date_closing_display);
        $this->assertEquals('2026-12-05', $tournament->date_withdrawal_display);
        $this->assertEquals('2026-11-01', $tournament->date_open_display);
        $this->assertEquals('2026-12-01', $tournament->date_start_display);
        
        // $this->assertNotEmpty($tournament->current_phase);
        $this->assertIsArray($tournament->finals);
        $this->assertCount(6, $tournament->finals);
    }
}
