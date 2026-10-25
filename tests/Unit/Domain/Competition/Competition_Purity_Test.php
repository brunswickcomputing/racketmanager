<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use ReflectionClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Competition_Purity_Test extends TestCase {

	public function test_competition_does_not_store_repository_or_service_properties(): void {
		$properties = new ReflectionClass( Competition::class )->getProperties();
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

	public function test_competition_has_no_methods_with_repository_or_service_parameters(): void {
		$methods = ( new ReflectionClass( Competition::class ) )->getMethods();
		foreach ( $methods as $method ) {
			foreach ( $method->getParameters() as $param ) {
				$type = $param->getType();
				if ( null === $type ) {
					continue;
				}
				$types = $type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType
					? $type->getTypes()
					: [ $type ];
				foreach ( $types as $t ) {
					$typeName = $t->getName();
					$this->assertDoesNotMatchRegularExpression(
						'/(Repository|Service|Factory|Container)/i',
						$typeName,
						sprintf(
							'Method %s() parameter $%s type %s must not reference repository, service, factory, or container.',
							$method->getName(),
							$param->getName(),
							$typeName
						)
					);
				}
			}
		}
	}

	public function test_repository_read_methods_do_not_exist_on_competition(): void {
		$rc = new ReflectionClass( Competition::class );
		$this->assertFalse( $rc->hasMethod( 'set_competition_repository' ), 'Competition::set_competition_repository must not exist.' );
		$this->assertFalse( $rc->hasMethod( 'get_events' ), 'Competition::get_events must not exist.' );
		$this->assertFalse( $rc->hasMethod( 'get_teams' ), 'Competition::get_teams must not exist.' );
		$this->assertFalse( $rc->hasMethod( 'get_players' ), 'Competition::get_players must not exist.' );
		$this->assertFalse( $rc->hasMethod( 'get_matches' ), 'Competition::get_matches must not exist.' );
	}

	public function test_serialize_unserialize_roundtrip(): void {
		$competition = new Competition(
			'Summer League',
			Competition_Type::LEAGUE,
			'adults',
			[ '2026' => [ 'name' => '2026' ] ],
			[ 'mode' => 'league' ],
			15
		);

		$serialized   = serialize( $competition );
		$unserialized = unserialize( $serialized );

		$this->assertInstanceOf( Competition::class, $unserialized );
		$this->assertSame( 15, $unserialized->id );
		$this->assertSame( 'Summer League', $unserialized->name );
		$this->assertSame( 'league', $unserialized->settings->get( 'mode' ) );
	}
}
