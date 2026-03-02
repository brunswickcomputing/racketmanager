<?php
/**
 * Draw action resolver (pure)
 *
 * Pure logic for resolving whether a policy matches a POST payload and (if so)
 * returning the handler context.
 *
 * @package RacketManager
 * @subpackage Services/Admin/Championship
 */

namespace Racketmanager\Services\Admin\Championship;

final class Draw_Action_Resolver {

    public const DETECT_POST_ACTION_IN     = 'post_action_in';
    public const DETECT_POST_ACTION_EQUALS = 'post_action_equals';
    public const DETECT_POST_FIELD_EQUALS  = 'post_field_equals';
    public const DETECT_RANKING_MODE       = 'ranking_mode';

    public const TAB_FROM_RESULT_OR_DEFAULT = 'from_result_or_default';

    /**
     * @param array $post Typically sanitised/unslashed POST payload (controller-owned)
     * @param array $policy A single policy descriptor
     * @return array|null Context array if policy matches, otherwise null
     */
    public static function resolve( array $post, array $policy ): ?array {
        $requires = $policy['detect_requires'] ?? array();
        foreach ( (array) $requires as $required_key ) {
            if ( ! array_key_exists( strval( $required_key ), $post ) ) {
                return null;
            }
        }

        $detector = strval( $policy['detect'] ?? '' );
        $args     = $policy['detect_args'] ?? array();

        $context = match ( $detector ) {
            self::DETECT_POST_ACTION_IN => self::detect_post_action_in( $post, (array) $args ),
            self::DETECT_POST_ACTION_EQUALS => self::detect_post_action_equals( $post, strval( $args[0] ?? '' ) ),
            self::DETECT_POST_FIELD_EQUALS => self::detect_post_field_equals( $post, strval( $args[0] ?? '' ), strval( $args[1] ?? '' ) ),
            self::DETECT_RANKING_MODE => self::detect_ranking_mode( $post ),
            default => null,
        };

        return $context;
    }

    private static function detect_post_action_in( array $post, array $allowed ): ?array {
        if ( ! isset( $post['action'] ) ) {
            return null;
        }
        $action = strval( $post['action'] );
        if ( in_array( $action, array_map( 'strval', $allowed ), true ) ) {
            return array();
        }
        return null;
    }

    private static function detect_post_action_equals( array $post, string $expected ): ?array {
        if ( ! isset( $post['action'] ) ) {
            return null;
        }
        return ( strval( $post['action'] ) === $expected ) ? array() : null;
    }

    private static function detect_post_field_equals( array $post, string $field, string $expected ): ?array {
        if ( '' === $field || ! isset( $post[ $field ] ) ) {
            return null;
        }
        return ( strval( $post[ $field ] ) === $expected ) ? array() : null;
    }

    private static function detect_ranking_mode( array $post ): ?array {
        $mode = self::ranking_mode_from_post( $post );
        if ( null === $mode ) {
            return null;
        }
        return array( 'mode' => $mode );
    }

    private static function ranking_mode_from_post( array $post ): ?string {
        if ( isset( $post['saveRanking'] ) ) {
            return 'manual';
        }
        if ( isset( $post['randomRanking'] ) ) {
            return 'random';
        }
        if ( isset( $post['ratingPointsRanking'] ) ) {
            return 'ratings';
        }
        return null;
    }
}
