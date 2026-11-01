<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

require_once __DIR__ . '/../../wp-stubs.php';

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Services\View\Php_View_Renderer;
use stdClass;

/**
 * Regression coverage for explicit $vm / $vars scope in tournament and match templates
 * (no extract()): required inputs are guarded, and per-row partials never fall back to page-level data.
 */
final class Tournament_Match_Vm_Scope_Guard_Test extends TestCase {
    private Php_View_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
    }

    public function test_draw_body_skips_null_fixture_rows_instead_of_rendering_page_level_card(): void {
        $draw          = new stdClass();
        $draw->leagues = array();

        $html = $this->renderer->render_to_string(
            'tournament/draw-body',
            array(
                'draw'     => $draw,
                'fixtures' => array( null, new Tournament_Match_Card_View_Model( id: 1, show_header_location: true, location: 'ROW-CARD' ) ),
                'card'     => new Tournament_Match_Card_View_Model( id: 2, show_header_location: true, location: 'STALE-PAGE-CARD' ),
            )
        );

        $this->assertStringContainsString( 'ROW-CARD', $html );
        $this->assertStringNotContainsString( 'STALE-PAGE-CARD', $html );
    }

    public function test_match_tournament_reads_fixture_details_from_vars_and_renders_nothing_without_them(): void {
        $this->assertSame( '', trim( $this->renderer->render_to_string( 'match-tournament', array( 'tournament' => new stdClass() ) ) ) );
    }

    public function test_tournament_draw_renders_nothing_when_draw_missing(): void {
        $this->assertSame( '', trim( $this->renderer->render_to_string( 'tournament/draw', array( 'tournament' => new stdClass() ) ) ) );
    }

    public function test_legacy_match_template_does_not_treat_arbitrary_vm_with_id_as_match(): void {
        $vm     = new stdClass();
        $vm->id = 99;

        set_error_handler( static fn(): bool => true, E_USER_DEPRECATED | E_DEPRECATED );
        try {
            $html = $this->renderer->render_to_string( 'match', $vm );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( '', trim( $html ) );
    }
}
