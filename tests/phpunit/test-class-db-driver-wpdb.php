<?php
namespace WP_Stream;

class Test_DB_Driver_WPDB extends WP_StreamTestCase {
	/**
	 * Holds the DB_Driver_WPDB class
	 *
	 * @var DB_Driver_WPDB driver
	 */
	protected $driver;

	public function setUp(): void {
		parent::setUp();

		$this->driver = new DB_Driver_WPDB();
	}

	public function test_construct() {
		$this->assertNotEmpty( $this->driver->table );
		$this->assertNotEmpty( $this->driver->table_meta );

		global $wpdb;
		$this->assertEquals( $this->driver->table, $wpdb->stream );
		$this->assertEquals( $this->driver->table_meta, $wpdb->streammeta );
		$this->assertEquals( $this->driver->table_meta, $wpdb->recordmeta );
	}

	/**
	 * Also tests the insert_meta method
	 */
	public function test_insert() {
		$dummy_data         = $this->dummy_stream_data();
		$dummy_data['meta'] = $this->dummy_meta_data();

		$stream_id = $this->driver->insert_record( $dummy_data );

		$this->assertNotFalse( $stream_id );
		$this->assertGreaterThan( 0, $stream_id );

		$this->assertEquals( 0, did_action( 'wp_stream_record_insert_error' ) );
		$this->assertGreaterThan( 0, did_action( 'wp_stream_record_inserted' ) );

		global $wpdb;

		// Check that records exist
		$stream_result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->stream} WHERE ID = %d", $stream_id ),
			ARRAY_A
		);
		$this->assertNotEmpty( $stream_result );

		foreach ( $this->dummy_stream_data() as $dummy_key => $dummy_value ) {
			$this->assertArrayHasKey( $dummy_key, $stream_result );
			if ( 'created' === $dummy_key ) {
				// It may take up to two seconds to insert a record, so check the time difference
				$dummy_time  = strtotime( $dummy_value );
				$result_time = strtotime( $stream_result[ $dummy_key ] );
				$this->assertTrue( $dummy_time > 0 );
				$this->assertTrue( $result_time > 0 );
				$this->assertTrue( $result_time - $dummy_time < 2 );
				$this->assertTrue( $result_time - $dummy_time >= -2 );
			} else {
				$this->assertEquals( $dummy_value, $stream_result[ $dummy_key ] );
			}
		}

		// Check that meta exists
		$meta_result = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->streammeta} WHERE record_id = %d", $stream_id ),
			ARRAY_A
		);
		$this->assertNotEmpty( $meta_result );

		$found_all_keys = true;
		foreach ( $meta_result as $meta_row ) {
			$key   = $meta_row['meta_key'];
			$value = $meta_row['meta_value'];
			if ( ! isset( $dummy_data['meta'][ $key ] ) || $value !== $dummy_data['meta'][ $key ] ) {
				$found_all_keys = false;
			}
		}

		$this->assertTrue( $found_all_keys );
	}

	public function test_get_column_values() {
		$summaries = $this->driver->get_column_values( 'summary' );
		$this->assertNotEmpty( $summaries );

		global $wpdb;
		$wpdb->suppress_errors( true );

		$bad_column = $this->driver->get_column_values( 'daisy' );
		$this->assertEmpty( $bad_column );

		$wpdb->suppress_errors( false );
	}

	public function test_table_names() {
		$table_names = $this->driver->get_table_names();

		$this->assertNotEmpty( $table_names );
		$this->assertIsArray( $table_names );
		$this->assertEquals( array( $this->driver->table, $this->driver->table_meta ), $table_names );
	}

	private function dummy_stream_data() {
		return array(
			'object_id' => 10,
			'site_id'   => '1',
			'blog_id'   => get_current_blog_id(),
			'user_id'   => '1',
			'user_role' => 'administrator',
			'created'   => gmdate( 'Y-m-d h:i:s' ),
			'summary'   => '"Hello Dave" plugin activated',
			'ip'        => '192.168.0.1',
			'connector' => 'installer',
			'context'   => 'plugins',
			'action'    => 'activated',
		);
	}

	private function dummy_meta_data() {
		return array(
			'space_helmet' => 'false',
		);
	}
}
