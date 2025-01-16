<?php
/**
 * Renders and manages the plugin Settings page.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

use WP_Roles;
use WP_User;
use WP_User_Query;

/**
 * Class - Settings
 */
class Settings {

	/**
	 * Holds instance of plugin object
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Settings key/identifier
	 *
	 * @var string
	 */
	public $option_key = 'wp_stream';

	/**
	 * Network settings key/identifier
	 *
	 * @var string
	 */
	public $network_options_key = 'wp_stream_network';

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Settings fields
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Class constructor.
	 *
	 * @param Plugin $plugin Instance of plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->option_key = $this->get_option_key();
		$this->options    = $this->get_options();

		// Register settings, and fields.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Remove records when records TTL is shortened.
		add_action(
			'update_option_' . $this->option_key,
			array(
				$this,
				'updated_option_ttl_remove_records',
			),
			10,
			2
		);

		// Apply label translations for settings.
		add_filter(
			'wp_stream_serialized_labels',
			array(
				$this,
				'get_settings_translations',
			)
		);

		// Ajax callback function to search users.
		add_action( 'wp_ajax_stream_get_users', array( $this, 'get_users' ) );

		// Ajax callback function to search IPs.
		add_action( 'wp_ajax_stream_get_ips', array( $this, 'get_ips' ) );
	}

	/**
	 * Ajax callback function to search users, used on exclude setting page
	 *
	 * @uses \WP_User_Query
	 */
	public function get_users() {
		if ( ! defined( 'DOING_AJAX' ) || ! current_user_can( $this->plugin->admin->settings_cap ) ) {
			return;
		}

		check_ajax_referer( 'stream_get_users', 'nonce' );

		$response = (object) array(
			'status'  => false,
			'message' => esc_html__( 'There was an error in the request', 'stream' ),
		);

		$search = '';
		$input  = wp_stream_filter_input( INPUT_POST, 'find' );

		if ( ! isset( $input['term'] ) ) {
			$search = wp_unslash( trim( $input['term'] ) );
		}

		$request = (object) array(
			'find' => $search,
		);

		add_filter(
			'user_search_columns',
			array(
				$this,
				'add_display_name_search_columns',
			),
			10,
			3
		);

		$users = new WP_User_Query(
			array(
				'search'         => "*{$request->find}*",
				'search_columns' => array(
					'user_login',
					'user_nicename',
					'user_email',
					'user_url',
				),
				'orderby'        => 'display_name',
				'number'         => $this->plugin->admin->preload_users_max,
			)
		);

		remove_filter(
			'user_search_columns',
			array(
				$this,
				'add_display_name_search_columns',
			),
			10
		);

		if ( 0 === $users->get_total() ) {
			wp_send_json_error( $response );
		}
		$users_array = $users->results;

		if ( is_multisite() && is_super_admin() ) {
			$super_admins = get_super_admins();
			foreach ( $super_admins as $admin ) {
				$user          = get_user_by( 'login', $admin );
				$users_array[] = $user;
			}
		}

		$response->status        = true;
		$response->message       = '';
		$response->roles         = $this->get_roles();
		$response->users         = array();
		$users_added_to_response = array();

		foreach ( $users_array as $key => $user ) {
			// exclude duplications.
			if ( array_key_exists( $user->ID, $users_added_to_response ) ) {
				continue;
			} else {
				$users_added_to_response[ $user->ID ] = true;
			}

			$author = new Author( $user->ID );

			$args = array(
				'id'   => $author->ID,
				'text' => $author->display_name,
			);

			$args['tooltip'] = esc_attr(
				sprintf(
					/* translators: %1$d: user ID, %2$s: username, %3$s: email, %4$s: user role (e.g. "42", "administrator", "foo@bar.com", "subscriber") */
					__( 'ID: %1$d\nUser: %2$s\nEmail: %3$s\nRole: %4$s', 'stream' ),
					$author->id,
					$author->user_login,
					$author->user_email,
					ucwords( $author->get_role() )
				)
			);

			$args['icon'] = $author->get_avatar_src( 32 );

			$response->users[] = $args;
		}

		usort(
			$response->users,
			function ( $a, $b ) {
				return strcmp( $a['text'], $b['text'] );
			}
		);

		if ( empty( $search ) || preg_match( '/wp|cli|system|unknown/i', $search ) ) {
			$author            = new Author( 0 );
			$response->users[] = array(
				'id'      => '0',
				'text'    => $author->get_display_name(),
				'icon'    => $author->get_avatar_src( 32 ),
				'tooltip' => esc_html__( 'Actions performed by the system when a user is not logged in (e.g. auto site upgrader, or invoking WP-CLI without --user)', 'stream' ),
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Ajax callback function to search IP addresses, used on exclude setting page
	 */
	public function get_ips() {
		if ( ! defined( 'DOING_AJAX' ) || ! current_user_can( $this->plugin->admin->settings_cap ) ) {
			return;
		}

		check_ajax_referer( 'stream_get_ips', 'nonce' );

		$ips  = $this->plugin->db->existing_records( 'ip' );
		$find = wp_stream_filter_input( INPUT_POST, 'find' );

		if ( isset( $find['term'] ) && '' !== $find['term'] ) {
			$ips = array_filter(
				$ips,
				function ( $ip ) use ( $find ) {
					return 0 === strpos( $ip, $find['term'] );
				}
			);
		}

		if ( $ips ) {
			wp_send_json_success( $ips );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Filter the columns to search in a WP_User_Query search.
	 *
	 * @param array          $search_columns Array of column names to be searched.
	 * @param string         $search Text being searched.
	 * @param \WP_User_Query $query current WP_User_Query instance.
	 *
	 * @return array
	 */
	public function add_display_name_search_columns( $search_columns, $search, $query ) {
		unset( $search );
		unset( $query );

		$search_columns[] = 'display_name';

		return $search_columns;
	}

	/**
	 * Returns the option key
	 *
	 * @return string
	 */
	public function get_option_key() {
		$option_key = $this->option_key;

		$current_page = wp_stream_filter_input( INPUT_GET, 'page' );

		if ( ! $current_page ) {
			$current_page = wp_stream_filter_input( INPUT_GET, 'action' );
		}

		if ( 'wp_stream_network_settings' === $current_page ) {
			$option_key = $this->network_options_key;
		}

		return apply_filters( 'wp_stream_settings_option_key', $option_key );
	}

	/**
	 * Return settings fields
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields = array(
			'general'  => array(
				'title'  => esc_html__( 'General', 'stream' ),
				'fields' => array(
					array(
						'name'    => 'role_access',
						'title'   => esc_html__( 'Role Access', 'stream' ),
						'type'    => 'multi_checkbox',
						'desc'    => esc_html__( 'Users from the selected roles above will have permission to view Stream Records. However, only site Administrators can access Stream Settings.', 'stream' ),
						'choices' => $this->get_roles(),
						'default' => array( 'administrator' ),
					),
					array(
						'name'        => 'records_ttl',
						'title'       => esc_html__( 'Keep Records for', 'stream' ),
						'type'        => 'number',
						'class'       => 'small-text',
						'desc'        => esc_html__( 'Maximum number of days to keep activity records.', 'stream' ),
						'default'     => 30,
						'min'         => 1,
						'max'         => 999,
						'step'        => 1,
						'after_field' => esc_html__( 'days', 'stream' ),
					),
					array(
						'name'        => 'keep_records_indefinitely',
						'title'       => esc_html__( 'Keep Records Indefinitely', 'stream' ),
						'type'        => 'checkbox',
						'desc'        => sprintf( '<strong>%s</strong> %s', esc_html__( 'Not recommended.', 'stream' ), esc_html__( 'Purging old records helps to keep your WordPress installation running optimally.', 'stream' ) ),
						'after_field' => esc_html__( 'Enabled', 'stream' ),
						'default'     => 0,
					),
				),
			),
			'exclude'  => array(
				'title'  => esc_html__( 'Exclude', 'stream' ),
				'fields' => array(
					array(
						'name'    => 'rules',
						'title'   => esc_html__( 'Exclude Rules', 'stream' ),
						'type'    => 'rule_list',
						'desc'    => esc_html__( 'Create rules to exclude certain kinds of activity from being recorded by Stream.', 'stream' ),
						'default' => array(),
						'nonce'   => 'stream_get_ips',
					),
				),
			),
			'advanced' => array(
				'title'  => esc_html__( 'Advanced', 'stream' ),
				'fields' => array(
					array(
						'name'        => 'comment_flood_tracking',
						'title'       => esc_html__( 'Comment Flood Tracking', 'stream' ),
						'type'        => 'checkbox',
						'desc'        => esc_html__( 'WordPress will automatically prevent duplicate comments from flooding the database. By default, Stream does not track these attempts unless you opt-in here. Enabling this is not necessary or recommended for most sites.', 'stream' ),
						'after_field' => esc_html__( 'Enabled', 'stream' ),
						'default'     => 0,
					),
					array(
						'name'    => 'delete_all_records',
						'title'   => esc_html__( 'Reset Stream Database', 'stream' ),
						'type'    => Admin::is_running_async_deletion() ? 'none' : 'link',
						'href'    => add_query_arg(
							array(
								'action'                => 'wp_stream_reset',
								'wp_stream_nonce_reset' => wp_create_nonce( 'stream_nonce_reset' ),
							),
							admin_url( 'admin-ajax.php' )
						),
						'class'   => 'warning',
						'desc'    => esc_html( $this->get_deletion_warning() ),
						'default' => 0,
						'sticky'  => 'bottom',
					),
				),
			),
		);

		// If Akismet is active, allow Admins to opt-in to Akismet tracking.
		if ( class_exists( 'Akismet' ) ) {
			$akismet_tracking = array(
				'name'        => 'akismet_tracking',
				'title'       => esc_html__( 'Akismet Tracking', 'stream' ),
				'type'        => 'checkbox',
				'desc'        => esc_html__( 'Akismet already keeps statistics for comment attempts that it blocks as SPAM. By default, Stream does not track these attempts unless you opt-in here. Enabling this is not necessary or recommended for most sites.', 'stream' ),
				'after_field' => esc_html__( 'Enabled', 'stream' ),
				'default'     => 0,
			);

			array_push( $fields['advanced']['fields'], $akismet_tracking );
		}

		$wp_cron_tracking = array(
			'name'        => 'wp_cron_tracking',
			'title'       => esc_html__( 'WP Cron Tracking', 'stream' ),
			'type'        => 'checkbox',
			'desc'        => esc_html__( 'By default, Stream does not track activity performed by WordPress cron events unless you opt-in here. Enabling this is not necessary or recommended for most sites.', 'stream' ),
			'after_field' => esc_html__( 'Enabled', 'stream' ),
			'default'     => 0,
		);

		array_push( $fields['advanced']['fields'], $wp_cron_tracking );

		/**
		 * Filter allows for modification of options fields
		 *
		 * @return array  Array of option fields
		 */
		$this->fields = apply_filters( 'wp_stream_settings_option_fields', $fields );

		// Sort option fields in each tab by title ASC.
		foreach ( $this->fields as $tab => $options ) {
			$titles = array();

			foreach ( $options['fields'] as $field ) {
				$prefix = null;

				if ( ! empty( $field['sticky'] ) ) {
					$prefix = ( 'bottom' === $field['sticky'] ) ? 'ZZZ' : 'AAA';
				}

				$titles[] = $prefix . $field['title'];
			}

			array_multisort( $titles, SORT_ASC, $this->fields[ $tab ]['fields'] );
		}

		return $this->fields;
	}

	/**
	 * Returns a list of options based on the current screen.
	 *
	 * @return array
	 */
	public function get_options() {
		$option_key = $this->option_key;
		$defaults   = $this->get_defaults( $option_key );

		/**
		 * Filter allows for modification of options
		 *
		 * @param array
		 *
		 * @return array Updated array of options
		 */
		return apply_filters(
			'wp_stream_settings_options',
			wp_parse_args(
				is_network_admin() ? (array) get_site_option( $option_key, array() ) : (array) get_option( $option_key, array() ),
				$defaults
			),
			$option_key
		);
	}

	/**
	 * Iterate through registered fields and extract default values
	 *
	 * @return array
	 */
	public function get_defaults() {
		$fields   = $this->get_fields();
		$defaults = array();

		foreach ( $fields as $section_name => $section ) {
			foreach ( $section['fields'] as $field ) {
				$defaults[ $section_name . '_' . $field['name'] ] = isset( $field['default'] ) ? $field['default'] : null;
			}
		}

		return (array) $defaults;
	}

	/**
	 * Retrieves the deletion warning message based on the site type
	 * and whether or not there is currently a process running to delete the tables.
	 *
	 * @return string The deletion warning message.
	 */
	public function get_deletion_warning(): string {

		// Check if there is an action scheduler event running already deleting things.
		if ( Admin::is_running_async_deletion() ) {

			$warning = __( 'Currently deleting records. Please be patient, this can take a while.', 'stream' );

		} elseif ( $this->plugin->is_multisite_network_activated() ) {

			$warning = __( 'Warning: This will delete all activity records from the database for all sites.', 'stream' );

		} elseif ( $this->plugin->is_multisite_not_network_activated() ) {

			$warning = __( 'Warning: This will delete all activity records from the database for this site.', 'stream' );

		} else {

			$warning = __( 'Warning: This will delete all activity records from the database.', 'stream' );
		}

		return $warning;
	}

	/**
	 * Registers settings fields and sections
	 *
	 * @return void
	 */
	public function register_settings() {
		$sections = $this->get_fields();

		register_setting(
			$this->option_key,
			$this->option_key,
			array(
				$this,
				'sanitize_settings',
			)
		);

		foreach ( $sections as $section_name => $section ) {
			add_settings_section(
				$section_name,
				null,
				'__return_false',
				$this->option_key
			);

			foreach ( $section['fields'] as $field_idx => $field ) {
				// No field type associated, skip, no GUI.
				if ( ! isset( $field['type'] ) ) {
					continue;
				}

				add_settings_field(
					$field['name'],
					$field['title'],
					( isset( $field['callback'] ) ? $field['callback'] : array(
						$this,
						'output_field',
					) ),
					$this->option_key,
					$section_name,
					$field + array(
						'section'   => $section_name,
						'label_for' => sprintf( '%s_%s_%s', $this->option_key, $section_name, $field['name'] ),
					)
				);
			}
		}
	}

	/**
	 * Sanitization callback for settings field values before save
	 *
	 * @param array $input  Raw input.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$output   = array();
		$sections = $this->get_fields();

		foreach ( $sections as $section => $data ) {
			if ( empty( $data['fields'] ) || ! is_array( $data['fields'] ) ) {
				continue;
			}

			foreach ( $data['fields'] as $field ) {
				$type = ! empty( $field['type'] ) ? $field['type'] : null;
				$name = ! empty( $field['name'] ) ? sprintf( '%s_%s', $section, $field['name'] ) : null;

				if ( empty( $type ) || ! isset( $input[ $name ] ) || '' === $input[ $name ] ) {
					continue;
				}

				$output[ $name ] = $this->sanitize_setting_by_field_type( $input[ $name ], $type );
			}
		}

		return $output;
	}

	/**
	 * Sanitizes a setting value based on the field type.
	 *
	 * @param mixed  $value      The value to be sanitized.
	 * @param string $field_type The type of field.
	 *
	 * @return mixed The sanitized value.
	 */
	public function sanitize_setting_by_field_type( $value, $field_type ) {

		// Sanitize depending on the type of field.
		switch ( $field_type ) {
			case 'number':
				$sanitized_value = is_numeric( $value ) ? intval( trim( $value ) ) : '';
				break;
			case 'checkbox':
				$sanitized_value = is_numeric( $value ) ? absint( trim( $value ) ) : '';
				break;
			default:
				if ( is_array( $value ) ) {
					$sanitized_value = $value;

					// Support all values in multidimentional arrays too.
					array_walk_recursive(
						$sanitized_value,
						function ( &$v ) {
							$v = sanitize_text_field( trim( $v ) );
						}
					);
				} else {
					$sanitized_value = sanitize_text_field( trim( $value ) );
				}
		}

		return $sanitized_value;
	}

	/**
	 * Compile HTML needed for displaying the field
	 *
	 * @param array $field Field settings.
	 *
	 * @return string HTML to be displayed
	 */
	public function render_field( $field ) {
		$output      = null;
		$type        = isset( $field['type'] ) ? $field['type'] : null;
		$section     = isset( $field['section'] ) ? $field['section'] : null;
		$name        = isset( $field['name'] ) ? $field['name'] : null;
		$class       = isset( $field['class'] ) ? $field['class'] : null;
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : null;
		$description = isset( $field['desc'] ) ? $field['desc'] : null;
		$href        = isset( $field['href'] ) ? $field['href'] : null;
		$rows        = isset( $field['rows'] ) ? $field['rows'] : 10;
		$cols        = isset( $field['cols'] ) ? $field['cols'] : 50;
		$after_field = isset( $field['after_field'] ) ? $field['after_field'] : null;
		$default     = isset( $field['default'] ) ? $field['default'] : null;
		$min         = isset( $field['min'] ) ? $field['min'] : 0;
		$max         = isset( $field['max'] ) ? $field['max'] : 999;
		$step        = isset( $field['step'] ) ? $field['step'] : 1;
		$title       = isset( $field['title'] ) ? $field['title'] : null;
		$nonce       = isset( $field['nonce'] ) ? $field['nonce'] : null;

		if ( isset( $field['value'] ) ) {
			$current_value = $field['value'];
		} elseif ( isset( $this->options[ $section . '_' . $name ] ) ) {
				$current_value = $this->options[ $section . '_' . $name ];
		} else {
			$current_value = null;
		}

		$option_key = $this->option_key;

		if ( is_callable( $current_value ) ) {
			$current_value = call_user_func( $current_value );
		}

		if ( ! $type || ! $section || ! $name ) {
			return '';
		}

		if ( 'multi_checkbox' === $type && ( empty( $field['choices'] ) || ! is_array( $field['choices'] ) ) ) {
			return '';
		}

		switch ( $type ) {
			case 'text':
			case 'number':
				$output = sprintf(
					'<input type="%1$s" name="%2$s[%3$s_%4$s]" id="%2$s_%3$s_%4$s" class="%5$s" placeholder="%6$s" min="%7$d" max="%8$d" step="%9$d" value="%10$s" /> %11$s',
					esc_attr( $type ),
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class ),
					esc_attr( $placeholder ),
					esc_attr( $min ),
					esc_attr( $max ),
					esc_attr( $step ),
					esc_attr( $current_value ),
					wp_kses_post( $after_field )
				);
				break;
			case 'textarea':
				$output = sprintf(
					'<textarea name="%1$s[%2$s_%3$s]" id="%1$s_%2$s_%3$s" class="%4$s" placeholder="%5$s" rows="%6$d" cols="%7$d">%8$s</textarea> %9$s',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class ),
					esc_attr( $placeholder ),
					absint( $rows ),
					absint( $cols ),
					esc_textarea( $current_value ),
					wp_kses_post( $after_field )
				);
				break;
			case 'checkbox':
				if ( isset( $current_value ) ) {
					$value = $current_value;
				} elseif ( isset( $default ) ) {
					$value = $default;
				} else {
					$value = 0;
				}

				$output = sprintf(
					'<label><input type="checkbox" name="%1$s[%2$s_%3$s]" id="%1$s[%2$s_%3$s]" value="1" %4$s /> %5$s</label>',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					checked( $value, 1, false ),
					wp_kses_post( $after_field )
				);
				break;
			case 'multi_checkbox':
				$output = sprintf(
					'<div id="%1$s[%2$s_%3$s]"><fieldset>',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name )
				);
				// Fallback if nothing is selected.
				$output       .= sprintf(
					'<input type="hidden" name="%1$s[%2$s_%3$s][]" value="__placeholder__" />',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name )
				);
				$current_value = (array) $current_value;
				$choices       = $field['choices'];
				if ( is_callable( $choices ) ) {
					$choices = call_user_func( $choices );
				}
				foreach ( $choices as $value => $label ) {
					$output .= sprintf(
						'<label>%1$s <span>%2$s</span></label><br />',
						sprintf(
							'<input type="checkbox" name="%1$s[%2$s_%3$s][]" value="%4$s" %5$s />',
							esc_attr( $option_key ),
							esc_attr( $section ),
							esc_attr( $name ),
							esc_attr( $value ),
							checked( in_array( $value, $current_value, true ), true, false )
						),
						esc_html( $label )
					);
				}
				$output .= '</fieldset></div>';
				break;
			case 'select':
				$current_value = $this->options[ $section . '_' . $name ];
				$default_value = isset( $default['value'] ) ? $default['value'] : '-1';
				$default_name  = isset( $default['name'] ) ? $default['name'] : 'Choose Setting';

				$output  = sprintf(
					'<select name="%1$s[%2$s_%3$s]" class="%1$s_%2$s_%3$s">',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name )
				);
				$output .= sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $default_value ),
					checked( $default_value === $current_value, true, false ),
					esc_html( $default_name )
				);
				foreach ( $field['choices'] as $value => $label ) {
					$output .= sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $value ),
						checked( $value === $current_value, true, false ),
						esc_html( $label )
					);
				}
				$output .= '</select>';
				break;
			case 'file':
				$output = sprintf(
					'<input type="file" name="%1$s[%2$s_%3$s]" class="%4$s">',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class )
				);
				break;
			case 'link':
				$output = sprintf(
					'<a id="%1$s_%2$s_%3$s" class="%4$s" href="%5$s">%6$s</a>',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class ),
					esc_attr( $href ),
					esc_attr( $title )
				);
				break;
			case 'select2':
				if ( ! isset( $current_value ) ) {
					$current_value = '';
				}

				$data_values = array();

				if ( isset( $field['choices'] ) ) {
					$choices = $field['choices'];
					if ( is_callable( $choices ) ) {
						$param   = ( isset( $field['param'] ) ) ? $field['param'] : null;
						$choices = call_user_func( $choices, $param );
					}
					foreach ( $choices as $key => $value ) {
						if ( is_array( $value ) ) {
							$child_values = array();
							if ( isset( $value['children'] ) ) {
								$child_values = array();
								foreach ( $value['children'] as $child_key => $child_value ) {
									$child_values[] = array(
										'id'   => $child_key,
										'text' => $child_value,
									);
								}
							}
							if ( isset( $value['label'] ) ) {
								$data_values[] = array(
									'id'       => $key,
									'text'     => $value['label'],
									'children' => $child_values,
								);
							}
						} else {
							$data_values[] = array(
								'id'   => $key,
								'text' => $value,
							);
						}
					}
					$class .= ' with-source';
				}

				$input_html = sprintf(
					'<input type="hidden" name="%1$s[%2$s_%3$s]" data-values=\'%4$s\' value="%5$s" class="select2-select %6$s" data-placeholder="%7$s" />',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( wp_json_encode( $data_values ) ),
					esc_attr( $current_value ),
					esc_attr( $class ),
					/* translators: %s: the title of the dropdown menu (e.g. "users") */
					sprintf( esc_html__( 'Any %s', 'stream' ), $title )
				);

				$output = sprintf(
					'<div class="%1$s_%2$s_%3$s">%4$s</div>',
					esc_attr( $option_key ),
					esc_attr( $section ),
					esc_attr( $name ),
					$input_html
				);

				break;
			case 'rule_list':
				$users  = count_users();
				$form   = new Form_Generator();
				$output = '<p class="description">' . esc_html( $description ) . '</p>';

				$actions_top    = sprintf( '<input type="button" class="button" id="%1$s_new_rule" value="&#43; %2$s" />', esc_attr( $section . '_' . $name ), esc_html__( 'Add New Rule', 'stream' ) );
				$actions_bottom = sprintf( '<input type="button" class="button" id="%1$s_remove_rules" value="%2$s" />', esc_attr( $section . '_' . $name ), esc_html__( 'Delete Selected Rules', 'stream' ) );

				$output .= sprintf( '<div class="tablenav top">%1$s</div>', $actions_top );
				$output .= '<table class="wp-list-table widefat fixed stream-exclude-list">';

				unset( $description );

				$heading_row = sprintf(
					'<tr>
						<td scope="col" class="manage-column column-cb check-column">%1$s</td>
						<th scope="col" class="manage-column">%2$s</th>
						<th scope="col" class="manage-column">%3$s</th>
						<th scope="col" class="manage-column">%4$s</th>
						<th scope="col" class="manage-column">%5$s</th>
						<th scope="col" class="actions-column manage-column"><span class="hidden">%6$s</span></th>
					</tr>',
					'<input class="cb-select" type="checkbox" />',
					esc_html__( 'Author or Role', 'stream' ),
					esc_html__( 'Context', 'stream' ),
					esc_html__( 'Action', 'stream' ),
					esc_html__( 'IP Address', 'stream' ),
					esc_html__( 'Filters', 'stream' )
				);

				$exclude_rows = array();

				// Account for when no rules have been added yet.
				if ( ! is_array( $current_value ) ) {
					$current_value = array();
				}

				// Prepend an empty row.
				$current_value['exclude_row'] = ( isset( $current_value['exclude_row'] ) ? $current_value['exclude_row'] : array() ) + array( 'helper' => '' );

				foreach ( $current_value['exclude_row'] as $key => $value ) {
					// Prepare values.
					$author_or_role = isset( $current_value['author_or_role'][ $key ] ) ? $current_value['author_or_role'][ $key ] : '';
					$connector      = isset( $current_value['connector'][ $key ] ) ? $current_value['connector'][ $key ] : '';
					$context        = isset( $current_value['context'][ $key ] ) ? $current_value['context'][ $key ] : '';
					$action         = isset( $current_value['action'][ $key ] ) ? $current_value['action'][ $key ] : '';
					$ip_address     = isset( $current_value['ip_address'][ $key ] ) ? $current_value['ip_address'][ $key ] : '';

					// Author or Role dropdown menu.
					$author_or_role_values   = array();
					$author_or_role_selected = array();

					foreach ( $this->get_roles() as $role_id => $role ) {
						$args  = array(
							'value' => $role_id,
							'text'  => $role,
						);
						$count = isset( $users['avail_roles'][ $role_id ] ) ? $users['avail_roles'][ $role_id ] : 0;

						if ( ! empty( $count ) ) {
							/* translators: %d: a number of users (e.g. "42") */
							$args['user_count'] = sprintf( _n( '%d user', '%d users', absint( $count ), 'stream' ), absint( $count ) );
						}

						if ( $role_id === $author_or_role ) {
							$author_or_role_selected['value'] = $role_id;
							$author_or_role_selected['text']  = $role;
						}

						$author_or_role_values[] = $args;
					}

					if ( empty( $author_or_role_selected ) && is_numeric( $author_or_role ) ) {
						$user                    = new WP_User( $author_or_role );
						$display_name            = ( 0 === $user->ID ) ? esc_html__( 'N/A', 'stream' ) : $user->display_name;
						$author_or_role_selected = array(
							'value' => $user->ID,
							'text'  => $display_name,
						);
						$author_or_role_values[] = $author_or_role_selected;
					}

					$author_or_role_input = $form->render_field(
						'select2',
						array(
							'name'    => esc_attr( sprintf( '%1$s[%2$s_%3$s][%4$s][]', $option_key, $section, $name, 'author_or_role' ) ),
							'options' => $author_or_role_values,
							'classes' => 'author_or_role',
							// Data attributes are escaped in Form_Generator::prepare_data_attributes_string().
							'data'    => array(
								'placeholder'   => __( 'Any Author or Role', 'stream' ),
								'nonce'         => wp_create_nonce( 'stream_get_users' ),
								'selected-id'   => isset( $author_or_role_selected['value'] ) ? $author_or_role_selected['value'] : '',
								'selected-text' => isset( $author_or_role_selected['text'] ) ? $author_or_role_selected['text'] : '',
							),
						),
						false
					);

					// Context dropdown menu.
					$context_values = array();

					foreach ( $this->get_terms_labels( 'context' ) as $context_id => $context_data ) {
						if ( is_array( $context_data ) ) {
							$child_values = array();
							if ( isset( $context_data['children'] ) ) {
								$child_values = array();
								foreach ( $context_data['children'] as $child_id => $child_value ) {
									$child_values[] = array(
										'value'  => $context_id . '-' . $child_id,
										'text'   => $child_value,
										'parent' => $context_id,
									);
								}
							}
							if ( isset( $context_data['label'] ) ) {
								$context_values[] = array(
									'value'    => $context_id,
									'text'     => $context_data['label'],
									'children' => $child_values,
								);
							}
						} else {
							$context_values[] = array(
								'value' => $context_id,
								'text'  => $context_data,
							);
						}
					}

					$connector_or_context_input = $form->render_field(
						'select2',
						array(
							'name'    => esc_attr( sprintf( '%1$s[%2$s_%3$s][%4$s][]', $option_key, $section, $name, 'connector_or_context' ) ),
							'options' => $context_values,
							'classes' => 'connector_or_context',
							// Data attributes are escaped in Form_Generator::prepare_data_attributes_string().
							'data'    => array(
								'group'       => 'connector',
								'placeholder' => __( 'Any Context', 'stream' ),
							),
						),
						false
					);

					$connector_input = $form->render_field(
						'hidden',
						array(
							'name'    => esc_attr( sprintf( '%1$s[%2$s_%3$s][%4$s][]', $option_key, $section, $name, 'connector' ) ),
							'value'   => $connector,
							'classes' => 'connector',
						),
						false
					);

					$context_input = $form->render_field(
						'hidden',
						array(
							'name'    => esc_attr( sprintf( '%1$s[%2$s_%3$s][%4$s][]', $option_key, $section, $name, 'context' ) ),
							'value'   => $context,
							'classes' => 'context',
						),
						false
					);

					// Action dropdown menu.
					$action_values = array();

					foreach ( $this->get_terms_labels( 'action' ) as $action_id => $action_data ) {
						$action_values[] = array(
							'value' => $action_id,
							'text'  => $action_data,
						);
					}

					$action_input = $form->render_field(
						'select2',
						array(
							'name'    => esc_attr( sprintf( '%1$s[%2$s_%3$s][%4$s][]', $option_key, $section, $name, 'action' ) ),
							'value'   => $action,
							'options' => $action_values,
							'classes' => 'action',
							// Data attributes are escaped in Form_Generator::prepare_data_attributes_string().
							'data'    => array(
								'placeholder' => __( 'Any Action', 'stream' ),
							),
						),
						false
					);

					// IP Address input.
					$ip_address_input = $form->render_field(
						'select2',
						array(
							'name'     => esc_attr( sprintf( '%1$s[%2$s_%3$s][%4$s][]', $option_key, $section, $name, 'ip_address' ) ),
							'value'    => $ip_address,
							'classes'  => 'ip_address',
							// Data attributes are escaped in Form_Generator::prepare_data_attributes_string().
							'data'     => array(
								'placeholder' => __( 'Any IP Address', 'stream' ),
								'nonce'       => wp_create_nonce( 'stream_get_ips' ),
							),
							'multiple' => true,
						),
						false
					);

					// Hidden helper input.
					$helper_input = sprintf(
						'<input type="hidden" name="%1$s[%2$s_%3$s][%4$s][]" value="" />',
						esc_attr( $option_key ),
						esc_attr( $section ),
						esc_attr( $name ),
						'exclude_row'
					);

					$exclude_rows[] = sprintf(
						'<tr class="%1$s %2$s">
							<th scope="row" class="check-column">%3$s %4$s</th>
							<td>%5$s</td>
							<td>%6$s %7$s %8$s</td>
							<td>%9$s</td>
							<td>%10$s</td>
							<th scope="row" class="actions-column">
								<a href="#" class="exclude_rules_remove_rule_row">%11$s</a>
							</th>
						</tr>',
						( 0 !== (int) $key % 2 ) ? 'alternate' : '',
						( 'helper' === (string) $key ) ? 'hidden helper' : '',
						'<input class="cb-select" type="checkbox" />',
						$helper_input,
						$author_or_role_input,
						$connector_or_context_input,
						$connector_input,
						$context_input,
						$action_input,
						$ip_address_input,
						esc_html__( 'Delete', 'stream' )
					);
				}

				$no_rules_found_row = sprintf(
					'<tr class="no-items hidden"><td class="colspanchange" colspan="5">%1$s</td></tr>',
					esc_html__( 'No rules found.', 'stream' )
				);

				$output .= '<thead>' . $heading_row . '</thead>';
				$output .= '<tfoot>' . $heading_row . '</tfoot>';
				$output .= '<tbody>' . $no_rules_found_row . implode( '', $exclude_rows ) . '</tbody>';

				$output .= '</table>';

				$output .= sprintf( '<div class="tablenav bottom">%1$s</div>', $actions_bottom );

				break;
		}
		$output .= ! empty( $description ) ? wp_kses_post( sprintf( '<p class="description">%s</p>', $description ) ) : null;

		return $output;
	}

	/**
	 * Render Callback for post_types field
	 *
	 * @param array $field  Field to be rendered.
	 *
	 * @return string
	 */
	public function output_field( $field ) {
		$method = 'output_' . $field['name'];

		if ( method_exists( $this, $method ) ) {
			return call_user_func( array( $this, $method ), $field );
		}

		$output = $this->render_field( $field );

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get an array of user roles
	 *
	 * @return array
	 */
	public function get_roles() {
		$wp_roles = new WP_Roles();
		$roles    = array();

		foreach ( $wp_roles->get_names() as $role => $label ) {
			$roles[ $role ] = translate_user_role( $label );
		}

		return $roles;
	}

	/**
	 * Function will return all terms labels of given column
	 *
	 * @param string $column Name of the column.
	 *
	 * @return array
	 */
	public function get_terms_labels( $column ) {
		$return_labels = array();

		if ( isset( $this->plugin->connectors->term_labels[ 'stream_' . $column ] ) ) {
			if ( 'context' === $column && isset( $this->plugin->connectors->term_labels['stream_connector'] ) ) {
				$connectors = $this->plugin->connectors->term_labels['stream_connector'];
				$contexts   = $this->plugin->connectors->term_labels['stream_context'];

				foreach ( $connectors as $connector => $connector_label ) {
					$return_labels[ $connector ]['label'] = $connector_label;
					foreach ( $contexts as $context => $context_label ) {
						if ( isset( $this->plugin->connectors->contexts[ $connector ] ) && array_key_exists( $context, $this->plugin->connectors->contexts[ $connector ] ) ) {
							$return_labels[ $connector ]['children'][ $context ] = $context_label;
						}
					}
				}
			} else {
				$return_labels = $this->plugin->connectors->term_labels[ 'stream_' . $column ];
			}

			ksort( $return_labels );
		}

		return $return_labels;
	}

	/**
	 * Remove records when records TTL is shortened
	 *
	 * @action update_option_wp_stream
	 *
	 * @param array $old_value  Old value.
	 * @param array $new_value  New value.
	 */
	public function updated_option_ttl_remove_records( $old_value, $new_value ) {
		$ttl_before = isset( $old_value['general_records_ttl'] ) ? (int) $old_value['general_records_ttl'] : - 1;
		$ttl_after  = isset( $new_value['general_records_ttl'] ) ? (int) $new_value['general_records_ttl'] : - 1;

		if ( $ttl_after < $ttl_before ) {
			/**
			 * Action assists in purging when TTL is shortened
			 */
			do_action( 'wp_stream_auto_purge' );
		}
	}

	/**
	 * Get translations of serialized Stream settings
	 *
	 * @filter wp_stream_serialized_labels
	 *
	 * @param array $labels  Setting labels.
	 *
	 * @return array Multidimensional array of fields
	 */
	public function get_settings_translations( $labels ) {
		if ( ! isset( $labels[ $this->option_key ] ) ) {
			$labels[ $this->option_key ] = array();
		}

		foreach ( $this->get_fields() as $section_slug => $section ) {
			foreach ( $section['fields'] as $field ) {
				$labels[ $this->option_key ][ sprintf( '%s_%s', $section_slug, $field['name'] ) ] = $field['title'];
			}
		}

		return $labels;
	}
}
