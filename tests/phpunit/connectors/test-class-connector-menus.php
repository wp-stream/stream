<?php
/**
 * Tests for the Menus Connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Menus extends WP_StreamTestCase {
	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Make partial of Connector_ACF class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Menus::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	public function test_callback_wp_create_nav_menu() {
		// Expected log calls.
		$this->mock->expects( $this->exactly( 1 ) )
			->method( 'log' )
			->with(
				$this->equalTo( __( 'Created new menu "%s"', 'stream' ) ),
				$this->callback(
					function ( $subject ) {
						$expected = array( 'name' => 'test-menu' );
						return array_intersect_key( $expected, $subject ) === $expected;
					}
				),
				$this->greaterThan( 0 ),
				$this->equalTo( 'test-menu' ),
				$this->equalTo( 'created' )
			);

		// Create nav menu to trigger callback.
		wp_create_nav_menu( 'test-menu' );

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_wp_create_nav_menu' ) );
	}

	public function test_callback_wp_update_nav_menu() {
		// Create nav menu for later use.
		$menu_id = wp_create_nav_menu( 'test-menu' );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 1 ) )
			->method( 'log' )
			->with(
				$this->equalTo( _x( 'Updated menu "%s"', 'Menu name', 'stream' ) ),
				$this->equalTo(
					array(
						'name'      => 'test-menu',
						'menu_id'   => $menu_id,
						'menu_data' => array(
							'description' => 'yo',
							'menu-name'   => 'test-menu',
						),
					)
				),
				$this->equalTo( $menu_id ),
				$this->equalTo( 'test-menu' ),
				$this->equalTo( 'updated' )
			);

		// Update nav menu to trigger callback.
		wp_update_nav_menu_object(
			$menu_id,
			array(
				'description' => 'yo',
				'menu-name'   => 'test-menu',
			)
		);

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_wp_update_nav_menu' ) );
	}

	public function test_callback_delete_nav_menu() {
		// Unregister Menus connector to avoid callback conflicts.
		$this->plugin->connectors->unload_connector( 'menus' );

		// Create nav menu for later use.
		$menu_id = wp_create_nav_menu( 'test-menu' );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 1 ) )
			->method( 'log' )
			->with(
				$this->equalTo( _x( 'Deleted "%s"', 'Menu name', 'stream' ) ),
				$this->equalTo(
					array(
						'name'    => 'test-menu',
						'menu_id' => $menu_id,
					)
				),
				$this->equalTo( $menu_id ),
				$this->equalTo( 'test-menu' ),
				$this->equalTo( 'deleted' )
			);

		// Delete nav menu to trigger callback.
		wp_delete_nav_menu( $menu_id );

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_delete_nav_menu' ) );
	}

	public function test_callback_update_option_theme_mods() {
		// Create nav menu and nav menu location for later use.
		$menu_id = wp_create_nav_menu( 'test-menu' );
		register_nav_menu( 'main', 'Main Navigation' );

		// Create theme mods options for later use.
		$locations         = get_theme_mod( 'nav_menu_locations' );
		$locations['main'] = '';
		set_theme_mod( 'nav_menu_locations', $locations );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo(
						_x(
							'"%1$s" has been assigned to "%2$s"',
							'1: Menu name, 2: Theme location',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'name'        => 'test-menu',
							'location'    => 'Main Navigation',
							'location_id' => 'main',
							'menu_id'     => $menu_id,
						)
					),
					$this->equalTo( $menu_id ),
					$this->equalTo( 'test-menu' ),
					$this->equalTo( 'assigned' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" has been unassigned from "%2$s"',
							'1: Menu name, 2: Theme location',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'name'        => 'test-menu',
							'location'    => 'Main Navigation',
							'location_id' => 'main',
							'menu_id'     => $menu_id,
						)
					),
					$this->equalTo( $menu_id ),
					$this->equalTo( 'test-menu' ),
					$this->equalTo( 'unassigned' ),
				)
			);

		// Assign/Unassigned menu to a theme nav menu location to trigger callback.
		$locations['main'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		$locations['main'] = '';
		set_theme_mod( 'nav_menu_locations', $locations );

		// No test action for this callback.
	}
}
