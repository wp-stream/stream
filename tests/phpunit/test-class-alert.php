<?php
namespace WP_Stream;

/**
 * Class Test_Alert
 *
 * @package WP_Stream
 * @group alerts
 */
class Test_Alert extends WP_StreamTestCase {

	public function test_construct() {
		$data  = $this->dummy_alert_data();
		$alert = new Alert( $data, $this->plugin );

		foreach ( $data as $field => $value ) {
			$this->assertEquals( $alert->$field, $value );
		}
	}

	public function test_construct_blank() {
		$data  = $this->dummy_alert_data();
		$alert = new Alert( null, $this->plugin );

		$this->assertEmpty( $alert->ID );
		$this->assertEmpty( $alert->date );
		$this->assertEmpty( $alert->author );
		$this->assertEmpty( $alert->alert_type );

		$this->assertEquals( $alert->status, 'wp_stream_disabled' );
		$this->assertEquals( $alert->alert_meta, array() );
	}

	public function test_save() {
		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );

		$post_id = $alert->save();
		$this->assertNotFalse( $post_id );
		$this->assertNotEquals( 0, $alert->ID );

		$alert->alert_type = 'none';
		$alert->save();
		$alert_type = $alert->get_meta( 'alert_type', true );
		$this->assertEquals( 'none', $alert_type );
	}

	public function test_process_settings_form() {
		$this->markTestIncomplete(
			'Not implemented yet.'
		);
	}

	public function test_get_meta() {
		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$alert->save();

		$value = $alert->get_meta( 'alert_type', true );
		$this->assertEquals( 'highlight', $value );
	}

	public function test_update_meta() {
		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$alert->save();

		$alert->update_meta( 'test_meta', 'test_value' );

		$value = $alert->get_meta( 'test_meta' );
		$this->assertContains( 'test_value', $value );

		$value = $alert->get_meta( 'test_meta', true );
		$this->assertEquals( 'test_value', $value );
	}

	public function test_get_title() {
		$data  = $this->dummy_alert_data();
		$alert = new Alert( $data, $this->plugin );

		$this->assertEquals( 'Administrator > Plugins > Activated', $alert->get_title() );

		$alert->alert_meta['trigger_action'] = 'updated';
		$this->assertEquals( 'Administrator > Plugins > Updated', $alert->get_title() );

		$alert->alert_meta['trigger_context'] = 'posts';
		$this->assertEquals( 'Administrator > Posts > Updated', $alert->get_title() );

		$alert->alert_meta['trigger_author'] = '';
		$this->assertEquals( 'Any User > Posts > Updated', $alert->get_title() );
	}

	public function test_get_alert_type_obj() {
		$data  = $this->dummy_alert_data();
		$alert = new Alert( $data, $this->plugin );

		$alert->alert_type = '';
		$this->assertEquals( new Alert_Type_None( $this->plugin ), $alert->get_alert_type_obj() );

		$alert->alert_type = 'highlight';
		$this->assertEquals( new Alert_Type_Highlight( $this->plugin ), $alert->get_alert_type_obj() );
	}

	public function test_check_record() {
		$action = new \MockAction();
		$data   = $this->dummy_alert_data();
		$alert  = new Alert( $data, $this->plugin );

		add_filter( 'wp_stream_alert_trigger_check', array( $action, 'filter' ) );
		$alert->check_record( 0, $this->dummy_stream_data() );

		$this->assertEquals( 1, $action->get_call_count() );
	}

	public function test_send_alert() {
		$this->markTestIncomplete(
			'Not implemented yet.'
		);
	}

	private function dummy_alert_data() {
		return (object) array(
			'ID'         => 1,
			'date'       => gmdate( 'Y-m-d H:i:s' ),
			'status'     => 'wp_stream_enabled',
			'author'     => '1',
			'alert_type' => 'highlight',
			'alert_meta' => array(
				'trigger_action'  => 'activated',
				'trigger_author'  => 'administrator',
				'trigger_context' => 'plugins',
			),
		);
	}

	private function dummy_stream_data() {
		return array(
			'object_id' => null,
			'site_id'   => '1',
			'blog_id'   => get_current_blog_id(),
			'user_id'   => '1',
			'user_role' => 'administrator',
			'created'   => gmdate( 'Y-m-d H:i:s' ),
			'summary'   => '"Hello Dave" plugin activated',
			'ip'        => '192.168.0.1',
			'connector' => 'installer',
			'context'   => 'plugins',
			'action'    => 'activated',
		);
	}
}
