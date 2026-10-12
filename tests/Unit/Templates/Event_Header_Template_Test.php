<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;

class Event_Header_Template_Test extends TestCase {
    private string $template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->template_path = dirname( __DIR__, 3 ) . '/templates/includes/event-header.php';
        if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
            define( 'RACKETMANAGER_PATH', dirname( __DIR__, 3 ) . '/' );
        }
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
        }
    }

    public function test_renders_with_enum_competition_type() {
        global $wp_query, $racketmanager;
        $wp_query = new \stdClass();
        $wp_query->query = [];
        $racketmanager = (object) [
            'date_format' => 'Y-m-d',
        ];

        $event = (object) [
            'name'        => 'Men Singles',
            'is_box'      => false,
            'competition' => (object) [
                'name'           => 'Summer Tournament',
                'type'           => Competition_Type::TOURNAMENT,
                'current_season' => [
                    'name'       => '2026',
                    'date_start' => '2026-06-01',
                    'date_end'   => '2026-06-15',
                ],
            ],
        ];
        $curr_season = '2026';
        $seasons     = [
            ['name' => '2026'],
        ];

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Men Singles', $output );
        $this->assertStringContainsString( 'Summer Tournament', $output );
        $this->assertStringContainsString( 'lta-icons.svg#icon-bracket', $output );
    }

    public function test_renders_with_league_enum_competition_type() {
        global $wp_query, $racketmanager;
        $wp_query = new \stdClass();
        $wp_query->query = [];
        $racketmanager = (object) [
            'date_format' => 'Y-m-d',
        ];

        $event = (object) [
            'name'        => 'Premier Division',
            'is_box'      => false,
            'competition' => (object) [
                'name'           => 'Summer League',
                'type'           => Competition_Type::LEAGUE,
                'current_season' => [
                    'name'       => '2026',
                    'date_start' => '2026-05-01',
                    'date_end'   => '2026-08-31',
                ],
            ],
        ];
        $curr_season = '2026';
        $seasons     = [
            ['name' => '2026'],
        ];

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Premier Division', $output );
        $this->assertStringContainsString( 'bootstrap-icons.svg#table', $output );
        $this->assertStringContainsString( 'value="/leagues/premier-division/"', $output );
    }
}
