<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Services\Admin\Championship;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Admin\Championship\Draw_Action_Resolver;

final class Draw_Action_Resolver_Test extends TestCase {

    public function test_detects_ranking_mode_manual(): void {
        $policy = array(
            'detect' => Draw_Action_Resolver::DETECT_RANKING_MODE,
        );

        $context = Draw_Action_Resolver::resolve( array( 'saveRanking' => '1' ), $policy );

        self::assertIsArray( $context );
        self::assertSame( 'manual', $context['mode'] ?? null );
    }

    public function test_detects_ranking_mode_random(): void {
        $policy = array(
            'detect' => Draw_Action_Resolver::DETECT_RANKING_MODE,
        );

        $context = Draw_Action_Resolver::resolve( array( 'randomRanking' => '1' ), $policy );

        self::assertIsArray( $context );
        self::assertSame( 'random', $context['mode'] ?? null );
    }

    public function test_detects_post_action_equals(): void {
        $policy = array(
            'detect'      => Draw_Action_Resolver::DETECT_POST_ACTION_EQUALS,
            'detect_args' => array( 'startFinals' ),
        );

        $context = Draw_Action_Resolver::resolve( array( 'action' => 'startFinals' ), $policy );

        self::assertIsArray( $context );
        self::assertSame( array(), $context );
    }

    public function test_detect_requires_blocks_match_when_missing(): void {
        $policy = array(
            'detect'          => Draw_Action_Resolver::DETECT_POST_ACTION_IN,
            'detect_args'     => array( 'add', 'replace' ),
            'detect_requires' => array( 'rounds' ),
        );

        $context = Draw_Action_Resolver::resolve( array( 'action' => 'add' ), $policy );

        self::assertNull( $context );
    }

    public function test_returns_null_for_unknown_detector(): void {
        $policy = array( 'detect' => 'nope' );
        self::assertNull( Draw_Action_Resolver::resolve( array( 'action' => 'x' ), $policy ) );
    }
}
