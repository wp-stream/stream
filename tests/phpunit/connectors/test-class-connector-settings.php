<?php
namespace WP_Stream;

/**
 * Tests for Settings connector class callbacks.
 *
 * @package WP_Stream
 */
class Test_Connector_Settings extends WP_StreamTestCase {
	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->plugin->connectors->unload_connectors();

		// Make partial of Connector_Settings class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Settings::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	public function test_is_option_ignored() {
		$this->assertTrue( $this->mock->is_option_ignored( '_transient_option_name' ) );
		$this->assertTrue( $this->mock->is_option_ignored( '_site_transient_option_name' ) );
		$this->assertTrue( $this->mock->is_option_ignored( 'option_name$' ) );
		$this->assertTrue( $this->mock->is_option_ignored( 'image_default_link_type' ) );
		$this->assertTrue( $this->mock->is_option_ignored( 'medium_large_size_w' ) );
		$this->assertTrue( $this->mock->is_option_ignored( 'medium_large_size_h' ) );

		$this->assertFalse( $this->mock->is_option_ignored( 'option_site_transient_name' ) );
		$this->assertFalse( $this->mock->is_option_ignored( 'option_transient_name' ) );
		$this->assertFalse( $this->mock->is_option_ignored( 'option_$_name' ) );
		$this->assertFalse( $this->mock->is_option_ignored( 'not_ignored' ) );

		// Test custom ignores.
		$this->assertFalse( $this->mock->is_option_ignored( 'ignore_me' ) );

		add_filter(
			'wp_stream_is_option_ignored',
			function ( $is_ignored, $option_name, $default_ignored ) {
				return in_array( $option_name, array_merge( array( 'ignore_me' ), $default_ignored ), true );
			},
			10,
			3
		);

		$this->assertTrue( $this->mock->is_option_ignored( 'ignore_me' ) );
	}

	public function test_callback_updated_option() {
		// If multisite use site_option methods and test "update_site_option" callback
		// instead of the update_option callback.
		$add_method    = is_multisite() ? 'add_site_option' : 'add_option';
		$update_method = is_multisite() ? 'update_site_option' : 'update_option';

		// Create options in database for later use.
		call_user_func( $add_method, 'users_can_register', 0 );
		call_user_func( $add_method, 'permalink_structure', '' );
		call_user_func( $add_method, 'category_base', '' );
		call_user_func( $add_method, 'tag_base', '' );

		$this->mock->expects( $this->exactly( 4 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo( __( '"%s" setting was updated', 'stream' ) ),
					$this->equalTo(
						array(
							'label'     => 'Membership',
							'option'    => 'users_can_register',
							'context'   => 'settings',
							'old_value' => '0',
							'value'     => '1',
						)
					),
					$this->equalTo( null ),
					$this->equalTo( 'settings' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo( __( '"%s" setting was updated', 'stream' ) ),
					$this->equalTo(
						array(
							'label'     => 'Permalink Settings',
							'option'    => 'permalink_structure',
							'context'   => 'permalink',
							'old_value' => '',
							'value'     => '/%year%/%postname%/',
						)
					),
					$this->equalTo( null ),
					$this->equalTo( 'permalink' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo( __( '"%s" setting was updated', 'stream' ) ),
					$this->equalTo(
						array(
							'label'     => 'Category base',
							'option'    => 'category_base',
							'context'   => 'permalink',
							'old_value' => '',
							'value'     => 'cat/',
						)
					),
					$this->equalTo( null ),
					$this->equalTo( 'permalink' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo( __( '"%s" setting was updated', 'stream' ) ),
					$this->equalTo(
						array(
							'label'     => 'Tag base',
							'option'    => 'tag_base',
							'context'   => 'permalink',
							'old_value' => '',
							'value'     => 'tag/',
						)
					),
					$this->equalTo( null ),
					$this->equalTo( 'permalink' ),
					$this->equalTo( 'updated' ),
				)
			);

		// Simulate being on the WP Customizr page.
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		do_action( 'customize_save', new \WP_Customize_Manager( array() ) );

		// Update options to trigger callback.
		call_user_func( $update_method, 'users_can_register', 1 );

		// Use this to prevent repeated log calls.
		global $wp_actions;
		unset( $wp_actions['customize_save'] );

		call_user_func( $update_method, 'permalink_structure', '/%year%/%postname%/' );
		call_user_func( $update_method, 'category_base', 'cat/' );
		call_user_func( $update_method, 'tag_base', 'tag/' );

		// If multisite only check update_site_option test callback.
		if ( is_multisite() ) {
			$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_update_site_option' ) );
		} else {
			// Check callback test action.
			$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_update_option_tag_base' ) );
			$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_update_option_category_base' ) );
			$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_update_option_permalink_structure' ) );
			$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_updated_option' ) );
			$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_update_option' ) );
		}
	}
}
