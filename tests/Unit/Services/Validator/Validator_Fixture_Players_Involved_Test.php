<?php
declare(strict_types=1);

namespace {
    // Mock __ function if it doesn't exist
    if (!function_exists('__')) {
        function __($text, $domain) {
            return $text;
        }
    }
}

namespace Racketmanager\Tests\Unit\Services\Validator {

    use PHPUnit\Framework\TestCase;
    use Racketmanager\Services\Validator\Validator_Fixture;
    use Racketmanager\Services\Registration_Service;
    use Racketmanager\Services\Competition_Service;
    use Racketmanager\Services\Club_Service;
    use Racketmanager\Services\Player_Service;
    use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
    use stdClass;

    class Validator_Fixture_Players_Involved_Test extends TestCase
    {
        private $validator;
        private $registration_service;
        private $competition_service;
        private $club_service;
        private $player_service;

        protected function setUp(): void
        {
            parent::setUp();
            
            $this->registration_service = $this->createMock(Registration_Service::class);
            $this->competition_service = $this->createMock(Competition_Service::class);
            $this->club_service = $this->createMock(Club_Service::class);
            $this->player_service = $this->createMock(Player_Service::class);

            $racketmanager = new stdClass();
            $racketmanager->container = new class($this) {
                private $test;
                public function __construct($test) {
                    $this->test = $test;
                }
                public function get($service) {
                    return $this->test->get_mock_service($service);
                }
            };
            $GLOBALS['racketmanager'] = $racketmanager;

            $this->validator = new Validator_Fixture();
        }

        public function get_mock_service($service)
        {
            return match($service) {
                'registration_service' => $this->registration_service,
                'competition_service' => $this->competition_service,
                'club_service' => $this->club_service,
                'player_service' => $this->player_service,
                default => new stdClass(),
            };
        }

        protected function tearDown(): void
        {
            unset($GLOBALS['racketmanager']);
            parent::tearDown();
        }

        private function create_mock_player_dto(bool $system_record = false): Club_Player_DTO
        {
            $dto = $this->getMockBuilder(Club_Player_DTO::class)
                ->disableOriginalConstructor()
                ->getMock();
            $dto->system_record = $system_record;
            return $dto;
        }

        public function test_players_involved_success(): void
        {
            $players = [
                'home' => [1 => 'H1', 2 => 'H2'],
                'away' => [1 => 'A1', 2 => 'A2']
            ];
            $player_numbers = [1, 2];
            
            $this->registration_service->method('get_registration')
                ->willReturn($this->create_mock_player_dto(false));

            $result = $this->validator->players_involved($players, $player_numbers, 1, false, false);

            $this->assertFalse($this->validator->error);
            $this->assertEmpty($this->validator->err_msgs);
        }

        public function test_players_involved_missing_selection(): void
        {
            $players = [
                'home' => [1 => 'H1'], // 2 is missing
                'away' => [1 => 'A1', 2 => 'A2']
            ];
            $player_numbers = [1, 2];

            $this->registration_service->method('get_registration')
                ->willReturn($this->create_mock_player_dto(false));

            $this->validator->players_involved($players, $player_numbers, 1, false, false);

            $this->assertTrue($this->validator->error);
            $this->assertContains('players_1_home_2', $this->validator->err_flds);
            $this->assertContains('Player not selected', $this->validator->err_msgs);
        }

        public function test_players_involved_system_record_skipped(): void
        {
            $players = [
                'home' => [1 => 'H1'],
                'away' => [1 => 'A1']
            ];
            $player_numbers = [1];

            // System record = true should skip the rest of the logic
            $this->registration_service->method('get_registration')
                ->willReturn($this->create_mock_player_dto(true));

            $this->validator->players_involved($players, $player_numbers, 1, false, false);

            $this->assertFalse($this->validator->error);
        }

        public function test_players_involved_playoff_must_have_played(): void
        {
            $players = [
                'home' => [1 => 'H1'],
                'away' => [1 => 'A1']
            ];
            $player_numbers = [1];

            $this->registration_service->method('get_registration')
                ->willReturn($this->create_mock_player_dto(false));

            // players_involved array is private, so we can't easily set it.
            // But if we call it once with playoff=false, H1 will be added to players_involved.
            // If we then call it with playoff=true for A1, A1 is NOT in players_involved, so it should error.
            
            // First call to add H1 and A1 to players_involved is NOT possible because it iterates over both home and away.
            // Wait, if I call it for rubber 1, it adds H1 and A1.
            // If I then call it for rubber 2, and A1 is there again, it might check.
            
            $this->validator->players_involved($players, $player_numbers, 1, false, false);
            $this->assertFalse($this->validator->error);

            // Now call for rubber 2 where A2 is new and it's a playoff
            $players2 = [
                'home' => [1 => 'H1'],
                'away' => [1 => 'A2'] // A2 was not in rubber 1
            ];
            $this->validator->players_involved($players2, $player_numbers, 2, true, false);

            $this->assertTrue($this->validator->error);
            $this->assertContains('players_2_away_1', $this->validator->err_flds);
            $this->assertContains('Player for playoff must have played', $this->validator->err_msgs);
        }

        public function test_players_involved_reverse_rubber_must_have_played(): void
        {
            $players = [
                'home' => [1 => 'H1'],
                'away' => [1 => 'A1']
            ];
            $player_numbers = [1];

            $this->registration_service->method('get_registration')
                ->willReturn($this->create_mock_player_dto(false));

            $this->validator->players_involved($players, $player_numbers, 1, false, true);

            $this->assertTrue($this->validator->error);
            $this->assertContains('Player for reverse rubber must have played', $this->validator->err_msgs);
        }

        public function test_players_involved_duplicate_player(): void
        {
            $players = [
                'home' => [1 => 'H1'],
                'away' => [1 => 'H1'] // Same player on both sides
            ];
            $player_numbers = [1];

            $this->registration_service->method('get_registration')
                ->willReturn($this->create_mock_player_dto(false));

            $this->validator->players_involved($players, $player_numbers, 1, false, false);

            $this->assertTrue($this->validator->error);
            $this->assertContains('Player already selected', $this->validator->err_msgs);
        }
    }
}
