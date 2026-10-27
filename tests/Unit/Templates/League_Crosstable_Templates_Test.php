<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League_Team;
use stdClass;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

/**
 * Render-level contract of the two crosstable templates (P1 item 5a): they only echo the
 * pre-computed `crosstable_columns` / `crosstable_cells` / `crosstable_teams` values and no
 * longer call the legacy `get_league_query_service()` / `get_league_team()` helpers.
 */
final class League_Crosstable_Templates_Test extends TestCase {
    private const FRONTEND_TEMPLATE = '/templates/league/crosstable.php';
    private const ADMIN_TEMPLATE    = '/templates/admin/league/crosstable.php';

    /** @var string[] */
    private array $warnings = array();

    protected function setUp(): void {
        parent::setUp();
        $this->warnings = array();
        set_error_handler(
            function ( int $severity, string $message ): bool {
                $this->warnings[] = $message;
                return true;
            }
        );
    }

    protected function tearDown(): void {
        restore_error_handler();
        parent::tearDown();
    }

    public function test_frontend_crosstable_renders_cells_in_row_then_column_order(): void {
        $westcliff = (object) array( 'id' => 101, 'title' => 'Westcliff 1', 'rank' => 1 );
        $leigh     = (object) array( 'id' => 202, 'title' => 'Leigh 2', 'rank' => 2 );
        $thorpe    = (object) array( 'id' => 303, 'title' => 'Thorpe 3', 'rank' => 3 );

        $output = $this->render(
            self::FRONTEND_TEMPLATE,
            array(
                'league'             => $this->league(),
                'teams'              => array( $westcliff, $leigh, $thorpe ),        // ranked rows
                'crosstable_columns' => array( $thorpe, $westcliff, $leigh ),        // league order ≠ rank order
                'crosstable_cells'   => $this->cells( array( 101, 202, 303 ), array( 303, 101, 202 ) ),
            )
        );

        $this->assertSame( array(), $this->warnings );
        $this->assertStringContainsString( "aria-describedby='Crosstable Premier League'", $output );
        $this->assertSame( array( '1', '2', '3' ), $this->column_headers( $output ) );
        $this->assertSame(
            array(
                array( '1', 'Westcliff 1', '101v303', '&nbsp;', '101v202' ),
                array( '2', 'Leigh 2', '202v303', '202v101', '&nbsp;' ),
                array( '3', 'Thorpe 3', '&nbsp;', '303v101', '303v202' ),
            ),
            $this->rows( $output )
        );
    }

    public function test_frontend_crosstable_prints_blank_cell_when_column_or_cell_is_missing(): void {
        $westcliff = (object) array( 'id' => 101, 'title' => 'Westcliff 1', 'rank' => 1 );
        $leigh     = (object) array( 'id' => 202, 'title' => 'Leigh 2', 'rank' => 2 );

        $output = $this->render(
            self::FRONTEND_TEMPLATE,
            array(
                'league'             => $this->league(),
                'teams'              => array( $westcliff, $leigh ),
                'crosstable_columns' => array( $westcliff ),                           // second column unknown
                'crosstable_cells'   => array( 101 => array( 101 => '&nbsp;' ) ),      // Leigh row has no cells
            )
        );

        $this->assertSame( array(), $this->warnings );
        $this->assertSame( array( '1', '2' ), $this->column_headers( $output ) );
        $this->assertSame(
            array(
                array( '1', 'Westcliff 1', '&nbsp;', '&nbsp;' ),
                array( '2', 'Leigh 2', '&nbsp;', '&nbsp;' ),
            ),
            $this->rows( $output )
        );
    }

    public function test_frontend_crosstable_without_teams_shows_message_and_no_table(): void {
        $output = $this->render(
            self::FRONTEND_TEMPLATE,
            array(
                'league' => $this->league(),
                'teams'  => array(),
            )
        );

        $this->assertSame( array(), $this->warnings );
        $this->assertStringContainsString( 'No teams found', $output );
        $this->assertStringNotContainsString( '<table', $output );
    }

    public function test_admin_crosstable_uses_hydrated_teams_for_rank_rows_and_columns(): void {
        $westcliff = League_Team::from_object( (object) array( 'id' => 11, 'title' => 'Westcliff 1' ) );
        $leigh     = League_Team::from_object( (object) array( 'id' => 22, 'title' => 'Leigh 2' ) );
        $league    = $this->league();
        $league->num_teams = 2;

        $output = $this->render(
            self::ADMIN_TEMPLATE,
            array(
                'league'           => $league,
                'teams'            => array( (object) array( 'id' => 11, 'title' => 'RAW ROW' ) ), // must be ignored
                'crosstable_teams' => array( $westcliff, $leigh ),
                'crosstable_cells' => $this->cells( array( 11, 22 ), array( 11, 22 ) ),
            )
        );

        $this->assertSame( array(), $this->warnings );
        $this->assertStringNotContainsString( 'RAW ROW', $output );
        $this->assertStringContainsString( 'aria-describedby="Crosstable Premier League"', $output );
        $this->assertSame( array( '1', '2' ), $this->column_headers( $output ) );
        $this->assertSame(
            array(
                array( '1', 'Westcliff 1', '&nbsp;', '11v22' ),
                array( '2', 'Leigh 2', '22v11', '&nbsp;' ),
            ),
            $this->rows( $output )
        );
    }

    public function test_admin_crosstable_prints_blank_cells_when_num_teams_exceeds_hydrated_teams(): void {
        $westcliff = League_Team::from_object( (object) array( 'id' => 11, 'title' => 'Westcliff 1' ) );
        $league    = $this->league();
        $league->num_teams = 3; // league expects three teams, only one entered so far

        $output = $this->render(
            self::ADMIN_TEMPLATE,
            array(
                'league'           => $league,
                'teams'            => array( $westcliff ),
                'crosstable_teams' => array( $westcliff ),
                'crosstable_cells' => array( 11 => array( 11 => '&nbsp;' ) ),
            )
        );

        $this->assertSame( array(), $this->warnings );
        $this->assertSame( array( '1', '2', '3' ), $this->column_headers( $output ) );
        $this->assertSame( array( array( '1', 'Westcliff 1', '&nbsp;', '&nbsp;', '&nbsp;' ) ), $this->rows( $output ) );
    }

    /**
     * Score matrix in the `League_Query_Service::get_crosstable_field()` shape: '&nbsp;' on the diagonal.
     *
     * @param int[] $row_ids
     * @param int[] $column_ids
     *
     * @return array<int, array<int, string>>
     */
    private function cells( array $row_ids, array $column_ids ): array {
        $cells = array();
        foreach ( $row_ids as $row ) {
            foreach ( $column_ids as $column ) {
                $cells[ $row ][ $column ] = $row === $column ? '&nbsp;' : "{$row}v{$column}";
            }
        }
        return $cells;
    }

    private function league(): stdClass {
        $league        = new stdClass();
        $league->id    = 7;
        $league->title = 'Premier League';
        $league->teams = array();
        return $league;
    }

    /**
     * @param array<string, mixed> $vars Template variables.
     */
    private function render( string $template, array $vars ): string {
        $path = dirname( __DIR__, 3 ) . $template;
        ob_start();
        try {
            ( static function () use ( $path, $vars ): void {
                extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
                require $path;
            } )();
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /** @return string[] Text of the numbered `<th class="fixture">` column headers. */
    private function column_headers( string $html ): array {
        preg_match_all( '/<th class="fixture"[^>]*>(.*?)<\/th>/s', $html, $matches );
        return array_map( 'trim', $matches[1] );
    }

    /** @return string[][] Each body row as [ rank, title, cell… ]. */
    private function rows( string $html ): array {
        preg_match( '/<tbody>(.*)<\/tbody>/s', $html, $body );
        preg_match_all( '/<tr>(.*?)<\/tr>/s', $body[1] ?? '', $rows );
        $result = array();
        foreach ( $rows[1] as $row ) {
            preg_match_all( '/<t[hd][^>]*>(.*?)<\/t[hd]>/s', $row, $cells );
            $result[] = array_map( 'trim', $cells[1] );
        }
        return $result;
    }
}
