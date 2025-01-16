<?php
namespace WP_Stream;

class Test_Exporter_CSV extends WP_StreamTestCase {
	/**
	 * Holds the export csv class
	 *
	 * @var Exporter_CSV
	 */
	protected $csv_exporter;

	/**
	 * Set up for tests
	 */
	public function setUp(): void {
		parent::setUp();
		$_GET['page'] = 'wp_stream';

		$this->plugin->admin->export->register_exporters();
		$exporters = $this->plugin->admin->export->get_exporters();

		$this->assertNotEmpty( $exporters );
		$this->assertArrayHasKey( 'csv', $exporters );
		$this->csv_exporter = $exporters['csv'];
	}

	/**
	 * Test CSV exporter output
	 */
	public function test_output_file() {
		$array   = array(
			array(
				'key'  => 'value',
				'key2' => 'value2',
			),
		);
		$columns = array(
			'key'  => 'Key',
			'key2' => 'Key2',
		);

		$this->expectOutputString( "Key,Key2\nvalue,value2\n" );
		$this->csv_exporter->output_file( $array, $columns );
	}
}
