<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter_Factory;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter_Graph_Builder;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter_Options_Parser;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Team_Service;
use ReflectionClass;
use RuntimeException;
use stdClass;
use TypeError;

#[AllowMockObjectsWithoutExpectations]
final class Fixture_Presentation_Adapter_Extraction_Test extends TestCase {

    public function test_adapter_method_count_is_less_than_or_equal_to_30(): void {
        $reflection = new ReflectionClass( Fixture_Presentation_Adapter::class );
        $methods    = $reflection->getMethods();

        self::assertLessThanOrEqual(
            30,
            count( $methods ),
            'Fixture_Presentation_Adapter must have 30 or fewer methods.'
        );
        self::assertSame(
            23,
            count( $methods ),
            'Fixture_Presentation_Adapter must expose exactly its 23 public methods.'
        );
    }

    public function test_options_parser_pick_option_precedence_and_conflicts(): void {
        $parser = new Fixture_Presentation_Adapter_Options_Parser();

        // 1. Conflict: passed both positionally and by name
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessageIsOrContains( "Option 'link' was passed both positionally and by name" );
        $parser->pick_option( array( 'link' => 'named', 0 => 'pos' ), 'link', 0, array(), '' );
    }

    public function test_options_parser_pick_option_precedence(): void {
        $parser = new Fixture_Presentation_Adapter_Options_Parser();

        // 2. Named takes precedence over dictionary
        $val1 = $parser->pick_option( array( 'link' => 'https://named' ), 'link', 0, array( 'link' => 'https://dict' ), '' );
        self::assertSame( 'https://named', $val1 );

        // 3. Positional takes precedence over dictionary
        $val2 = $parser->pick_option( array( 0 => 'https://pos' ), 'link', 0, array( 'link' => 'https://dict' ), '' );
        self::assertSame( 'https://pos', $val2 );

        // 4. Dictionary used when not in options
        $val3 = $parser->pick_option( array(), 'link', 0, array( 'link' => 'https://dict' ), '' );
        self::assertSame( 'https://dict', $val3 );

        // 5. Default used when absent everywhere
        $val4 = $parser->pick_option( array(), 'link', 0, array(), 'https://default' );
        self::assertSame( 'https://default', $val4 );
    }

    public function test_options_parser_validates_all_five_option_types_with_given_suffix(): void {
        $parser = new Fixture_Presentation_Adapter_Options_Parser();

        // 1. link (must be string)
        try {
            $parser->validate_option_types( 12345, null, null, null, null );
            self::fail( 'Expected TypeError on invalid link type' );
        } catch ( TypeError $e ) {
            self::assertStringContainsString( "Option 'link' must be of type string, int given", $e->getMessage() );
        }

        // 2. is_update_allowed (must be ?object)
        try {
            $parser->validate_option_types( '', 'invalid_update_object', null, null, null );
            self::fail( 'Expected TypeError on invalid is_update_allowed type' );
        } catch ( TypeError $e ) {
            self::assertStringContainsString( "Option 'is_update_allowed' must be of type ?object, string given", $e->getMessage() );
        }

        // 3. prev_home_title (must be ?string)
        try {
            $parser->validate_option_types( '', null, 999, null, null );
            self::fail( 'Expected TypeError on invalid prev_home_title type' );
        } catch ( TypeError $e ) {
            self::assertStringContainsString( "Option 'prev_home_title' must be of type ?string, int given", $e->getMessage() );
        }

        // 4. prev_away_title (must be ?string)
        try {
            $parser->validate_option_types( '', null, null, false, null );
            self::fail( 'Expected TypeError on invalid prev_away_title type' );
        } catch ( TypeError $e ) {
            self::assertStringContainsString( "Option 'prev_away_title' must be of type ?string, bool given", $e->getMessage() );
        }

        // 5. option_gateway (must be ?Option_Gateway_Interface)
        try {
            $parser->validate_option_types( '', null, null, null, new stdClass() );
            self::fail( 'Expected TypeError on invalid option_gateway type' );
        } catch ( TypeError $e ) {
            self::assertStringContainsString( "Option 'option_gateway' must be of type ?" . Option_Gateway_Interface::class . ', stdClass given', $e->getMessage() );
        }
    }

    public function test_graph_builder_resolve_competition_flag_branches(): void {
        $builder = new Fixture_Presentation_Adapter_Graph_Builder();

        // Branch 1: $comp_type === $target_type
        $comp1 = new stdClass();
        self::assertTrue( $builder->resolve_competition_flag( $comp1, 'cup', 'cup' ) );

        // Branch 2: $competition->is_{target} is truthy
        $comp2         = new stdClass();
        $comp2->is_cup = true;
        self::assertTrue( $builder->resolve_competition_flag( $comp2, 'other', 'cup' ) );

        // Branch 3: method is_{target}() exists and returns truthy
        $comp3 = new class() {
            public function is_cup(): bool {
                return true;
            }
        };
        self::assertTrue( $builder->resolve_competition_flag( $comp3, 'other', 'cup' ) );

        // Branch 4: false when none match
        $comp4 = new stdClass();
        self::assertFalse( $builder->resolve_competition_flag( $comp4, 'league', 'cup' ) );
    }

    public function test_graph_builder_resolve_competition_flag_short_circuits_method(): void {
        $builder = new Fixture_Presentation_Adapter_Graph_Builder();

        $comp = new class() {
            public bool $is_championship = true;

            public function is_championship(): bool {
                throw new RuntimeException( 'Method must not be called when property is already truthy' );
            }
        };

        // When property is_championship is true, method should never be invoked.
        self::assertTrue( $builder->resolve_competition_flag( $comp, 'other', 'championship' ) );
    }

    public function test_factory_resolve_fixture_link_fallbacks(): void {
        $home_team = $this->createMock( Team::class );
        $home_dtls = new Team_Details_DTO( $home_team, null, null );
        $away_team = $this->createMock( Team::class );
        $away_dtls = new Team_Details_DTO( $away_team, null, null );
        $league    = (object) array( 'id' => 5 );

        // 1. Service absent -> returns fixture link if present
        $fixture1 = Fixture_Factory::from_object( (object) array( 'id' => 1, 'link' => 'https://fixture.test/1' ) );
        self::assertSame( 'https://fixture.test/1', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture1, $league, $home_dtls, $away_dtls, null ) );

        // 2. Service absent -> returns '' when fixture link is null
        $fixture2 = Fixture_Factory::from_object( (object) array( 'id' => 2, 'link' => null ) );
        self::assertSame( '', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture2, $league, $home_dtls, $away_dtls, null ) );

        // 3. Service absent -> preserves '0' fixture link
        $fixture3 = Fixture_Factory::from_object( (object) array( 'id' => 3, 'link' => '0' ) );
        self::assertSame( '0', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture3, $league, $home_dtls, $away_dtls, null ) );

        // 4. League absent -> service not called, falls back to fixture link
        $service = $this->createMock( Fixture_Link_Service::class );
        $service->expects( self::never() )->method( 'get_fixture_link' );
        self::assertSame( 'https://fixture.test/1', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture1, null, $home_dtls, $away_dtls, $service ) );

        // 5. Service present with league -> returns service link when non-empty
        $service2 = $this->createMock( Fixture_Link_Service::class );
        $service2->expects( self::once() )
            ->method( 'get_fixture_link' )
            ->with( $fixture1, $league, $home_dtls, $away_dtls )
            ->willReturn( 'https://service.test/match/1' );
        self::assertSame( 'https://service.test/match/1', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture1, $league, $home_dtls, $away_dtls, $service2 ) );

        // 6. Service returns empty string -> falls back to fixture link
        $service3 = $this->createMock( Fixture_Link_Service::class );
        $service3->expects( self::once() )
            ->method( 'get_fixture_link' )
            ->willReturn( '' );
        self::assertSame( 'https://fixture.test/1', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture1, $league, $home_dtls, $away_dtls, $service3 ) );

        // 7. Service returns '0' -> empty('0') is true, falls back to fixture link if non-empty
        $service4 = $this->createMock( Fixture_Link_Service::class );
        $service4->expects( self::once() )
            ->method( 'get_fixture_link' )
            ->willReturn( '0' );
        self::assertSame( 'https://fixture.test/1', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture1, $league, $home_dtls, $away_dtls, $service4 ) );

        // 8. Service returns empty string and fixture link is null -> returns ''
        $service5 = $this->createMock( Fixture_Link_Service::class );
        $service5->expects( self::once() )
            ->method( 'get_fixture_link' )
            ->willReturn( '' );
        self::assertSame( '', Fixture_Presentation_Adapter_Factory::resolve_fixture_link( $fixture2, $league, $home_dtls, $away_dtls, $service5 ) );
    }

    public function test_factory_resolve_event_and_competition(): void {
        $comp_service = $this->createMock( Competition_Service::class );

        // --- resolve_event ---
        // Null league
        self::assertNull( Fixture_Presentation_Adapter_Factory::resolve_event( null, $comp_service ) );

        // League with get_event_id() returning > 0
        $event_obj = $this->createMock( Event::class );
        $league1   = new class() {
            public function get_event_id(): int {
                return 12;
            }
        };
        $comp_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 12 )
            ->willReturn( $event_obj );
        self::assertSame( $event_obj, Fixture_Presentation_Adapter_Factory::resolve_event( $league1, $comp_service ) );

        // League with get_event_id() returning 0 -> falls back to $league->event
        $league2        = new class() {
            public ?object $event = null;
            public function get_event_id(): int {
                return 0;
            }
        };
        $fallback_event = (object) array( 'id' => 99 );
        $league2->event = $fallback_event;
        self::assertSame( $fallback_event, Fixture_Presentation_Adapter_Factory::resolve_event( $league2, $comp_service ) );

        // League without get_event_id method -> returns $league->event or null
        $league3 = (object) array( 'id' => 1 );
        self::assertNull( Fixture_Presentation_Adapter_Factory::resolve_event( $league3, $comp_service ) );

        // --- resolve_competition ---
        // Null event
        self::assertNull( Fixture_Presentation_Adapter_Factory::resolve_competition( null, $comp_service ) );

        // Event with get_competition_id() returning > 0
        $comp_obj = $this->createMock( Competition::class );
        $event1   = new class() {
            public function get_competition_id(): int {
                return 34;
            }
        };
        $comp_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 34 )
            ->willReturn( $comp_obj );
        self::assertSame( $comp_obj, Fixture_Presentation_Adapter_Factory::resolve_competition( $event1, $comp_service ) );

        // Event with get_competition_id() returning 0 -> falls back to $event->competition
        $event2              = new class() {
            public ?object $competition = null;
            public function get_competition_id(): int {
                return 0;
            }
        };
        $fallback_comp       = (object) array( 'id' => 88 );
        $event2->competition = $fallback_comp;
        self::assertSame( $fallback_comp, Fixture_Presentation_Adapter_Factory::resolve_competition( $event2, $comp_service ) );

        // Event without get_competition_id method -> returns $event->competition or null
        $event3 = (object) array( 'id' => 2 );
        self::assertNull( Fixture_Presentation_Adapter_Factory::resolve_competition( $event3, $comp_service ) );
    }

    public function test_factory_resolve_team_details(): void {
        $team_service = $this->createMock( Team_Service::class );
        $team         = $this->createMock( Team::class );
        $dto          = new Team_Details_DTO( $team, null, null );

        // 1. Numeric integer -> get_team_details(int)
        $team_service->expects( self::once() )
            ->method( 'get_team_details' )
            ->with( 42 )
            ->willReturn( $dto );
        self::assertSame( $dto, Fixture_Presentation_Adapter_Factory::resolve_team_details( 42, $team_service ) );

        // 2. Numeric string -> get_team_details(int)
        $team_service2 = $this->createMock( Team_Service::class );
        $team_service2->expects( self::once() )
            ->method( 'get_team_details' )
            ->with( 77 )
            ->willReturn( $dto );
        self::assertSame( $dto, Fixture_Presentation_Adapter_Factory::resolve_team_details( '77', $team_service2 ) );

        // 3. Non-numeric string -> derive_team_details(string)
        $team_service3 = $this->createMock( Team_Service::class );
        $team_service3->expects( self::once() )
            ->method( 'derive_team_details' )
            ->with( 'Club Alpha 1st' )
            ->willReturn( $dto );
        self::assertSame( $dto, Fixture_Presentation_Adapter_Factory::resolve_team_details( 'Club Alpha 1st', $team_service3 ) );

        // 4. Empty string -> returns null, neither method called
        $team_service4 = $this->createMock( Team_Service::class );
        $team_service4->expects( self::never() )->method( 'get_team_details' );
        $team_service4->expects( self::never() )->method( 'derive_team_details' );
        self::assertNull( Fixture_Presentation_Adapter_Factory::resolve_team_details( '', $team_service4 ) );

        // 5. Null -> returns null, neither method called
        self::assertNull( Fixture_Presentation_Adapter_Factory::resolve_team_details( null, $team_service4 ) );
    }

    public function test_factory_load_fixture_rubbers_short_circuit_behavior(): void {
        $repo = $this->createMock( Rubber_Repository_Interface::class );

        // 1. Null repository -> does not throw or load
        $fixture1 = Fixture_Factory::from_object( (object) array( 'id' => 10 ) );
        Fixture_Presentation_Adapter_Factory::load_fixture_rubbers( $fixture1, null, null );
        self::assertNull( $fixture1->rubbers );

        // 2. Already hydrated rubbers -> repository not called
        $fixture2 = Fixture_Factory::from_object( (object) array( 'id' => 10 ) );
        $fixture2->set_rubbers( array() );
        $repo->expects( self::never() )->method( 'find_by_fixture_id' );
        Fixture_Presentation_Adapter_Factory::load_fixture_rubbers( $fixture2, null, $repo );

        // 3. Fixture ID <= 0 -> repository not called
        $fixture3 = Fixture_Factory::from_object( (object) array( 'id' => 0 ) );
        Fixture_Presentation_Adapter_Factory::load_fixture_rubbers( $fixture3, null, $repo );
        self::assertNull( $fixture3->rubbers );

        // 4. League has num_rubbers <= 0 -> repository not called
        $fixture4 = Fixture_Factory::from_object( (object) array( 'id' => 12 ) );
        $league   = (object) array( 'num_rubbers' => 0 );
        Fixture_Presentation_Adapter_Factory::load_fixture_rubbers( $fixture4, $league, $repo );
        self::assertNull( $fixture4->rubbers );

        // 5. Valid conditions met -> repository loads rubbers
        $fixture5 = Fixture_Factory::from_object( (object) array( 'id' => 15 ) );
        $rubber   = $this->createMock( Rubber::class );
        $repo2    = $this->createMock( Rubber_Repository_Interface::class );
        $repo2->expects( self::once() )->method( 'find_by_fixture_id' )->with( 15 )->willReturn( array( $rubber ) );

        Fixture_Presentation_Adapter_Factory::load_fixture_rubbers( $fixture5, null, $repo2 );
        self::assertSame( array( $rubber ), $fixture5->get_rubbers() );
    }
}
