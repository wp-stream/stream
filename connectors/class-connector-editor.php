<?php
/**
 * Connector for Editor
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class - Connector_Editor
 */
class Connector_Editor extends Connector {
	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public $name = 'editor';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public $actions = array();

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	private $edited_file = array();

	/**
	 * Register connector in the WP Frontend
	 *
	 * @var bool
	 */
	public $register_frontend = false;

	/**
	 * Register all context hooks
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		add_action( 'wp_ajax_edit-theme-plugin-file', array( $this, 'get_edition_data' ), 1 );
	}

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public function get_label() {
		return esc_html__( 'Editor', 'stream' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public function get_action_labels() {
		return array(
			'updated' => esc_html__( 'Updated', 'stream' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		/**
		 * Filter available context labels for the Editor connector
		 *
		 * @return array Array of context slugs and their translated labels
		 */
		return apply_filters(
			'wp_stream_editor_context_labels',
			array(
				'themes'  => esc_html__( 'Themes', 'stream' ),
				'plugins' => esc_html__( 'Plugins', 'stream' ),
			)
		);
	}

	/**
	 * Get the context based on wp_redirect location
	 *
	 * @param  string $location The URL of the redirect.
	 *
	 * @return string Context slug
	 */
	public function get_context( $location ) {
		$context = null;

		if ( false !== strpos( $location, 'theme-editor.php' ) ) {
			$context = 'themes';
		}

		if ( false !== strpos( $location, 'plugin-editor.php' ) ) {
			$context = 'plugins';
		}

		/**
		 * Filter available contexts for the Editor connector
		 *
		 * @param  string  $context  Context slug
		 * @param  string  $location The URL of the redirect
		 * @return string            Context slug
		 */
		return apply_filters( 'wp_stream_editor_context', $context, $location );
	}

	/**
	 * Get the message format for file updates
	 *
	 * @return string Translated string
	 */
	public function get_message() {
		/* translators: %1$s: a file name, %2$s: a theme / plugin name (e.g. "index.php", "Stream") */
		return _x(
			'"%1$s" in "%2$s" updated',
			'1: File name, 2: Theme/plugin name',
			'stream'
		);
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 *
	 * @param array  $links   Previous links registered.
	 * @param object $record  Stream record.
	 *
	 * @return array Action links
	 */
	public function action_links( $links, $record ) {
		if ( current_user_can( 'edit_theme_options' ) ) {
			$file_name = $record->get_meta( 'file', true );
			$file_path = $record->get_meta( 'file_path', true );

			if ( ! empty( $file_name ) && ! empty( $file_path ) ) {
				$theme_slug    = $record->get_meta( 'theme_slug', true );
				$plugin_slug   = $record->get_meta( 'plugin_slug', true );
				$theme_exists  = ( ! empty( $theme_slug ) && file_exists( $file_path ) );
				$plugin_exists = ( ! empty( $plugin_slug ) && file_exists( $file_path ) );

				if ( $theme_exists ) {
					$links[ esc_html__( 'Edit File', 'stream' ) ] = add_query_arg(
						array(
							'theme' => rawurlencode( $theme_slug ),
							'file'  => rawurlencode( $file_name ),
						),
						self_admin_url( 'theme-editor.php' )
					);

					$links[ esc_html__( 'Theme Details', 'stream' ) ] = add_query_arg(
						array(
							'theme' => rawurlencode( $theme_slug ),
						),
						self_admin_url( 'themes.php' )
					);
				}

				if ( $plugin_exists ) {
					$links[ esc_html__( 'Edit File', 'stream' ) ] = add_query_arg(
						array(
							'plugin' => rawurlencode( $plugin_slug ),
							'file'   => rawurlencode( str_ireplace( trailingslashit( WP_PLUGIN_DIR ), '', $file_path ) ),
						),
						self_admin_url( 'plugin-editor.php' )
					);
				}
			}
		}

		return $links;
	}

	/**
	 * Retrieves data submitted on the screen, prepares it for the appropriate context type and logs the changes
	 *
	 * @action wp_ajax_edit-theme-plugin-file
	 */
	public function get_edition_data() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$action         = wp_stream_filter_input( INPUT_POST, 'action' );
		$request_method = wp_stream_filter_input( INPUT_SERVER, 'REQUEST_METHOD' );
		$theme_slug     = wp_stream_filter_input( INPUT_POST, 'theme' );
		$plugin_slug    = wp_stream_filter_input( INPUT_POST, 'plugin' );
		$relative_file  = wp_stream_filter_input( INPUT_POST, 'file' );

		if ( ! empty( $theme_slug ) && ! check_admin_referer( 'edit-theme_' . $theme_slug . '_' . $relative_file, 'nonce' ) ) {
			return;
		}

		if ( ! empty( $plugin_slug ) && ! check_admin_referer( 'edit-plugin_' . $relative_file, 'nonce' ) ) {
			return;
		}

		if ( ( isset( $request_method ) && 'POST' !== $request_method ) || ( 'edit-theme-plugin-file' !== $action ) ) {
			return;
		}

		$location = null;

		if ( $theme_slug ) {
			$location          = 'theme-editor.php';
			$this->edited_file = $this->get_theme_data( $theme_slug );
		}

		if ( $plugin_slug ) {
			$location          = 'plugin-editor.php';
			$this->edited_file = $this->get_plugin_data( $plugin_slug );
		}

		if ( ! $location ) {
			return;
		}

		$this->log_changes( $location );
	}

	/**
	 * Retrieve theme data needed for the log message
	 *
	 * @param string $slug  The theme slug (e.g. twentyfourteen).
	 *
	 * @return mixed $output Compacted variables
	 */
	public function get_theme_data( $slug ) {
		$theme = wp_get_theme( $slug );

		if ( ! $theme->exists() || ( $theme->errors() && 'theme_no_stylesheet' === $theme->errors()->get_error_code() ) ) {
			return false;
		}

		$allowed_files = $theme->get_files( 'php', 1 );
		$style_files   = $theme->get_files( 'css' );
		$file          = wp_stream_filter_input( INPUT_POST, 'file' );

		$allowed_files['style.css'] = $style_files['style.css'];

		if ( empty( $file ) ) {
			$file_name = 'style.css';
			$file_path = $allowed_files['style.css'];
		} else {
			$file_name = $file;
			$file_path = sprintf( '%s/%s', $theme->get_stylesheet_directory(), $file_name );
		}

		$file_md5 = md5_file( $file_path );
		$name     = $theme->get( 'Name' );

		$output = compact(
			'file_name',
			'file_path',
			'file_md5',
			'slug',
			'name'
		);

		return $output;
	}

	/**
	 * Retrieve plugin data needed for the log message
	 *
	 * @param  string $slug    The plugin file base name (e.g. akismet/akismet.php).
	 * @return mixed  $output  Compacted variables.
	 */
	public function get_plugin_data( $slug ) {
		$base      = null;
		$name      = null;
		$slug      = current( explode( '/', $slug ) );
		$file_name = wp_stream_filter_input( INPUT_POST, 'file' );
		$file_path = WP_PLUGIN_DIR . '/' . $file_name;
		$file_md5  = md5_file( $file_path );
		$plugins   = get_plugins();

		foreach ( $plugins as $key => $plugin_data ) {
			if ( 0 === strpos( $key, $slug ) ) {
				$base = $key;
				$name = $plugin_data['Name'];
				break;
			}
		}

		$file_name = str_ireplace( trailingslashit( $slug ), '', $file_name );
		$slug      = ! empty( $base ) ? $base : $slug;

		$output = compact(
			'file_name',
			'file_path',
			'file_md5',
			'slug',
			'name'
		);

		return $output;
	}

	/**
	 * Logs changes
	 *
	 * @param string $location Location.
	 */
	public function log_changes( string $location ): string {
		if ( ! empty( $this->edited_file ) ) {
			if ( md5_file( $this->edited_file['file_path'] ) === $this->edited_file['file_md5'] ) {
				$context = $this->get_context( $location );

				switch ( $context ) {
					case 'themes':
						$name_key = 'theme_name';
						$slug_key = 'theme_slug';
						break;
					case 'plugins':
						$name_key = 'plugin_name';
						$slug_key = 'plugin_slug';
						break;
					default:
						$name_key = 'name';
						$slug_key = 'slug';
				}

				$this->log(
					$this->get_message(),
					array(
						'file'      => (string) $this->edited_file['file_name'],
						$name_key   => (string) $this->edited_file['name'],
						$slug_key   => (string) $this->edited_file['slug'],
						'file_path' => (string) $this->edited_file['file_path'],
					),
					null,
					$context,
					'updated'
				);
			}
		}

		return $location;
	}
}
