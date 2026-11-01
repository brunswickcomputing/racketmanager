<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates\Admin;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\League_Standings_Page_View_Model;
use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Tests\Helpers\Creates_View_Renderer;

final class Admin_Array_Scope_Render_Test extends TestCase {
    use Creates_View_Renderer;

    private function render_strict( string $template, object|array $data ): string {
        set_error_handler( static function ( int $severity, string $message, string $file, int $line ): never {
            throw new ErrorException( $message, 0, $severity, $file, $line );
        } );
        try {
            return $this->create_view_renderer()->render_to_string( $template, $data );
        } finally {
            restore_error_handler();
        }
    }

    public function test_results_screen_renders_array_supplied_filters_and_checker_rows(): void {
        $html = $this->render_strict( 'admin/show-results', [
            'tab'                  => 'resultschecker',
            'season_select'        => '2026',
            'competition_select'   => '7',
            'event_select'         => '9',
            'results_check_filter' => 'all',
            'seasons'              => [ (object) [ 'name' => '2026' ] ],
            'competitions'         => [ (object) [ 'id' => 7, 'name' => 'Summer League' ] ],
            'events'               => [ (object) [ 'id' => 9, 'name' => 'Mixed Doubles' ] ],
            'results_checkers'     => [ (object) [
                'id' => 12, 'formatted_date' => '01/11/2026',
                'match_link' => '/match/12', 'match_title' => 'Leigh v Westcliff',
                'team_title' => 'Leigh A', 'player_link' => '/player/20',
                'player_name' => 'Alice Player', 'description' => 'Eligibility check',
                'show_status' => true, 'tooltip' => 'Approved by administrator', 'status_desc' => 'Approved',
            ] ],
            'pending_results_matches' => [], 'awaiting_confirmation_matches' => [],
            'challenged_results_matches' => [], 'approved_results_matches' => [],
            'postponed_matches' => [], 'tournament_results_matches' => [],
        ] );

        self::assertStringContainsString( "activaTab('resultschecker')", $html );
        self::assertStringContainsString( 'Summer League', $html );
        self::assertStringContainsString( 'Mixed Doubles', $html );
        self::assertStringContainsString( 'Alice Player', $html );
        self::assertStringContainsString( 'Eligibility check', $html );
        self::assertMatchesRegularExpression( '/value="2026"\s+selected="selected"/', $html );
    }

    public function test_settings_array_scope_matches_typed_page_and_preserves_child_options(): void {
        $options = [
            'keys' => [ 'googleMapsKey' => 'maps-key', 'recaptchaSiteKey' => 'site-key' ],
            'billing' => [ 'bankName' => 'Tennis Bank', 'invoiceNumber' => 124 ],
            'checks' => [ 'ageLimitCheck' => true, 'rosterLeadTime' => 48 ],
            'display' => [ 'wtn' => true ],
        ];
        $html = $this->render_strict( 'admin/show-settings', [ 'tab' => 'billing', 'options' => $options ] );
        $typed_html = $this->render_strict( 'admin/show-settings', new Options_Page_View_Model( options: $options, tab: 'billing' ) );

        self::assertSame( $typed_html, $html );
        self::assertStringContainsString( "activaTab('billing')", $html );
        self::assertStringContainsString( 'maps-key', $html );
        self::assertStringContainsString( 'Tennis Bank', $html );
        self::assertStringContainsString( "value='48'", $html );
    }

    public function test_crosstable_reads_array_rows_and_precomputed_scores(): void {
        $html = $this->render_strict( 'admin/league/crosstable', [
            'league' => (object) [ 'title' => 'Premier', 'num_teams' => 2 ],
            'teams' => [],
            'crosstable_teams' => [ (object) [ 'id' => 10, 'title' => 'Leigh A' ], (object) [ 'id' => 20, 'title' => 'Westcliff B' ] ],
            'crosstable_cells' => [ 10 => [ 20 => '4-2' ], 20 => [ 10 => '2-4' ] ],
        ] );

        self::assertStringContainsString( 'Leigh A', $html );
        self::assertStringContainsString( 'Westcliff B', $html );
        self::assertStringContainsString( '>4-2</td>', $html );
        self::assertStringContainsString( '>2-4</td>', $html );
    }

    public function test_event_matches_reads_explicit_vm_scope(): void {
        $html = $this->render_strict( 'admin/event/matches', [ 'vm' => (object) [
            'matches' => [ (object) [ 'match_day' => 3, 'date' => '2026-11-01', 'match_title' => 'Leigh v Westcliff', 'league' => (object) [ 'title' => 'Premier' ] ] ],
        ] ] );

        self::assertStringContainsString( 'Leigh v Westcliff', $html );
        self::assertStringContainsString( '2026-11-01', $html );
        self::assertStringContainsString( 'Premier', $html );
    }

    public function test_league_matches_preserves_filters_scores_and_pagination(): void {
        $league = (object) [
            'id' => 8, 'num_sets' => 1, 'num_rubbers' => 0,
            'event' => (object) [ 'competition' => (object) [ 'type' => 'league', 'is_championship' => false ] ],
        ];
        $html = $this->render_strict( 'admin/league/matches', [
            'league' => $league, 'season' => '2026', 'group' => 'A', 'match_day' => 3, 'team_id' => 10,
            'teams' => [ (object) [ 'id' => 10, 'title' => 'Leigh A' ] ],
            'pagination_matches' => '<span>Page 2 of 3</span>',
            'matches' => [ (object) [
                'id' => 44, 'home_team' => 10, 'away_team' => 20, 'match_title' => 'Leigh v Westcliff',
                'sets' => [ 1 => [ 'player1' => 6, 'player2' => 4 ] ], 'home_points' => 1, 'away_points' => 0,
            ] ],
        ] );

        self::assertStringContainsString( 'Leigh v Westcliff', $html );
        self::assertStringContainsString( 'name="current_match_day" value="3"', $html );
        self::assertStringContainsString( 'name="group" value="A"', $html );
        self::assertStringContainsString( 'name="custom[44][sets][1][player1]" value="6"', $html );
        self::assertStringContainsString( 'Page 2 of 3', $html );
    }

    public function test_standings_accepts_non_tournament_view_model_and_array_scope(): void {
        $league = (object) [ 'event' => (object) [ 'competition' => (object) [
            'type' => 'league', 'is_championship' => false, 'settings' => [ 'team_ranking' => 'manual' ],
        ] ] ];
        $vm = new League_Standings_Page_View_Model( league: $league, season: '2026' );
        $typed_html = $this->render_strict( 'admin/league/standings', $vm );
        $array_html = $this->render_strict( 'admin/league/standings', [ 'league' => $league, 'season' => '2026', 'teams' => [] ] );

        self::assertSame( $typed_html, $array_html );
        self::assertStringContainsString( 'name="season" value="2026"', $typed_html );
        self::assertStringContainsString( 'Save Ranking', $typed_html );
    }

    public function test_constitution_preserves_supplied_state_instead_of_using_defaults(): void {
        $html = $this->render_strict( 'admin/event/constitution', [
            'event' => (object) [ 'id' => 9, 'is_box' => false, 'leagues' => [ (object) [ 'id' => 8 ] ] ],
            'seasons' => [ '2026' ], 'event_seasons' => [ '2026' ],
            'latest_season' => '2026', 'latest_event_season' => '2025',
            'constitution_action' => 'create', 'constitution_exists' => true, 'updates_allowed' => false,
            'teams' => [], 'leagues' => [], 'standing_status' => [],
        ] );

        self::assertStringContainsString( 'name="constitutionAction" value="create"', $html );
        self::assertStringContainsString( 'name="latest_event_season" value="2025"', $html );
        self::assertStringContainsString( 'Email Constitution', $html );
        self::assertStringNotContainsString( 'No seasons defined', $html );
    }

    public function test_upgrade_templates_receive_array_data(): void {
        $html = $this->render_strict( 'admin/show-upgrade', [ 'installed' => '10.2.3' ] );
        self::assertStringContainsString( 'Current version 10.2.3', $html );

        $upgrade = new class {
            public int $calls = 0;

            public function run(): void {
                ++$this->calls;
            }
        };
        $html = $this->render_strict( 'admin/upgrade/do-upgrade', [ 'upgrade' => $upgrade ] );
        self::assertSame( 1, $upgrade->calls );
        self::assertStringContainsString( 'Upgrade successful', $html );
    }
}