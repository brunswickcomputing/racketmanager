<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class League_Team_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_construct_creates_pure_entity(): void {
        $row = (object) [
            'id'             => 25,
            'table_id'       => 500,
            'team_id'        => 25,
            'club_id'        => 10,
            'team_type'      => 'P',
            'roster'         => [ 77 ],
            'points_plus'    => 10.0,
            'points_minus'   => 2.0,
            'points_2_plus'  => 5,
            'points_2_minus' => 1,
            'diff'           => 8,
            'status'         => 'W',
            'title'          => 'Team Alpha',
        ];

        $league_team = new League_Team( $row );

        $this->assertSame( 25, $league_team->id );
        $this->assertSame( 25, $league_team->get_team_id() );
        $this->assertSame( 500, $league_team->table_id );
        $this->assertSame( 10, $league_team->club_id );
        $this->assertNull( $league_team->club );
        $this->assertSame( [ 77 ], $league_team->roster );
        $this->assertTrue( $league_team->is_withdrawn );
        $this->assertSame( 'Team Alpha', $league_team->title );
    }

    public function test_factory_from_object_creates_pure_entity(): void {
        $row = (object) [
            'id'        => 25,
            'table_id'  => 500,
            'club_id'   => 10,
            'team_type' => 'P',
            'roster'    => serialize( [ 77 ] ),
        ];

        $league_team = League_Team_Factory::from_object( $row );

        $this->assertNull( $league_team->club );
        $this->assertEmpty( $league_team->players );
    }

    public function test_league_team_has_no_methods_with_repository_or_service_parameters(): void {
        $methods = ( new ReflectionClass( League_Team::class ) )->getMethods();
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

    public function test_league_team_does_not_store_repository_or_service_properties(): void {
        $properties = ( new ReflectionClass( League_Team::class ) )->getProperties();
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
}
