<?php

namespace Addons\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

final class IllegalUsers {
	/**
	 * @var array|string[]
	 */
	public array $common_usernames = [
		'administrator',
		'user',
		'user1',
		'admin',
		'admin1',
	];

	// --------------------------------------------------

	/**
	 * @param array $usernames
	 *
	 * @return array
	 */
	public function get_illegal_usernames( array $usernames = [] ): array {
		$illegal_usernames = apply_filters( '_illegal_users', $usernames );

		return array_map(
			'strtolower',
			array_merge(
				$illegal_usernames,
				$this->common_usernames
			)
		);
	}

	// --------------------------------------------------

	/**
	 * @param array $new_username
	 *
	 * @return false|int
	 */
	public function change_common_username( array $new_username ): false|int {
		global $wpdb;

		return $wpdb->update(
			$wpdb->users,
			[ 'user_login' => $new_username['user_login'] ],
			[ 'ID' => $new_username['ID'] ]
		);
	}

	// --------------------------------------------------

	/**
	 * @return array
	 */
	public function check_for_common_usernames(): array {
		// Get all users for validating usernames.
		$all_users = get_users(
			[
				'orderby' => 'user_login',
				'order'   => 'ASC',
				'fields'  => [
					'ID',
					'user_login',
				],
			]
		);

		// Get all admins.
		$admins = get_users(
			[
				'role'    => 'administrator',
				'orderby' => 'user_login',
				'order'   => 'ASC',
				'fields'  => [
					'ID',
					'user_login',
				],
			]
		);

		// Check for illegal usernames.
		foreach ( $admins as $key => $admin ) {
			// Remove the user if its username is not in the illegal list.
			if ( ! in_array( strtolower( $admin->user_login ), $this->get_illegal_usernames(), false ) ) {
				unset( $admins[ $key ] );
			}
		}

		// Build the response array.
		return [
			'all_users'     => $all_users,
			'admin_matches' => array_values( $admins ),
		];
	}

	// --------------------------------------------------

	/**
	 * @param array $usernames
	 *
	 * @return array
	 */
	public function update_common_usernames( array $usernames ): array {
		// Bail if the 'usernames' array is empty.
		if ( empty( $usernames ) ) {
			return [];
		}

		// Loop the specified usernames.
		foreach ( $usernames as $key => $username ) {
			// Remove the successful changes and return the failed only if any.
			if ( $this->change_common_username( $username ) === 1 ) {
				unset( $usernames[ $key ] );
			}
		}

		return $usernames;
	}
}
