<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Domain\DTO\Competition\League_Team_Hydration_DTO;
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

        $league_team = League_Team::from_object( $row );

        $this->assertSame( 25, $league_team->id );
        $this->assertSame( 25, $league_team->get_team_id() );
        $this->assertSame( 500, $league_team->table_id );
        $this->assertSame( 10, $league_team->club_id );
        $this->assertNull( $league_team->club );
        $this->assertSame( [ 77 ], $league_team->roster );
        $this->assertTrue( $league_team->is_withdrawn );
        $this->assertSame( 'Team Alpha', $league_team->title );
    }

    public function test_hydrates_serialized_custom_and_roster_columns(): void {
        $row = (object) [
            'id'             => 25,
            'table_id'       => 500,
            'team_id'        => 25,
            'league_id'      => 3,
            'season'         => '2026',
            'roster'         => serialize( [ 77, 78 ] ),
            'custom'         => serialize( [ 'add_points' => 2, 'title' => "O\\'Brien Park" ] ),
            'points_plus'    => 10.0,
            'points_minus'   => 2.0,
            'points_2_plus'  => 5,
            'points_2_minus' => 1,
            'diff'           => 8,
            'status'         => '',
            'title'          => 'Team Alpha',
        ];

        $league_team = League_Team::from_object( $row );

        $this->assertSame( [ 77, 78 ], $league_team->roster );
        $this->assertSame( [ 'add_points' => 2, 'title' => "O'Brien Park" ], $league_team->custom );
        $this->assertSame( "O'Brien Park", $league_team->title ); // custom keys are merged over the row and unslashed.
        $this->assertEqualsWithDelta( 2.0, $league_team->add_points, 0.0001 );
        $this->assertEqualsWithDelta( 12.0, $league_team->points_plus, 0.0001 );
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

    public function test_constructor_accepts_only_the_hydration_dto(): void {
        $this->expectException( \TypeError::class );
        new League_Team( (object) [ 'id' => 1 ] );
    }

    public function test_factory_create_from_dto_lenient_from_object_and_from_array_hydrate_identically(): void {
        $row = [
            'id'             => '25',
            'table_id'       => '500',
            'team_id'        => '25',
            'league_id'      => '3',
            'season'         => '2026',
            'title'          => 'Team Alpha',
            'status'         => '+',
            'points_plus'    => '10',
            'points_minus'   => '2',
            'points_2_plus'  => '5',
            'points_2_minus' => '1',
            'add_points'     => '1.5',
            'diff'           => '8',
            'rank'           => '2',
            'profile'        => '1',
            'custom'         => serialize( [ 'games_won' => '12', 'games_allowed' => '4' ] ),
            'unknown_column' => 'dropped',
        ];

        $from_factory = League_Team_Factory::from_object( (object) $row );
        $from_dto     = League_Team_Factory::create_from_dto( League_Team_Hydration_DTO::from_array( $row ) );
        $lenient      = League_Team::from_object( (object) $row );
        $from_array   = League_Team::from_array( $row );

        $this->assertEquals( $from_factory, $from_dto );
        $this->assertEquals( $from_factory, $lenient );
        $this->assertEquals( $from_factory, $from_array );

        // Derived behaviour preserved: add_points, diff sign, status icon, custom merge.
        $this->assertEqualsWithDelta( 11.5, $lenient->points_plus, 0.0001 );
        $this->assertSame( [ 'plus' => 11.5, 'minus' => 2.0 ], $lenient->points );
        $this->assertSame( [ 'plus' => 5, 'minus' => 1 ], $lenient->points_2 );
        $this->assertSame( '+8', $lenient->diff );
        $this->assertSame( 'arrow-up', $lenient->status_icon );
        $this->assertFalse( $lenient->is_withdrawn );
        $this->assertSame( 'Team Alpha', $lenient->title );
        $this->assertSame( 12, $lenient->games_won );
        $this->assertSame( 4, $lenient->games_allowed );
        $this->assertSame( 2, $lenient->rank );
        $this->assertSame( 3, $lenient->get_league_id() );
        $this->assertSame( '2026', $lenient->get_season() );
        $this->assertFalse( property_exists( $lenient, 'unknown_column' ) );
    }

    public function test_empty_constructor_yields_empty_league_team(): void {
        $league_team = new League_Team();
        $this->assertNull( $league_team->id );
        $this->assertNull( $league_team->title );
        $this->assertSame( 0, $league_team->get_team_id() );
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
