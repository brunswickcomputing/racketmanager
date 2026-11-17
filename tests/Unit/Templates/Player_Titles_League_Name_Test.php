<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\View\Php_View_Renderer;

final class Player_Titles_League_Name_Test extends TestCase {
    private Php_View_Renderer $view_renderer;

    protected function setUp(): void {
        parent::setUp();

        $template_path       = defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH . 'templates/' : dirname( __DIR__, 3 ) . '/templates/';
        $this->view_renderer = new Php_View_Renderer( $template_path );
    }

    private function render_titles( array $titles, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $this->view_renderer->render_to_string( 'player/titles', [], [
                'titles' => $titles,
            ] );
        } finally {
            restore_error_handler();
        }
    }

    public function test_titles_template_prefers_league_name_over_legacy_title(): void {
        $titles = [
            '2025/26' => [
                'Annual Open Tournament' => [
                    (object) [
                        'team_id'     => 1,
                        'winner_id'   => 1,
                        'loser_id'    => 2,
                        'tournament'  => 'Annual Open Tournament',
                        'draw'        => 'Men Singles Championship',
                        'league_name' => 'Canonical League Name',
                        'title'       => 'Legacy League Title',
                    ],
                ],
            ],
        ];

        $warnings = [];
        $html     = $this->render_titles( $titles, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Canonical League Name', $html );
        $this->assertStringNotContainsString( 'Legacy League Title', $html );
    }

    public function test_titles_template_prefers_league_name_inside_draw_link(): void {
        $titles = [
            '2025/26' => [
                'Annual Open Tournament' => [
                    (object) [
                        'team_id'     => 1,
                        'winner_id'   => 1,
                        'loser_id'    => 2,
                        'tournament'  => 'Annual Open Tournament',
                        'draw'        => 'Men Singles Championship',
                        'draw_url'    => '/tournament/annual-open-tournament/draw/men-singles-championship/',
                        'league_name' => 'Canonical League Name',
                        'title'       => 'Legacy League Title',
                    ],
                ],
            ],
        ];

        $warnings = [];
        $html     = $this->render_titles( $titles, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( '/tournament/annual-open-tournament/draw/men-singles-championship/', $html );
        $this->assertStringContainsString( 'Canonical League Name', $html );
        $this->assertStringNotContainsString( 'Legacy League Title', $html );
    }

    public function test_titles_template_falls_back_to_legacy_title_when_league_name_missing(): void {
        $titles = [
            '2024' => [
                'Annual Open Tournament' => [
                    (object) [
                        'team_id'    => 2,
                        'winner_id'  => 1,
                        'loser_id'   => 2,
                        'tournament' => 'Annual Open Tournament',
                        'draw'       => 'Men Doubles Championship',
                        'title'      => 'Legacy Only Title',
                    ],
                ],
            ],
        ];

        $warnings = [];
        $html     = $this->render_titles( $titles, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Legacy Only Title', $html );
    }
}
