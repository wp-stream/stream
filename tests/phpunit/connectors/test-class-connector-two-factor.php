<?php
/**
 * WP Integration Test w/ Two Factor
 *
 * Tests for Two Factor connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Two_Factor extends WP_StreamTestCase {

	/**
	 * Our user's id.
	 *
	 * @var string
	 */
	public $user_id;

	/**
	 * Our user.
	 *
	 * @var \WP_User
	 */
	public $user;

	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		$this->plugin->connectors->unload_connectors();

		// Make partial of Connector_Two_Factor class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Two_Factor::class )
			->onlyMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();

		// Allow us to have the Two_Factor_Dummy option.
		remove_all_filters( 'two_factor_providers' );

		if ( empty( $this->user_id ) ) {
			$this->user_id = self::factory()->user->create(
				array(
					'user_login'   => 'testuser',
					'user_role'    => 'administrator',
					'display_name' => 'testuserdisplay',
				)
			);

			$this->user = get_user_by( 'ID', $this->user_id );

			\Two_Factor_Core::enable_provider_for_user( $this->user_id, 'Two_Factor_Dummy' );
		}
	}

	/**
	 * Confirm that Two Factor is installed and active.
	 */
	public function test_two_factor_installed_and_activated() {
		$this->assertTrue( class_exists( 'Two_Factor_Core' ) );
	}

	/**
	 * Test that adding a provider triggers the log.
	 */
	public function test_callback_added_user_meta() {

		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					__(
						'Enabled provider: %s',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'provider' => 'Two_Factor_Email',
					)
				),
				$this->user_id,
				'user-settings',
				'enabled'
			);

			\Two_Factor_Core::enable_provider_for_user( $this->user_id, 'Two_Factor_Email' );
	}

	/**
	 * Tests the "callback_save_two_factor_user_authenticated" callback.
	 * This tests the log via doing the action.
	 */
	public function test_callback_two_factor_user_authenticated() {

		wp_set_current_user( $this->user_id );

		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					__(
						'Authenticated via %s',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'provider' => 'Two_Factor_Dummy',
					)
				),
				$this->user_id,
				'auth',
				'authenticated',
				$this->user_id
			);

			$provider = \Two_Factor_Core::get_provider_for_user( $this->user, 'Two_Factor_Dummy' );

			// We can't test the method so we'll trigger the action.
			do_action( 'two_factor_user_authenticated', $this->user, \Two_Factor_Core::get_provider_for_user( $this->user, $provider ) );
	}

	/**
	 * Test that adding a provider triggers the log.
	 */
	public function test_callback_updated_user_meta() {

		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					__(
						'Disabled provider: %s',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'provider' => 'Two_Factor_Dummy',
					),
				),
				$this->user_id,
				'user-settings',
				'disabled'
			);

			\Two_Factor_Core::disable_provider_for_user( $this->user_id, 'Two_Factor_Dummy' );
	}
}
