<?php
namespace WP_Stream;

class Test_Export extends WP_StreamTestCase {
	/**
	 * Holds the export base class
	 *
	 * @var Export
	 */
	protected $export;

	/**
	 * Set up for tests
	 */
	public function setUp(): void {
		parent::setUp();
		$_GET['page'] = 'wp_stream';
		$this->export = new Export( $this->plugin );
		$this->assertNotEmpty( $this->export );
		$this->assertNotEmpty( $this->export->get_exporters() );
	}

	/**
	 * Test class constructor
	 */
	public function test_construct() {
		$this->assertNotEmpty( $this->export->plugin );

		$_GET['page'] = 'not_wp_stream';
		$dummy_export = new Export( $this->plugin );
		$this->assertEmpty( $dummy_export->get_exporters() );
	}

	/**
	 * Test that render download uses selected renderer
	 */
	public function test_render_download() {
		$_GET['record-actions']              = 'export-csv';
		$_GET['stream_record_actions_nonce'] = wp_create_nonce( 'stream_record_actions_nonce' );

		ob_start();
		$this->export->render_download();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertStringStartsWith( 'Date,Summary,User,"User ID",Connector,Context,"Object ID",Action,"IP Address"', $output );

		unset( $_GET['action'] );
	}

	/**
	 * Test no output on normal page load
	 */
	public function test_render_output_blank() {
		ob_start();
		$this->export->render_download();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Test that record building grab correct columns
	 */
	public function test_build_record() {
		$columns = array( 'connector' => '' );
		$data    = (object) $this->dummy_stream_data();
		$output  = $this->export->build_record( $data, $columns );

		$this->assertNotEmpty( $output );
		$this->assertArrayHasKey( 'connector', $output );
		$this->assertEquals( $data->connector, $output['connector'] );

		$columns = array( 'context' => '' );
		$output  = $this->export->build_record( $data, $columns );

		$this->assertNotEmpty( $output );
		$this->assertArrayNotHasKey( 'connector', $output );
		$this->assertArrayHasKey( 'context', $output );
		$this->assertEquals( $data->context, $output['context'] );
	}

	/**
	 * Test pagination limit is increased
	 */
	public function test_disable_paginate() {
		$limit = $this->export->disable_paginate();
		$this->assertEquals( $limit, 10000 );
	}

	/**
	 * Test pagination limit filter
	 */
	public function test_export_limit_filter() {
		add_filter(
			'wp_stream_export_limit',
			static function () {
				return 5;
			}
		);

		$filtered_limit = $this->export->disable_paginate();
		$this->assertEquals( $filtered_limit, 5 );

		remove_all_filters( 'wp_stream_export_limit' );
	}

	/**
	 * Test for present columns returning
	 */
	public function test_expand_columns() {
		$test_data = array(
			'date'    => '',
			'summary' => '',
			'user_id' => '',
			'context' => '',
			'action'  => '',
			'ip'      => '',
		);
		$columns   = $this->export->expand_columns( $test_data );

		$this->assertArrayHasKey( 'date', $columns );
		$this->assertArrayHasKey( 'summary', $columns );
		$this->assertArrayHasKey( 'user_id', $columns );
		$this->assertArrayHasKey( 'connector', $columns );
		$this->assertArrayHasKey( 'context', $columns );
		$this->assertArrayHasKey( 'action', $columns );
		$this->assertArrayHasKey( 'ip', $columns );
	}

	/**
	 * Test registering exporters.
	 */
	public function test_register_exporters() {
		$_GET['page'] = 'not_wp_stream';
		$this->export = new Export( $this->plugin );
		$this->assertEmpty( $this->export->get_exporters() );

		$this->export->register_exporters();

		$this->assertNotEmpty( $this->export->get_exporters() );
		$this->assertArrayHasKey( 'json', $this->export->get_exporters() );
		$this->assertArrayHasKey( 'csv', $this->export->get_exporters() );
	}

	/**
	 * Test registering a invalid class type produces an error
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_register_exporter_invalid_class() {
		add_filter(
			'wp_stream_exporters',
			function ( $exporters ) {
				$exporters['test'] = new \stdClass();
				remove_all_filters( 'wp_stream_exporters' );
				return $exporters;
			}
		);
		$this->export->register_exporters();

		$exporters = $this->export->get_exporters();
		$this->assertFalse( isset( $exporters['test'] ) );
	}

	/**
	 * Test exporter validation
	 */
	public function test_is_valid_exporter() {
		$exporters = $this->export->get_exporters();
		$this->assertArrayHasKey( 'json', $exporters );
		$this->assertTrue( $this->export->is_valid_exporter( $exporters['json'] ) );
	}

	/**
	 * Test exporter validation produces false
	 */
	public function test_is_not_valid_exporter() {
		$this->assertFalse( $this->export->is_valid_exporter( new \stdClass() ) );
	}

	/**
	 * Return dummy stream data
	 */
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
