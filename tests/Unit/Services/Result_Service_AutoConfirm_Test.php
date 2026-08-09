<?php

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Result_Service;

class Result_Service_AutoConfirm_Test extends TestCase {
    private $fixture_repository;
    private $team_repository;
    private $result_service;

    protected function setUp(): void {
        parent::setUp();
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->team_repository    = $this->createMock( Team_Repository_Interface::class );
        
        $this->result_service = new Result_Service(
            $this->fixture_repository,
            $this->team_repository
        );

        // Mock global $racketmanager
        global $racketmanager;
        $racketmanager = new class {
            public $site_name = 'Test';
            public $site_url = 'http://test.local';
            public $shortcodes;
            public function __construct() {
                $this->shortcodes = new class {
                    public function load_template() { return 'template'; }
                };
            }
            public function get_options() {
                return [
                    'league' => [
                        'resultConfirmation' => 'auto'
                    ]
                ];
            }
            public function get_confirmation_email() { return 'test@test.com'; }
        };
    }

    /**
     * Test that if a match is already Pending (P), saving it again as a non-admin
     * does not promote it to Confirmed (Y) even when auto-confirm is enabled.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_subsequent_save_keeps_status_pending_when_not_admin(): void {
        $fixture_obj = (object)[
            'id' => 1,
            'league_id' => 10,
            'home_team' => 101,
            'away_team' => 102,
            'confirmed' => 'P', // Already Pending
            'season' => '2024'
        ];
        $fixture = new Fixture($fixture_obj);

        $result = new Result(
            home_points: 2.0,
            away_points: 1.0,
            status: 1,
            sets: [],
            custom: []
        );

        // Assume current_user_can('manage_racketmanager') returns false (not admin)
        // In our test environment, we expect it to be false by default or stubbed.

        // Apply result again with 'P'
        $this->result_service->apply_to_fixture($fixture, $result, 'P');

        // Status should remain 'P'
        $this->assertEquals('P', $fixture->get_confirmed(), 'Status should remain P on subsequent save by non-admin');
    }

    /**
     * Test that if a match is already Confirmed (Y), saving it again keeps it Confirmed (Y).
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_subsequent_save_keeps_status_confirmed_when_auto_on(): void {
        $fixture_obj = (object)[
            'id' => 1,
            'league_id' => 10,
            'home_team' => 101,
            'away_team' => 102,
            'confirmed' => 'Y', // Already Confirmed
            'season' => '2024'
        ];
        $fixture = new Fixture($fixture_obj);

        $result = new Result(
            home_points: 2.0,
            away_points: 1.0,
            status: 1,
            sets: [],
            custom: []
        );

        // Apply result
        $this->result_service->apply_to_fixture($fixture, $result, 'Y');

        // Status should remain 'Y'
        $this->assertEquals('Y', $fixture->get_confirmed(), 'Status should remain Y on subsequent save');
    }
}
