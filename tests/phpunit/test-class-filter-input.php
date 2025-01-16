<?php
namespace WP_Stream;

class Test_Filter_Input extends WP_StreamTestCase {
	/**
	 * Holds the connectors base class
	 *
	 * @var Filter_Input
	 */
	protected $filter;

	public function setUp(): void {
		parent::setUp();

		$this->filter = new Filter_Input();
		$this->assertNotEmpty( $this->filter );
	}

	public function test_super() {
		$_POST['pod_bay_doors'] = 'closed';
		$this->assertEquals( $_POST['pod_bay_doors'], $this->filter->super( INPUT_POST, 'pod_bay_doors' ) ); // phpcs:ignore WordPress.Security.NonceVerification

		$_GET['cause_of_failure'] = 'human error';
		$this->assertEquals( $_GET['cause_of_failure'], $this->filter->super( INPUT_GET, 'cause_of_failure' ) ); // phpcs:ignore WordPress.Security.NonceVerification

		$this->setExpectedException( 'Exception', 'Invalid use, type must be one of INPUT_* family.' );
		$this->filter->super( 42, 'What do you get if you multiply six by nine?' );
	}

	public function test_filter() {
		$this->assertEquals( 'String', $this->filter->filter( 'String' ) );
		$this->assertEquals( '', $this->filter->filter( 'notanemail.com', FILTER_VALIDATE_EMAIL ) );
		$this->assertEquals( 'support@wp-stream.com', $this->filter->filter( 'support@wp-stream.com', FILTER_VALIDATE_EMAIL ) );
		$this->assertEquals( '', $this->filter->filter( 'not.an.ip.address', FILTER_VALIDATE_IP ) );
		$this->assertEquals( '192.168.0.1', $this->filter->filter( '192.168.0.1', FILTER_VALIDATE_IP ) );
		$this->assertEquals( 'support@wp-stream.com', $this->filter->filter( '(support):@wp-stream.com;', FILTER_SANITIZE_EMAIL ) );
	}

	public function test_is_regex() {
		$this->assertFalse( $this->filter->is_regex( '(' ) );
		$this->assertTrue( $this->filter->is_regex( '[A-Z]' ) );
	}

	public function test_is_ip_address() {
		$this->assertFalse( $this->filter->is_ip_address( 'not.an.ip.address' ) );
		$this->assertTrue( $this->filter->is_ip_address( '192.168.0.1' ) );
	}
}
