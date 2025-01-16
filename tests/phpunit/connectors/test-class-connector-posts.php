<?php
/**
 * Tests for Posts connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Posts extends WP_StreamTestCase {
	/**
	 * Holds local timestamp in "Y-m-d H:i:s" format
	 *
	 * @var string
	 */
	private $date;

	/**
	 * Holds GMT timestamp in "Y-m-d H:i:s" format
	 *
	 * @var string
	 */
	private $date_gmt;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->plugin->connectors->unload_connectors();

		// Set static timestamps.
		$this->date     = '2007-07-04 12:30:00';
		$this->date_gmt = get_gmt_from_date( $this->date );

		// Make partial of Connector_Posts class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Posts::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	/**
	 * Tests "transition_post_status" callback function.
	 */
	public function test_callback_transition_post_status() {
		// Create post args for later use.
		$post_args = array(
			'post_title'    => 'Test post',
			'post_content'  => 'Lorem ipsum dolor...',
			'post_date'     => $this->date,
			'post_date_gmt' => $this->date_gmt,
			'post_status'   => 'draft',
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->exactly( 12 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s drafted',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'draft',
								'old_status'    => 'new',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'created' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s published',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'publish',
								'old_status'    => 'draft',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s unpublished',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'draft',
								'old_status'    => 'publish',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s draft saved',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'draft',
								'old_status'    => 'draft',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s pending review',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'pending',
								'old_status'    => 'draft',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s scheduled for %3$s',
							'1: Post title, 2: Post type singular name, 3: Scheduled post date',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'future',
								'old_status'    => 'pending',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" scheduled %2$s published',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'publish',
								'old_status'    => 'future',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s privately published',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'private',
								'old_status'    => 'publish',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s trashed',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'trash',
								'old_status'    => 'private',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'trashed' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s restored from trash',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'publish',
								'old_status'    => 'trash',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'untrashed' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s updated',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'publish',
								'old_status'    => 'publish',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'updated' ),
				),
				array(
					$this->equalTo(
						_x(
							'"%1$s" %2$s published',
							'1: Post title, 2: Post type singular name',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'post_title'    => 'Test post',
								'singular_name' => 'post',
								'new_status'    => 'publish',
								'old_status'    => 'new',
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'created' ),
				)
			);

		// Create post/update post status trigger callbacks.
		$post_id = wp_insert_post( $post_args );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'pending',
			)
		);
		$time = strtotime( 'tomorrow' );
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_status'   => 'future',
				'post_date'     => date( 'Y-m-d H:i:s', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $time ),
			)
		);
		$time = strtotime( 'now' );
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_status'   => 'publish',
				'post_date'     => date( 'Y-m-d H:i:s', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $time ),
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'private',
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'trash',
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		// Expected log to be made with "created" action.
		wp_insert_post(
			array(
				'post_title'   => 'Test post',
				'post_content' => 'Lorem ipsum dolor...',
				'post_status'  => 'publish',
			)
		);

		/**
		 * Expected log to not be called for newly published attachment
		 * because it's an excluded post type.
		 */
		wp_insert_post(
			array(
				'post_title'   => 'Test attachment',
				'post_content' => 'Lorem ipsum dolor...',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);

		// Confirm callback execution.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_transition_post_status' ) );
	}

	/**
	 * Tests "deleted_post" callback function.
	 */
	public function test_callback_deleted_post() {
		// Create post for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test post',
				'post_content' => 'Lorem ipsum dolor...',
				'post_status'  => 'publish',
			)
		);

		$auto_draft_post_id = wp_insert_post(
			array(
				'post_title'   => 'Test post',
				'post_content' => 'Lorem ipsum dolor...',
				'post_status'  => 'auto-draft',
			)
		);

		$attachment_post_id = wp_insert_post(
			array(
				'post_title'   => 'Test post',
				'post_content' => 'Lorem ipsum dolor...',
				'post_type'    => 'attachment',
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'"%1$s" %2$s deleted from trash',
						'1: Post title, 2: Post type singular name',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'post_title'    => 'Test post',
						'singular_name' => 'post',
					)
				),
				$this->greaterThan( 0 ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'deleted' )
			);

		// Delete post and trigger mock.
		wp_delete_post( $post_id, true );

		// Delete auto-drafted post and attachment to confirm these actions is ignored.
		wp_delete_post( $auto_draft_post_id, true );
		wp_delete_post( $attachment_post_id, true );

		// Confirm callback execution.
		$this->assertGreaterThan( 0, did_action( $this->action_prefix . 'callback_deleted_post' ) );
	}
}
