<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

/**
 * Competitions returned and cached by the repository must be request-neutral: the request season and
 * the venue name are applied at the application boundary, never during hydration.
 */
final class Competition_Repository_Hydration_Test extends TestCase {
    use Creates_Competition_Season_Service;
    use Manages_Wordpress_Globals;

    private object $wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET                               = [];
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_mode']    = 'serialize';
        $GLOBALS['wp_stubs_cache']         = [];
        unset( $GLOBALS['wp'] );

        $row = (object) [
            'id'        => 7,
            'name'      => 'Summer Cups',
            'type'      => 'cup',
            'age_group' => 'open',
            'settings'  => json_encode( [ 'mode' => 'championship' ] ),
            'seasons'   => json_encode(
                [
                    '2016' => [
                        'name'       => '2016',
                        'venue'      => 12,
                        'date_start' => '2016-06-01',
                        'date_end'   => '2016-08-01',
                    ],
                    '2026' => [
                        'name'       => '2026',
                        'venue'      => 130,
                        'date_start' => '2026-06-01',
                        'date_end'   => '2026-08-01',
                    ],
                ]
            ),
        ];

        $this->wpdb = new class( $row ) extends wpdb {
            public int $row_queries = 0;

            public function __construct( private readonly object $row ) {
                $this->prefix = 'wp_';
            }

            public function prepare( $query, ...$args ): string {
                return (string) $query;
            }

            public function get_row( $query = null, $output = 'OBJECT', $y = 0 ): object {
                ++$this->row_queries;

                return $this->row;
            }

            public function get_results( $query = null, $output = 'OBJECT' ): array {
                return [ $this->row ];
            }
        };
        $GLOBALS['wpdb'] = $this->wpdb;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_ignores_request_season_and_does_not_resolve_venue(): void {
        $_GET['season']   = '2016';
        $_GET['season_7'] = '2016';
        $GLOBALS['wp']    = (object) [ 'query_vars' => [ 'season' => '2016' ] ];

        $competition = ( new Competition_Repository() )->find_by_id( 7 );

        $this->assertInstanceOf( Competition::class, $competition );
        $this->assertSame( '2026', $competition->current_season['name'], 'Hydration uses the domain default season, not the request' );
        $this->assertArrayNotHasKey( 'venue_name', $competition->current_season, 'Venue lookup belongs to Competition_Season_Service' );
    }

    public function test_cached_entity_is_request_neutral(): void {
        $_GET['season'] = '2016';
        $repository     = new Competition_Repository();
        $repository->find_by_id( 7 );

        $cached = $GLOBALS['wp_stubs_cache']['competitions']['7'] ?? null;

        $this->assertInstanceOf( Competition::class, $cached );
        $this->assertSame( '2026', $cached->current_season['name'] );
        $this->assertArrayNotHasKey( 'venue_name', $cached->current_season );
    }

    public function test_request_season_applied_by_service_does_not_leak_into_cache(): void {
        $repository = new Competition_Repository();
        $service    = $this->competition_season_service( [ 12 => 'Hadleigh', 130 => 'Thorpe Bay' ] );

        $_GET['season'] = '2016';
        $selected       = $repository->find_by_id( 7 );
        $data           = $service->resolve_current_season( $selected );
        $this->assertSame( '2016', $data['name'] );
        $this->assertSame( 'Hadleigh', $selected->current_season['venue_name'] );

        unset( $_GET['season'] );
        $neutral = $repository->find_by_id( 7 );

        $this->assertSame( 1, $this->wpdb->row_queries, 'The second lookup is a cache hit' );
        $this->assertSame( '2026', $neutral->current_season['name'] );
        $this->assertArrayNotHasKey( 'venue_name', $neutral->current_season );
        $this->assertSame( 'Thorpe Bay', $service->resolve_current_season( $neutral )['venue_name'] );
    }

    public function test_find_all_and_find_by_return_request_neutral_entities(): void {
        $_GET['season'] = '2016';
        $repository     = new Competition_Repository();

        $all = $repository->find_all();
        $by  = $repository->find_by( [ 'type' => 'cup' ] );

        $this->assertSame( '2026', $all[0]->current_season['name'] );
        $this->assertSame( '2026', $by[0]->current_season['name'] );
    }
}
