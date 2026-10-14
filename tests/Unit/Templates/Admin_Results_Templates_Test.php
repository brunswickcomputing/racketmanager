<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Admin_Results_Templates_Test extends TestCase {

    private function row( string $referrer = 'pending-results', ?string $result_link = null, string $tooltip = '' ): Admin_Results_Match_View_Model {
        return new Admin_Results_Match_View_Model(
            league_id: 12,
            league_title: 'Division 1',
            formatted_date: '2026-04-04',
            match_title: 'Home vs Away',
            match_link: "https://example.test/match/home-vs-away/?referrer={$referrer}",
            result_link: $result_link ?? "https://example.test/match/home-vs-away/result/?referrer={$referrer}",
            confirmed_display: 'Pending',
            score: '6-4 6-2',
            tooltip: $tooltip,
        );
    }

    public function test_pending_results_template_renders_referrer_links_without_php_terminator(): void {
        $pending_results_matches = array( $this->row( 'pending-results' ) );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/pending-results.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( '2026-04-04', $output );
        $this->assertStringContainsString( 'Home vs Away', $output );
        $this->assertStringContainsString( 'https://example.test/match/home-vs-away/?referrer=pending-results', $output );
        $this->assertStringContainsString( 'https://example.test/match/home-vs-away/result/?referrer=pending-results', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
        $this->assertStringContainsString( 'Enter result', $output );
    }

    public function test_awaiting_confirmation_template_renders_view_result_link(): void {
        $awaiting_confirmation_matches = array( $this->row( 'awaiting-confirmation' ) );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/awaiting-confirmation.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'View result', $output );
        $this->assertStringContainsString( 'result/?referrer=awaiting-confirmation', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_challenged_results_template_renders_view_result_link_and_score(): void {
        $challenged_results_matches = array( $this->row( 'challenge-results' ) );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/challenged-results.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Home vs Away', $output );
        $this->assertStringContainsString( '6-4 6-2', $output );
        $this->assertStringContainsString( 'View result', $output );
        $this->assertStringContainsString( 'result/?referrer=challenge-results', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_approved_results_template_renders_nonce_and_view_result_link(): void {
        $approved_results_matches = array( $this->row( 'results' ) );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/results.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'name="_wpnonce"', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( '6-4 6-2', $output );
        $this->assertStringContainsString( 'View result', $output );
        $this->assertStringContainsString( 'result/?referrer=results', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_postponed_template_renders_tooltip_and_enter_result_link(): void {
        $postponed_matches = array( $this->row( 'postponed', null, 'Match overdue by 3 days' ) );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/postponed.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'data-bs-title="Match overdue by 3 days"', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Home vs Away', $output );
        $this->assertStringContainsString( 'Enter result', $output );
        $this->assertStringContainsString( 'result/?referrer=postponed', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_tournament_template_renders_tooltip_and_direct_match_result_link(): void {
        // Tournament result link does not end with /result/
        $tournament_results_matches = array( $this->row( 'tournament', 'https://example.test/match/home-vs-away/?referrer=tournament', 'Overdue' ) );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/tournament.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'data-bs-title="Overdue"', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Home vs Away', $output );
        $this->assertStringContainsString( 'Enter result', $output );
        $this->assertStringContainsString( 'https://example.test/match/home-vs-away/?referrer=tournament', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_empty_results_shows_fallback_messages(): void {
        $pending_results_matches       = array();
        $awaiting_confirmation_matches = array();
        $challenged_results_matches    = array();
        $approved_results_matches      = array();
        $postponed_matches             = array();
        $tournament_results_matches    = array();

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/pending-results.php';
        $output_pending = (string) ob_get_clean();
        $this->assertStringContainsString( 'No results found for criteria', $output_pending );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/awaiting-confirmation.php';
        $output_awaiting = (string) ob_get_clean();
        $this->assertStringContainsString( 'No results found for criteria', $output_awaiting );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/challenged-results.php';
        $output_challenged = (string) ob_get_clean();
        $this->assertStringContainsString( 'No results found for criteria', $output_challenged );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/results.php';
        $output_approved = (string) ob_get_clean();
        $this->assertStringContainsString( 'No results found for criteria', $output_approved );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/postponed.php';
        $output_postponed = (string) ob_get_clean();
        $this->assertStringContainsString( 'No fixtures found for criteria', $output_postponed );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/results/tournament.php';
        $output_tournament = (string) ob_get_clean();
        $this->assertStringContainsString( 'No fixtures found for criteria', $output_tournament );
    }
}
