<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Club_Link_View_Model;

class Player_Presenter_Refactored_Methods_Test extends TestCase {
    private Player_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Player_Presenter();
    }

    public function test_present_titles_with_array_and_object_matches_calculates_urls(): void {
        $titles = [
            '2026' => [
                'Championship' => [
                    [
                        'draw' => 'Men Singles',
                    ],
                    (object) [
                        'draw' => 'Mixed Doubles',
                    ],
                ],
            ],
        ];

        $presented = $this->presenter->present_titles( $titles );

        $this->assertArrayHasKey( '2026', $presented );
        $this->assertArrayHasKey( 'Championship', $presented['2026'] );
        $matches = $presented['2026']['Championship'];
        $this->assertCount( 2, $matches );

        $this->assertSame( '/tournament/championship/', $matches[0]->tournament_url );
        $this->assertSame( '/tournament/championship/draw/men-singles/', $matches[0]->draw_url );

        $this->assertSame( '/tournament/championship/', $matches[1]->tournament_url );
        $this->assertSame( '/tournament/championship/draw/mixed-doubles/', $matches[1]->draw_url );
    }

    public function test_present_titles_with_tournament_override_on_match(): void {
        $titles = [
            '2026' => [
                'Outer Tournament' => [
                    [
                        'tournament' => 'Override Tournament',
                        'draw'       => 'Finals',
                    ],
                ],
            ],
        ];

        $presented = $this->presenter->present_titles( $titles );
        $match     = $presented['2026']['Outer Tournament'][0];

        $this->assertSame( '/tournament/override-tournament/', $match->tournament_url );
        $this->assertSame( '/tournament/override-tournament/draw/finals/', $match->draw_url );
    }

    public function test_present_titles_with_integer_tournament_key(): void {
        $titles = [
            '2026' => [
                101 => [
                    [
                        'draw' => 'Main',
                    ],
                ],
            ],
        ];

        $presented = $this->presenter->present_titles( $titles );

        $this->assertArrayHasKey( 101, $presented['2026'] );
        $match = $presented['2026'][101][0];

        $this->assertSame( '/tournament/101/', $match->tournament_url );
        $this->assertSame( '/tournament/101/draw/main/', $match->draw_url );
    }

    public function test_present_titles_with_non_iterable_matches_gives_empty_array(): void {
        $titles = [
            '2026' => [
                'Championship' => 'non-iterable-value',
            ],
        ];

        $presented = $this->presenter->present_titles( $titles );

        $this->assertSame( [ '2026' => [ 'Championship' => [] ] ], $presented );
    }

    public function test_present_titles_with_non_array_season_is_kept_as_is(): void {
        $titles = [
            '2025' => 'cancelled-season',
            '2026' => [],
        ];

        $presented = $this->presenter->present_titles( $titles );

        $this->assertSame( 'cancelled-season', $presented['2025'] );
        $this->assertSame( [], $presented['2026'] );
    }

    public function test_present_titles_with_array_iterator_matches(): void {
        $matches   = new ArrayIterator(
            [
                [ 'draw' => 'Quarter' ],
                [ 'draw' => 'Semi' ],
            ]
        );
        $titles   = [
            '2026' => [
                'Cup' => $matches,
            ],
        ];

        $presented = $this->presenter->present_titles( $titles );

        $this->assertCount( 2, $presented['2026']['Cup'] );
        $this->assertSame( '/tournament/cup/draw/quarter/', $presented['2026']['Cup'][0]->draw_url );
        $this->assertSame( '/tournament/cup/draw/semi/', $presented['2026']['Cup'][1]->draw_url );
    }

    public function test_present_titles_with_empty_array_returns_empty_array(): void {
        $this->assertSame( [], $this->presenter->present_titles( [] ) );
    }

    public function test_display_name_precedence_in_present_player(): void {
        $player_with_display_name = new class() {
            public string $display_name = 'Display Name Priority';
            public string $firstname    = 'First';
            public string $surname      = 'Last';

            public function get_display_name(): string {
                return 'Method Name';
            }
        };

        $vm1 = $this->presenter->present_player( $player_with_display_name );
        $this->assertSame( 'Display Name Priority', $vm1->display_name );

        $player_with_method_only = new class() {
            public string $firstname = 'First';
            public string $surname   = 'Last';

            public function get_display_name(): string {
                return 'Method Name';
            }
        };

        $vm2 = $this->presenter->present_player( $player_with_method_only );
        $this->assertSame( 'Method Name', $vm2->display_name );

        $player_with_names_only = (object) [
            'firstname' => 'First',
            'surname'   => 'Last',
        ];

        $vm3 = $this->presenter->present_player( $player_with_names_only );
        $this->assertSame( 'First Last', $vm3->display_name );

        $player_with_no_names = (object) [];

        $vm4 = $this->presenter->present_player( $player_with_no_names );
        $this->assertSame( '', $vm4->display_name );
    }

    public function test_stats_precedence_in_present_player(): void {
        $player_with_both = (object) [
            'stats'      => [ 'played' => 10, 'won' => 8 ],
            'statistics' => (object) [ 'played' => 5, 'won' => 2 ],
        ];

        $vm1 = $this->presenter->present_player( $player_with_both );
        $this->assertSame( [ 'played' => 10, 'won' => 8 ], $vm1->stats );

        $player_with_non_array_stats = (object) [
            'stats'      => 'not-an-array',
            'statistics' => (object) [ 'played' => 5, 'won' => 2 ],
        ];

        $vm2 = $this->presenter->present_player( $player_with_non_array_stats );
        $this->assertSame( [ 'played' => 5, 'won' => 2 ], $vm2->stats );

        $player_with_neither = (object) [];

        $vm3 = $this->presenter->present_player( $player_with_neither );
        $this->assertNull( $vm3->stats );
    }

    public function test_present_club_membership_falsy_dates_and_casts(): void {
        $data = [
            'club_name'             => 'Test Club',
            'approval_date'         => '0',
            'removal_date'          => '',
            'registration_date'     => 0,
            'club_id'               => '12',
            'user_id'               => '34',
            'registration_id'       => '56',
            'approved_by_user_id'   => '78',
            'registered_by_user_id' => '90',
            'removed_by_user_id'    => '99',
            'system_record'         => '0',
        ];

        $vm = $this->presenter->present_club_membership( $data );

        $this->assertSame( 'Test Club', $vm->club_name );
        $this->assertNull( $vm->approval_date );
        $this->assertSame( '', $vm->approval_date_display );
        $this->assertNull( $vm->removal_date );
        $this->assertSame( '', $vm->removal_date_display );
        $this->assertNull( $vm->registration_date );
        $this->assertSame( '', $vm->registration_date_display );

        $this->assertSame( 12, $vm->club_id );
        $this->assertSame( 34, $vm->user_id );
        $this->assertSame( 56, $vm->registration_id );
        $this->assertSame( 78, $vm->approved_by_user_id );
        $this->assertSame( 90, $vm->registered_by_user_id );
        $this->assertSame( 99, $vm->removed_by_user_id );
        $this->assertFalse( $vm->system_record );
    }

    public function test_present_club_membership_truthy_dates_and_truthy_system_record(): void {
        $data = (object) [
            'club_name'         => 'Active Club',
            'approval_date'     => '2026-03-01',
            'removal_date'      => '2026-04-01',
            'registration_date' => '2026-01-01',
            'system_record'     => '1',
        ];

        $vm = $this->presenter->present_club_membership( $data );

        $this->assertSame( '2026-03-01', $vm->approval_date );
        $this->assertSame( '2026-04-01', $vm->removal_date );
        $this->assertSame( '2026-01-01', $vm->registration_date );
        $this->assertTrue( $vm->system_record );
    }

    public function test_club_link_item_preserves_false_and_zero_url_semantics(): void {
        $player = (object) [ 'display_name' => 'Test Player' ];

        $item_with_false_url = [
            'club_name' => 'False URL Club',
            'club_url'  => false,
        ];
        $item_with_zero_url  = [
            'club_name' => 'Zero URL Club',
            'club_url'  => 0,
        ];
        $item_with_empty_url = [
            'club_name' => 'Empty URL Club',
            'club_url'  => '',
        ];

        $vm = $this->presenter->present_player(
            $player,
            clubs: [ $item_with_false_url, $item_with_zero_url, $item_with_empty_url ]
        );

        $this->assertCount( 3, $vm->clubs );
        $this->assertInstanceOf( Club_Link_View_Model::class, $vm->clubs[0] );
        $this->assertSame( '', $vm->clubs[0]->club_url );

        $this->assertInstanceOf( Club_Link_View_Model::class, $vm->clubs[1] );
        $this->assertSame( '0', $vm->clubs[1]->club_url );

        $this->assertInstanceOf( Club_Link_View_Model::class, $vm->clubs[2] );
        $this->assertSame( '/clubs/empty-url-club/', $vm->clubs[2]->club_url );
    }

    public function test_get_win_loss_summary_calculates_win_pct_when_missing(): void {
        $stats = [
            'total' => [
                'played'       => 4,
                'matches_won'  => 3,
                'matches_lost' => 1,
            ],
        ];

        $summary = $this->presenter->get_win_loss_summary( $stats );
        $this->assertNotNull( $summary );
        $this->assertSame( 4, $summary['played'] );
        $this->assertSame( 3, $summary['matches_won'] );
        $this->assertSame( 1, $summary['matches_lost'] );
        $this->assertSame( 75.0, $summary['win_pct'] );
        $this->assertSame( '3-1 (4)', $summary['record_text'] );
    }

    public function test_get_win_loss_summary_returns_null_for_invalid_and_empty_inputs(): void {
        $this->assertNull( $this->presenter->get_win_loss_summary( [] ) );
        $this->assertNull( $this->presenter->get_win_loss_summary( (object) [] ) );
        $this->assertNull( $this->presenter->get_win_loss_summary( 123 ) );
        $this->assertNull( $this->presenter->get_win_loss_summary( [ 'total' => [] ] ) );
        $this->assertNull( $this->presenter->get_win_loss_summary( (object) [ 'total' => (object) [ 'played' => -1 ] ] ) );
    }

    public function test_extract_player_id_precedence_and_fallbacks(): void {
        $league = (object) [];

        // 1. Array with id.
        $links = $this->presenter->present_league_player_links( $league, [ [ 'id' => 10, 'display_name' => 'P10' ] ] );
        $this->assertArrayHasKey( 10, $links );

        // 2. Array without id.
        $links = $this->presenter->present_league_player_links( $league, [ [ 'display_name' => 'P0' ] ] );
        $this->assertArrayHasKey( 0, $links );

        // 3. Object with 'id'.
        $player_id = (object) [ 'id' => 20, 'display_name' => 'P20' ];
        $this->assertSame( 20, $this->presenter->present_player( $player_id )->id );

        // 4. Object with 'ID' (capitalized).
        $player_cap_id = (object) [ 'ID' => 30, 'display_name' => 'P30' ];
        $this->assertSame( 30, $this->presenter->present_player( $player_cap_id )->id );

        // 5. Object with both 'id' and 'ID': 'id' takes precedence.
        $player_both = (object) [ 'id' => 40, 'ID' => 50, 'display_name' => 'P40' ];
        $this->assertSame( 40, $this->presenter->present_player( $player_both )->id );

        // 6. Object with get_id() method.
        $player_getter = new class() {
            public string $display_name = 'P60';
            public function get_id(): int {
                return 60;
            }
        };
        $this->assertSame( 60, $this->presenter->present_player( $player_getter )->id );

        // 7. Object with 'id' and get_id(): 'id' takes precedence.
        $player_id_and_getter = new class() {
            public int $id = 70;
            public string $display_name = 'P70';
            public function get_id(): int {
                return 80;
            }
        };
        $this->assertSame( 70, $this->presenter->present_player( $player_id_and_getter )->id );

        // 8. Object with neither id, ID, nor get_id().
        $player_none = (object) [ 'display_name' => 'P0' ];
        $this->assertSame( 0, $this->presenter->present_player( $player_none )->id );
    }

    public function test_extract_player_name_precedence_and_fallbacks(): void {
        $league = (object) [
            'title'          => 'League',
            'current_season' => [ 'name' => '2026' ],
        ];

        // 1. Array with fullname.
        $links = $this->presenter->present_league_player_links( $league, [ [ 'id' => 1, 'fullname' => 'Full Name', 'display_name' => 'Display Name' ] ] );
        $this->assertStringContainsString( '/player/full-name/', $links[1]['player_link'] );

        // 2. Array with display_name only.
        $links = $this->presenter->present_league_player_links( $league, [ [ 'id' => 2, 'display_name' => 'Display Only' ] ] );
        $this->assertStringContainsString( '/player/display-only/', $links[2]['player_link'] );

        // 3. Array with neither.
        $links = $this->presenter->present_league_player_links( $league, [ [ 'id' => 3 ] ] );
        $this->assertStringContainsString( '/player//', $links[3]['player_link'] );

        // 4. Object with get_fullname() and display_name: get_fullname() takes precedence.
        $player_getter = new class() {
            public int $id              = 4;
            public string $display_name = 'Ignored Display';
            public function get_fullname(): string {
                return 'Method Fullname';
            }
        };
        $links = $this->presenter->present_league_player_links( $league, [ $player_getter ] );
        $this->assertStringContainsString( '/player/method-fullname/', $links[4]['player_link'] );

        // 5. Object with display_name.
        $player_display = (object) [ 'id' => 5, 'display_name' => 'Display Prop' ];
        $links = $this->presenter->present_league_player_links( $league, [ $player_display ] );
        $this->assertStringContainsString( '/player/display-prop/', $links[5]['player_link'] );

        // 6. Object with firstname and surname fallback.
        $player_names = (object) [ 'id' => 6, 'firstname' => 'Alice', 'surname' => 'Smith' ];
        $links = $this->presenter->present_league_player_links( $league, [ $player_names ] );
        $this->assertStringContainsString( '/player/alice-smith/', $links[6]['player_link'] );

        // 7. Object with neither.
        $player_empty = (object) [ 'id' => 7 ];
        $links = $this->presenter->present_league_player_links( $league, [ $player_empty ] );
        $this->assertStringContainsString( '/player//', $links[7]['player_link'] );
    }
}
