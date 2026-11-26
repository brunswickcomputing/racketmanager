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
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use TypeError;

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
        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            League_Team::class,
			new League_Team( (object) [ 'id' => 1 ] )
        );
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
        $this->assertNull( $league_team->table_id );
        $this->assertNull( $league_team->title );
        $this->assertSame( 0, $league_team->get_team_id() );
    }

    /**
     * Test domain mutators update state consistently.
     */
    public function test_domain_mutators_update_state_consistently(): void {
        $team = new League_Team();

        $team->set_match_counts( 4, 2, 1, 1 );
        $this->assertSame( 4, $team->done_matches );
        $this->assertSame( 2, $team->won_matches );
        $this->assertSame( 1, $team->draw_matches );
        $this->assertSame( 1, $team->lost_matches );
        $this->assertSame( 0.625, $team->win_percent );

        $team->update_standings_stats(
            array(
                'plus'  => 10.0,
                'minus' => 4.0,
            ),
            6.0,
            array(
                'plus'  => 2,
                'minus' => 1,
            ),
            array(
                'bonus' => 1,
            )
        );
        $expected_points   = array(
            'plus'  => 10.0,
            'minus' => 4.0,
        );
        $expected_points_2 = array(
            'plus'  => 2,
            'minus' => 1,
        );
        $expected_custom   = array(
            'bonus' => 1,
        );
        $this->assertSame( $expected_points, $team->points );
        $this->assertSame( 6, $team->diff );
        $this->assertSame( $expected_points_2, $team->points_2 );
        $this->assertSame( $expected_custom, $team->custom );

        $team->set_points_2_plus( 5 );
        $team->set_points_2_minus( 2 );
        $this->assertSame( 5, $team->points_2_plus );
        $this->assertSame( 2, $team->points_2_minus );

        $team->set_diff( 12.0 );
        $this->assertSame( 12, $team->diff );
        $team->set_diff( '+14' );
        $this->assertSame( '+14', $team->diff );

        $team->set_rank( 3 );
        $team->record_previous_rank();
        $this->assertSame( 3, $team->old_rank );

        $team->set_old_rank( 1 );
        $this->assertSame( 1, $team->old_rank );

        $team->set_league( 'Premier Division' );
        $this->assertSame( 'Premier Division', $team->league );

        $info = (object) array( 'captain' => 'John Doe' );
        $team->set_info( $info );
        $this->assertSame( $info, $team->info );

        $team->set_league_id( 42 );
        $this->assertSame( 42, $team->league_id );

        $team->set_table_id( 99 );
        $this->assertSame( 99, $team->table_id );
        $this->assertSame( 99, $team->get_table_id() );
        $team->set_table_id( null );
        $this->assertNull( $team->table_id );
        $this->assertNull( $team->get_table_id() );

        $team->set_id( 123 );
        $this->assertSame( 123, $team->id );
        $team->set_id( null );
        $this->assertNull( $team->id );
    }

    public function test_league_team_has_no_methods_with_repository_or_service_parameters(): void {
        $methods = new ReflectionClass( League_Team::class )->getMethods();
        foreach ( $methods as $method ) {
            foreach ( $method->getParameters() as $param ) {
                $type = $param->getType();
                if ( null === $type ) {
                    continue;
                }
                $types = $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType
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
        $properties = new ReflectionClass( League_Team::class )->getProperties();
        foreach ( $properties as $property ) {
            $name = $property->getName();
            $this->assertDoesNotMatchRegularExpression(
                '/(repository|service|container)/i',
                $name,
                "Property \$$name must not reference repository, service, or container."
            );

            $type = $property->getType();
            if ( $type instanceof ReflectionNamedType ) {
                $typeName = $type->getName();
                $this->assertDoesNotMatchRegularExpression(
                    '/(Repository|Service|Container)/i',
                    $typeName,
                    "Property \$$name type $typeName must not reference repository, service, or container."
                );
            } elseif ( $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType ) {
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

    /**
     * Test all public properties enforce private(set) asymmetric visibility immutability.
     */
    public function test_public_properties_enforce_private_set_immutability(): void {
        $team       = new League_Team();
        $ref        = new ReflectionClass( League_Team::class );
        $properties = $ref->getProperties( \ReflectionProperty::IS_PUBLIC );

        $this->assertCount( 64, $properties );

        $sample_values = array(
            'int'                            => 1,
            'float'                          => 1.0,
            'int|float'                      => 1.0,
            'string'                         => 'test',
            'bool'                           => true,
            'array'                          => array(),
            'mixed'                          => 'test',
            'object'                         => (object) array(),
            'string|int'                     => 1,
            'array|string|null'              => null,
            'Racketmanager\Domain\Club\Club' => new \Racketmanager\Domain\Club\Club(),
        );

        foreach ( $properties as $property ) {
            $name = $property->getName();
            $this->assertTrue(
                $property->isPrivateSet(),
                sprintf( 'Property $%s must be declared private(set).', $name )
            );

            $type = $property->getType();
            $val  = null;
            if ( $type ) {
                $type_name = ltrim( $type->__toString(), '?' );
                if ( isset( $sample_values[ $type_name ] ) ) {
                    $val = $sample_values[ $type_name ];
                }
            }

            $error_thrown = false;
            try {
                $team->$name = $val;
            } catch ( \Error $e ) {
                $error_thrown = true;
                $this->assertMatchesRegularExpression(
                    '/Cannot modify private\(set\) property/i',
                    $e->getMessage(),
                    sprintf( 'Expected property $%s to reject external modification with private(set) error.', $name )
                );
            }
            $this->assertTrue( $error_thrown, sprintf( 'Property $%s must not be publicly mutable.', $name ) );
        }
    }

    /**
     * Test all domain mutators update encapsulated state.
     */
    public function test_all_domain_mutators_update_encapsulated_state(): void {
        $team = new League_Team();

        $club = new \Racketmanager\Domain\Club\Club();
        $team->set_club( $club );
        $this->assertSame( $club, $team->club );

        $team->set_club_id( 15 );
        $this->assertSame( 15, $team->club_id );

        $team->set_club( null );
        $this->assertNull( $team->club );

        $team->set_club_id( null );
        $this->assertNull( $team->club_id );

        $player1       = new \stdClass();
        $player1->id   = 101;
        $player1->name = 'Player 1';
        $players       = array( $player1 );
        $team->set_players( $players );
        $this->assertSame( $players, $team->players );

        $team->set_title( 'Hardened Team Title' );
        $this->assertSame( 'Hardened Team Title', $team->title );

        $team->set_class( 'first-row active' );
        $this->assertSame( 'first-row active', $team->class );

        $points_formatted = array(
            'plus'  => '10 pts',
            'minus' => '2 pts',
        );
        $team->set_points_formatted( $points_formatted );
        $this->assertSame( $points_formatted, $team->points_formatted );

        $team->set_withdrawn( true );
        $this->assertTrue( $team->is_withdrawn );
        $team->set_withdrawn( false );
        $this->assertFalse( $team->is_withdrawn );

        $team->set_match_day( 'Wednesday' );
        $this->assertSame( 'Wednesday', $team->match_day );

        $team->set_match_time( '18:30' );
        $this->assertSame( '18:30', $team->match_time );

        $team->set_captain_id( 77 );
        $this->assertSame( 77, $team->captain_id );

        $team->set_captain_name( 'Captain Bob' );
        $this->assertSame( 'Captain Bob', $team->captain_name );

        $team->set_contactno( '07700900123' );
        $this->assertSame( '07700900123', $team->contactno );

        $team->set_contactemail( 'bob@captain.test' );
        $this->assertSame( 'bob@captain.test', $team->contactemail );

        $team->set_league_status( 'L' );
        $this->assertSame( 'L', $team->league_status );

        $team->set_rating( 4.25 );
        $this->assertSame( 4.25, $team->rating );

        $metrics = array(
            'sets_won'       => 14,
            'sets_allowed'   => 4,
            'sets_shared'    => '1',
            'games_won'      => 84,
            'games_allowed'  => 42,
            'straight_set'   => array( 3 ),
            'split_set'      => array( 2 ),
            'no_team'        => '1',
            'no_player'      => '0',
            'rubbers_won'    => 6,
            'rubbers_shared' => 1,
            'matches_won'    => 3,
            'matches_shared' => 1,
        );
        $team->apply_standings_metrics( $metrics );

        $this->assertSame( 14, $team->sets_won );
        $this->assertSame( 4, $team->sets_allowed );
        $this->assertSame( '1', $team->sets_shared );
        $this->assertSame( 84, $team->games_won );
        $this->assertSame( 42, $team->games_allowed );
        $this->assertSame( array( 3 ), $team->straight_set );
        $this->assertSame( array( 2 ), $team->split_set );
        $this->assertSame( '1', $team->no_team );
        $this->assertSame( '0', $team->no_player );
        $this->assertSame( 6, $team->rubbers_won );
        $this->assertSame( 1, $team->rubbers_shared );
        $this->assertSame( 3, $team->matches_won );
        $this->assertSame( 1, $team->matches_shared );
    }

    /**
     * Test win_percent calculation and reset on zero matches.
     */
    public function test_win_percent_resets_to_zero_when_done_matches_is_zero(): void {
        $team = new League_Team();

        $team->set_match_counts( 4, 3, 0, 1 );
        $this->assertSame( 0.75, $team->win_percent );

        $team->set_match_counts( 0, 0, 0, 0 );
        $this->assertSame( 0, $team->win_percent );
        $this->assertSame( 0, $team->done_matches );
    }

    /**
     * Test apply_standings_metrics preserves unspecified keys and casts integer string values.
     */
    public function test_apply_standings_metrics_partial_update_and_type_casting(): void {
        $team = new League_Team();

        $initial_metrics = array(
            'sets_won'     => 10,
            'games_won'    => 60,
            'rubbers_won'  => 5,
            'straight_set' => array( 2 ),
        );
        $team->apply_standings_metrics( $initial_metrics );

        $this->assertSame( 10, $team->sets_won );
        $this->assertSame( 60, $team->games_won );
        $this->assertSame( 5, $team->rubbers_won );
        $this->assertSame( array( 2 ), $team->straight_set );
        $this->assertSame( 0, $team->sets_allowed );

        $partial_metrics = array(
            'sets_won'     => '16',
            'sets_allowed' => '8',
            'games_won'    => '92',
        );
        $team->apply_standings_metrics( $partial_metrics );

        $this->assertSame( 16, $team->sets_won );
        $this->assertSame( 8, $team->sets_allowed );
        $this->assertSame( 92, $team->games_won );
        $this->assertSame( 5, $team->rubbers_won );
        $this->assertSame( array( 2 ), $team->straight_set );
    }

    /**
     * Test update_standings_stats clears custom property when null is passed.
     */
    public function test_update_standings_stats_clears_custom_when_null_passed(): void {
        $team = new League_Team();

        $team->update_standings_stats(
            array(
                'plus'  => 5.0,
                'minus' => 1.0,
            ),
            4.0,
            array(
                'plus'  => 2,
                'minus' => 0,
            ),
            array(
                'bonus' => 2,
            )
        );
        $this->assertSame( array( 'bonus' => 2 ), $team->custom );

        $team->update_standings_stats(
            array(
                'plus'  => 5.0,
                'minus' => 1.0,
            ),
            4.0,
            array(
                'plus'  => 2,
                'minus' => 0,
            ),
            null
        );
        $this->assertNull( $team->custom );
    }

    public function test_get_diff_returns_integer_for_positive_negative_and_zero_values(): void {
        $positive_team = League_Team::from_array( [ 'diff' => 8 ] );
        $this->assertSame( '+8', $positive_team->diff );
        $this->assertSame( 8, $positive_team->get_diff() );

        $negative_team = League_Team::from_array( [ 'diff' => -3 ] );
        $this->assertSame( -3, $negative_team->diff );
        $this->assertSame( -3, $negative_team->get_diff() );

        $negative_string_team = League_Team::from_array( [ 'diff' => '-5' ] );
        $this->assertSame( -5, $negative_string_team->get_diff() );

        $zero_team = League_Team::from_array( [ 'diff' => 0 ] );
        $this->assertSame( 0, $zero_team->diff );
        $this->assertSame( 0, $zero_team->get_diff() );

        $float_string_team = League_Team::from_array( [ 'diff' => '12.0' ] );
        $this->assertSame( '+12.0', $float_string_team->diff );
        $this->assertSame( 12, $float_string_team->get_diff() );

        $empty_team = new League_Team();
        $this->assertSame( 0, $empty_team->diff );
        $this->assertSame( 0, $empty_team->get_diff() );
    }
}
