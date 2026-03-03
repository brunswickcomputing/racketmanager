<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

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
            'matches'
        );

        self::assertStringContainsString( 'admin.php?', $url );
        self::assertStringContainsString( 'view=draw', $url );
        self::assertStringContainsString( 'tournament=10', $url );
        self::assertStringContainsString( 'league=20', $url );
        self::assertStringContainsString( 'league-tab=matches', $url );
    }

    public function test_preserves_optional_context_params_from_query_or_post(): void {
        $url = Admin_Redirect_Url_Builder::tournament_match(
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
        $url = Admin_Redirect_Url_Builder::tournament_matches(
            array( 'page' => 'racketmanager-tournaments' ),
            array(),
            7,
            8,
            'final'
        );

        self::assertStringContainsString( 'view=matches', $url );
        self::assertStringContainsString( 'tournament=7', $url );
        self::assertStringContainsString( 'league_id=8', $url );
        self::assertStringContainsString( 'final=final', $url );
    }
}
