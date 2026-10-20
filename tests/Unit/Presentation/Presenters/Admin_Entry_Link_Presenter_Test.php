<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Admin_Entry_Link_Presenter_Test extends TestCase {

    private Admin_Entry_Link_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Admin_Entry_Link_Presenter();
    }

    public function test_club_entry_urls_returns_empty_when_entries_empty(): void {
        $competition = (object) array( 'name' => 'Summer Cup' );
        $urls        = $this->presenter->club_entry_urls( $competition, '2026', array() );

        self::assertSame( array(), $urls );
    }

    public function test_club_entry_urls_returns_empty_when_competition_name_missing(): void {
        $competition = (object) array();
        $entries     = array(
            (object) array( 'id' => 1, 'shortcode' => 'WTC' ),
        );
        $urls        = $this->presenter->club_entry_urls( $competition, '2026', $entries );

        self::assertSame( array(), $urls );
    }

    public function test_club_entry_urls_builds_map_keyed_by_shortcode_and_id_for_objects(): void {
        $competition = (object) array( 'name' => 'Mixed League' );
        $entries     = array(
            (object) array( 'id' => 10, 'shortcode' => 'WTC' ),
            (object) array( 'id' => 20, 'shortcode' => 'LTC' ),
        );

        $urls = $this->presenter->club_entry_urls( $competition, '2026', $entries );

        self::assertSame(
            array(
                'WTC' => '/entry-form/mixed-league/2026/wtc/',
                '10'  => '/entry-form/mixed-league/2026/wtc/',
                'LTC' => '/entry-form/mixed-league/2026/ltc/',
                '20'  => '/entry-form/mixed-league/2026/ltc/',
            ),
            $urls
        );
    }

    public function test_club_entry_urls_builds_map_for_arrays(): void {
        $competition = array( 'name' => 'Summer Cup' );
        $entries     = array(
            array( 'id' => 5, 'shortcode' => 'DEANES' ),
        );

        $urls = $this->presenter->club_entry_urls( $competition, '2025', $entries );

        self::assertSame(
            array(
                'DEANES' => '/entry-form/summer-cup/2025/deanes/',
                '5'      => '/entry-form/summer-cup/2025/deanes/',
            ),
            $urls
        );
    }

    public function test_player_entry_urls_returns_empty_when_tournament_name_missing(): void {
        $urls = $this->presenter->player_entry_urls(
            (object) array(),
            array(
                'confirmed' => array(
                    'D' => array(
                        (object) array( 'id' => 1, 'display_name' => 'John Doe' ),
                    ),
                ),
            )
        );

        self::assertSame( array(), $urls );
    }

    public function test_player_entry_urls_builds_map_for_objects_and_skips_non_arrays(): void {
        $tournament = (object) array( 'name' => 'Test Tournament' );
        $entries    = array(
            'confirmed' => array(
                'D' => array(
                    (object) array( 'id' => 1, 'display_name' => 'John Doe' ),
                ),
            ),
            'unpaid'    => 'not-an-array',
            'pending'   => array(
                'S' => array(
                    array( 'id' => 2, 'display_name' => 'Jane Roe' ),
                ),
            ),
            'withdrawn' => array(),
        );

        $urls = $this->presenter->player_entry_urls( $tournament, $entries );

        self::assertSame(
            array(
                'John Doe' => '/tournament/entry-form/test-tournament/player/john-doe/',
                '1'        => '/tournament/entry-form/test-tournament/player/john-doe/',
                'Jane Roe' => '/tournament/entry-form/test-tournament/player/jane-roe/',
                '2'        => '/tournament/entry-form/test-tournament/player/jane-roe/',
            ),
            $urls
        );
    }

    public function test_player_entry_urls_builds_map_for_array_tournament(): void {
        $urls = $this->presenter->player_entry_urls(
            array( 'name' => 'Spring Open' ),
            array(
                'confirmed' => array(
                    array(
                        array( 'id' => 9, 'display_name' => 'Pat Smith' ),
                    ),
                ),
            )
        );

        self::assertSame(
            array(
                'Pat Smith' => '/tournament/entry-form/spring-open/player/pat-smith/',
                '9'         => '/tournament/entry-form/spring-open/player/pat-smith/',
            ),
            $urls
        );
    }
}
