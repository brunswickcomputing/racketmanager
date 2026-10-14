<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;

final class Admin_Redirect_Url_Builder_Test extends TestCase {

    public function test_draw_view_forces_view_and_preserves_tab(): void {
        $url = Admin_Redirect_Url_Builder::tournament_draw_view(
            array(
                'page' => 'racketmanager-tournaments',
                'view' => 'something-else',
            ),
            array(),
            'draw',
            10,
            20,
            'fixtures'
        );

        self::assertStringContainsString( 'admin.php?', $url );
        self::assertStringContainsString( 'view=draw', $url );
        self::assertStringContainsString( 'tournament=10', $url );
        self::assertStringContainsString( 'league=20', $url );
        self::assertStringContainsString( 'tab=fixtures', $url );
    }

    public function test_preserves_optional_context_params_from_query_or_post(): void {
        $url = Admin_Redirect_Url_Builder::tournament_fixture(
            array(
                'page' => 'racketmanager-tournaments',
                'leg'  => '2',
            ),
            array(
                'mode' => 'edit',
            ),
            1,
            2,
            'semi',
            123
        );

        self::assertStringContainsString( 'view=match', $url );
        self::assertStringContainsString( 'leg=2', $url );
        self::assertStringContainsString( 'mode=edit', $url );
        self::assertStringContainsString( 'edit=123', $url );
    }

    public function test_matches_redirect_includes_final_when_present(): void {
        $url = Admin_Redirect_Url_Builder::tournament_fixtures(
            array( 'page' => 'racketmanager-tournaments' ),
            array(),
            7,
            8,
            'final'
        );

        self::assertStringContainsString( 'view=fixtures', $url );
        self::assertStringContainsString( 'tournament=7', $url );
        self::assertStringContainsString( 'league_id=8', $url );
        self::assertStringContainsString( 'final=final', $url );
    }

    public function test_information_view_forces_view_and_preserves_tournament(): void {
        $url = Admin_Redirect_Url_Builder::tournament_information_view(
            array(
                'page' => 'racketmanager-tournaments',
                'view' => 'something-else',
            ),
            array(),
            44
        );

        self::assertStringContainsString( 'admin.php?', $url );
        self::assertStringContainsString( 'view=information', $url );
        self::assertStringContainsString( 'tournament=44', $url );
    }

    public function test_league_screen_keeps_identifying_args_and_filters(): void {
        $url = Admin_Redirect_Url_Builder::league_screen(
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'overview',
                'competition_id' => '12',
                'season'         => '2026',
                'group'          => 'East',
                'match_day'      => '3',
                'team_id'        => '10',
                'match_paged'    => '2',
                'standingstable' => 'custom',
                'contactTeam'    => '1',
                'doAction'       => 'delete',
            )
        );

        self::assertStringContainsString( 'admin.php?', $url );
        self::assertStringContainsString( 'page=racketmanager-leagues', $url );
        self::assertStringContainsString( 'view=overview', $url );
        self::assertStringContainsString( 'competition_id=12', $url );
        self::assertStringContainsString( 'season=2026', $url );
        self::assertStringContainsString( 'group=East', $url );
        self::assertStringContainsString( 'match_day=3', $url );
        self::assertStringContainsString( 'team_id=10', $url );
        self::assertStringContainsString( 'match_paged=2', $url );
        self::assertStringContainsString( 'standingstable=custom', $url );
        self::assertStringNotContainsString( 'contactTeam', $url );
        self::assertStringNotContainsString( 'doAction', $url );
    }

    public function test_league_screen_appends_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::league_screen(
            array(
                'page'      => 'racketmanager-leagues',
                'view'      => 'league',
                'league_id' => '5',
                'season'    => '2026',
            ),
            array(
                'tab' => 'matches',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-leagues', $url );
        self::assertStringContainsString( 'view=league', $url );
        self::assertStringContainsString( 'league_id=5', $url );
        self::assertStringContainsString( 'tab=matches', $url );
    }

    public function test_index_screen_uses_default_page(): void {
        $url = Admin_Redirect_Url_Builder::index_screen();

        self::assertStringContainsString( 'page=racketmanager', $url );
        self::assertStringNotContainsString( 'age_group', $url );
    }

    public function test_index_screen_preserves_age_group(): void {
        $url = Admin_Redirect_Url_Builder::index_screen( array( 'page' => 'racketmanager', 'age_group' => 'junior' ) );

        self::assertStringContainsString( 'page=racketmanager', $url );
        self::assertStringContainsString( 'age_group=junior', $url );
    }

    public function test_index_screen_drops_empty_age_group(): void {
        $url = Admin_Redirect_Url_Builder::index_screen( array( 'age_group' => '' ) );

        self::assertStringNotContainsString( 'age_group', $url );
    }

    public function test_season_screen_preserves_view_and_uses_default_page(): void {
        $url = Admin_Redirect_Url_Builder::season_screen( array( 'view' => 'overview' ) );

        self::assertStringContainsString( 'page=racketmanager-seasons', $url );
        self::assertStringContainsString( 'view=overview', $url );
    }

    public function test_competition_screen_preserves_identifying_params_and_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::competition_screen(
            array(
                'page'           => 'racketmanager-cups',
                'view'           => 'config',
                'competition_id' => '3',
                'season'         => '2025',
                'unwanted'       => 'drop',
            ),
            array( 'tab' => 'events' )
        );

        self::assertStringContainsString( 'page=racketmanager-cups', $url );
        self::assertStringContainsString( 'view=config', $url );
        self::assertStringContainsString( 'competition_id=3', $url );
        self::assertStringContainsString( 'season=2025', $url );
        self::assertStringContainsString( 'tab=events', $url );
        self::assertStringNotContainsString( 'unwanted', $url );
    }

    public function test_event_screen_preserves_identifying_params_and_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::event_screen(
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'event-config',
                'competition_id' => '1',
                'event_id'       => '10',
            ),
            array( 'tab' => 'fixtures' )
        );

        self::assertStringContainsString( 'page=racketmanager-leagues', $url );
        self::assertStringContainsString( 'view=event-config', $url );
        self::assertStringContainsString( 'competition_id=1', $url );
        self::assertStringContainsString( 'event_id=10', $url );
        self::assertStringContainsString( 'tab=fixtures', $url );
    }

    public function test_finances_screen_preserves_identifying_params_and_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::finances_screen(
            array(
                'page'           => 'racketmanager-finances',
                'view'           => 'charges',
                'competition_id' => '4',
                'season'         => '2026',
                'club_id'        => '12',
                'charges'        => '5',
                'unwanted'       => 'drop',
            ),
            array(
                'view'    => 'charge',
                'charges' => '10',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-finances', $url );
        self::assertStringContainsString( 'view=charge', $url );
        self::assertStringContainsString( 'charges=10', $url );
        self::assertStringContainsString( 'competition_id=4', $url );
        self::assertStringContainsString( 'season=2026', $url );
        self::assertStringContainsString( 'club_id=12', $url );
        self::assertStringNotContainsString( 'unwanted', $url );
    }

    public function test_finances_screen_handles_invoice_context(): void {
        $url = Admin_Redirect_Url_Builder::finances_screen(
            array(
                'invoice' => '20',
                'status'  => 'sent',
            ),
            array(
                'view' => 'invoice',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-finances', $url );
        self::assertStringContainsString( 'view=invoice', $url );
        self::assertStringContainsString( 'invoice=20', $url );
        self::assertStringContainsString( 'status=sent', $url );
    }

    public function test_players_screen_handles_query_and_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::players_screen(
            array(
                'page'      => 'racketmanager-clubs',
                'view'      => 'player',
                'player_id' => '10',
                'club_id'   => '3',
                'extra'     => 'ignored',
            ),
            array(
                'tab' => 'players',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-clubs', $url );
        self::assertStringContainsString( 'view=player', $url );
        self::assertStringContainsString( 'player_id=10', $url );
        self::assertStringContainsString( 'club_id=3', $url );
        self::assertStringContainsString( 'tab=players', $url );
        self::assertStringNotContainsString( 'extra=ignored', $url );
    }

    public function test_results_screen_handles_query_and_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::results_screen(
            array(
                'page'                 => 'racketmanager-results',
                'season'               => '2024',
                'competition'          => '5',
                'event'                => '12',
                'filterResultsChecker' => 'outstanding',
                'tab'                  => 'resultschecker',
                'extra'                => 'ignored',
            ),
            array(
                'filterResultsChecker' => '1',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-results', $url );
        self::assertStringContainsString( 'season=2024', $url );
        self::assertStringContainsString( 'competition=5', $url );
        self::assertStringContainsString( 'event=12', $url );
        self::assertStringContainsString( 'filterResultsChecker=1', $url );
        self::assertStringContainsString( 'tab=resultschecker', $url );
        self::assertStringNotContainsString( 'extra=ignored', $url );
    }

    public function test_options_screen_builds_default_url(): void {
        $url = Admin_Redirect_Url_Builder::options_screen();

        self::assertStringContainsString( 'page=racketmanager-settings', $url );
        self::assertStringNotContainsString( 'tab=', $url );
    }

    public function test_options_screen_preserves_tab(): void {
        $url = Admin_Redirect_Url_Builder::options_screen(
            array(
                'page'  => 'racketmanager-settings',
                'tab'   => 'billing',
                'extra' => 'ignored',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-settings', $url );
        self::assertStringContainsString( 'tab=billing', $url );
        self::assertStringNotContainsString( 'extra=ignored', $url );
    }

    public function test_options_screen_merges_extra_args(): void {
        $url = Admin_Redirect_Url_Builder::options_screen(
            array(
                'tab' => 'players',
            ),
            array(
                'tab' => 'championship',
            )
        );

        self::assertStringContainsString( 'page=racketmanager-settings', $url );
        self::assertStringContainsString( 'tab=championship', $url );
    }
}
