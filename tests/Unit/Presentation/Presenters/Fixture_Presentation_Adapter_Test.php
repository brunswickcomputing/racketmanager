<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use AllowDynamicProperties;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Enums\Club_Role;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Team_Service;
use ReflectionClass;
use stdClass;
use TypeError;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Presentation_Adapter_Test extends TestCase {

    public function test_adapter_hydrates_from_realistic_fixture_under_strict_types(): void {
        $data              = new stdClass();
        $data->id          = 101;
        $data->league_id   = 10;
        $data->home_team   = '1';
        $data->away_team   = '2';
        $data->date        = '2026-05-15 18:30:00';
        $data->season      = '2026';
        $data->leg         = 2;
        $data->match_day   = 5;
        $data->home_points = '3.5';
        $data->away_points = '0.5';
        $data->status      = 1;
        $data->host        = 'home';
        $data->location    = 'Court 1';

        $fixture = Fixture_Factory::from_object( $data );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_name' )->willReturn( 'Division 1' );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $league->num_rubbers = 2;

        $event = $this->createMock( Event::class );
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_name' )->willReturn( 'Men\'s Doubles' );
        $event->method( 'get_type' )->willReturn( 'league' );
        $event->method( 'get_competition_id' )->willReturn( 30 );

        $competition = $this->createMock( Competition::class );
        $competition->method( 'get_id' )->willReturn( 30 );
        $competition->method( 'get_name' )->willReturn( 'Summer League' );
        $competition->type = Competition_Type::LEAGUE;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_league_repository' )->willReturn( $league_repo );
        $comp_service->expects( $this->once() )->method( 'get_event_by_id' )->with( 20 )->willReturn( $event );
        $comp_service->expects( $this->once() )->method( 'get_by_id' )->with( 30 )->willReturn( $competition );

        $team1 = $this->createMock( Team::class );
        $team1->method( 'get_name' )->willReturn( 'Club A 1' );
        $team1_dto = new Team_Details_DTO( $team1, null, null );

        $team2 = $this->createMock( Team::class );
        $team2->method( 'get_name' )->willReturn( 'Club B 1' );
        $team2_dto = new Team_Details_DTO( $team2, null, null );

        $team_service = $this->createMock( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturnMap(
            [
                [ 1, $team1_dto ],
                [ 2, $team2_dto ],
            ]
        );

        $rubber = new ReflectionClass( Rubber::class )->newInstanceWithoutConstructor();
        $rubber->id = 1;
        $rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
        $rubber_repository->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 101 )->willReturn( [ $rubber ] );

        $adapter = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $comp_service,
            $team_service,
            rubber_repository: $rubber_repository
        );

        $this->assertSame( 101, $adapter->id );
        $this->assertSame( 2, $adapter->leg );
        $this->assertSame( 5, $adapter->match_day );
        $this->assertSame( '3.5', $adapter->home_points );
        $this->assertSame( '0.5', $adapter->away_points );
        $this->assertSame( 1, $adapter->status );
        $this->assertSame( 'Division 1', $adapter->league->title );
        $this->assertSame( 'Men\'s Doubles', $adapter->league->event->name );
        $this->assertSame( 'Summer League', $adapter->league->event->competition->name );
        $this->assertSame( 'Club A 1', $adapter->home_title );
        $this->assertSame( 'Club B 1', $adapter->away_title );
        $this->assertSame( 'Club A 1 - Club B 1', $adapter->title );
        $this->assertSame( '18:30', $adapter->start_time );
        $this->assertSame( '18:30', $adapter->get_start_time() );
        $this->assertSame( [ $rubber ], $adapter->get_rubbers() );
    }

    public function test_adapter_uses_option_gateway_for_date_format(): void {
        $data       = new stdClass();
        $data->id   = 102;
        $data->date = '2026-05-15 18:30:00';
        $fixture    = Fixture_Factory::from_object( $data );

        $option_gateway = $this->create_stub_option_gateway();

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            option_gateway: $option_gateway
        );

        $this->assertSame( '15/05/2026', $adapter->match_date );
    }

    public function test_adapter_exposes_start_time_for_timed_and_midnight_fixtures(): void {
        // Timed fixture -> start_time property and getter return '19:30'
        $data_timed       = new stdClass();
        $data_timed->id   = 103;
        $data_timed->date = '2026-05-15 19:30:00';
        $fixture_timed    = Fixture_Factory::from_object( $data_timed );

        $adapter_timed = new Fixture_Presentation_Adapter( fixture: $fixture_timed );
        $this->assertSame( '19:30', $adapter_timed->start_time );
        $this->assertSame( '19:30', $adapter_timed->get_start_time() );

        // Midnight fixture -> start_time property and getter return null
        $data_midnight       = new stdClass();
        $data_midnight->id   = 104;
        $data_midnight->date = '2026-05-15 00:00:00';
        $fixture_midnight    = Fixture_Factory::from_object( $data_midnight );

        $adapter_midnight = new Fixture_Presentation_Adapter( fixture: $fixture_midnight );
        $this->assertNull( $adapter_midnight->start_time );
        $this->assertNull( $adapter_midnight->get_start_time() );

        // Date-only fixture -> start_time property and getter return null
        $data_date_only       = new stdClass();
        $data_date_only->id   = 105;
        $data_date_only->date = '2026-05-15';
        $fixture_date_only    = Fixture_Factory::from_object( $data_date_only );

        $adapter_date_only = new Fixture_Presentation_Adapter( fixture: $fixture_date_only );
        $this->assertNull( $adapter_date_only->start_time );
        $this->assertNull( $adapter_date_only->get_start_time() );
    }

    public function test_adapter_does_not_carry_allow_dynamic_properties_attribute(): void {
        $reflection = new ReflectionClass( Fixture_Presentation_Adapter::class );
        $attributes = $reflection->getAttributes( AllowDynamicProperties::class );
        $this->assertEmpty( $attributes, 'Fixture_Presentation_Adapter must not carry #[AllowDynamicProperties].' );
    }

    public function test_adapter_allows_league_entity_assignment_to_league_property(): void {
        $data    = new stdClass();
        $data->id = 106;
        $fixture = Fixture_Factory::from_object( $data );
        $adapter = new Fixture_Presentation_Adapter( fixture: $fixture );

        $league = $this->createMock( League::class );
        $adapter->league = $league;

        $this->assertSame( $league, $adapter->league );
    }

    /**
     * Create a dummy fixture entity for constructor tests.
     */
    private function create_test_fixture(): Fixture {
        $data            = new stdClass();
        $data->id        = 201;
        $data->date      = '2026-05-15 18:30:00';
        $data->home_team = 'Team Home';
        $data->away_team = 'Team Away';

        return Fixture_Factory::from_object( $data );
    }

    /**
     * Create a stub option gateway configured with d/m/Y date format.
     */
    private function create_stub_option_gateway(): Option_Gateway_Interface {
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            static function ( string $key, mixed $def_val = null ): mixed {
                return 'date_format' === $key ? 'd/m/Y' : $def_val;
            }
        );

        return $option_gateway;
    }

    public function test_constructor_supports_legacy_positional_arguments_including_previous_titles_and_gateway(): void {
        $fixture           = $this->create_test_fixture();
        $is_update_allowed = (object) array( 'user_can_update' => true );
        $option_gateway    = $this->create_stub_option_gateway();

        $adapter = new Fixture_Presentation_Adapter(
            $fixture,
            null,
            null,
            null,
            null,
            null,
            'https://example.com/match/201',
            $is_update_allowed,
            'Winner of Semi 1',
            'Runner-up of Semi 2',
            $option_gateway
        );

        $this->assertSame( 'https://example.com/match/201', $adapter->link );
        $this->assertSame( $is_update_allowed, $adapter->is_update_allowed );
        $this->assertSame( 'Winner of Semi 1', $adapter->home_title );
        $this->assertSame( 'Runner-up of Semi 2', $adapter->away_title );
        $this->assertSame( 'Winner of Semi 1 - Runner-up of Semi 2', $adapter->title );
        $this->assertNotNull( $adapter->prev_home_match );
        $this->assertSame( 'Winner of Semi 1', $adapter->prev_home_match->match_title );
        $this->assertNotNull( $adapter->prev_away_match );
        $this->assertSame( 'Runner-up of Semi 2', $adapter->prev_away_match->match_title );
        $this->assertSame( '15/05/2026', $adapter->match_date );
    }

    public function test_constructor_supports_named_arguments_with_explicit_null_and_values(): void {
        $fixture           = $this->create_test_fixture();
        $is_update_allowed = (object) array( 'user_can_update' => true );
        $option_gateway    = $this->create_stub_option_gateway();

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            link: 'https://example.com/named',
            is_update_allowed: $is_update_allowed,
            prev_home_title: 'Previous Home Title',
            prev_away_title: null,
            option_gateway: $option_gateway
        );

        $this->assertSame( 'https://example.com/named', $adapter->link );
        $this->assertSame( $is_update_allowed, $adapter->is_update_allowed );
        $this->assertSame( 'Previous Home Title', $adapter->home_title );
        $this->assertSame( 'Team Away', $adapter->away_title );
        $this->assertNotNull( $adapter->prev_home_match );
        $this->assertSame( 'Previous Home Title', $adapter->prev_home_match->match_title );
        $this->assertNull( $adapter->prev_away_match );
        $this->assertSame( '15/05/2026', $adapter->match_date );
    }

    public function test_constructor_supports_dictionary_options_via_positional_and_named_variants(): void {
        $fixture           = $this->create_test_fixture();
        $is_update_allowed = (object) array( 'user_can_update' => true );
        $option_gateway    = $this->create_stub_option_gateway();

        $options = array(
            'link'              => 'https://example.com/dict',
            'is_update_allowed' => $is_update_allowed,
            'prev_home_title'   => 'Dict Home',
            'prev_away_title'   => 'Dict Away',
            'option_gateway'    => $option_gateway,
        );

        // Positional dictionary invocation (as 7th argument).
        $adapter_pos = new Fixture_Presentation_Adapter(
            $fixture,
            null,
            null,
            null,
            null,
            null,
            $options
        );

        $this->assertSame( 'https://example.com/dict', $adapter_pos->link );
        $this->assertSame( $is_update_allowed, $adapter_pos->is_update_allowed );
        $this->assertSame( 'Dict Home', $adapter_pos->home_title );
        $this->assertSame( 'Dict Away', $adapter_pos->away_title );
        $this->assertNotNull( $adapter_pos->prev_home_match );
        $this->assertSame( 'Dict Home', $adapter_pos->prev_home_match->match_title );
        $this->assertNotNull( $adapter_pos->prev_away_match );
        $this->assertSame( 'Dict Away', $adapter_pos->prev_away_match->match_title );
        $this->assertSame( '15/05/2026', $adapter_pos->match_date );

        // Named dictionary invocation (options: $options).
        $adapter_named = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            options: $options
        );

        $this->assertSame( 'https://example.com/dict', $adapter_named->link );
        $this->assertSame( $is_update_allowed, $adapter_named->is_update_allowed );
        $this->assertSame( 'Dict Home', $adapter_named->home_title );
        $this->assertSame( 'Dict Away', $adapter_named->away_title );
        $this->assertSame( '15/05/2026', $adapter_named->match_date );
    }

    public function test_constructor_defaults_when_options_are_omitted(): void {
        $fixture = $this->create_test_fixture();
        $adapter = new Fixture_Presentation_Adapter( fixture: $fixture );

        $this->assertSame( '', $adapter->link );
        $this->assertFalse( $adapter->is_update_allowed->user_can_update );
        $this->assertSame( 'Team Home', $adapter->home_title );
        $this->assertSame( 'Team Away', $adapter->away_title );
        $this->assertNull( $adapter->prev_home_match );
        $this->assertNull( $adapter->prev_away_match );
    }

    public function test_constructor_supports_mixed_dictionary_and_named_overrides_without_collision(): void {
        $fixture = $this->create_test_fixture();

        // Positional dictionary with named extra argument.
        $adapter_mixed = new Fixture_Presentation_Adapter(
            $fixture,
            null,
            null,
            null,
            null,
            null,
            array( 'link' => 'https://example.com/dict-only' ),
            prev_home_title: 'Previous Home from Named'
        );
        $this->assertSame( 'https://example.com/dict-only', $adapter_mixed->link );
        $this->assertSame( 'Previous Home from Named', $adapter_mixed->home_title );

        // Positional dictionary with named override.
        $adapter_override = new Fixture_Presentation_Adapter(
            $fixture,
            null,
            null,
            null,
            null,
            null,
            array( 'link' => 'https://example.com/from-dict' ),
            link: 'https://example.com/from-named'
        );
        $this->assertSame( 'https://example.com/from-named', $adapter_override->link );

        // Named dictionary with named override.
        $adapter_named_dict = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            options: array(
                'link'            => 'https://example.com/from-dict',
                'prev_home_title' => 'Dict Home',
            ),
            link: 'https://example.com/override'
        );
        $this->assertSame( 'https://example.com/override', $adapter_named_dict->link );
        $this->assertSame( 'Dict Home', $adapter_named_dict->home_title );
    }

    public function test_constructor_rejects_unknown_named_parameters(): void {
        $fixture = $this->create_test_fixture();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessageIsOrContains( "Unknown option 'linkk'" );

        $this->assertInstanceOf(
            Fixture_Presentation_Adapter::class,
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                linkk: 'https://example.com/typo'
            )
        );
    }

    public function test_constructor_rejects_unknown_keys_in_dictionary(): void {
        $fixture = $this->create_test_fixture();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessageIsOrContains( "Unknown option 'invalid_key' in options dictionary" );

        $this->assertInstanceOf(
            Fixture_Presentation_Adapter::class,
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                options: array( 'invalid_key' => 'value' )
            )
        );
    }

    public function test_constructor_rejects_positional_and_named_collision(): void {
        $fixture = $this->create_test_fixture();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessageIsOrContains( "Option 'link' was passed both positionally and by name" );

        $this->assertInstanceOf(
            Fixture_Presentation_Adapter::class,
            new Fixture_Presentation_Adapter(
                $fixture,
                null,
                null,
                null,
                null,
                null,
                'https://example.com/pos',
                link: 'https://example.com/named'
            )
        );
    }

    public function test_constructor_rejects_malformed_dictionary_variants(): void {
        $fixture = $this->create_test_fixture();

        try {
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                options: 'not-an-array'
            );
            $this->fail( 'Expected InvalidArgumentException for non-array options parameter.' );
        } catch ( InvalidArgumentException $e ) {
            $this->assertStringContainsString( "Option 'options' must be an array", $e->getMessage() );
        }

        try {
            new Fixture_Presentation_Adapter(
                $fixture,
                null,
                null,
                null,
                null,
                null,
                array( 'link' => 'https://example.com' ),
                options: array( 'link' => 'https://example.com' )
            );
            $this->fail( 'Expected InvalidArgumentException for duplicate dictionary sources.' );
        } catch ( InvalidArgumentException $e ) {
            $this->assertStringContainsString( "Cannot pass both positional dictionary and 'options' named parameter", $e->getMessage() );
        }

        try {
            new Fixture_Presentation_Adapter(
                $fixture,
                null,
                null,
                null,
                null,
                null,
                array( 'link' => 'https://example.com' ),
                'extra-positional-arg'
            );
            $this->fail( 'Expected InvalidArgumentException for positional options following positional dictionary.' );
        } catch ( InvalidArgumentException $e ) {
            $this->assertStringContainsString( 'Positional options cannot follow a positional options dictionary', $e->getMessage() );
        }
    }

    public function test_constructor_enforces_strict_types_for_options(): void {
        $fixture = $this->create_test_fixture();

        // Invalid link type: int.
        try {
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                link: 123
            );
            $this->fail( 'Expected TypeError for integer link.' );
        } catch ( TypeError $e ) {
            $this->assertStringContainsString( "Option 'link' must be of type string", $e->getMessage() );
        }

        // Invalid is_update_allowed type: array.
        try {
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                is_update_allowed: array( 'user_can_update' => true )
            );
            $this->fail( 'Expected TypeError for array is_update_allowed.' );
        } catch ( TypeError $e ) {
            $this->assertStringContainsString( "Option 'is_update_allowed' must be of type ?object", $e->getMessage() );
        }

        // Invalid prev_home_title type: int.
        try {
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                prev_home_title: 456
            );
            $this->fail( 'Expected TypeError for integer prev_home_title.' );
        } catch ( TypeError $e ) {
            $this->assertStringContainsString( "Option 'prev_home_title' must be of type ?string", $e->getMessage() );
        }

        // Invalid prev_away_title type: array.
        try {
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                prev_away_title: array( 'invalid' )
            );
            $this->fail( 'Expected TypeError for array prev_away_title.' );
        } catch ( TypeError $e ) {
            $this->assertStringContainsString( "Option 'prev_away_title' must be of type ?string", $e->getMessage() );
        }

        // Invalid option_gateway type: stdClass instead of Option_Gateway_Interface.
        try {
            new Fixture_Presentation_Adapter(
                fixture: $fixture,
                option_gateway: new stdClass()
            );
            $this->fail( 'Expected TypeError for non-gateway object.' );
        } catch ( TypeError $e ) {
            $this->assertStringContainsString( 'Option \'option_gateway\' must be of type ?Racketmanager\Application\Ports\Option_Gateway_Interface', $e->getMessage() );
        }
    }

    public function test_constructor_handles_missing_graph_entities_gracefully(): void {
        $data            = new stdClass();
        $data->id        = 101;
        $data->league_id = 10;
        $data->season    = '2026';
        $fixture         = Fixture_Factory::from_object( $data );
        $adapter         = new Fixture_Presentation_Adapter( $fixture );

        $this->assertSame( 10, $adapter->league->id );
        $this->assertSame( '', $adapter->league->title );
        $this->assertSame( '', $adapter->league->name );
        $this->assertSame( '', $adapter->league->type );
        $this->assertSame( '', $adapter->league->mode );
        $this->assertSame( '', $adapter->league->sport );
        $this->assertSame( 0, $adapter->league->num_rubbers );
        $this->assertNull( $adapter->league->num_sets );
        $this->assertSame( 0, $adapter->league->event_id );
        $this->assertSame( array( 'name' => '2026' ), $adapter->league->current_season );

        $this->assertSame( 0, $adapter->league->event->id );
        $this->assertSame( '', $adapter->league->event->name );
        $this->assertSame( '', $adapter->league->event->type );
        $this->assertSame( 0, $adapter->league->event->competition_id );
        $this->assertFalse( $adapter->league->event->is_box );

        $this->assertSame( 0, $adapter->league->event->competition->id );
        $this->assertSame( '', $adapter->league->event->competition->name );
        $this->assertSame( '', $adapter->league->event->competition->type );
        $this->assertFalse( $adapter->league->event->competition->is_championship );
        $this->assertFalse( $adapter->league->event->competition->is_cup );
        $this->assertFalse( $adapter->league->event->competition->is_tournament );
        $this->assertFalse( $adapter->league->event->competition->is_league );
    }

    public function test_constructor_falls_back_to_public_properties_when_getters_are_absent(): void {
        $fixture = $this->create_test_fixture();

        $league           = new stdClass();
        $league->id       = 77;
        $league->title    = 'Prop League';
        $league->mode     = 'cup';
        $league->sport    = 'squash';
        $league->event_id = 88;

        $event                 = new stdClass();
        $event->id             = 88;
        $event->name           = 'Prop Event';
        $event->type           = 'box';
        $event->competition_id = 99;

        $competition       = new stdClass();
        $competition->id   = 99;
        $competition->name = 'Prop Comp';
        $competition->type = 'cup';

        $adapter = new Fixture_Presentation_Adapter(
            $fixture,
            $league,
            $event,
            $competition
        );

        $this->assertSame( 77, $adapter->league->id );
        $this->assertSame( 'Prop League', $adapter->league->title );
        $this->assertSame( 'Prop League', $adapter->league->name );
        $this->assertSame( 'cup', $adapter->league->type );
        $this->assertSame( 'cup', $adapter->league->mode );
        $this->assertSame( 'squash', $adapter->league->sport );
        $this->assertSame( 88, $adapter->league->event_id );

        $this->assertSame( 88, $adapter->league->event->id );
        $this->assertSame( 'Prop Event', $adapter->league->event->name );
        $this->assertSame( 'box', $adapter->league->event->type );
        $this->assertSame( 99, $adapter->league->event->competition_id );
        $this->assertTrue( $adapter->league->event->is_box );

        $this->assertSame( 99, $adapter->league->event->competition->id );
        $this->assertSame( 'Prop Comp', $adapter->league->event->competition->name );
        $this->assertSame( 'cup', $adapter->league->event->competition->type );
        $this->assertTrue( $adapter->league->event->competition->is_cup );
        $this->assertFalse( $adapter->league->event->competition->is_championship );
        $this->assertFalse( $adapter->league->event->competition->is_tournament );
        $this->assertFalse( $adapter->league->event->competition->is_league );
    }

    public function test_constructor_resolves_backed_enum_types_and_competition_flags_via_methods_and_properties(): void {
        $fixture = $this->create_test_fixture();

        $league = new class() {
            public function get_id(): int {
                return 12;
            }
            public function get_name(): string {
                return 'Tournament Div';
            }
            public function get_type(): Competition_Type {
                return Competition_Type::TOURNAMENT;
            }
        };

        $event = new class() {
            public function get_id(): int {
                return 24;
            }
            public function get_name(): string {
                return 'Cup Singles';
            }
            public function get_type(): Competition_Type {
                return Competition_Type::CUP;
            }
        };

        $competition = new class() {
            public bool $is_championship = true;
            public function get_id(): int {
                return 36;
            }
            public function get_name(): string {
                return 'Club Championship';
            }
            public function get_type(): string {
                return 'championship';
            }
        };

        $adapter = new Fixture_Presentation_Adapter(
            $fixture,
            $league,
            $event,
            $competition
        );

        $this->assertSame( 'tournament', $adapter->league->type );
        $this->assertSame( 'tournament', $adapter->league->mode );
        $this->assertSame( 'cup', $adapter->league->event->type );
        $this->assertSame( 'championship', $adapter->league->event->competition->type );
        $this->assertTrue( $adapter->league->event->competition->is_championship );
        $this->assertFalse( $adapter->league->event->competition->is_cup );
        $this->assertFalse( $adapter->league->event->competition->is_tournament );
        $this->assertFalse( $adapter->league->event->competition->is_league );
    }

    public function test_constructor_resolves_event_id_from_event_when_league_is_absent(): void {
        $fixture = $this->create_test_fixture();

        $event = $this->createMock( Event::class );
        $event->method( 'get_id' )->willReturn( 42 );

        $adapter = new Fixture_Presentation_Adapter(
            $fixture,
            null,
            $event
        );

        $this->assertSame( 42, $adapter->league->event_id );
        $this->assertSame( 42, $adapter->league->event->id );
    }

    public function test_constructor_resolves_match_date_variations(): void {
        $fixture = $this->create_test_fixture();

        // 1. Fixture with date and custom option gateway date format.
        $gateway = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            static function ( string $key, mixed $def_val = null ): mixed {
                return 'date_format' === $key ? 'd/m/Y' : $def_val;
            }
        );

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            option_gateway: $gateway
        );

        // Date in create_test_fixture is '2026-05-15 18:30:00'.
        if ( function_exists( 'mysql2date' ) ) {
            $this->assertSame( '15/05/2026', $adapter->match_date );
        } else {
            $this->assertSame( '2026-05-15 18:30:00', $adapter->match_date );
        }

        // 2. Fixture without date.
        $empty_data     = new stdClass();
        $empty_data->id = 202;
        $empty_fixture  = Fixture_Factory::from_object( $empty_data );

        $empty_adapter = new Fixture_Presentation_Adapter(
            fixture: $empty_fixture,
            option_gateway: $gateway
        );

        $this->assertSame( '', $empty_adapter->match_date );
    }

    public function test_constructor_resolves_int_backed_enum_types_without_string_casting(): void {
        $fixture = $this->create_test_fixture();

        $league = new class() {
            public function get_id(): int {
                return 10;
            }
            public function get_name(): string {
                return 'Int Enum League';
            }
            public function get_type(): Club_Role {
                return Club_Role::MATCH_SECRETARY;
            }
        };

        $event = new class() {
            public function get_id(): int {
                return 20;
            }
            public function get_name(): string {
                return 'Int Enum Event';
            }
            public function get_type(): Club_Role {
                return Club_Role::COACH;
            }
        };

        $competition = new class() {
            public function get_id(): int {
                return 30;
            }
            public function get_name(): string {
                return 'Int Enum Comp';
            }
            public function get_type(): Club_Role {
                return Club_Role::MATCH_SECRETARY;
            }
        };

        $adapter = new Fixture_Presentation_Adapter(
            $fixture,
            $league,
            $event,
            $competition
        );

        $this->assertSame( 1, $adapter->league->type );
        $this->assertSame( 1, $adapter->league->mode );
        $this->assertSame( 2, $adapter->league->event->type );
        $this->assertSame( 1, $adapter->league->event->competition->type );
        $this->assertFalse( $adapter->league->event->competition->is_championship );
        $this->assertFalse( $adapter->league->event->competition->is_cup );
        $this->assertFalse( $adapter->league->event->competition->is_tournament );
        $this->assertFalse( $adapter->league->event->competition->is_league );
    }

    public function test_constructor_resolves_competition_flags_via_legacy_methods(): void {
        $fixture = $this->create_test_fixture();

        $competition = new class() {
            public function get_id(): int {
                return 55;
            }
            public function get_name(): string {
                return 'Legacy Flag Comp';
            }
            public function get_type(): string {
                return 'custom_type';
            }
            public function is_championship(): bool {
                return false;
            }
            public function is_cup(): bool {
                return true;
            }
            public function is_tournament(): bool {
                return false;
            }
            public function is_league(): bool {
                return true;
            }
        };

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            competition: $competition
        );

        $this->assertSame( 'custom_type', $adapter->league->event->competition->type );
        $this->assertFalse( $adapter->league->event->competition->is_championship );
        $this->assertTrue( $adapter->league->event->competition->is_cup );
        $this->assertFalse( $adapter->league->event->competition->is_tournament );
        $this->assertTrue( $adapter->league->event->competition->is_league );
    }

    public function test_constructor_falls_back_to_zero_when_league_object_has_no_id(): void {
        $data            = new stdClass();
        $data->id        = 301;
        $data->league_id = 99;
        $fixture         = Fixture_Factory::from_object( $data );

        $league = new stdClass();

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            league: $league
        );

        $this->assertSame( 0, $adapter->league->id );

        $null_league_adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            league: null
        );

        $this->assertSame( 99, $null_league_adapter->league->id );
    }

    public function test_from_fixture_handles_absent_league_and_event_fallbacks(): void {
        $comp_service = $this->createMock( Competition_Service::class );
        $team_service = $this->createMock( Team_Service::class );

        // Case A: league_id is 0, but $fixture->league exists directly.
        $data_direct            = new stdClass();
        $data_direct->id        = 110;
        $data_direct->league_id = 0;
        $fixture_direct         = Fixture_Factory::from_object( $data_direct );

        $direct_comp       = new stdClass();
        $direct_comp->id   = 32;
        $direct_comp->name = 'Direct Comp';

        $direct_event              = new stdClass();
        $direct_event->id          = 22;
        $direct_event->name        = 'Direct Event';
        $direct_event->competition = $direct_comp;

        $direct_league        = new stdClass();
        $direct_league->id    = 12;
        $direct_league->title = 'Direct League';
        $direct_league->name  = 'Direct League';
        $direct_league->event = $direct_event;

        $fixture_direct->league = $direct_league;

        $adapter_direct = Fixture_Presentation_Adapter::from_fixture(
            $fixture_direct,
            $comp_service,
            $team_service
        );

        $this->assertSame( 12, $adapter_direct->league->id );
        $this->assertSame( 'Direct League', $adapter_direct->league->name );
        $this->assertSame( 'Direct Event', $adapter_direct->league->event->name );
        $this->assertSame( 'Direct Comp', $adapter_direct->league->event->competition->name );

        // Case B: league_id is 0 and no league attached at all.
        $data_none            = new stdClass();
        $data_none->id        = 111;
        $data_none->league_id = 0;
        $fixture_none         = Fixture_Factory::from_object( $data_none );

        $adapter_none = Fixture_Presentation_Adapter::from_fixture(
            $fixture_none,
            $comp_service,
            $team_service
        );

        $this->assertSame( 0, $adapter_none->league->id );
        $this->assertSame( '', $adapter_none->league->title );
        $this->assertSame( 0, $adapter_none->league->event->id );
        $this->assertSame( 0, $adapter_none->league->event->competition->id );
    }

    public function test_from_fixture_resolves_numeric_and_string_team_values_and_empty_teams(): void {
        $comp_service = $this->createMock( Competition_Service::class );
        $team_service = $this->createMock( Team_Service::class );

        $team_numeric = $this->createMock( Team::class );
        $team_numeric->method( 'get_name' )->willReturn( 'Club Numeric' );
        $dto_numeric = new Team_Details_DTO( $team_numeric, null, null );

        $team_string = $this->createMock( Team::class );
        $team_string->method( 'get_name' )->willReturn( 'Club Derived' );
        $dto_string = new Team_Details_DTO( $team_string, null, null );

        $team_service->expects( $this->once() )
            ->method( 'get_team_details' )
            ->with( 15 )
            ->willReturn( $dto_numeric );

        $team_service->expects( $this->once() )
            ->method( 'derive_team_details' )
            ->with( 'Winner_Group_A' )
            ->willReturn( $dto_string );

        $data            = new stdClass();
        $data->id        = 120;
        $data->home_team = '15';
        $data->away_team = 'Winner_Group_A';
        $fixture         = Fixture_Factory::from_object( $data );

        $adapter = Fixture_Presentation_Adapter::from_fixture(
            $fixture,
            $comp_service,
            $team_service
        );

        $this->assertSame( 'Club Numeric', $adapter->home_title );
        $this->assertSame( 'Club Derived', $adapter->away_title );

        // Empty teams case.
        $data_empty            = new stdClass();
        $data_empty->id        = 121;
        $data_empty->home_team = null;
        $data_empty->away_team = '';
        $fixture_empty         = Fixture_Factory::from_object( $data_empty );

        $team_service_empty = $this->createMock( Team_Service::class );
        $team_service_empty->expects( $this->never() )->method( 'get_team_details' );
        $team_service_empty->expects( $this->never() )->method( 'derive_team_details' );

        $adapter_empty = Fixture_Presentation_Adapter::from_fixture(
            $fixture_empty,
            $comp_service,
            $team_service_empty
        );

        $this->assertSame( '', $adapter_empty->home_title );
        $this->assertSame( '', $adapter_empty->away_title );
    }

    public function test_from_fixture_resolves_link_hierarchy_and_fallbacks(): void {
        $comp_service = $this->createMock( Competition_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $league       = new stdClass();
        $league->id   = 10;

        // Scenario 1: link service returns a valid URL.
        $data_1       = new stdClass();
        $data_1->id   = 131;
        $data_1->link = 'https://example.com/fixture-link';
        $fixture_1    = Fixture_Factory::from_object( $data_1 );

        $link_service_1 = $this->createMock( Fixture_Link_Service::class );
        $link_service_1->expects( $this->once() )
            ->method( 'get_fixture_link' )
            ->willReturn( 'https://example.com/service-link' );

        $fixture_1->league = $league;
        $adapter_1         = Fixture_Presentation_Adapter::from_fixture(
            $fixture_1,
            $comp_service,
            $team_service,
            link_service: $link_service_1
        );
        $this->assertSame( 'https://example.com/service-link', $adapter_1->link );

        // Scenario 2: link service returns empty string, falls back to fixture link.
        $link_service_2 = $this->createMock( Fixture_Link_Service::class );
        $link_service_2->expects( $this->once() )
            ->method( 'get_fixture_link' )
            ->willReturn( '' );

        $adapter_2 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_1,
            $comp_service,
            $team_service,
            link_service: $link_service_2
        );
        $this->assertSame( 'https://example.com/fixture-link', $adapter_2->link );

        // Scenario 3: link service is null, falls back directly to fixture link.
        $adapter_3 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_1,
            $comp_service,
            $team_service,
            link_service: null
        );
        $this->assertSame( 'https://example.com/fixture-link', $adapter_3->link );

        // Scenario 4: league is null, falls back to fixture link without calling link service.
        $data_no_league       = new stdClass();
        $data_no_league->id   = 132;
        $data_no_league->link = 'https://example.com/no-league';
        $fixture_no_league    = Fixture_Factory::from_object( $data_no_league );

        $link_service_4 = $this->createMock( Fixture_Link_Service::class );
        $link_service_4->expects( $this->never() )->method( 'get_fixture_link' );

        $adapter_4 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_no_league,
            $comp_service,
            $team_service,
            link_service: $link_service_4
        );
        $this->assertSame( 'https://example.com/no-league', $adapter_4->link );

        // Scenario 5: fixture has no link and link service is null -> returns empty string.
        $data_no_link     = new stdClass();
        $data_no_link->id = 133;
        $fixture_no_link  = Fixture_Factory::from_object( $data_no_link );

        $adapter_5 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_no_link,
            $comp_service,
            $team_service
        );
        $this->assertSame( '', $adapter_5->link );

        // Scenario 6: link service returns literal '0', treated as empty and falls back to fixture link.
        $link_service_6 = $this->createMock( Fixture_Link_Service::class );
        $link_service_6->expects( $this->once() )
            ->method( 'get_fixture_link' )
            ->willReturn( '0' );

        $adapter_6 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_1,
            $comp_service,
            $team_service,
            link_service: $link_service_6
        );
        $this->assertSame( 'https://example.com/fixture-link', $adapter_6->link );

        // Scenario 7: link service returns literal '0' and fixture link is absent/null -> returns '0'.
        $data_7            = new stdClass();
        $data_7->id        = 134;
        $fixture_7         = Fixture_Factory::from_object( $data_7 );
        $fixture_7->league = $league;

        $link_service_7 = $this->createMock( Fixture_Link_Service::class );
        $link_service_7->expects( $this->once() )
            ->method( 'get_fixture_link' )
            ->willReturn( '0' );

        $adapter_7 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_7,
            $comp_service,
            $team_service,
            link_service: $link_service_7
        );
        $this->assertSame( '0', $adapter_7->link );

        // Scenario 8: no link service and fixture link is literal '0' -> returns '0'.
        $data_8       = new stdClass();
        $data_8->id   = 135;
        $data_8->link = '0';
        $fixture_8    = Fixture_Factory::from_object( $data_8 );

        $adapter_8 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_8,
            $comp_service,
            $team_service,
            link_service: null
        );
        $this->assertSame( '0', $adapter_8->link );
    }

    public function test_from_fixture_enforces_rubber_lazy_load_conditions(): void {
        $comp_service = $this->createMock( Competition_Service::class );
        $team_service = $this->createMock( Team_Service::class );

        $rubber     = ( new ReflectionClass( Rubber::class ) )->newInstanceWithoutConstructor();
        $rubber->id = 88;

        // Condition 1: rubber repository is null -> no loading attempted.
        $data_1    = new stdClass();
        $data_1->id = 141;
        $fixture_1 = Fixture_Factory::from_object( $data_1 );

        $adapter_1 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_1,
            $comp_service,
            $team_service,
            rubber_repository: null
        );
        $this->assertSame( array(), $adapter_1->get_rubbers() );

        // Condition 2: fixture rubbers already loaded (non-null) -> repo never called.
        $fixture_2          = Fixture_Factory::from_object( $data_1 );
        $fixture_2->rubbers = array( $rubber );

        $repo_2 = $this->createMock( Rubber_Repository_Interface::class );
        $repo_2->expects( $this->never() )->method( 'find_by_fixture_id' );

        $adapter_2 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_2,
            $comp_service,
            $team_service,
            rubber_repository: $repo_2
        );
        $this->assertSame( array( $rubber ), $adapter_2->get_rubbers() );

        // Condition 3: fixture ID is 0 or negative -> repo never called.
        $data_zero     = new stdClass();
        $data_zero->id = 0;
        $fixture_zero  = Fixture_Factory::from_object( $data_zero );

        $repo_3 = $this->createMock( Rubber_Repository_Interface::class );
        $repo_3->expects( $this->never() )->method( 'find_by_fixture_id' );

        Fixture_Presentation_Adapter::from_fixture(
            $fixture_zero,
            $comp_service,
            $team_service,
            rubber_repository: $repo_3
        );

        // Condition 4: league exists with num_rubbers <= 0 -> repo never called.
        $data_4            = new stdClass();
        $data_4->id        = 144;
        $data_4->league_id = 0;
        $fixture_4         = Fixture_Factory::from_object( $data_4 );

        $league_zero_rubbers              = new stdClass();
        $league_zero_rubbers->id          = 15;
        $league_zero_rubbers->num_rubbers = 0;
        $fixture_4->league                = $league_zero_rubbers;

        $repo_4 = $this->createMock( Rubber_Repository_Interface::class );
        $repo_4->expects( $this->never() )->method( 'find_by_fixture_id' );

        Fixture_Presentation_Adapter::from_fixture(
            $fixture_4,
            $comp_service,
            $team_service,
            rubber_repository: $repo_4
        );

        // Condition 5: league is null, fixture id > 0, rubbers null -> repo called.
        $data_5            = new stdClass();
        $data_5->id        = 145;
        $data_5->league_id = 0;
        $fixture_5         = Fixture_Factory::from_object( $data_5 );

        $repo_5 = $this->createMock( Rubber_Repository_Interface::class );
        $repo_5->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 145 )
            ->willReturn( array( $rubber ) );

        $adapter_5 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_5,
            $comp_service,
            $team_service,
            rubber_repository: $repo_5
        );
        $this->assertSame( array( $rubber ), $adapter_5->get_rubbers() );

        // Condition 6: league has num_rubbers > 0 -> repo called.
        $data_6            = new stdClass();
        $data_6->id        = 146;
        $data_6->league_id = 0;
        $fixture_6         = Fixture_Factory::from_object( $data_6 );

        $league_with_rubbers              = new stdClass();
        $league_with_rubbers->id          = 16;
        $league_with_rubbers->num_rubbers = 4;
        $fixture_6->league                = $league_with_rubbers;

        $repo_6 = $this->createMock( Rubber_Repository_Interface::class );
        $repo_6->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 146 )
            ->willReturn( array( $rubber ) );

        $adapter_6 = Fixture_Presentation_Adapter::from_fixture(
            $fixture_6,
            $comp_service,
            $team_service,
            rubber_repository: $repo_6
        );
        $this->assertSame( array( $rubber ), $adapter_6->get_rubbers() );
    }
}
