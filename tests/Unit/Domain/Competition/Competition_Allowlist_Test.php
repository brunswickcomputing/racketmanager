<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;

/**
 * Exercises the Competition allowlist contract through the real repository path:
 * events are listed via Competition_Repository::find_events() against a wpdb double.
 */
final class Competition_Allowlist_Test extends TestCase {
    use Manages_Wordpress_Globals;

    /**
     * Event queries issued against the wpdb double.
     *
     * @var string[]
     */
    private array $queries = [];

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->queries = [];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Register events as database rows for the wpdb double and as hydrated Event objects.
     *
     * @param Event[] $events Events.
     */
    private function register_events( array $events ): void {
        $rows = [];
        $map  = [];
        foreach ( $events as $event ) {
            $rows[]               = (object) [ 'id' => $event->id ];
            $map[ $event->id ] = $event;
        }
        $GLOBALS['wp_stubs_events'] = $map;

        $wpdb                       = new \wpdb();
        $wpdb->get_results_callback = function ( string $query ) use ( $rows ): array {
            $this->queries[] = $query;
            return str_contains( $query, 'racketmanager_events' ) ? $rows : [];
        };
        $GLOBALS['wpdb'] = $wpdb;
    }

    private function create_competition( array $seasons = [] ): Competition {
        $competition = Competition_Factory::create_from_dto(
            new Competition_Hydration_DTO(
                id: 100,
                name: 'Allowlist Test League',
                type: Competition_Type::LEAGUE,
                age_group: 'Adults',
                seasons: $seasons,
                settings: []
            )
        );
        $competition->set_competition_repository( new \Racketmanager\Repositories\Competition_Repository() );

        return $competition;
    }

    private function create_event(
        int $id,
        string $name = 'Men Doubles',
        int $competition_id = 100,
        array $seasons = [ '2026' => [ 'name' => '2026' ] ]
    ): Event {
        $event                 = ( new ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = $name;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( $seasons );
        return $event;
    }

    public function test_is_event_allowed_with_populated_allowlist(): void {
        $competition      = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10, 20 ] ] ] );
        $event_allowed    = $this->create_event( 10 );
        $event_disallowed = $this->create_event( 30 );

        $this->assertTrue( $competition->is_event_allowed( $event_allowed, '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( $event_disallowed, '2026', false ) );
        $this->assertTrue( $competition->is_event_allowed( $event_allowed, '2026', true ) );
        $this->assertFalse( $competition->is_event_allowed( $event_disallowed, '2026', true ) );
    }

    public function test_is_event_allowed_with_explicit_empty_allowlist(): void {
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [] ] ] );
        $event       = $this->create_event( 10 );

        $this->assertTrue( $competition->is_event_allowed( $event, '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( $event, '2026', true ) );
    }

    public function test_is_event_allowed_with_absent_allowlist_config(): void {
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );
        $event       = $this->create_event( 10 );

        $this->assertTrue( $competition->is_event_allowed( $event, '2026', false ) );
        $this->assertTrue( $competition->is_event_allowed( $event, '2026', true ) );
    }

    public function test_is_event_allowed_with_null_entry_events(): void {
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => null ] ] );
        $event       = $this->create_event( 10 );

        $this->assertTrue( $competition->is_event_allowed( $event, '2026', false ) );
        $this->assertTrue( $competition->is_event_allowed( $event, '2026', true ) );
    }

    public function test_is_event_allowed_rejects_unconfigured_season(): void {
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10 ] ] ] );
        $event       = $this->create_event( 10, 'Men Doubles', 100, [ '2024' => [ 'name' => '2024' ] ] );

        $this->assertFalse( $competition->is_event_allowed( $event, '2024', false ) );
    }

    public function test_is_event_allowed_rejects_missing_season_when_no_current_season(): void {
        $competition = $this->create_competition( [] );
        $event       = $this->create_event( 10 );

        $this->assertFalse( $competition->is_event_allowed( $event, null, false ) );
        $this->assertFalse( $competition->is_event_allowed( $event, '', true ) );
    }

    public function test_is_event_allowed_rejects_foreign_competition_event(): void {
        $competition   = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10 ] ] ] );
        $foreign_event = $this->create_event( 10, 'Men Doubles', 999 );

        $this->assertFalse( $competition->is_event_allowed( $foreign_event, '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( $foreign_event, '2026', true ) );
    }

    public function test_is_event_allowed_rejects_event_without_competition(): void {
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );
        $event       = ( new ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id   = 10;
        $event->seasons = json_encode( [ '2026' => [ 'name' => '2026' ] ] );

        $this->assertFalse( $competition->is_event_allowed( $event, '2026', false ) );
    }

    public function test_is_event_allowed_rejects_event_outside_season(): void {
        $competition        = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10 ] ] ] );
        $other_season_event = $this->create_event( 10, 'Men Doubles', 100, [ '2024' => [ 'name' => '2024' ] ] );

        $this->assertFalse( $competition->is_event_allowed( $other_season_event, '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( $other_season_event, '2026', true ) );
    }

    public function test_is_event_allowed_rejects_unknown_numeric_event(): void {
        $this->register_events( [ $this->create_event( 10 ) ] );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );

        // Id 77 is hydrated by the get_event stub without a competition, so it is not ours.
        $this->assertFalse( $competition->is_event_allowed( 77, '2026', false ) );
    }

    public function test_is_event_allowed_does_not_trust_arbitrary_object(): void {
        $foreign = $this->create_event( 10, 'Men Doubles', 999 );
        $this->register_events( [ $foreign ] );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10 ] ] ] );

        $forged                 = new stdClass();
        $forged->id             = 10;
        $forged->competition_id = 100;
        $forged->seasons        = [ '2026' => [ 'name' => '2026' ] ];

        $this->assertFalse( $competition->is_event_allowed( $forged, '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( $forged, '2026', true ) );
        $this->assertNull( $competition->resolve_event( $forged, '2026' ) );
    }

    public function test_arbitrary_object_is_resolved_to_the_stored_event(): void {
        $own = $this->create_event( 10 );
        $this->register_events( [ $own ] );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );

        $reference = new stdClass();
        $reference->id = 10;

        $this->assertSame( $own, $competition->resolve_event( $reference, '2026' ) );
    }

    public function test_is_event_allowed_resolves_event_by_numeric_id_without_listing_events(): void {
        $this->register_events( [ $this->create_event( 10 ), $this->create_event( 20, 'Ladies Doubles' ) ] );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10 ] ] ] );

        $this->assertTrue( $competition->is_event_allowed( 10, '2026', false ) );
        $this->assertTrue( $competition->is_event_allowed( '10', '2026', true ) );
        $this->assertFalse( $competition->is_event_allowed( 20, '2026', false ) );
        $this->assertSame( [], $this->queries, 'Numeric lookup must use get_event and not list events.' );
    }

    public function test_zero_event_ids_are_rejected_even_when_a_global_event_exists(): void {
        $this->register_events( [ $this->create_event( 10 ) ] );
        $GLOBALS['event'] = $this->create_event( 10 );
        $competition      = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );

        $this->assertFalse( $competition->is_event_allowed( 0, '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( '0', '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( '', '2026', false ) );
    }

    public function test_is_event_allowed_resolves_event_by_string_name(): void {
        $this->register_events(
            [
                $this->create_event( 10, 'Men Doubles' ),
                $this->create_event( 20, 'Ladies Doubles' ),
            ]
        );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10 ] ] ] );

        $this->assertTrue( $competition->is_event_allowed( 'Men Doubles', '2026', false ) );
        $this->assertTrue( $competition->is_event_allowed( 'men-doubles', '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( 'ladies-doubles', '2026', false ) );
        $this->assertFalse( $competition->is_event_allowed( 'unknown-event', '2026', false ) );
    }

    public function test_name_lookup_does_not_mutate_cached_events_and_is_not_filtered_by_entered_teams(): void {
        $this->register_events( [ $this->create_event( 10, 'Men Doubles' ) ] );
        $competition         = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );
        $sentinel            = $this->create_event( 99, 'Cached' );
        $competition->events = [ $sentinel ];
        $competition->event_index = [ 99 => 0 ];

        $this->assertTrue( $competition->is_event_allowed( 'Men Doubles', '2026', true ) );

        $this->assertSame( [ $sentinel ], $competition->events );
        $this->assertSame( [ 99 => 0 ], $competition->event_index );
        $this->assertCount( 1, $this->queries );
        $this->assertStringNotContainsString( 'league_teams', $this->queries[0] );
    }

    public function test_get_entry_events_uses_event_season_configuration_not_entered_teams(): void {
        $this->register_events(
            [
                $this->create_event( 10, 'Men Doubles' ),
                $this->create_event( 20, 'Ladies Doubles' ),
                $this->create_event( 30, 'Mixed Doubles' ),
                $this->create_event( 40, 'Old Season Event', 100, [ '2024' => [ 'name' => '2024' ] ] ),
            ]
        );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 10, 30 ] ] ] );

        $entry_events = $competition->get_entry_events( '2026' );

        $this->assertSame( [ 10, 30 ], array_map( static fn( Event $event ): int => $event->id, $entry_events ) );
        $this->assertCount( 1, $this->queries );
        $this->assertStringNotContainsString( 'league_teams', $this->queries[0], 'No season/team filter may be applied by the repository.' );
    }

    public function test_get_entry_events_returns_all_season_events_when_allowlist_absent(): void {
        $this->register_events( [ $this->create_event( 10 ), $this->create_event( 20, 'Ladies Doubles' ) ] );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );

        $this->assertCount( 2, $competition->get_entry_events( '2026' ) );
    }

    public function test_get_entry_events_returns_empty_when_explicitly_empty(): void {
        $this->register_events( [ $this->create_event( 10 ) ] );
        $competition = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [] ] ] );

        $this->assertSame( [], $competition->get_entry_events( '2026' ) );
    }

    public function test_get_entry_events_does_not_mutate_cached_events(): void {
        $this->register_events( [ $this->create_event( 10 ) ] );
        $competition         = $this->create_competition( [ '2026' => [ 'name' => '2026' ] ] );
        $competition->events = [];

        $competition->get_entry_events( '2026' );

        $this->assertSame( [], $competition->events );
    }

    public function test_get_frontend_events_shows_all_season_events_for_empty_allowlist_and_filters_populated(): void {
        $this->register_events( [ $this->create_event( 10 ), $this->create_event( 20, 'Ladies Doubles' ) ] );

        $empty = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [] ] ] );
        $this->assertCount( 2, $empty->get_frontend_events( '2026' ) );

        $populated = $this->create_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 20 ] ] ] );
        $events    = $populated->get_frontend_events( '2026' );
        $this->assertSame( [ 20 ], array_map( static fn( Event $event ): int => $event->id, $events ) );
    }
}
