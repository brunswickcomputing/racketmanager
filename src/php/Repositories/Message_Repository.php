<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Communication\Message;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;
use function is_array;
use function is_numeric;
use function is_string;
use function md5;
use function microtime;
use function wp_cache_delete;
use function wp_cache_get;
use function wp_cache_set;

class Message_Repository implements Message_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct( ?wpdb $wpdb = null ) {
        $this->wpdb       = $wpdb ?? $GLOBALS['wpdb'];
        $this->table_name = $this->wpdb->prefix . 'racketmanager_messages';
    }

    public function find( int|string $message_id ): ?Message {
        if ( ! is_numeric( $message_id ) ) {
            return null;
        }

        $id = (int) $message_id;
        if ( $id <= 0 ) {
            return null;
        }

        $message = wp_cache_get( $id, 'message' );
        if ( ! $message ) {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT `id`, `userid`, `date`, `status`, `subject`, `sender`, `message_object` FROM `$this->table_name` WHERE `id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $row ) {
                return null;
            }

            $message = new Message( $row );
            wp_cache_set( $message->id, $message, 'message' );
        }

        return $message instanceof Message ? $message : null;
    }

    public function find_by_id( int|string|null $id ): ?Message {
        if ( null === $id ) {
            return null;
        }
        return $this->find( $id );
    }

    public function find_by_user( int $user_id, array $args = [] ): array|int {
        $defaults = array(
            'count'   => false,
            'status'  => false,
            'orderby' => array( 'date' => 'DESC' ),
        );
        $args     = array_merge( $defaults, $args );
        $count    = $args['count'];
        $status   = $args['status'];
        $orderby  = $args['orderby'];

        $sql          = " FROM `$this->table_name` WHERE `userid` = %d";
        $params       = [ $user_id ];
        $search_terms = [];

        if ( $status ) {
            switch ( $status ) {
                case 'unread':
                    $status = '1';
                    break;
                case 'read':
                    $status = '0';
                    break;
                default:
                    break;
            }
            $search_terms[] = '`status` = %s';
            $params[]       = (string) $status;
        }

        if ( ! empty( $search_terms ) ) {
            $sql .= ' AND ' . implode( ' AND ', $search_terms );
        }

        if ( $count ) {
            $query = $this->wpdb->prepare( 'SELECT COUNT(id)' . $sql, ...$params );
            $cache_key = $this->messages_query_cache_key( $query );
            $message_count = wp_cache_get( $cache_key, 'messages' );
            if ( false === $message_count || null === $message_count ) {
                $message_count = (int) $this->wpdb->get_var( $query );
                wp_cache_set( $cache_key, $message_count, 'messages' );
            }
            return (int) $message_count;
        }

        $order = Util::order_by_string( $orderby );
        $query = $this->wpdb->prepare( 'SELECT `id`' . $sql . ( '' !== $order ? $order : '' ), ...$params );
        $cache_key = $this->messages_query_cache_key( $query );
        $rows = wp_cache_get( $cache_key, 'messages' );
        if ( false === $rows || null === $rows ) {
            $rows = $this->wpdb->get_results( $query );
            wp_cache_set( $cache_key, $rows, 'messages' );
        }

        $messages = [];
        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $msg = $this->find( (int) $row->id );
                if ( $msg ) {
                    $messages[] = $msg;
                }
            }
        }

        return $messages;
    }

    public function delete_by_user_and_type( int $user_id, string $type ): int|false {
        if ( 'all' === $type ) {
            $result = $this->wpdb->delete(
                $this->table_name,
                [ 'userid' => $user_id ],
                [ '%d' ]
            );
        } else {
            $status = $type;
            if ( 'unread' === $type ) {
                $status = '1';
            } elseif ( 'read' === $type ) {
                $status = '0';
            }
            $result = $this->wpdb->delete(
                $this->table_name,
                [ 'status' => $status, 'userid' => $user_id ],
                [ '%s', '%d' ]
            );
        }
        $this->invalidate_messages_cache();
        return false !== $result ? (int) $result : false;
    }

    public function save( object $entity ): bool|int {
        /** @var Message $entity */
        $data = [
            'userid'         => $entity->userid ?? 0,
            'date'           => $entity->date ?? '',
            'status'         => (string) ( $entity->status ?? '' ),
            'subject'        => $entity->subject ?? '',
            'sender'         => $entity->sender ?? '',
            'message_object' => $entity->message_object ?? '',
        ];

        $format = [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ];

        if ( empty( $entity->id ) ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $format );
            if ( $inserted ) {
                $entity->id = (int) $this->wpdb->insert_id;
                wp_cache_set( $entity->id, $entity, 'message' );
                $this->invalidate_messages_cache();
                return $entity->id;
            }
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table_name,
            $data,
            [ 'id' => $entity->id ],
            $format,
            [ '%d' ]
        );

        if ( false !== $updated ) {
            wp_cache_delete( $entity->id, 'message' );
            wp_cache_set( $entity->id, $entity, 'message' );
            $this->invalidate_messages_cache();
            return $entity->id;
        }

        return false;
    }

    public function delete( int $id ): bool {
        $deleted = $this->wpdb->delete(
            $this->table_name,
            [ 'id' => $id ],
            [ '%d' ]
        );
        wp_cache_delete( $id, 'message' );
        $this->invalidate_messages_cache();
        return false !== $deleted;
    }

    private function messages_query_cache_key( string $query ): string {
        return md5( $query . ':' . $this->messages_last_changed() );
    }

    private function messages_last_changed(): string {
        $last_changed = wp_cache_get( 'last_changed', 'messages' );
        if ( ! is_string( $last_changed ) || '' === $last_changed ) {
            $last_changed = microtime();
            wp_cache_set( 'last_changed', $last_changed, 'messages' );
        }
        return $last_changed;
    }

    private function invalidate_messages_cache(): void {
        wp_cache_set( 'last_changed', microtime(), 'messages' );
        if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) && function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'messages' );
        }
    }
}
