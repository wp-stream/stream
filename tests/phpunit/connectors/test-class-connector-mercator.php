<?php
/**
 * WP Integration Test w/ Mercator
 *
 * Tests for Mercator connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Mercator extends WP_StreamTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		// Add hook to provide mock blog details because sub-sites aren't
		// created with an options table to use.
		add_filter( 'site_details', array( $this, 'get_testsite_details' ) );

		// Make partial of Connector_Mercator class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Mercator::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	/**
	 * Mock function that define stub blog details that would be store in the blog options table
	 * which isn't created during tests.
	 *
	 * @param object $details
	 * @return object
	 */
	public function get_testsite_details( $details ) {
		global $base;

		$details->blogname   = 'testsite';
		$details->siteurl    = $base . '/testsite';
		$details->post_count = 0;
		$details->home       = $base . '/testsite';

		return $details;
	}

	/**
	 * Test for get_context_labels().
	 *
	 * @group ms-required
	 */
	public function test_get_context_labels() {
		// Validate this works for foreign characters as well.
		$id     = $this->factory->blog->create( array( 'title' => 'ובזכויותיהם' ) );
		$labels = $this->mock->get_context_labels();
		$this->assertArrayHasKey( 'blog-1', $labels );
		$this->assertArrayHasKey( 'blog-' . $id, $labels );
	}

	public function test_callback_mercator_mapping_made_primary() {
		// Make blog and alias for later use.
		$blog_id = $this->factory->blog->create( array( 'title' => 'testsite' ) );
		$mapping = \Mercator\Mapping::create( $blog_id, 'example.com' );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 3 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo(
						_x(
							'"%1$s" domain alias was created for "%2$s"',
							'1. Domain alias 2. Site name',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'domain'    => 'example.org',
							'site_name' => 'testsite',
						)
					),
					$this->equalTo( $blog_id ),
					$this->equalTo( 'testsite' ),
					$this->equalTo( 'created' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" domain alias was deleted for "%2$s"',
							'1. Domain alias 2. Site name',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'domain'    => 'example.com',
							'site_name' => 'testsite',
						)
					),
					$this->equalTo( $blog_id ),
					$this->equalTo( 'testsite' ),
					$this->equalTo( 'deleted' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" domain alias was make primary for "%2$s"',
							'1. Domain alias 2. Site name',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'domain'    => 'example.com',
							'site_name' => 'testsite',
						)
					),
					$this->equalTo( $blog_id ),
					$this->equalTo( 'testsite' ),
					$this->equalTo( 'made_primary' ),
				)
			);

		// Make alias primary domain for site to trigger callback.
		$mapping->make_primary();

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_mercator_mapping_made_primary' ) );
	}

	public function test_callback_mercator_mapping_updated() {
		// Make blog and aliases for later use.
		$blog_id = $this->factory->blog->create( array( 'title' => 'testsite' ) );
		$mapping = \Mercator\Mapping::create( $blog_id, 'example.com', true );
		$mapping->make_primary();

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'The domain alias "%1$s" was updated to "%2$s" for site "%3$s"',
						'1. Old Domain alias 2. Domain alias 2. Site name',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'old_domain' => 'example.com',
						'domain'     => 'testsite.com',
						'site_name'  => 'testsite',
					)
				),
				$this->equalTo( $blog_id ),
				$this->equalTo( 'testsite' ),
				$this->equalTo( 'updated' )
			);

		// Change site alias to trigger callback.
		$mapping->set_domain( 'testsite.com' );

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_mercator_mapping_updated' ) );
	}

	public function test_callback_mercator_mapping_deleted() {
		// Make blog and alias for later use.
		$blog_id = $this->factory->blog->create( array( 'title' => 'testsite' ) );
		$mapping = \Mercator\Mapping::create( $blog_id, 'example.com', true );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" domain alias was deleted for "%2$s"',
						'1. Domain alias 2. Site name',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'domain'    => 'example.com',
						'site_name' => 'testsite',
					)
				),
				$this->equalTo( $blog_id ),
				$this->equalTo( 'testsite' ),
				$this->equalTo( 'deleted' )
			);

		/*
		 * Execute action to trigger callback because the tables need to
		 * run the \Mercator\Mapping::delete() don't currently exist.
		 */
		do_action( 'mercator.mapping.deleted', $mapping ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_mercator_mapping_deleted' ) );
	}

	public function test_callback_mercator_mapping_created() {
		// Make blog for later use.
		$blog_id = $this->factory->blog->create( array( 'title' => 'testsite' ) );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" domain alias was created for "%2$s"',
						'1. Domain alias 2. Site name',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'domain'    => 'example.com',
						'site_name' => 'testsite',
					)
				),
				$this->equalTo( $blog_id ),
				$this->equalTo( 'testsite' ),
				$this->equalTo( 'created' )
			);

		// Create and assign domain alias to site to trigger callback.
		\Mercator\Mapping::create( $blog_id, 'example.com', true );

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_mercator_mapping_created' ) );
	}
}
