<?php
namespace WP_Stream;

/**
 * Class Test_Alert_Trigger_Action
 *
 * @package WP_Stream
 * @group alerts
 */
class Test_Alert_Trigger_Action extends Test_Alert_Trigger {

	public function setUp(): void {
		parent::setUp();
		$this->trigger = new Alert_Trigger_Action( $this->plugin );

		$this->alert                               = $this->plugin->alerts->get_alert();
		$this->alert->alert_meta['trigger_action'] = 'activated';
	}

	public function test_check_record_bad() {
		$data           = $this->dummy_stream_data();
		$data['action'] = 'updated';

		$status = $this->trigger->check_record( true, null, $data, $this->alert );
		$this->assertFalse( $status );
	}

	public function test_save_fields() {
		$_POST['wp_stream_trigger_action'] = 'updated';

		$this->assertNotEquals( 'updated', $this->alert->alert_meta['trigger_action'] );
		$this->trigger->save_fields( $this->alert );
		$this->assertEquals( 'updated', $this->alert->alert_meta['trigger_action'] );

		unset( $_POST['wp_stream_trigger_action'] );
	}
}
