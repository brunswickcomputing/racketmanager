<?php
declare( strict_types=1 );
/**
 * Wpdb_Transactions trait
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Concerns;

/**
 * Shared transaction helpers for repositories holding a `$this->wpdb` connection.
 *
 * Each helper is a no-op when no connection is available so composite
 * operations degrade gracefully in tests that construct repositories without one.
 */
trait Wpdb_Transactions {
    /**
     * Start a database transaction.
     *
     * @return void
     */
    protected function begin_transaction(): void {
        $this->run_transaction_statement( 'START TRANSACTION' );
    }

    /**
     * Commit the current database transaction.
     *
     * @return void
     */
    protected function commit(): void {
        $this->run_transaction_statement( 'COMMIT' );
    }

    /**
     * Roll back the current database transaction.
     *
     * @return void
     */
    protected function rollback(): void {
        $this->run_transaction_statement( 'ROLLBACK' );
    }

    /**
     * Issue a transaction control statement when a connection is available.
     *
     * @param string $statement
     *
     * @return void
     */
    private function run_transaction_statement( string $statement ): void {
        if ( isset( $this->wpdb ) && $this->wpdb ) {
            $this->wpdb->query( $statement );
        }
    }
}
