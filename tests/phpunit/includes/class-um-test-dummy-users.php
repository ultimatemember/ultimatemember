<?php
/**
 * Deterministic "dummy" users for member directory tests.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Test_Dummy_Users
 *
 * Builds a set of users with varied roles, account statuses, countries, profile and cover photos,
 * "hide my profile" choices, registration dates and last logins. The data is generated from the
 * user index, so tests compute the expected directory results from definitions() instead of
 * hard-coding them.
 */
class UM_Test_Dummy_Users {

	/**
	 * Default number of users.
	 */
	const COUNT = 40;

	/**
	 * Registration date of the first user; every next user registered a day later.
	 */
	const FIRST_REGISTERED = '2026-01-01 09:00:00';

	/**
	 * @var string[]
	 */
	private static $first_names = array( 'Olena', 'Taras', 'Iryna', 'Mykola', 'Sofia', 'Andriy', 'Daryna', 'Bohdan', 'Kateryna', 'Ostap' );

	/**
	 * @var string[]
	 */
	private static $last_names = array( 'Shevchenko', 'Kovalenko', 'Bondarenko', 'Tkachenko', 'Kravets', 'Oliynyk', 'Melnyk', 'Savchenko' );

	/**
	 * @var string[]
	 */
	private static $roles = array( 'subscriber', 'contributor', 'author', 'subscriber', 'editor' );

	/**
	 * @var string[]
	 */
	private static $countries = array( 'Ukraine', 'Poland', 'Germany', 'Canada' );

	/**
	 * User definitions.
	 *
	 * @param int $count Number of users.
	 *
	 * @return array[] Definitions keyed by user login. Keys: `login`, `first_name`, `last_name`,
	 *                 `role`, `status`, `country`, `photo`, `cover`, `hidden`, `registered`
	 *                 (timestamp), `last_login` (timestamp or null).
	 */
	public static function definitions( $count = self::COUNT ) {
		$statuses = array(
			7 => 'awaiting_admin_review',
			8 => 'awaiting_email_confirmation',
			9 => 'rejected',
		);

		$first_registered = strtotime( self::FIRST_REGISTERED . ' UTC' );

		$users = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$login = sprintf( 'dummy%02d', $i );

			$status = isset( $statuses[ $i % 10 ] ) ? $statuses[ $i % 10 ] : 'approved';
			if ( 13 === $i ) {
				$status = 'inactive';
			}

			$users[ $login ] = array(
				'login'      => $login,
				'first_name' => self::$first_names[ $i % count( self::$first_names ) ],
				'last_name'  => self::$last_names[ $i % count( self::$last_names ) ] . $i, // Unique last names.
				'role'       => self::$roles[ $i % count( self::$roles ) ],
				'status'     => $status,
				'country'    => self::$countries[ $i % count( self::$countries ) ],
				'photo'      => 0 === $i % 3,
				'cover'      => 0 === $i % 5,
				'hidden'     => 0 === $i % 11,
				'registered' => $first_registered + $i * DAY_IN_SECONDS,
				// Some users never logged in; the others in a different order than registration.
				'last_login' => 0 === $i % 6 ? null : $first_registered + ( ( $i * 17 ) % $count ) * HOUR_IN_SECONDS + 60 * DAY_IN_SECONDS,
			);
		}

		return $users;
	}

	/**
	 * Create the users.
	 *
	 * @param WP_UnitTest_Factory $factory     Test factory.
	 * @param array[]             $definitions User definitions.
	 *
	 * @return int[] User IDs keyed by login.
	 */
	public static function create( $factory, array $definitions ) {
		$ids = array();
		foreach ( $definitions as $login => $user ) {
			$user_id = $factory->user->create(
				array(
					'user_login'      => $login,
					'user_email'      => $login . '@example.org',
					'first_name'      => $user['first_name'],
					'last_name'       => $user['last_name'],
					'display_name'    => $user['first_name'] . ' ' . $user['last_name'],
					'role'            => $user['role'],
					'user_registered' => gmdate( 'Y-m-d H:i:s', $user['registered'] ),
				)
			);

			UM()->common()->users()->set_status( $user_id, $user['status'] );
			update_user_meta( $user_id, 'country', $user['country'] );

			if ( $user['photo'] ) {
				update_user_meta( $user_id, 'profile_photo', 'profile_photo.jpg' );
			}
			if ( $user['cover'] ) {
				update_user_meta( $user_id, 'cover_photo', 'cover_photo.jpg' );
			}
			if ( $user['hidden'] ) {
				update_user_meta( $user_id, 'hide_in_members', array( 'Yes' ) );
			}
			if ( null !== $user['last_login'] ) {
				update_user_meta( $user_id, '_um_last_login', gmdate( 'Y-m-d H:i:s', $user['last_login'] ) ); // As set_last_login() stores it.
			}

			$ids[ $login ] = $user_id;
		}

		return $ids;
	}
}
