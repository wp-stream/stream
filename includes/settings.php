<?php
/**
 * Settings class
 *
 * @author X-Team <x-team.com>
 * @author Shady Sharaf <shady@x-team.com>
 */
class WP_Stream_Settings {

	/**
	 * Settings key/identifier
	 */
	const KEY = 'wp_stream';

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	public static $options = array();

	/**
	 * Settings fields
	 *
	 * @var array
	 */
	public static $fields = array();

	/**
	 * Public constructor
	 *
	 * @return \WP_Stream_Settings
	 */
	public static function load() {

		// Parse field information gathering default values
		$defaults = self::get_defaults();

		/**
		 * Filter allows for modification of options
		 *
		 * @param  array  array of options
		 * @return array  updated array of options
		 */
		self::$options = apply_filters(
			'wp_stream_options',
			wp_parse_args(
				(array) get_option( self::KEY, array() ),
				$defaults
			)
		);

		// Register settings, and fields
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Check if we need to flush rewrites rules
		add_action( 'update_option_' . self::KEY, array( __CLASS__, 'updated_option_trigger_flush_rules' ), 10, 2 );

		// Remove records when records TTL is shortened
		add_action( 'update_option_' . self::KEY, array( __CLASS__, 'updated_option_ttl_remove_records' ), 10, 2 );

		add_filter( 'wp_stream_serialized_labels', array( __CLASS__, 'get_settings_translations' ) );

		// Ajax call to return the array of users for Select2
		add_action( 'wp_ajax_stream_find_user', array( __CLASS__, 'find_users' ) );

	}

	/**
	 * Return settings fields
	 *
	 * @return array Multidimensional array of fields
	 */
	public static function get_fields() {
		if ( empty( self::$fields ) ) {
			$fields = array(
				'general' => array(
					'title'  => __( 'General', 'stream' ),
					'fields' => array(
						array(
							'name'        => 'log_activity_for',
							'title'       => __( 'Log Activity for', 'stream' ),
							'type'        => 'multi_checkbox',
							'desc'        => __( 'Only the selected roles above will have their activity logged.', 'stream' ),
							'choices'     => self::get_roles(),
							'default'     => array_keys( self::get_roles() ),
						),
						array(
							'name'        => 'role_access',
							'title'       => __( 'Role Access', 'stream' ),
							'type'        => 'user_n_role_select',
							'desc'        => __( 'Users from the selected roles above will have permission to view Stream Records. However, only site Administrators can access Stream Settings.', 'stream' ),
							'default'     => array( 'administrator' ),
						),
						array(
							'name'        => 'private_feeds',
							'title'       => __( 'Private Feeds', 'stream' ),
							'type'        => 'checkbox',
							'desc'        => sprintf(
								__( 'Users from the selected roles above will be given a private key found in their %suser profile%s to access feeds of Stream Records securely.', 'stream' ),
								sprintf(
									'<a href="%s" title="%s">',
									admin_url( 'profile.php' ),
									esc_attr__( 'View Profile', 'stream' )
								),
								'</a>'
							),
							'after_field' => __( 'Enabled' ),
							'default'     => 0,
						),
						array(
							'name'        => 'records_ttl',
							'title'       => __( 'Keep Records for', 'stream' ),
							'type'        => 'number',
							'class'       => 'small-text',
							'desc'        => __( 'Maximum number of days to keep activity records. Leave blank to keep records forever.', 'stream' ),
							'default'     => 90,
							'after_field' => __( 'days', 'stream' ),
						),
						array(
							'name'        => 'delete_all_records',
							'title'       => __( 'Reset Stream Database', 'stream' ),
							'type'        => 'link',
							'href'        => add_query_arg(
								array(
									'action'          => 'wp_stream_reset',
									'wp_stream_nonce' => wp_create_nonce( 'stream_nonce' ),
								),
								admin_url( 'admin-ajax.php' )
							),
							'desc'        => __( 'Warning: Clicking this will delete all activity records from the database.', 'stream' ),
							'default'     => 0,
						),
					),
				),
				'connectors' => array(
					'title' => __( 'Connectors', 'stream' ),
					'fields' => array(
						array(
							'name'        => 'active_connectors',
							'title'       => __( 'Active Connectors', 'stream' ),
							'type'        => 'multi_checkbox',
							'desc'        => __( 'Only the selected connectors above will have their activity logged.', 'stream' ),
							'choices'     => array( __CLASS__, 'get_connectors' ),
							'default'     => array( __CLASS__, 'get_default_connectors' ),
						),
					),
				),
			);
			/**
			 * Filter allows for modification of options fields
			 *
			 * @param  array  array of fields
			 * @return array  updated array of fields
			 */
			self::$fields = apply_filters( 'wp_stream_options_fields', $fields );
		}
		return self::$fields;
	}

	/**
	 * Iterate through registered fields and extract default values
	 *
	 * @return array Default option values
	 */
	public static function get_defaults() {
		$fields   = self::get_fields();
		$defaults = array();
		foreach ( $fields as $section_name => $section ) {
			foreach ( $section['fields'] as $field ) {
				$defaults[$section_name.'_'.$field['name']] = isset( $field['default'] )
					? $field['default']
					: null;
			}
		}
		return $defaults;
	}

	/**
	 * Registers settings fields and sections
	 *
	 * @return void
	 */
	public static function register_settings() {

		$sections = self::get_fields();

		register_setting( self::KEY, self::KEY );

		foreach ( $sections as $section_name => $section ) {
			add_settings_section(
				$section_name,
				null,
				'__return_false',
				self::KEY
			);

			foreach ( $section['fields'] as $field_idx => $field ) {
				if ( ! isset( $field['type'] ) ) { // No field type associated, skip, no GUI
					continue;
				}
				add_settings_field(
					$field['name'],
					$field['title'],
					( isset( $field['callback'] ) ? $field['callback'] : array( __CLASS__, 'output_field' ) ),
					self::KEY,
					$section_name,
					$field + array(
						'section'   => $section_name,
						'label_for' => sprintf( '%s_%s_%s', self::KEY, $section_name, $field['name'] ), // xss ok
					)
				);
			}
		}
	}

	/**
	 * Check if we have updated a settings that requires rewrite rules to be flushed
	 *
	 * @param array $old_value
	 * @param array $new_value
	 *
	 * @internal param $option
	 * @internal param string $option
	 * @action   updated_option
	 * @return void
	 */
	public static function updated_option_trigger_flush_rules( $old_value, $new_value ) {
		if ( is_array( $new_value ) && is_array( $old_value ) ) {
			$new_value = ( array_key_exists( 'general_private_feeds', $new_value ) ) ? $new_value['general_private_feeds'] : 0;
			$old_value = ( array_key_exists( 'general_private_feeds', $old_value ) ) ? $old_value['general_private_feeds'] : 0;
			if ( $new_value !== $old_value ) {
				delete_option( 'rewrite_rules' );
			}
		}
	}

	/**
	 * Compile HTML needed for displaying the field
	 *
	 * @param  array  $field  Field settings
	 * @return string         HTML to be displayed
	 */
	public static function render_field( $field ) {

		$output = null;

		$type          = isset( $field['type'] ) ? $field['type'] : null;
		$section       = isset( $field['section'] ) ? $field['section'] : null;
		$name          = isset( $field['name'] ) ? $field['name'] : null;
		$class         = isset( $field['class'] ) ? $field['class'] : null;
		$placeholder   = isset( $field['placeholder'] ) ? $field['placeholder'] : null;
		$description   = isset( $field['desc'] ) ? $field['desc'] : null;
		$href          = isset( $field['href'] ) ? $field['href'] : null;
		$after_field   = isset( $field['after_field'] ) ? $field['after_field'] : null;
		$title         = isset( $field['title'] ) ? $field['title'] : null;
		$current_value = self::$options[$section . '_' . $name];

		if ( is_callable( $current_value ) ) {
			$current_value = call_user_func( $current_value );
		}

		if ( ! $type || ! $section || ! $name ) {
			return;
		}

		if ( 'multi_checkbox' === $type
			&& ( empty( $field['choices'] ) || ! is_array( $field['choices'] ) )
		) {
			return;
		}

		switch ( $type ) {
			case 'text':
			case 'number':
				$output = sprintf(
					'<input type="%1$s" name="%2$s[%3$s_%4$s]" id="%2$s_%3$s_%4$s" class="%5$s" placeholder="%6$s" value="%7$s" /> %8$s',
					esc_attr( $type ),
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class ),
					esc_attr( $placeholder ),
					esc_attr( $current_value ),
					$after_field // xss ok
				);
				break;
			case 'checkbox':
				$output = sprintf(
					'<label><input type="checkbox" name="%1$s[%2$s_%3$s]" id="%1$s[%2$s_%3$s]" value="1" %4$s /> %5$s</label>',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name ),
					checked( $current_value, 1, false ),
					$after_field // xss ok
				);
				break;

			case 'user_n_role_select':
				$output = sprintf(
					'<div id="%1$s[%2$s_%3$s]">',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name )
				);

				$current_value = (array) $current_value;
				$data_roles    = self::get_roles_for_select2();
				$data_selected = array();

				foreach ( $current_value as $k => $value ){
					if ( ! is_string( $value ) && ! is_numeric( $value ) )
						continue;

					if ( is_numeric( $value ) ){
						$user = new WP_User( $value );
						$data_selected[] = array(
							'id' => $user->ID,
							'login' => $user->user_login,
							'nicename' => $user->user_nicename,
							'email' => $user->user_email,
							'display_name' => $user->display_name,
							'avatar' => 'http://gravatar.com/avatar/' . md5( strtolower( trim( $user->user_email ) ) ),
						);
					} else {
						foreach ( $data_roles as $role ){
							if ($role['id'] != $value)
								continue;

							$data_selected[] = $role;
						}
					}
				}

				$data_l10n = array(
					'roles' => __( 'Roles', 'stream' ),
					'users' => __( 'Users', 'stream' ),
				);

				$output .= sprintf(
					'<input type="hidden" class="user_n_role_select" data-roles=\'%1$s\' data-selected=\'%2$s\' value="%3$s" data-localization=\'%4$s\' />',
					json_encode( $data_roles ),
					json_encode( $data_selected ),
					esc_attr( implode( ',', $current_value ) ),
					json_encode( $data_l10n )
				);

				// Fallback if nothing is selected
				$output .= sprintf(
					'<input type="hidden" name="%1$s[%2$s_%3$s][]" class="user_n_role_select_placeholder" value="__placeholder__" />',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name )
				);

				$output .= '</div>';
				break;
			case 'multi_checkbox':
				$output = sprintf(
					'<div id="%1$s[%2$s_%3$s]"><fieldset>',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name )
				);
				// Fallback if nothing is selected
				$output .= sprintf(
					'<input type="hidden" name="%1$s[%2$s_%3$s][]" value="__placeholder__" />',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name )
				);
				$current_value = (array) $current_value;
				$choices = $field['choices'];
				if ( is_callable( $choices ) ) {
					$choices = call_user_func( $choices );
				}
				foreach ( $choices as $value => $label ) {
					$output .= sprintf(
						'<label>%1$s <span>%2$s</span></label><br />',
						sprintf(
							'<input type="checkbox" name="%1$s[%2$s_%3$s][]" value="%4$s" %5$s />',
							esc_attr( self::KEY ),
							esc_attr( $section ),
							esc_attr( $name ),
							esc_attr( $value ),
							checked( in_array( $value, $current_value ), true, false )
						),
						esc_html( $label )
					);
				}
				$output .= '</fieldset></div>';
				break;
			case 'file':
				$output = sprintf(
					'<input type="file" name="%1$s[%2$s_%3$s]" id="%1$s_%2$s_%3$s" class="%4$s">',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class )
				);
				break;
			case 'link':
				$output = sprintf(
					'<a id="%1$s_%2$s_%3$s" class="%4$s" href="%5$s">%6$s</a>',
					esc_attr( self::KEY ),
					esc_attr( $section ),
					esc_attr( $name ),
					esc_attr( $class ),
					esc_attr( $href ),
					esc_attr( $title )
				);
				break;
		}

		$output .= ! empty( $description ) ? sprintf( '<p class="description">%s</p>', $description /* xss ok */ ) : null;

		return $output;
	}

	/**
	 * Render Callback for post_types field
	 *
	 * @param array $field
	 *
	 * @internal param $args
	 * @return void
	 */
	public static function output_field( $field ) {
		$method = 'output_' . $field['name'];
		if ( method_exists( __CLASS__, $method ) ) {
			return call_user_func( array( __CLASS__, $method ), $field );
		}

		$output = self::render_field( $field );
		echo $output; // xss okay
	}

	/**
	 * Get an array of user roles
	 *
	 * @return array
	 */
	public static function get_roles() {
		$wp_roles = new WP_Roles();
		$roles    = array();

		foreach ( $wp_roles->get_names() as $role => $label ) {
			$roles[ $role ] = translate_user_role( $label );
		}

		return $roles;
	}

	/**
	 * Get an array of registered Connectors
	 *
	 * @return array
	 */
	public static function get_connectors() {
		return WP_Stream_Connectors::$term_labels['stream_connector'];
	}

	/**
	 * Get an array of registered Connectors
	 *
	 * @return array
	 */
	public static function get_default_connectors() {
		return array_keys( WP_Stream_Connectors::$term_labels['stream_connector'] );
	}

	public static function get_roles_for_select2( $locked = array( 'administrator' ) ) {
		$roles = self::get_roles();
		$data_roles = array();
		foreach ( $roles as $key => $role ){
			$data_roles[] = array(
				'id'     => $key,
				'text'   => $role,
				'locked' => ( in_array( $key, $locked ) ? true : false )
			);
		}
		return $data_roles;
	}

	/**
	 * Get an array of active Connectors
	 *
	 * @return array
	 */
	public static function get_active_connectors() {
		$active_connectors = self::$options['connectors_active_connectors'];
		if ( is_callable( $active_connectors ) ) {
			$active_connectors = call_user_func( $active_connectors );
		}
		$active_connectors = wp_list_filter(
			$active_connectors,
			array( '__placeholder__' ),
			'NOT'
		);

		return $active_connectors;
	}

	/**
	 * Get translations of serialized Stream settings
	 *
	 * @filter wp_stream_serialized_labels
	 * @return array Multidimensional array of fields
	 */
	public static function get_settings_translations( $labels ) {
		if ( ! isset( $labels[self::KEY] ) ) {
			$labels[self::KEY] = array();
		}

		foreach ( self::get_fields() as $section_slug => $section ) {
			foreach ( $section['fields'] as $field ) {
				$labels[self::KEY][sprintf( '%s_%s', $section_slug, $field['name'] )] = $field['title'];
			}
		}

		return $labels;
	}

	/**
	 * Remove records when records TTL is shortened
	 *
	 * @param array $old_value
	 * @param array $new_value
	 *
	 * @action update_option_wp_stream
	 * @return void
	 */
	public static function updated_option_ttl_remove_records( $old_value, $new_value ) {
		$ttl_before = isset( $old_value['general_records_ttl'] ) ? (int) $old_value['general_records_ttl'] : -1;
		$ttl_after  = isset( $new_value['general_records_ttl'] ) ? (int) $new_value['general_records_ttl'] : -1;

		if ( $ttl_after < $ttl_before ) {
			/**
			 * Action assists in purging when TTL is shortened
			 */
			do_action( 'wp_stream_auto_purge' );
		}
	}

	/**
	 * Function to output the Users from the search for Select2 AJAX
	 *
	 * @return void
	 */
	public static function find_users(){
		if ( ! defined( 'DOING_AJAX' ) ) return;
		$response = (object) array(
			'status' => false,
			'message' => __( 'There was an error in the request', 'stream' ),
		);

		$request = (object) array(
			'find' => ( isset( $_POST['find'] )? esc_attr( trim( $_POST['find'] ) ) : '' ),
			'page' => ( isset( $_POST['page'] )? absint( trim( $_POST['page'] ) ) - 1 : 0 ),
			'limit' => ( isset( $_POST['limit'] )? absint( trim( $_POST['limit'] ) ) : 25 ),
		);


		add_filter( 'user_search_columns', array( __CLASS__, '_filter_user_search_columns' ), 10, 3 );

		$users = new WP_User_Query(
			array(
				'search' => "*{$request->find}*",
				'number' => $request->limit,
				'offset' => $request->page * $request->limit,
				'search_columns' => array(
					'user_login',
					'user_email',
				),
			)
		);

		if ( $users->get_total() === 0 ){
			exit( json_encode( $response ) );
		}

		$response->status  = true;
		$response->message = '';
		$response->users   = array();
		$response->total   = $users->get_total();

		foreach ( $users->results as $key => $user ) {
			$args = array(
				'id' => $user->ID,
				'login' => $user->user_login,
				'nicename' => $user->user_nicename,
				'email' => $user->user_email,
				'display_name' => $user->display_name,
				'avatar' => 'http://gravatar.com/avatar/' . md5( strtolower( trim( $user->user_email ) ) ),
			);

			$response->users[] = $args;
		}

		exit( json_encode( $response ) );
	}


	/**
	 * Filtering the Columns that we will search for users on our Select2 Fields
	 *
	 * Usage:
	 * add_filter( 'user_search_columns', array( __CLASS__, '_filter_user_search_columns' ), 10, 3 );
	 *
	 * @param  [type] $search_columns Columns that will be searched
	 * @param  [type] $search         Search object
	 * @param  [type] $query          Search Query
	 *
	 * @filter user_search_columns
	 * @return [type]                 Columns after adding `display_name`
	 */
	public static function _filter_user_search_columns( $search_columns, $search, $query ){
		$search_columns[] = 'display_name';
		return $search_columns;
	}
}
