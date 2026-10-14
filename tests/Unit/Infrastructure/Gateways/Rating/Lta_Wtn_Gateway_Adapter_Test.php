<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Gateways\Rating;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player;
use Racketmanager\Exceptions\LTA_System_Not_Available_Exception;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Lta_Wtn_Gateway_Adapter_Test extends TestCase {

    private Lta_Wtn_Gateway_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->adapter = new Lta_Wtn_Gateway_Adapter();
        unset(
            $GLOBALS['wp_stubs_remote_get_return'],
            $GLOBALS['wp_stubs_remote_get_default'],
            $GLOBALS['wp_stubs_remote_get_callback']
        );
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_remote_get_return'],
            $GLOBALS['wp_stubs_remote_get_default'],
            $GLOBALS['wp_stubs_remote_get_callback']
        );
        parent::tearDown();
    }

    public function test_prepare_env_success_sets_cookies_and_args(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'session_token';

        $GLOBALS['wp_stubs_remote_get_return'] = [
            'https://competitions.lta.org.uk/cookiewall/' => [
                'response' => [ 'code' => 200 ],
                'cookies'  => [ $cookie ],
                'body'     => '<html>Cookiewall</html>',
            ],
        ];

        $this->adapter->prepare_env();
        $this->assertSame( 'session_token&c=1&cp=31', $cookie->value );
    }

    public function test_prepare_env_throws_exception_on_non_200_response(): void {
        $GLOBALS['wp_stubs_remote_get_return'] = [
            'https://competitions.lta.org.uk/cookiewall/' => [
                'response' => [ 'code' => 503 ],
                'cookies'  => [],
                'body'     => 'Service Unavailable',
            ],
        ];

        $this->expectException( LTA_System_Not_Available_Exception::class );
        $this->adapter->prepare_env();
    }

    public function test_fetch_player_wtn_success_parses_html_response(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '
            <div class="results">
                <h5 class="media__title">
                    <a href="/player/profile/12345">John Doe</a>
                </h5>
            </div>
        ';

        $profile_body = '
            <div class="player-profile">
                World Tennis Number
                <ul class="list--inline list">
                    <li class="list__item">
                        <span class="tag-duo__title">Singles</span>
                        <svg></svg> 22.4 </span>
                    </li>
                    <li class="list__item">
                        <span class="tag-duo__title">Doubles</span>
                        <svg></svg> 19.8 </span>
                    </li>
                </ul>
            </div>
        ';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body, $profile_body ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/profile/12345' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $profile_body,
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '10203040';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertTrue( $result['status'] );
        $this->assertIsArray( $result['value'] );
        $this->assertSame( '22.4', $result['value']['S'] ?? null );
        $this->assertSame( '19.8', $result['value']['D'] ?? null );
    }

    public function test_fetch_player_wtn_returns_error_status_when_player_search_fails(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return new WP_Error( 'http_request_failed', 'Connection timeout' );
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '99999999';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'search error', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_status_when_player_not_found(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => '<div class="no-results">No players found</div>',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '99999999';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'Player not found', $result['message'] );
    }

    public function test_fetch_player_wtn_encodes_search_query(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $requested_url = '';
        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, &$requested_url ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                $requested_url = $url;
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => '<div class="no-results">No players found</div>',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '10 20&30';

        $this->adapter->fetch_player_wtn( $player );

        $this->assertStringContainsString( 'Query=10%2020%2630', $requested_url );
    }

    public function test_fetch_player_wtn_returns_error_when_search_status_not_200(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 500 ],
                    'body'     => 'Server error',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'LTA number not found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_player_link_malformed(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => '<h5 class="media__title">No link here</h5>',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'Player link not found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_profile_request_fails_wp_error(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '<h5 class="media__title"><a href="/player/1">Player</a></h5>';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/1' ) ) {
                return new WP_Error( 'http_error', 'Profile connection timeout' );
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'View error', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_profile_response_not_200(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '<h5 class="media__title"><a href="/player/1">Player</a></h5>';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/1' ) ) {
                return [
                    'response' => [ 'code' => 500 ],
                    'body'     => 'Server error',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'Unable to retrieve player page', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_world_tennis_number_not_found(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '<h5 class="media__title"><a href="/player/1">Player</a></h5>';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/1' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => '<div>Player without tennis ratings</div>',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'WTN not found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_no_wtn_list_found(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '<h5 class="media__title"><a href="/player/1">Player</a></h5>';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/1' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => '<div>World Tennis Number without list block</div>',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'No WTN list found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_no_wtn_entries_found(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '<h5 class="media__title"><a href="/player/1">Player</a></h5>';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/1' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => 'World Tennis Number <ul class="list--inline list"></ul>',
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'No WTN entries found', $result['message'] );
    }

    public function test_fetch_player_wtn_handles_malformed_list_item_html_without_type_error(): void {
        $cookie = new stdClass();
        $cookie->name = 'st';
        $cookie->value = 'tok';

        $search_body = '<h5 class="media__title"><a href="/player/1">Player</a></h5>';
        $malformed_profile = 'World Tennis Number <ul class="list--inline list"><li class="list__item">Malformed Content Without Tags</li></ul>';

        $GLOBALS['wp_stubs_remote_get_callback'] = function( string $url ) use ( $cookie, $search_body, $malformed_profile ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }
            if ( str_contains( $url, 'DoSearch' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $search_body,
                ];
            }
            if ( str_contains( $url, 'player/1' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => $malformed_profile,
                ];
            }
            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };

        $this->adapter->prepare_env();

        $player = new Player();
        $player->btm = '12345678';

        $result = $this->adapter->fetch_player_wtn( $player );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'WTN values not set', $result['message'] );
    }
}
