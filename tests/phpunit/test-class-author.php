<?php
namespace WP_Stream;

class Test_Author extends WP_StreamTestCase {
	/**
	 * Holds the author base class
	 *
	 * @var Author
	 */
	protected $author;

	public function setUp(): void {
		parent::setUp();

		// Add admin user to test caps
		// We need to change user to verify editing option as admin or editor
		$administrator_id = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'test_admin',
				'email'      => 'test@land.com',
			)
		);
		wp_set_current_user( $administrator_id );

		$this->author = new Author( $administrator_id, get_user_meta( $administrator_id ) );
		$this->assertNotEmpty( $this->author );
	}

	/**
	 * Also tests private method locate_plugin
	 */
	public function test_construct() {
		$this->assertIsInt( $this->author->id );
		$this->assertNotEmpty( $this->author->id );
		$this->assertIsArray( $this->author->meta );
		$this->assertNotEmpty( $this->author->meta );
	}

	public function test_get() {
		$this->author->meta['agent'] = 'Heuristically programmed algorithmic computer';
		$this->assertNotEmpty( $this->author->display_name );
		$this->assertNotEmpty( $this->author->avatar_img );
		$this->assertNotEmpty( $this->author->avatar_src );
		$this->assertNotEmpty( $this->author->role );
		$this->assertNotEmpty( $this->author->agent );
		$this->assertNotEmpty( $this->author->data );
	}

	public function test_get_display_name() {
		$user = wp_get_current_user();
		$this->assertEquals( $user->display_name, $this->author->get_display_name() );
	}

	public function test_get_agent() {
		$agent                       = 'Heuristically programmed algorithmic computer';
		$this->author->meta['agent'] = $agent;
		$this->assertEquals( $agent, $this->author->get_agent() );
	}

	public function test_get_avatar_img() {
		$avatar = get_avatar( get_current_user_id(), 42 );
		$this->assertEquals( $avatar, $this->author->get_avatar_img( 42 ) );
	}

	public function test_get_avatar_src() {
		$img = get_avatar( get_current_user_id(), 42 );
		preg_match( '/src=([\'"])(.*?)\1/', $img, $matches );
		$avatar = html_entity_decode( $matches[2], ENT_COMPAT );
		$this->assertEquals( $avatar, $this->author->get_avatar_src( 42 ) );
	}

	public function test_get_role() {
		$this->assertEquals( 'Administrator', $this->author->get_role() );
	}

	public function test_is_deleted() {
		$this->assertFalse( $this->author->is_deleted() );
	}

	public function test_is_wp_cli() {
		$agent                       = 'wp_cli';
		$this->author->meta['agent'] = $agent;
		$this->assertTrue( $this->author->is_wp_cli() );

		$agent                       = 'Heuristically programmed algorithmic computer';
		$this->author->meta['agent'] = $agent;
		$this->assertFalse( $this->author->is_wp_cli() );
	}

	public function test_is_doing_wp_cron() {
		$this->assertFalse( $this->author->is_doing_wp_cron() );
	}

	public function test_toString() {
		$this->assertNotEmpty( $this->author );
	}

	public function test_get_current_agent() {
		$this->assertEmpty( $this->author->get_current_agent() );
	}

	public function test_get_agent_label() {
		$this->assertEmpty( $this->author->get_agent_label( '' ) );
		$this->assertEquals( 'via WP-CLI', $this->author->get_agent_label( 'wp_cli' ) );
		$this->assertEquals( 'during WP Cron', $this->author->get_agent_label( 'wp_cron' ) );
	}
}
