<?php
declare( strict_types=1 );

namespace {
    // Mock functions if they don't exist in the environment
    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain ) {
            return $text;
        }
    }
}

namespace Racketmanager\Tests\Unit\Services\Validator {

    use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use Racketmanager\Services\Validator\Validator_Fixture;
    use Racketmanager\Services\Registration_Service;
    use Racketmanager\Services\Competition_Service;
    use Racketmanager\Services\Club_Service;
    use Racketmanager\Services\Player_Service;
    use Racketmanager\Exceptions\Player_Not_Found_Exception;
    use stdClass;

    #[AllowMockObjectsWithoutExpectations]
class Validator_Fixture_Can_Player_Enter_Result_Test extends TestCase {
        private $validator;
        private $registration_service;
        private $competition_service;
        private $club_service;
        private $player_service;

        protected function setUp(): void {
            parent::setUp();
            $this->registration_service = $this->createMock( Registration_Service::class );
            $this->competition_service  = $this->createStub( Competition_Service::class );
            $this->club_service         = $this->createStub( Club_Service::class );
            $this->player_service       = $this->createStub( Player_Service::class );

            // Mock DI container
            $container = new class($this) {
                private $test;
                public function __construct($test) { $this->test = $test; }
                public function get($service) {
                    return $this->test->get_mock_service($service);
                }
            };

            $GLOBALS['racketmanager'] = new stdClass();
            $GLOBALS['racketmanager']->container = $container;

            $this->validator = new Validator_Fixture();
        }

        public function get_mock_service($service) {
            switch ($service) {
                case 'registration_service':
                    return $this->registration_service;
                case 'competition_service':
                    return $this->competition_service;
                case 'club_service':
                    return $this->club_service;
                case 'player_service':
                    return $this->player_service;
                default:
                    return $this->createStub(stdClass::class);
            }
        }

        protected function tearDown(): void {
            unset($GLOBALS['racketmanager']);
            unset($GLOBALS['current_user_id']);
            parent::tearDown();
        }

        public function test_can_player_enter_result_not_permitted(): void {
            $is_update_allowed = (object) [
                'user_can_update' => false,
                'user_type' => 'player'
            ];
            $match_players = [];

            $this->validator->can_player_enter_result($is_update_allowed, $match_players);

            $this->assertTrue($this->validator->error);
            $this->assertEquals('Result entry not permitted', $this->validator->msg);
        }

        public function test_can_player_enter_result_admin_success(): void {
            $is_update_allowed = (object) [
                'user_can_update' => true,
                'user_type' => 'admin'
            ];
            $match_players = [];

            $this->validator->can_player_enter_result($is_update_allowed, $match_players);

            $this->assertFalse($this->validator->error);
        }

        public function test_can_player_enter_result_player_found_success(): void {
            $is_update_allowed = (object) [
                'user_can_update' => true,
                'user_type' => 'player'
            ];
            
            // match_players is deeply nested: match_players -> teams -> players -> player_id
            $match_players = [
                'rubber_1' => [
                    'home' => [
                        'player_1' => '456',
                        'player_2' => '789'
                    ]
                ]
            ];

            $registration = (object) ['registration_id' => 456];
            // get_current_user_id() from wp-stubs.php returns 1
            $this->registration_service->method('get_clubs_for_player')
                ->with(1)
                ->willReturn([$registration]);

            $this->validator->can_player_enter_result($is_update_allowed, $match_players);

            $this->assertFalse($this->validator->error);
        }

        public function test_can_player_enter_result_player_not_found_error(): void {
            $is_update_allowed = (object) [
                'user_can_update' => true,
                'user_type' => 'player'
            ];
            
            $match_players = [
                'rubber_1' => [
                    'home' => [
                        'player_1' => '999'
                    ]
                ]
            ];

            $registration = (object) ['registration_id' => 456];
            // get_current_user_id() from wp-stubs.php returns 1
            $this->registration_service->method('get_clubs_for_player')
                ->with(1)
                ->willReturn([$registration]);

            $this->validator->can_player_enter_result($is_update_allowed, $match_players);

            $this->assertTrue($this->validator->error);
            $this->assertEquals('Player cannot submit results', $this->validator->msg);
        }

        public function test_can_player_enter_result_player_not_found_exception(): void {
            $is_update_allowed = (object) [
                'user_can_update' => true,
                'user_type' => 'player'
            ];
            
            $match_players = [
                'rubber_1' => [
                    'home' => [
                        'player_1' => '456'
                    ]
                ]
            ];

            // get_current_user_id() from wp-stubs.php returns 1
            $this->registration_service->method('get_clubs_for_player')
                ->with(1)
                ->willThrowException(new Player_Not_Found_Exception());

            $this->validator->can_player_enter_result($is_update_allowed, $match_players);

            $this->assertTrue($this->validator->error);
            $this->assertEquals('Player not found', $this->validator->msg);
        }
    }
}
