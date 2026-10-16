<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

/**
 * Lightweight wpdb stand-in that records prepared SQL for repository tests.
 */
class Recording_Wpdb {
	public string $prefix = 'wp_';
	public string $racketmanager = 'wp_racketmanager_leagues';
	public string $users = 'wp_users';
	public string $last_query = '';
	public int $insert_id = 1;
	/** @var array<int, array{sql:string,args:array}> */
	public array $prepared = [];
	/** @var array<int, array<string, mixed>> */
	public array $executed = [];
	public mixed $get_var_return = 0;
	public mixed $get_results_return = [];
	public mixed $get_row_return = null;
	public mixed $query_return = 1;
	public mixed $insert_return = true;
	public mixed $update_return = 1;
	public mixed $delete_return = 1;

	public function prepare( $query, ...$args ): string {
		if ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) ) {
			$args = $args[0];
		}
		$this->prepared[] = array(
			'sql'  => (string) $query,
			'args' => $args,
		);
		$this->last_query = (string) $query;
		return (string) $query;
	}

	public function get_var( $query ) {
		$this->record( 'get_var', $query );
		return $this->get_var_return;
	}

	public function get_results( $query ) {
		$this->record( 'get_results', $query );
		return $this->get_results_return;
	}

	public function get_row( $query ) {
		$this->record( 'get_row', $query );
		return $this->get_row_return;
	}

	public function query( $query ) {
		$this->record( 'query', $query );
		return $this->query_return;
	}

	public function insert( $table, $data, $format = null ) {
		$this->last_query = 'INSERT INTO ' . $table;
		$this->executed[] = array(
			'method' => 'insert',
			'table'  => $table,
			'data'   => $data,
		);
		return $this->insert_return;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$set = array();
		foreach ( (array) $data as $key => $value ) {
			$set[] = '`' . $key . '` = ' . ( is_string( $value ) ? "'" . $value . "'" : (string) $value );
		}
		$this->last_query = 'UPDATE ' . $table . ' SET ' . implode( ', ', $set );
		$this->executed[] = array(
			'method' => 'update',
			'table'  => $table,
			'data'   => $data,
			'where'  => $where,
		);
		return $this->update_return;
	}

	public function delete( $table, $where, $where_format = null ) {
		$this->last_query = 'DELETE FROM ' . $table;
		$this->executed[] = array(
			'method' => 'delete',
			'table'  => $table,
			'where'  => $where,
		);
		return $this->delete_return;
	}

	public function last_sql(): string {
		if ( ! empty( $this->prepared ) ) {
			return $this->prepared[ array_key_last( $this->prepared ) ]['sql'];
		}
		return $this->last_query;
	}

	public function sql_containing( string $needle ): ?string {
		foreach ( array_reverse( $this->prepared ) as $entry ) {
			if ( str_contains( $entry['sql'], $needle ) ) {
				return $entry['sql'];
			}
		}
		return str_contains( $this->last_query, $needle ) ? $this->last_query : null;
	}

	private function record( string $method, $query ): void {
		$this->last_query = (string) $query;
		$this->executed[] = array(
			'method' => $method,
			'sql'    => (string) $query,
		);
	}
}
