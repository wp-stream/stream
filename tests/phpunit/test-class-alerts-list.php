<?php
namespace WP_Stream;

/**
 * Class Test_Alerts_List
 *
 * @package WP_Stream
 * @group   alerts
 */
class Test_Alerts_List extends WP_StreamTestCase {

	public function test_construct() {
		$alerts_list = new Alerts_List( $this->plugin );
		$this->assertNotEmpty( $alerts_list->plugin );
	}

	public function test_suppress_bulk_actions() {
		$alerts_list      = new Alerts_List( $this->plugin );
		$actions          = array(
			'edit'          => 'edit',
			'testaction'    => 'testaction',
			'anotheraction' => 'anotheraction',
		);
		$expected_actions = array(
			'testaction'    => 'testaction',
			'anotheraction' => 'anotheraction',
		);
		$filtered_actions = $alerts_list->suppress_bulk_actions( $actions );
		$this->assertEquals( $expected_actions, $filtered_actions );
	}

	public function test_suppress_quick_edit() {
		$alerts_list      = new Alerts_List( $this->plugin );
		$actions          = array(
			'edit'                 => 'edit',
			'view'                 => 'view',
			'trash'                => 'trash',
			'inline hide-if-no-js' => 'inline hide-if-no-js',
		);
		$post             = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$filtered_actions = $alerts_list->suppress_quick_edit( $actions );
		$this->assertEquals( $actions, $filtered_actions );
	}

	public function test_custom_column_actions() {
		$alerts_list                = new Alerts_List( $this->plugin );
		$custom_column_actions_html = $alerts_list->custom_column_actions( 42 );
		$this->assertNotEmpty( $custom_column_actions_html );
	}
	public function test_display_custom_quick_edit() {
		$alerts_list = new Alerts_List( $this->plugin );
		ob_start();
		$alerts_list->display_custom_quick_edit();
		$output = ob_get_clean();
		$this->assertEmpty( $output );
	}
	public function test_enqueue_scripts() {
		$alerts_list = new Alerts_List( $this->plugin );
		global $current_screen;
		$current_screen->id = 'edit-wp_stream_alerts';
		$alerts_list->enqueue_scripts( '' );
		$this->assertTrue( wp_script_is( 'wp-stream-alerts-list' ) );
	}

	/**
	 * Test save_alert_inline_edit method.
	 */
	public function test_save_alert_inline_edit() {
		$alerts_list = new Alerts_List( $this->plugin );
		$post_id     = wp_insert_post(
			array(
				'post_type' => Alerts::POST_TYPE,
			)
		);
		$postarr     = array(
			'ID'        => $post_id,
			'post_type' => Alerts::POST_TYPE,
		);
		$data        = array();

		// Create and authenticate user.
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$_POST['post_type']                              = Alerts::POST_TYPE;
		$_POST['wp_stream_trigger_author']               = 'author';
		$_POST['wp_stream_trigger_connector_or_context'] = 'connector-context';
		$_POST['wp_stream_trigger_action']               = 'action';
		$_POST['wp_stream_alert_type']                   = 'type';
		$_POST['wp_stream_alert_status']                 = 'status';
		$_POST[ Alerts::POST_TYPE . '_edit_nonce' ]      = wp_create_nonce( 'stream-src/classes/class-alerts-list.php' );

		$alerts_list->save_alert_inline_edit( $data, $postarr );

		$alert_meta = get_post_meta( $post_id, 'alert_meta', true );
		$this->assertEquals( 'type', get_post_meta( $post_id, 'alert_type', true ), 'Alert type not saved' );
		$this->assertEquals( 'author', $alert_meta['trigger_author'], 'Trigger author not saved' );
		$this->assertEquals( 'connector', $alert_meta['trigger_connector'], 'Trigger connector not saved' );
		$this->assertEquals( 'context', $alert_meta['trigger_context'], 'Trigger context not saved' );
		$this->assertEquals( 'action', $alert_meta['trigger_action'], 'Trigger action not saved' );

		// Test case with just a connector data.
		$_POST['wp_stream_trigger_connector_or_context'] = 'connector';

		$alerts_list->save_alert_inline_edit( $data, $postarr );
		$alert_meta = get_post_meta( $post_id, 'alert_meta', true );
		$this->assertEquals( 'connector', $alert_meta['trigger_connector'], 'Trigger connector not saved' );
		$this->assertEmpty( $alert_meta['trigger_context'], 'Trigger context not saved empty' );

		// Test case with no connector or context data.
		$_POST['wp_stream_trigger_connector_or_context'] = '';

		$alerts_list->save_alert_inline_edit( $data, $postarr );
		$alert_meta = get_post_meta( $post_id, 'alert_meta', true );
		$this->assertEmpty( $alert_meta['trigger_connector'], 'Trigger connector not saved empty' );
		$this->assertEmpty( $alert_meta['trigger_context'], 'Trigger context not saved empty' );
	}
}
