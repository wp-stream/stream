<?php
namespace WP_Stream;

/**
 * Class Test_Alert_Trigger
 *
 * @package WP_Stream
 * @group alerts
 */
abstract class Test_Alert_Trigger extends WP_StreamTestCase {
	public $trigger;

	public function test_construct() {
		// assert that 2 actions + 2 filters have been added
	}

	public function test_check_record() {
		$data   = $this->dummy_stream_data();
		$status = $this->trigger->check_record( true, null, $data, $this->alert );
		$this->assertTrue( $status );
	}

	abstract public function test_check_record_bad();

	public function test_add_fields() {
		$form = new Form_Generator();
		$this->assertCount( 0, $form->fields );

		$this->trigger->add_fields( $form, $this->alert );
		$this->assertNotCount( 0, $form->fields );
	}

	abstract public function test_save_fields();

	public function test_get_display_value() {
		$output = $this->trigger->get_display_value( 'normal', $this->alert );
		$this->assertNotEmpty( $output );
	}

	protected function dummy_stream_data() {
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
