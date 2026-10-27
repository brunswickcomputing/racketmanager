<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Event;
use ReflectionClass;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Event_Purity_Test extends TestCase {

	public function test_constructor_does_not_require_competition_or_container(): void {
		$event_raw                 = new stdClass();
		$event_raw->id             = 11;
		$event_raw->name           = 'Open Singles';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 3;
		$event_raw->seasons        = '[]';

		$event = Event::from_object( $event_raw );

		$this->assertSame( 11, $event->id );
		$this->assertSame( 'Open Singles', $event->name );
		$this->assertNull( $event->competition );
		$this->assertFalse( $event->is_championship );
		$this->assertFalse( $event->is_box );
	}

	public function test_constructor_uses_supplied_competition_without_locator(): void {
		$competition            = $this->createStub( Competition::class );
		$competition->standings = array( 'points' => 1 );
		$competition->settings  = new Competition_Settings( array( 'mode' => 'league' ) );
		$competition->is_player_entry = false;
		$competition->current_season  = array( 'name' => '2026' );

		$event_raw                 = new stdClass();
		$event_raw->id             = 12;
		$event_raw->name           = 'Mixed Doubles';
		$event_raw->type           = 'doubles';
		$event_raw->competition_id = 4;
		$event_raw->seasons        = wp_json_encode( array( '2026' => array( 'name' => '2026' ) ) );

		$event = Event::from_object( $event_raw, $competition );

		$this->assertSame( $competition, $event->competition );
		$this->assertSame( array( 'points' => 1 ), $event->standings );
	}

	public function test_event_does_not_store_repository_or_service_collaborators(): void {
		$properties = ( new ReflectionClass( Event::class ) )->getProperties();
		foreach ( $properties as $property ) {
			$name = $property->getName();
			$this->assertDoesNotMatchRegularExpression(
				'/(repository|service|container)/i',
				$name,
				"Property \$$name must not reference repository, service, or container."
			);

			$type = $property->getType();
			if ( $type instanceof \ReflectionNamedType ) {
				$typeName = $type->getName();
				$this->assertDoesNotMatchRegularExpression(
					'/(Repository|Service|Container)/i',
					$typeName,
					"Property \$$name type $typeName must not reference repository, service, or container."
				);
			} elseif ( $type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType ) {
				foreach ( $type->getTypes() as $subType ) {
					$typeName = $subType->getName();
					$this->assertDoesNotMatchRegularExpression(
						'/(Repository|Service|Container)/i',
						$typeName,
						"Property \$$name type $typeName must not reference repository, service, or container."
					);
				}
			}
		}
	}
	public function test_methods_are_removed(): void {
		$reflection = new \ReflectionClass( Event::class );
		$this->assertFalse( $reflection->hasMethod( 'get_leagues' ), 'Method get_leagues should have been removed.' );
		$this->assertFalse( $reflection->hasMethod( 'get_matches' ), 'Method get_matches should have been removed.' );
		foreach ( array( 'get_club', 'get_clubs', 'get_teams', 'get_players', 'get_player_stats', 'get_teams_info', 'get_team_info', 'get_constitution' ) as $method ) {
			$this->assertFalse( $reflection->hasMethod( $method ), "Method $method should have been removed (use Event_Query_Service)." );
		}
		$this->assertFalse( $reflection->hasMethod( 'add_season' ), 'Method add_season should have been removed (use Competition_Season_Service::add_season_to_event).' );
		$this->assertFalse( $reflection->hasMethod( 'build_constitution' ), 'Method build_constitution should have been removed (use Event_Query_Service / Competition_Season_Service).' );
		foreach ( array( 'set_num_leagues', 'has_leagues', 'get_primary_league', 'get_primary_league_id', 'reload_settings', 'mark_teams_withdrawn', 'mark_teams_entered' ) as $method ) {
			$this->assertFalse( $reflection->hasMethod( $method ), "Method $method should have been removed (use Event_Query_Service / Repositories)." );
		}
	}

	public function test_no_event_methods_accept_repository_or_service_parameters(): void {
		$reflection = new \ReflectionClass( Event::class );
		foreach ( $reflection->getMethods() as $method ) {
			foreach ( $method->getParameters() as $param ) {
				$type = (string) $param->getType();
				$this->assertDoesNotMatchRegularExpression(
					'/(Repository|Service|Container)/i',
					$type,
					"Method {$method->getName()} parameter \${$param->getName()} must not reference Repository, Service, or Container."
				);
			}
		}
	}

	public function test_mutators_do_not_accept_repository_parameters(): void {
		$reflection = new \ReflectionClass( Event::class );
		$methods    = array(
			'set_name'       => 1,
			'set_settings'   => 1,
			'set_config'     => 1,
			'update_season'  => 1,
			'update_seasons' => 1,
		);

		foreach ( $methods as $method_name => $expected_param_count ) {
			$this->assertTrue( $reflection->hasMethod( $method_name ), "Event should have method $method_name." );
			$method = $reflection->getMethod( $method_name );
			$params = $method->getParameters();
			$this->assertCount( $expected_param_count, $params, "Method $method_name should have exactly $expected_param_count parameter(s)." );
			foreach ( $params as $param ) {
				$type = (string) $param->getType();
				$this->assertDoesNotMatchRegularExpression(
					'/Repository/i',
					$type,
					"Parameter \${$param->getName()} of $method_name must not typehint Repository."
				);
			}
		}
	}

	public function test_event_source_does_not_reference_services_or_application_layers(): void {
		$source = (string) file_get_contents( dirname( __DIR__, 4 ) . '/src/php/Domain/Competition/Event.php' );
		$this->assertDoesNotMatchRegularExpression( '/Racketmanager\\\\(Services|Application)\\\\/', $source );
	}
}
