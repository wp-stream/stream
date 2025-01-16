<?php
/**
 * Tests for Users connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Users extends WP_StreamTestCase {

	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Make partial of Connector_Users class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Users::class )
			->setMethods( array( 'log' ) )
			->getMock();

		$this->mock->register();
	}

	public function test_callback_user_register() {
		// Expected log calls.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo( esc_html__( 'New user registration', 'stream' ) ),
					$this->equalTo(
						array(
							'display_name' => 'TestGuy',
							'roles'        => 'Subscriber',
						)
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'users' ),
					$this->equalTo( 'created' ),
					$this->greaterThan( 0 ),
				),
				array(
					$this->equalTo(
						_x(
							'New user account created for %1$s (%2$s)',
							'1: User display name, 2: User role',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'display_name' => 'TestGuy2',
							'roles'        => 'Subscriber',
						)
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'users' ),
					$this->equalTo( 'created' ),
					$this->greaterThan( 0 ),
				)
			);

		// Do stuff.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );
		wp_set_current_user( $user_id );
		self::factory()->user->create( array( 'display_name' => 'TestGuy2' ) );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_user_register' ) );
	}

	public function test_callback_password_reset() {
		// Create user.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );
		wp_set_current_user( $user_id );
		$user = get_user_by( 'id', $user_id );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo( __( '%s\'s password was reset', 'stream' ) ),
					$this->equalTo( array( 'display_name' => 'TestGuy' ) ),
					$this->equalTo( $user_id ),
					$this->equalTo( 'profiles' ),
					$this->equalTo( 'password-reset' ),
					$this->equalTo( $user_id ),
				)
			);

		// Do stuff.
		$new_pass = 'blahblahblah';
		reset_password( $user, $new_pass );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_password_reset' ) );
	}

	public function test_callback_retrieve_password_and_profile_update() {
		// Create user.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );
		$user    = get_user_by( 'id', $user_id );

		// Expected log calls.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo( __( '%s\'s password was requested to be reset', 'stream' ) ),
					$this->equalTo( array( 'display_name' => 'TestGuy' ) ),
					$this->equalTo( $user_id ),
					$this->equalTo( 'sessions' ),
					$this->equalTo( 'forgot-password' ),
					$this->equalTo( $user_id ),
				),
				array(
					$this->equalTo( __( '%s\'s profile was updated', 'stream' ) ),
					$this->equalTo( array( 'display_name' => 'TestGuy' ) ),
					$this->equalTo( $user_id ),
					$this->equalTo( 'profiles' ),
					$this->equalTo( 'updated' ),
				)
			);

		// Do stuff.
		get_password_reset_key( $user );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_profile_update' ) );
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_retrieve_password' ) );
	}

	public function test_callback_set_logged_in_cookie() {
		// Create user.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( __( '%s logged in', 'stream' ) ),
				$this->equalTo( array( 'display_name' => 'TestGuy' ) ),
				$this->equalTo( $user_id ),
				$this->equalTo( 'sessions' ),
				$this->equalTo( 'login' ),
				$this->equalTo( $user_id )
			);

		// Do stuff.
		wp_set_auth_cookie( $user_id );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_set_logged_in_cookie' ) );
	}

	public function test_callback_clear_auth_cookie() {
		// Create and authenticate user.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		// Manually trigger the action to execute callback.
		add_filter( 'send_auth_cookies', '__return_false' );
		wp_clear_auth_cookie();

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_clear_auth_cookie' ) );
	}

	public function test_callback_deleted_user() {
		// Create Users.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );
		$user    = get_user_by( 'ID', $user_id );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo(
						_x(
							'%1$s\'s account was deleted (%2$s)',
							'1: User display name, 2: User roles',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'display_name' => 'TestGuy',
							'roles'        => 'Subscriber',
						)
					),
					$this->equalTo( $user_id ),
					$this->equalTo( 'users' ),
					$this->equalTo( 'deleted' ),
					$this->equalTo( 0 ),
				),
				array(
					$this->equalTo( esc_html__( 'User account #%d was deleted', 'stream' ) ),
					$this->equalTo(
						array(
							'display_name' => $user_id,
							'roles'        => '',
						)
					),
					$this->equalTo( $user_id ),
					$this->equalTo( 'users' ),
					$this->equalTo( 'deleted' ),
					$this->equalTo( 0 ),
				)
			);

		// Delete user and run action to simulate event and trigger callback.
		wp_delete_user( $user_id );
		do_action( 'deleted_user', $user_id, null, $user );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_delete_user' ) );
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_deleted_user' ) );
	}

	public function test_callback_set_user_role() {
		// Create user.
		$user_id = self::factory()->user->create( array( 'display_name' => 'TestGuy' ) );
		$user    = get_user_by( 'id', $user_id );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s role was changed from %2$s to %3$s',
						'1: User display name, 2: Old role, 3: New role',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'display_name' => 'TestGuy',
						'old_role'     => 'Subscriber',
						'new_role'     => 'Editor',
					)
				),
				$this->equalTo( $user_id ),
				$this->equalTo( 'profiles' ),
				$this->equalTo( 'updated' )
			);

		// Do stuff.
		$user->set_role( 'editor' );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_set_user_role' ) );
	}
}
