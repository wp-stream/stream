<?php
/**
 * Tests for Installer Connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Installer extends WP_StreamTestCase {

	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Make partial of Connector_Installer class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Installer::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	public function test_callback_upgrader_process_complete() {
		// Prepare scenario
		$this->markTestSkipped( 'This test skipped until the needed scenario can be properly simulated.' );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->withConsecutive(
				array(
					_x(
						'Installed %1$s: %2$s %3$s',
						'Plugin/theme installation. 1: Type (plugin/theme), 2: Plugin/theme name, 3: Plugin/theme version',
						'stream'
					),
					array(
						'type'        => 'plugin',
						'name'        => 'Hello Dolly',
						'version'     => '',
						'slug'        => 'hello_dolly.php',
						'success'     => true,
						'error'       => null,
						'old_version' => '',
					),
					null,
					'plugins',
					'installed',
				),
				array(
					_x(
						'Updated %1$s: %2$s %3$s',
						'Plugin/theme update. 1: Type (plugin/theme), 2: Plugin/theme name, 3: Plugin/theme version',
						'stream'
					),
					array(
						'type'        => 'theme',
						'name'        => 'Twenty Twenty',
						'version'     => '',
						'slug'        => 'twentytwenty',
						'success'     => true,
						'error'       => null,
						'old_version' => '',
					),
					null,
					'themes',
					'updated',
				)
			);

		// Simulate installing plugin and updating theme to trigger callback.

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_upgrader_process_complete' ) );
	}

	public function test_callback_activate_plugin() {
		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" plugin activated %2$s',
						'1: Plugin name, 2: Single site or network wide',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'name'         => 'Hello Dolly',
						'network_wide' => null,
					)
				),
				$this->equalTo( null ),
				$this->equalTo( 'plugins' ),
				$this->equalTo( 'activated' )
			);

		// Do stuff.
		\activate_plugin( 'hello.php' );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_activate_plugin' ) );
	}

	public function test_callback_deactivate_plugin() {
		// Prepare scenario
		\activate_plugin( 'hello.php' );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" plugin deactivated %2$s',
						'1: Plugin name, 2: Single site or network wide',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'name'         => 'Hello Dolly',
						'network_wide' => null,
					)
				),
				$this->equalTo( null ),
				$this->equalTo( 'plugins' ),
				$this->equalTo( 'deactivated' )
			);

		// Do stuff.
		\deactivate_plugins( array( 'hello.php' ) );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_deactivate_plugin' ) );
	}

	public function test_callback_switch_theme() {
		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( __( '"%s" theme activated', 'stream' ) ),
				$this->equalTo( array( 'name' => 'twentytwenty' ) ),
				$this->equalTo( null ),
				$this->equalTo( 'themes' ),
				$this->equalTo( 'activated' )
			);

		// Do stuff.
		switch_theme( 'twentytwenty' );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_switch_theme' ) );
	}

	public function test_callback_delete_site_transient_update_themes() {
		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( __( '"%s" theme deleted', 'stream' ) ),
				$this->equalTo( array( 'name' => 'twentyninteen' ) ),
				$this->equalTo( null ),
				$this->equalTo( 'themes' ),
				$this->equalTo( 'deleted' )
			);

		// Do stuff.
		delete_theme( 'twentyninteen' );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_delete_site_transient_update_themes' ) );
	}

	public function test_callback_pre_set_site_transient_update_plugins() {
		// Prepare scenario
		$this->markTestSkipped( 'This test skipped until the needed scenario can be properly simulated.' );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( __( '"%s" plugin deleted', 'stream' ) ),
				$this->equalTo(
					array(
						'name'         => 'Hello Dolly',
						'plugin'       => 'hello.php',
						'network_wide' => null,
					)
				),
				$this->equalTo( null ),
				$this->equalTo( 'plugins' ),
				$this->equalTo( 'deleted' )
			);

		// Do stuff.

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_pre_set_site_transient_update_plugins' ) );
	}

	public function test_callback__core_updated_successfully() {
		// Prepare scenario
		$this->markTestSkipped( 'This test skipped until the needed scenario can be properly simulated.' );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo( esc_html__( 'WordPress auto-updated to %s', 'stream' ) ),
					$this->equalTo(
						array(
							'new_version'  => '',
							'old_version'  => '',
							'auto_updated' => true,
						)
					),
					$this->equalTo( null ),
					$this->equalTo( 'WordPress' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo( esc_html__( 'WordPress updated to %s', 'stream' ) ),
					$this->equalTo(
						array(
							'new_version'  => '',
							'old_version'  => '',
							'auto_updated' => false,
						)
					),
					$this->equalTo( null ),
					$this->equalTo( 'WordPress' ),
					$this->equalTo( 'updated' ),
				)
			);

		// Do stuff.

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback__core_updated_successfully' ) );
	}
}
