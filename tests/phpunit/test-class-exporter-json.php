<?php
namespace WP_Stream;

class Test_Exporter_JSON extends WP_StreamTestCase {
	/**
	 * Holds the export json class
	 *
	 * @var Exporter_JSON
	 */
	protected $json_exporter;

	/**
	 * Set up for tests
	 */
	public function setUp(): void {
		parent::setUp();
		$_GET['page'] = 'wp_stream';

		$this->plugin->admin->export->register_exporters();
		$exporters = $this->plugin->admin->export->get_exporters();

		$this->assertNotEmpty( $exporters );
		$this->assertArrayHasKey( 'json', $exporters );
		$this->json_exporter = $exporters['json'];
	}

	/**
	 * Test JSON exporter output
	 */
	public function test_output_file() {
		$array = array( 'key' => 'value' );
		$this->expectOutputString( '{"key":"value"}' );
		$this->json_exporter->output_file( $array, array() );
	}
}
