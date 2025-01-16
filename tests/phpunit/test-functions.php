<?php
namespace WP_Stream;

class Test_Functions extends WP_StreamTestCase {
	public function test_wp_stream_get_iso_8601_extended_date() {
		$time = '1095379198';

		$date = wp_stream_get_iso_8601_extended_date( $time );
		$this->assertSame( $date, '2004-09-16T23:59:58+0000' );

		$offset_date = wp_stream_get_iso_8601_extended_date( $time, 5 );
		$this->assertSame( $offset_date, '2004-09-16T23:59:58+0500' );
	}
}
