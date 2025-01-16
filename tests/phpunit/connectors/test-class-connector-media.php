<?php
/**
 * Tests for Media Connector class callbacks.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Media extends WP_StreamTestCase {

	/**
	 * Runs before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Make partial of Connector_Media class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Media::class )
			->onlyMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();

		// Require image editor classes.
		require_once ABSPATH . 'wp-includes/class-wp-image-editor.php';
		require_once ABSPATH . 'wp-includes/class-wp-image-editor-gd.php';
		require_once ABSPATH . 'wp-admin/includes/image-edit.php';
	}

	public function test_callback_add_attachment() {
		// Create post for later use.
		$post_id = self::factory()->post->create( array( 'post_title' => 'Test post' ) );

		// Expected log calls.
		$this->mock->expects( $this->exactly( 3 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo( esc_html__( 'Added "%s" to Media library', 'stream' ) ),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'name'         => 'Document one',
								'parent_title' => null,
								'parent_id'    => 0,
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'document' ),
					$this->equalTo( 'uploaded' ),
				),
				array(
					$this->equalTo(
						_x(
							'Attached "%1$s" to "%2$s"',
							'1: Attachment title, 2: Parent post title',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) use ( $post_id ) {
							$expected = array(
								'name'         => 'Document one',
								'parent_title' => 'Test post',
								'parent_id'    => $post_id,
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'document' ),
					$this->equalTo( 'attached' ),
				),
				array(
					$this->equalTo(
						_x(
							'Attached "%1$s" to "%2$s"',
							'1: Attachment title, 2: Parent post title',
							'stream'
						)
					),
					$this->callback(
						function ( $subject ) {
							$expected = array(
								'name'         => 'Document one',
								'parent_title' => 'Unidentifiable post',
								'parent_id'    => 42,
							);
							return array_intersect_key( $expected, $subject ) === $expected;
						}
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'document' ),
					$this->equalTo( 'attached' ),
				)
			);

		// Create attachment to trigger callback.
		self::factory()->post->create(
			array(
				'post_title' => 'Document one',
				'post_type'  => 'attachment',
			)
		);

		self::factory()->post->create(
			array(
				'post_title'   => 'Document one',
				'post_type'    => 'attachment',
				'post_content' => 'some description',
				'post_parent'  => $post_id,
			)
		);

		// Create attachment with invalid post parent.
		self::factory()->post->create(
			array(
				'post_title'   => 'Document one',
				'post_type'    => 'attachment',
				'post_content' => 'some description',
				'post_parent'  => 42,
			)
		);

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_add_attachment' ) );
	}

	public function test_callback_edit_attachment() {
		// Create attachment for later use.
		$attachment_id = self::factory()->post->create(
			array(
				'post_title' => 'Attachment one',
				'post_type'  => 'attachment',
			)
		);

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( esc_html__( 'Updated "%s"', 'stream' ) ),
				$this->equalTo( array( 'name' => 'Document one' ) ),
				$this->equalTo( $attachment_id ),
				$this->equalTo( 'document' ),
				$this->equalTo( 'updated' )
			);

		// Update attachment to trigger callback.
		self::factory()->post->update_object(
			$attachment_id,
			array( 'post_title' => 'Document one' )
		);

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_edit_attachment' ) );
	}

	public function test_callback_delete_attachment() {
		// Create attachment for later use.
		$attachment_id = self::factory()->post->create(
			array(
				'post_title' => 'Attachment one',
				'post_type'  => 'attachment',
			)
		);

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( esc_html__( 'Deleted "%s"', 'stream' ) ),
				$this->callback(
					function ( $subject ) {
						$expected = array(
							'name'      => 'Attachment one',
							'parent_id' => null,
						);
						return array_intersect_key( $expected, $subject ) === $expected;
					}
				),
				$this->equalTo( $attachment_id ),
				$this->equalTo( 'document' ),
				$this->equalTo( 'deleted' )
			);

		// Delete attachment to trigger callback.
		wp_delete_attachment( $attachment_id, true );

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_delete_attachment' ) );
	}

	public function test_callback_wp_save_image_editor_file() {
		// Create attachment for later use.
		$attachment_id = self::factory()->post->create(
			array(
				'post_title' => 'Attachment one',
				'post_type'  => 'attachment',
			)
		);

		// Expected log calls.
		$this->mock->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( __( 'Edited image "%s"', 'stream' ) ),
				$this->equalTo(
					array(
						'name'     => 'icon-128x128.png',
						'filename' => WP_STEAM_TESTDATA . '/tmp/icon-128x128.png',
						'post_id'  => $attachment_id,
					)
				),
				$this->equalTo( $attachment_id ),
				$this->equalTo( 'document' ),
				$this->equalTo( 'edited' )
			);

		$image = new \WP_Image_Editor_GD( WP_STEAM_TESTDATA . '/images/icon-128x128.png' );
		$image->load();

		// Simulate editor page save to trigger callback.
		\wp_save_image_file(
			WP_STEAM_TESTDATA . '/tmp/icon-128x128.png',
			$image,
			'image/png',
			$attachment_id
		);

		// Check callback test action.
		$this->assertFalse( 0 === did_action( $this->action_prefix . 'callback_wp_save_image_editor_file' ) );
	}
}
