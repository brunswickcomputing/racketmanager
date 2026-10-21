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
