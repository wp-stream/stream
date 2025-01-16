<?php
namespace WP_Stream;

class Test_Connectors extends WP_StreamTestCase {
	/**
	 * Holds the connectors base class
	 *
	 * @var Connectors
	 */
	protected $connectors;

	public function setUp(): void {
		parent::setUp();

		$this->connectors = $this->plugin->connectors;
		$this->assertNotEmpty( $this->connectors );
	}

	public function test_construct() {
		$this->assertNotEmpty( $this->connectors->plugin );
		$this->assertInstanceOf( '\WP_Stream\Plugin', $this->connectors->plugin );
	}

	public function test_load_connectors() {
		$this->connectors->load_connectors();
		$this->assertNotEmpty( $this->connectors->connectors );
		$this->assertNotEmpty( $this->connectors->contexts );
		$this->assertNotEmpty( $this->connectors->term_labels['stream_connector'] );
		$this->assertNotEmpty( $this->connectors->term_labels['stream_context'] );
		$this->assertNotEmpty( $this->connectors->term_labels['stream_action'] );

		ob_start();
		$this->plugin->admin->admin_notices();
		$notices = ob_get_clean();

		$this->assertEmpty( $notices );
	}

	public function test_unload_connectors() {
		$this->connectors->load_connectors();
		$this->assertNotEmpty( $this->connectors->connectors );

		foreach ( $this->connectors->connectors as $connector ) {
			$this->assertTrue( $connector->is_registered() );
		}

		$this->connectors->unload_connectors();
		foreach ( $this->connectors->connectors as $connector ) {
			$this->assertFalse( $connector->is_registered() );
		}
	}

	public function test_reload_connectors() {
		$this->connectors->load_connectors();
		$this->assertNotEmpty( $this->connectors->connectors );
		$this->connectors->unload_connectors();
		foreach ( $this->connectors->connectors as $connector ) {
			$this->assertFalse( $connector->is_registered() );
		}

		$this->connectors->reload_connectors();
		foreach ( $this->connectors->connectors as $connector ) {
			$this->assertTrue( $connector->is_registered() );
		}
	}

	public function test_unload_connector() {
		$this->connectors->load_connectors();
		$this->assertNotEmpty( $this->connectors->connectors['posts'] );
		$this->assertTrue( $this->connectors->connectors['posts']->is_registered() );

		$this->connectors->unload_connector( 'posts' );
		$this->assertFalse( $this->connectors->connectors['posts']->is_registered() );
	}

	public function test_reload_connector() {
		$this->connectors->load_connectors();
		$this->assertNotEmpty( $this->connectors->connectors['posts'] );
		$this->connectors->unload_connector( 'posts' );
		$this->assertFalse( $this->connectors->connectors['posts']->is_registered() );

		$this->connectors->reload_connector( 'posts' );
		$this->assertTrue( $this->connectors->connectors['posts']->is_registered() );
	}
}
