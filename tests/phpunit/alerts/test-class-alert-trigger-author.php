<?php
namespace WP_Stream;

/**
 * Class Test_Alert_Trigger_Author
 *
 * @package WP_Stream
 * @group alerts
 */
class Test_Alert_Trigger_Author extends Test_Alert_Trigger {

	public function setUp(): void {
		parent::setUp();
		$this->trigger = new Alert_Trigger_Author( $this->plugin );

		$this->alert                               = $this->plugin->alerts->get_alert();
		$this->alert->alert_meta['trigger_author'] = '1';
	}

	public function test_check_record_bad() {
		$data            = $this->dummy_stream_data();
		$data['user_id'] = '2';

		$status = $this->trigger->check_record( true, null, $data, $this->alert );
		$this->assertFalse( $status );
	}

	public function test_save_fields() {
		$_POST['wp_stream_trigger_author'] = '0';

		$this->assertNotEquals( '0', $this->alert->alert_meta['trigger_author'] );
		$this->trigger->save_fields( $this->alert );
		$this->assertEquals( '0', $this->alert->alert_meta['trigger_author'] );

		unset( $_POST['wp_stream_trigger_author'] );
	}
}
