<?php
/**
 * WP Integration Test w/ WordPress SEO plugin
 *
 * Tests for WordPress SEO connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_WordPress_SEO extends WP_StreamTestCase {

	/**
	 * The WordPress SEO title meta key.
	 *
	 * @var string
	 */
	protected $title_meta_key = '_yoast_wpseo_title';

	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		if ( is_multisite() ) {
			$this->markTestSkipped( 'Currently testing in single sites only.' );
		}

		$this->plugin->connectors->unload_connectors();

		// Make partial of Connector_WordPress_SEO class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_WordPress_SEO::class )
			->onlyMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	/**
	 * Confirm that WordPress SEO is installed and active.
	 */
	public function test_wordpress_seo_installed_and_activated() {
		$this->assertTrue( defined( 'YOAST_ENVIRONMENT' ) );
	}

	/**
	 * Tests "added_post_meta" callback function.
	 * callback_added_post_meta( $meta_id, $object_id, $meta_key, $meta_value )
	 */
	public function test_callback_added_post_meta() {

		$expects = defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '23.5', '<' ) ? $ths->once() : $this->never();

		// Set expected calls for the Mock.
		$this->mock->expects( $expects )
			->method( 'log' )
			->with(
				$this->equalTo(
					__( 'Updated "SEO title" of "Test post %%!" Post', 'stream' )
				),
				$this->equalTo(
					array(
						'meta_key'   => $this->title_meta_key,
						'meta_value' => 'Test meta %!',
						'post_type'  => 'post',
					)
				),
				$this->greaterThan( 0 ),
				'wpseo_meta',
				'updated'
			);

		// Create post for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test post %!',
				'post_content' => 'Lorem ipsum dolor...',
				'post_status'  => 'publish',
			)
		);

		update_post_meta( $post_id, $this->title_meta_key, 'Test meta %!' );

		// Confirm callback execution.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_added_post_meta' ) );
	}
}
