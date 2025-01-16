<?php
namespace WP_Stream;

/**
 * Class Test_Alert_Trigger_Context
 *
 * @package WP_Stream
 * @group alerts
 */
class Test_Alert_Trigger_Context extends Test_Alert_Trigger {

	public function setUp(): void {
		parent::setUp();
		$this->trigger = new Alert_Trigger_Context( $this->plugin );

		$this->alert                                  = $this->plugin->alerts->get_alert();
		$this->alert->alert_meta['trigger_connector'] = 'installer';
		$this->alert->alert_meta['trigger_context']   = 'plugins';
	}

	public function test_check_record_bad() {
		$data              = $this->dummy_stream_data();
		$data['connector'] = 'settings';
		$data['context']   = 'general';

		$status = $this->trigger->check_record( true, null, $data, $this->alert );
		$this->assertFalse( $status );
	}

	public function test_save_fields() {
		$_POST['wp_stream_trigger_connector'] = 'settings';
		$_POST['wp_stream_trigger_context']   = 'general';

		$this->assertNotEquals( 'settings', $this->alert->alert_meta['trigger_connector'] );
		$this->assertNotEquals( 'general', $this->alert->alert_meta['trigger_context'] );
		$this->trigger->save_fields( $this->alert );
		$this->assertEquals( 'settings', $this->alert->alert_meta['trigger_connector'] );
		$this->assertEquals( 'general', $this->alert->alert_meta['trigger_context'] );

		unset( $_POST['wp_stream_trigger_author'] );
	}
}
