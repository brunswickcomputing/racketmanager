<?php
/**
 * League Standings Presenter Helper Test
 *
 * @package RacketManager
 * @subpackage Tests/Unit/Presentation/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\View_Models\League_Archive_View_Model;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;

final class League_Standings_Presenter_Helper_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        unset( $GLOBALS['wp_stubs_is_admin'] );
        $GLOBALS['wp_stubs_esc_html_callback'] = static fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_is_admin'], $GLOBALS['wp_stubs_esc_html_callback'] );
        parent::tearDown();
    }

    public function test_render_custom_standings_columns_returns_empty_when_fields_team_empty_or_missing(): void {
        $league = (object) [];
        $team = (object) [ 'id' => 1 ];
        self::assertSame( '', League_Standings_Presenter::render_custom_standings_columns( $league, $team, 'manual' ) );

        $league->fields_team = [];
        self::assertSame( '', League_Standings_Presenter::render_custom_standings_columns( $league, $team, 'manual' ) );
    }

    public function test_render_custom_standings_columns_admin_manual_with_single_and_keyed_inputs_and_outputs(): void {
        $GLOBALS['wp_stubs_is_admin'] = true;

        $league = (object) [
            'fields_team' => [
                'custom_single' => [ 'label' => 'Single Input', 'type' => 'input' ],
                'custom_keyed'  => [ 'label' => 'Keyed Input', 'type' => 'input', 'keys' => [ 'k1', 'k2' ] ],
                'custom_uninit' => [ 'label' => 'Uninit Keyed', 'type' => 'input', 'keys' => [ 'a', 'b' ] ],
                'custom_out'    => [ 'label' => 'Output Col', 'type' => 'output' ],
            ],
            'point_2_format' => '%d:%d',
            'standings'      => [ 'custom_out' => 1 ],
        ];
        $team = (object) [
            'id'            => 5,
            'custom_single' => '10',
            'custom_keyed'  => [ 'k1' => '2', 'k2' => '3' ],
            'custom_out'    => [ 7, 8 ],
        ];

        $output = League_Standings_Presenter::render_custom_standings_columns( $league, $team, 'manual' );

        $expected  = '<td class="column-custom_single column-num d-none d-md-table-cell" data-colname="Single Input"><input class="points" type="text" size="2" id="home_5" name="custom[5][custom_single]" value="10" /></td>';
        $expected .= '<td class="column-custom_keyed column-num d-none d-md-table-cell" data-colname="Keyed Input"><input class="points" type="text" size="2" id="home_5_k1" name="custom[5][custom_keyed][k1]" value="2" /><input class="points" type="text" size="2" id="home_5_k2" name="custom[5][custom_keyed][k2]" value="3" /></td>';
        $expected .= '<td class="column-custom_uninit column-num d-none d-md-table-cell" data-colname="Uninit Keyed"><input class="points" type="text" size="2" id="home_5_a" name="custom[5][custom_uninit][a]" value="" /><input class="points" type="text" size="2" id="home_5_b" name="custom[5][custom_uninit][b]" value="" /></td>';
        $expected .= '<td class="num column-custom_out d-none d-md-table-cell d-none d-md-table-cell" data-colname="Output Col">7:8</td>';

        self::assertSame( $expected, $output );
    }

    public function test_render_custom_standings_columns_frontend_renders_only_visible_columns(): void {
        $GLOBALS['wp_stubs_is_admin'] = false;

        $league = (object) [
            'fields_team' => [
                'custom_1' => [ 'label' => 'Col 1', 'type' => 'input' ],
                'custom_2' => [ 'label' => 'Col 2', 'type' => 'output' ],
                'custom_3' => [ 'label' => 'Col 3', 'type' => 'output' ],
            ],
            'point_2_format' => '%d-%d',
            'standings'      => [
                'custom_1' => 1,
                'custom_2' => 1,
                'custom_3' => 0,
            ],
        ];
        $team = (object) [
            'id'       => 1,
            'custom_1' => 'val1',
            'custom_2' => [ 4, 2 ],
            'custom_3' => [ 1, 0 ],
        ];

        $output = League_Standings_Presenter::render_custom_standings_columns( $league, $team, 'manual' );

        $expected  = '<td class="num column-custom_1 d-none d-md-table-cell d-none d-md-table-cell" data-colname="Col 1">val1</td>';
        $expected .= '<td class="num column-custom_2 d-none d-md-table-cell d-none d-md-table-cell" data-colname="Col 2">4-2</td>';

        self::assertSame( $expected, $output );
    }

    public function test_render_custom_standings_columns_score_rule_falls_back_to_visibility_check(): void {
        $GLOBALS['wp_stubs_is_admin'] = true;

        $league = (object) [
            'fields_team' => [
                'custom_input' => [ 'label' => 'Points Input', 'type' => 'input' ],
                'custom_out'   => [ 'label' => 'Points Output', 'type' => 'output' ],
            ],
            'point_2_format' => '%d-%d',
            'standings'      => [
                'custom_input' => 1,
                'custom_out'   => 0,
            ],
        ];
        $team = (object) [
            'id'           => 3,
            'custom_input' => '42',
            'custom_out'   => '10',
        ];

        $output = League_Standings_Presenter::render_custom_standings_columns( $league, $team, 'score' );

        $expected = '<td class="num column-custom_input d-none d-md-table-cell d-none d-md-table-cell" data-colname="Points Input">42</td>';
        self::assertSame( $expected, $output );
    }

    public function test_render_custom_standings_columns_escapes_malicious_markup(): void {
        $GLOBALS['wp_stubs_is_admin'] = true;

        $league = (object) [
            'fields_team' => [
                'bad<key>' => [ 'label' => 'Bad <script>label</script>', 'type' => 'input' ],
            ],
            'standings'   => [ 'bad<key>' => 1 ],
        ];
        $team = (object) [
            'id'        => '1" onfocus="alert(1)',
            'bad<key>' => '"><script>alert(2)</script>',
        ];

        $output = League_Standings_Presenter::render_custom_standings_columns( $league, $team, 'manual' );

        self::assertStringNotContainsString( '<script>', $output );
        self::assertStringNotContainsString( 'onfocus="alert(1)"', $output );
        self::assertStringContainsString( 'data-colname="Bad &lt;script&gt;label&lt;/script&gt;"', $output );
        self::assertStringContainsString( 'name="custom[1&quot; onfocus=&quot;alert(1)][bad&lt;key&gt;]"', $output );
        self::assertStringContainsString( 'value="&quot;&gt;&lt;script&gt;alert(2)&lt;/script&gt;"', $output );
    }

    public function test_is_column_visible_handles_null_and_populated_leagues(): void {
        self::assertFalse( League_Standings_Presenter::is_column_visible( null, 'status' ) );

        $league = (object) [
            'standings' => [
                'status'     => 1,
                'pld'        => 1,
                'won'        => 0,
                'winPercent' => 1,
            ],
        ];

        self::assertTrue( League_Standings_Presenter::is_column_visible( $league, 'status' ) );
        self::assertTrue( League_Standings_Presenter::is_column_visible( $league, 'pld' ) );
        self::assertFalse( League_Standings_Presenter::is_column_visible( $league, 'won' ) );
        self::assertFalse( League_Standings_Presenter::is_column_visible( $league, 'lost' ) );
        self::assertTrue( League_Standings_Presenter::is_column_visible( $league, 'winPercent' ) );
    }

    public function test_get_league_template_returns_configured_filename(): void {
        self::assertSame( '', League_Standings_Presenter::get_league_template( null, 'standings' ) );
        self::assertSame( '', League_Standings_Presenter::get_league_template( (object) [], '' ) );

        $league = (object) [
            'templates' => [
                'standings' => 'standings-custom.php',
                'crosstable' => 'crosstable-box.php',
            ],
        ];

        self::assertSame( 'standings-custom.php', League_Standings_Presenter::get_league_template( $league, 'standings' ) );
        self::assertSame( 'crosstable-box.php', League_Standings_Presenter::get_league_template( $league, 'crosstable' ) );
        self::assertSame( '', League_Standings_Presenter::get_league_template( $league, 'unknown' ) );
    }

    public function test_standings_view_model_delegates_to_presenter_helpers(): void {
        $league = (object) [
            'standings' => [
                'sets'  => 1,
                'games' => 0,
            ],
            'templates' => [
                'custom' => 'custom-tpl.php',
            ],
        ];

        $vm = new League_Standings_View_Model(
            template: 'standings',
            league: $league
        );

        self::assertTrue( $vm->show_column( 'sets' ) );
        self::assertFalse( $vm->show_column( 'games' ) );
        self::assertFalse( $vm->show_column( 'missing' ) );
        self::assertSame( 'custom-tpl.php', $vm->get_template_filename( 'custom' ) );
        self::assertSame( '', $vm->get_template_filename( 'missing' ) );
    }

    public function test_archive_view_model_get_template_filename(): void {
        $vm = new League_Archive_View_Model(
            league_id: 1,
            league_title: 'Premier League',
            league_seo_name: 'premier-league',
            competition_type: 'league',
            icon_path: '',
            icon_url: '',
            calendar_icon_url: '',
            event_name: 'Summer',
            event_url: '',
            competition_name: 'Summer League',
            competition_url: '',
            current_season_name: '2026',
            templates: [
                'standings' => 'custom-archive-standings.php',
            ]
        );

        self::assertSame( 'custom-archive-standings.php', $vm->get_template_filename( 'standings' ) );
        self::assertSame( '', $vm->get_template_filename( 'players' ) );
    }
}
