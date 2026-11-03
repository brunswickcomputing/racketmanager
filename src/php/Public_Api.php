<?php
/**
 * Public API: Public_Api class
 *
 * @author Paul Moffat
 * @package RacketManager
 */

declare( strict_types=1 );

namespace Racketmanager;

use Racketmanager\Domain\Communication\Message;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Public facade providing a stable, supported integration API for external themes and plugins.
 */
final class Public_Api {

    /**
     * Shared DI container instance.
     *
     * @var Simple_Container|null
     */
    private static ?Simple_Container $container = null;

    /**
     * Initialise the public API with the dependency injection container.
     *
     * @param Simple_Container $container Dependency injection container.
     * @return void
     */
    public static function init( Simple_Container $container ): void {
        self::$container = $container;
    }

    /**
     * Reset the cached container instance (primarily for testing).
     *
     * @return void
     */
    public static function reset(): void {
        self::$container = null;
    }

    /**
     * Resolve the active dependency injection container.
     *
     * @return Simple_Container|null
     */
    public static function get_container(): ?Simple_Container {
        if ( null !== self::$container ) {
            return self::$container;
        }
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container instanceof Simple_Container ) {
            self::$container = $racketmanager->container;
        }
        return self::$container;
    }

    /**
     * Alias for get_container().
     *
     * @return Simple_Container|null
     */
    public static function container(): ?Simple_Container {
        return self::get_container();
    }

    /**
     * Find a user by their WordPress / RacketManager user ID.
     *
     * @param int $user_id User ID.
     * @return User|null User domain entity or null if not found or container unavailable.
     */
    public static function get_user( int $user_id ): ?User {
        if ( $user_id <= 0 ) {
            return null;
        }
        $container = self::get_container();
        if ( ! $container?->has( 'user_repository' ) ) {
            return null;
        }
        /**
         * User repository instance.
         *
         * @var User_Repository_Interface $repo
         */
        $repo = $container->get( 'user_repository' );
        return $repo->find( $user_id );
    }

    /**
     * Encode a string into an SEO-friendly URL slug.
     *
     * @param string $string_field Input string to encode.
     * @return string Encoded slug.
     */
    public static function seo_url( string $string_field ): string {
        return Seo_Slug::encode( $string_field );
    }

    /**
     * Decode an SEO-friendly URL slug back to its approximate text representation.
     *
     * @param string $string_field Input slug to decode.
     * @return string Decoded string.
     */
    public static function un_seo_url( string $string_field ): string {
        return Seo_Slug::decode( $string_field );
    }

    /**
     * Get the count of unread messages for a user.
     *
     * @param int $user_id User ID.
     * @return int Unread message count, or 0 if repository unavailable.
     */
    public static function get_unread_message_count( int $user_id ): int {
        if ( $user_id <= 0 ) {
            return 0;
        }
        $container = self::get_container();
        if ( ! $container?->has( 'message_repository' ) ) {
            return 0;
        }
        /**
         * Message repository instance.
         *
         * @var Message_Repository_Interface $repo
         */
        $repo  = $container->get( 'message_repository' );
        $count = $repo->find_by_user(
            $user_id,
            [
                'count'  => true,
                'status' => 'unread',
            ]
        );
        return is_int( $count ) ? $count : 0;
    }

    /**
     * Retrieve messages for a user or message count with custom arguments.
     *
     * @param int                  $user_id User ID.
     * @param array<string, mixed> $args    Query arguments (e.g. ['count' => true, 'status' => 'unread']).
     * @return array<int, Message>|int List of messages or message count.
     */
    public static function get_messages( int $user_id, array $args = [] ): array|int {
        if ( $user_id <= 0 ) {
            return empty( $args['count'] ) ? [] : 0;
        }
        $container = self::get_container();
        if ( ! $container?->has( 'message_repository' ) ) {
            return empty( $args['count'] ) ? [] : 0;
        }
        /**
         * Message repository instance.
         *
         * @var Message_Repository_Interface $repo
         */
        $repo = $container->get( 'message_repository' );
        return $repo->find_by_user( $user_id, $args );
    }
}
