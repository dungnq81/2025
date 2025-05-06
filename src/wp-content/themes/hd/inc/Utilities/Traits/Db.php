<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Db {
	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param array|null $data
	 * @param bool $sanitize
	 *
	 * @return \WP_Error|int
	 */
	public static function bulkInsertRows( ?string $table_name, ?array $data, bool $sanitize = true ): \WP_Error|int {
		global $wpdb;

		if ( empty( $table_name ) || empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'invalid_data', 'Table name or data is invalid.' );
		}

		$table_name    = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$valid_columns = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );

		if ( empty( $valid_columns ) ) {
			return new \WP_Error( 'invalid_table', 'The specified table does not exist or has no valid columns.' );
		}

		$values            = [];
		$columns_in_insert = [];

		// Start a transaction
		$wpdb->query( 'START TRANSACTION' );

		foreach ( $data as $row ) {
			$valid_data = array_intersect_key( $row, array_flip( $valid_columns ) );

			if ( empty( $valid_data ) ) {
				continue;
			}

			if ( empty( $columns_in_insert ) ) {
				$columns_in_insert = array_keys( $valid_data );
			}

			$placeholders = array_fill( 0, count( $valid_data ), '%s' );
			$values[]     = $wpdb->prepare( '(' . implode( ', ', $placeholders ) . ')', ...array_values( $valid_data ) );
		}

		if ( empty( $values ) ) {
			return new \WP_Error( 'no_valid_data', 'No valid rows to insert.' );
		}

		$sql = "INSERT INTO `{$table_name}` (" . implode( ', ', $columns_in_insert ) . ') VALUES ' . implode( ', ', $values );

		$result = $wpdb->query( $sql );
		if ( $result !== false ) {
			// commit the transaction
			$wpdb->query( 'COMMIT' );

			return count( $values );
		}

		// Roll back the transaction
		$wpdb->query( 'ROLLBACK' );

		return new \WP_Error( 'insert_error', $wpdb->last_error );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param array|null $data
	 * @param bool $sanitize
	 *
	 * @return int|\WP_Error
	 */
	public static function insertOneRow( ?string $table_name, ?array $data, bool $sanitize = true ): \WP_Error|int {
		global $wpdb;

		// Validate input parameters
		if ( empty( $table_name ) || empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'invalid_data', 'Table name or data is invalid.' );
		}

		// Get columns of the table
		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$columns    = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );

		// If no columns found, table does not exist or is invalid
		if ( empty( $columns ) ) {
			return new \WP_Error( 'invalid_table', 'The specified table does not exist or has no valid columns.' );
		}

		// Remove invalid fields from $data that do not match table columns
		$valid_data = [];
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $columns, false ) ) {
				$valid_data[ $key ] = $value;
			}
		}

		// If no valid data exists, return error
		if ( empty( $valid_data ) ) {
			return new \WP_Error( 'no_valid_data', 'No valid data provided for insertion.' );
		}

		$result = $wpdb->insert( $table_name, $valid_data );
		if ( $result === false ) {
			return new \WP_Error( 'insert_error', $wpdb->last_error );
		}

		return $wpdb->insert_id;
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param int|null $id
	 * @param array|null $data
	 * @param bool $sanitize
	 *
	 * @return int|\WP_Error
	 */
	public static function updateOneRow( ?string $table_name, ?int $id, ?array $data, bool $sanitize = true ): \WP_Error|int {
		global $wpdb;

		// Validate input parameters
		if ( empty( $table_name ) || $id === null || empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'invalid_data', 'Table name, ID, or data is invalid.' );
		}

		// Check if the row with the given ID exists in the table
		if ( ! self::checkOneRow( $table_name, $id, $sanitize ) ) {
			return new \WP_Error( 'row_not_found', 'The specified row does not exist in the table.' );
		}

		// Get columns of the table
		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$columns    = $wpdb->get_col( "DESCRIBE $table_name", 0 );

		// If no columns are found, the table is invalid
		if ( empty( $columns ) ) {
			return new \WP_Error( 'invalid_table', 'The specified table does not exist or has no valid columns.' );
		}

		// Remove invalid fields from $data that do not match table columns
		$valid_data = [];
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $columns, false ) ) {
				$valid_data[ $key ] = $value;
			}
		}

		// If no valid data exists, return error
		if ( empty( $valid_data ) ) {
			return new \WP_Error( 'no_valid_data', 'No valid data provided for updating.' );
		}

		$row_updated = $wpdb->update( $table_name, $valid_data, [ 'id' => $id ] );

		// Check the result of the update operation
		if ( $row_updated === false ) {
			return new \WP_Error( 'update_failed', $wpdb->last_error ?: 'Unknown error during update operation.' );
		}

		return $row_updated;
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param int|null $id
	 * @param bool $sanitize
	 *
	 * @return int|\WP_Error
	 */
	public static function deleteOneRow( ?string $table_name, ?int $id, bool $sanitize = true ): \WP_Error|int {
		global $wpdb;

		// Validate input parameters
		if ( empty( $table_name ) || $id === null ) {
			return new \WP_Error( 'invalid_data', 'Table name or ID is invalid.' );
		}

		// Check if the row with the given ID exists in the table
		if ( ! self::checkOneRow( $table_name, $id, $sanitize ) ) {
			return new \WP_Error( 'row_not_found', 'The specified row does not exist in the table.' );
		}

		$table_name  = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$row_deleted = $wpdb->delete( $table_name, [ 'id' => $id ] );

		// Check the result of the delete operation
		if ( $row_deleted === false ) {
			return new \WP_Error( 'delete_failed', $wpdb->last_error ?: 'Unknown error during delete operation.' );
		}

		return $row_deleted;
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param string|null $column
	 * @param string|int|null $key
	 * @param bool $return_object
	 * @param bool $sanitize
	 * @param int $offset
	 * @param int $limit
	 * @param string|null $order_by
	 * @param string|null $order
	 *
	 * @return array|false|object|null
	 */
	public static function getRowsBy(
		?string $table_name,
		?string $column,
		string|int|null $key,
		bool $return_object = false,
		bool $sanitize = true,
		int $offset = 0,
		int $limit = - 1,
		?string $order_by = '',
		?string $order = 'ASC'
	): array|false|object|null {
		global $wpdb;

		if ( empty( $table_name ) || empty( $column ) || $key === null ) {
			return false;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$column     = $sanitize ? sanitize_text_field( $column ) : $column;

		$query = $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE `{$column}` = %s", $key );

		if ( ! empty( $order_by ) ) {
			$order = strtoupper( $order );
			$order = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'ASC';
			$query .= ' ORDER BY `' . esc_sql( $order_by ) . "` $order";
		}

		$offset = max( 0, $offset );

		if ( $limit > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit );
		} elseif ( $limit === - 1 ) {
			$query .= $wpdb->prepare( ' LIMIT %d, 18446744073709551615', $offset );
		}

		return $return_object ? $wpdb->get_results( $query ) : $wpdb->get_results( $query, ARRAY_A );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param string|null $column
	 * @param string|int|null $key
	 * @param bool $return_object
	 * @param bool $sanitize
	 *
	 * @return array|false|object|null
	 */
	public static function getOneRowBy( ?string $table_name, ?string $column, string|int|null $key, bool $return_object = false, bool $sanitize = true ): array|false|object|null {
		global $wpdb;

		if ( empty( $table_name ) || empty( $column ) || $key === null ) {
			return false;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$column     = $sanitize ? sanitize_text_field( $column ) : $column;

		$query = $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE `{$column}` = %s ORDER BY `id` DESC", $key );

		return $return_object ? $wpdb->get_row( $query ) : $wpdb->get_row( $query, ARRAY_A );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param int|null $id
	 * @param bool $return_object
	 * @param bool $sanitize
	 *
	 * @return array|false|object|null
	 */
	public static function getOneRow( ?string $table_name, ?int $id, bool $return_object = false, bool $sanitize = true ): array|false|object|null {
		global $wpdb;

		if ( empty( $table_name ) || $id === null ) {
			return false;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$query      = $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE `id` = %d", (int) $id );

		return $return_object ? $wpdb->get_row( $query ) : $wpdb->get_row( $query, ARRAY_A );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param int $offset
	 * @param int $limit
	 * @param bool $return_object
	 * @param bool $sanitize
	 * @param string|null $order_by
	 * @param string|null $order
	 *
	 * @return array|false|object|null
	 */
	public static function getRows(
		?string $table_name,
		int $offset = 0,
		int $limit = - 1,
		bool $return_object = false,
		bool $sanitize = true,
		?string $order_by = '',
		?string $order = 'ASC'
	): array|false|object|null {
		global $wpdb;

		if ( empty( $table_name ) ) {
			return false;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$query      = "SELECT * FROM `{$table_name}`";

		if ( ! empty( $order_by ) ) {
			$order = strtoupper( $order );
			$order = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'ASC';
			$query .= ' ORDER BY `' . esc_sql( $order_by ) . "` $order";
		}

		$offset = max( 0, $offset );

		if ( $limit > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit );
		} elseif ( $limit === - 1 ) {
			$query .= $wpdb->prepare( ' LIMIT %d, 18446744073709551615', $offset );
		}

		return $return_object ? $wpdb->get_results( $query ) : $wpdb->get_results( $query, ARRAY_A );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param int|null $id
	 * @param bool $sanitize
	 *
	 * @return bool
	 */
	public static function checkOneRow( ?string $table_name, ?int $id, bool $sanitize = true ): bool {
		global $wpdb;

		if ( empty( $table_name ) || $id === null ) {
			return false;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$query      = $wpdb->prepare( "SELECT 1 FROM `{$table_name}` WHERE `id` = %d LIMIT 1", $id );

		return (bool) $wpdb->get_var( $query );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param string|null $column
	 * @param string|int|null $value
	 * @param bool $sanitize
	 *
	 * @return bool
	 */
	public static function checkRowBy( ?string $table_name, ?string $column = null, string|int|null $value = null, bool $sanitize = true ): bool {
		global $wpdb;

		if ( empty( $table_name ) || empty( $column ) || $value === null ) {
			return false;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$column     = $sanitize ? sanitize_text_field( $column ) : $column;

		$query = $wpdb->prepare( "SELECT 1 FROM `{$table_name}` WHERE `{$column}` = %s LIMIT 1", $value );

		return (bool) $wpdb->get_var( $query );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $table_name
	 * @param string|null $column
	 * @param string|int|null $value
	 * @param bool $sanitize
	 *
	 * @return int
	 */
	public static function countRowsBy( ?string $table_name, ?string $column = null, string|int|null $value = null, bool $sanitize = true ): int {
		global $wpdb;

		if ( empty( $table_name ) ) {
			return 0;
		}

		$table_name = $sanitize ? sanitize_text_field( $wpdb->prefix . $table_name ) : $wpdb->prefix . $table_name;
		$column     = $sanitize ? sanitize_text_field( $column ) : $column;

		if ( ! $column ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name}`" );
		}

		// Execute the query and get the count
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table_name}` WHERE `{$column}` = %s", $value ) );
	}
}
