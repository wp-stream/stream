<?php
/**
 * Tests for Taxonomies Connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Taxonomies extends WP_StreamTestCase {
	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		$this->plugin->connectors->unload_connectors();

		// Make partial of Connector_Taxonomies class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Taxonomies::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
		$this->mock->get_context_labels();
	}

	public function test_callback_created_term() {
		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" %2$s created',
						'1: Term name, 2: Taxonomy singular label',
						'stream'
					)
				),
				$this->callback(
					function ( $subject ) {
						$expected = array(
							'term_name'      => 'test',
							'taxonomy_label' => 'Category',
							'taxonomy'       => 'category',
							'term_parent'    => 0,
						);
						return array_intersect_key( $expected, $subject ) === $expected;
					}
				),
				$this->greaterThan( 0 ),
				$this->equalTo( 'category' ),
				$this->equalTo( 'created' )
			);

		// Create term to trigger callback.
		wp_insert_term( 'test', 'category' );

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_created_term' ) );
	}

	public function test_callback_delete_term() {
		// Create term for later use.
		$term_data = wp_insert_term( 'test', 'category' );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" %2$s deleted',
						'1: Term name, 2: Taxonomy singular label',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'term_name'      => 'test',
						'taxonomy_label' => 'category',
						'term_id'        => $term_data['term_id'],
						'taxonomy'       => 'category',
						'term_parent'    => 0,
					)
				),
				$this->greaterThan( 0 ),
				$this->equalTo( 'category' ),
				$this->equalTo( 'deleted' )
			);

		// Delete term to trigger callback.
		wp_delete_term( $term_data['term_id'], 'category' );

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_delete_term' ) );
	}

	public function test_callback_edited_term() {
		// Create term for later use.
		$term_data = wp_insert_term( 'test', 'category' );

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" %2$s updated',
						'1: Term name, 2: Taxonomy singular label',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'term_name'      => 'test',
						'taxonomy_label' => 'category',
						'term_id'        => $term_data['term_id'],
						'taxonomy'       => 'category',
						'term_parent'    => 0,
					)
				),
				$this->greaterThan( 0 ),
				$this->equalTo( 'category' ),
				$this->equalTo( 'updated' )
			);

		// Edit term to trigger callbacks.
		$this->assertFalse(
			is_wp_error(
				wp_update_term(
					$term_data['term_id'],
					'category',
					array( 'name' => 'testing' )
				)
			)
		);

		// Check callback test action.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_edit_term' ) );
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_edited_term' ) );
	}
}
