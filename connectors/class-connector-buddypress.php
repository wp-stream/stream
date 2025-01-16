<?php
/**
 * Connector for BuddyPress
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class - Connector_BuddyPress
 */
class Connector_BuddyPress extends Connector {

	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public $name = 'buddypress';

	/**
	 * Holds tracked plugin minimum version required
	 *
	 * @const string
	 */
	const PLUGIN_MIN_VERSION = '2.0.1';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public $actions = array(
		'update_option',
		'add_option',
		'delete_option',
		'update_site_option',
		'add_site_option',
		'delete_site_option',

		'bp_before_activity_delete',
		'bp_activity_deleted_activities',

		'bp_activity_mark_as_spam',
		'bp_activity_mark_as_ham',
		'bp_activity_admin_edit_after',

		'groups_create_group',
		'groups_update_group',
		'groups_before_delete_group',
		'groups_details_updated',
		'groups_settings_updated',

		'groups_leave_group',
		'groups_join_group',

		'groups_promote_member',
		'groups_demote_member',
		'groups_ban_member',
		'groups_unban_member',
		'groups_remove_member',

		'xprofile_field_after_save',
		'xprofile_fields_deleted_field',

		'xprofile_group_after_save',
		'xprofile_groups_deleted_group',
	);

	/**
	 * Tracked option keys
	 *
	 * @var array
	 */
	public $options = array(
		'bp-active-components' => null,
		'bp-pages'             => null,
		'buddypress'           => null,
	);

	/**
	 * Flag to stop logging update logic twice
	 *
	 * @var bool
	 */
	public $is_update = false;

	/**
	 * Stores an activity to be deleted for use across multiple callbacks.
	 *
	 * @var bool
	 */
	public $deleted_activity = false;

	/**
	 * Stores post data of an activity to be deleted for use across multiple callbacks.
	 *
	 * @var array
	 */
	public $delete_activity_args = array();

	/**
	 * Flag for ignoring irrelevant activity deletions.
	 *
	 * @var bool
	 */
	public $ignore_activity_bulk_deletion = false;

	/**
	 * Check if plugin dependencies are satisfied and add an admin notice if not
	 *
	 * @return bool
	 */
	public function is_dependency_satisfied() {
		if ( class_exists( 'BuddyPress' ) && version_compare( \BuddyPress::instance()->version, self::PLUGIN_MIN_VERSION, '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public function get_label() {
		return esc_html_x( 'BuddyPress', 'buddypress', 'stream' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public function get_action_labels() {
		return array(
			'created'     => esc_html_x( 'Created', 'buddypress', 'stream' ),
			'updated'     => esc_html_x( 'Updated', 'buddypress', 'stream' ),
			'activated'   => esc_html_x( 'Activated', 'buddypress', 'stream' ),
			'deactivated' => esc_html_x( 'Deactivated', 'buddypress', 'stream' ),
			'deleted'     => esc_html_x( 'Deleted', 'buddypress', 'stream' ),
			'spammed'     => esc_html_x( 'Marked as spam', 'buddypress', 'stream' ),
			'unspammed'   => esc_html_x( 'Unmarked as spam', 'buddypress', 'stream' ),
			'promoted'    => esc_html_x( 'Promoted', 'buddypress', 'stream' ),
			'demoted'     => esc_html_x( 'Demoted', 'buddypress', 'stream' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		return array(
			'components'     => esc_html_x( 'Components', 'buddypress', 'stream' ),
			'groups'         => esc_html_x( 'Groups', 'buddypress', 'stream' ),
			'activity'       => esc_html_x( 'Activity', 'buddypress', 'stream' ),
			'profile_fields' => esc_html_x( 'Profile fields', 'buddypress', 'stream' ),
		);
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 *
	 * @param  array  $links   Previous links registered.
	 * @param  object $record  Stream record.
	 *
	 * @return array Action links
	 */
	public function action_links( $links, $record ) {

		// Check we have access to BuddyPress on this blog and that the user will have access to the links.
		if ( ! $this->is_dependency_satisfied() || ! bp_current_user_can_moderate() ) {
			return array();
		}

		// In the links, we also need to check the functions themselves as they're dependent on the BuddyPress module being enabled.
		if ( in_array( $record->context, array( 'components' ), true ) ) {
			$option_key = $record->get_meta( 'option_key', true );

			if ( 'bp-active-components' === $option_key ) {
				$links[ esc_html__( 'Edit', 'stream' ) ] = add_query_arg(
					array(
						'page' => 'bp-components',
					),
					get_admin_url( $record->blog_id, 'admin.php' )
				);
			} elseif ( 'bp-pages' === $option_key ) {
				$page_id = $record->get_meta( 'page_id', true );

				$links[ esc_html__( 'Edit setting', 'stream' ) ] = add_query_arg(
					array(
						'page' => 'bp-page-settings',
					),
					get_admin_url( $record->blog_id, 'admin.php' )
				);

				if ( $page_id ) {
					$links[ esc_html__( 'Edit Page', 'stream' ) ] = get_edit_post_link( $page_id );
					$links[ esc_html__( 'View', 'stream' ) ]      = get_permalink( $page_id );
				}
			}
		} elseif ( in_array( $record->context, array( 'settings' ), true ) ) {
			$links[ esc_html__( 'Edit setting', 'stream' ) ] = add_query_arg(
				array(
					'page' => $record->get_meta( 'page', true ),
				),
				get_admin_url( $record->blog_id, 'admin.php' )
			);
		} elseif ( in_array( $record->context, array( 'groups' ), true ) && function_exists( 'groups_get_group' ) ) {
			$group_id = $record->get_meta( 'id', true );
			$group    = \groups_get_group(
				array(
					'group_id' => $group_id,
				)
			);

			if ( $group ) {
				// Build actions URLs.
				$base_url   = \bp_get_admin_url( 'admin.php?page=bp-groups&amp;gid=' . $group_id );
				$delete_url = wp_nonce_url( $base_url . '&amp;action=delete', 'bp-groups-delete' );
				$edit_url   = $base_url . '&amp;action=edit';
				$visit_url  = function_exists( 'bp_get_group_url' ) ? \bp_get_group_url( $group ) : \bp_get_group_permalink( $group );

				$links[ esc_html__( 'Edit group', 'stream' ) ]   = $edit_url;
				$links[ esc_html__( 'View group', 'stream' ) ]   = $visit_url;
				$links[ esc_html__( 'Delete group', 'stream' ) ] = $delete_url;
			}
		} elseif ( in_array( $record->context, array( 'activity' ), true ) && function_exists( 'bp_activity_get' ) ) {
			$activity_id = $record->get_meta( 'id', true );
			$activities  = \bp_activity_get(
				array(
					'in'   => $activity_id,
					'spam' => 'all',
				)
			);
			if ( ! empty( $activities['activities'] ) ) {
				$activity = reset( $activities['activities'] );

				$base_url   = \bp_get_admin_url( 'admin.php?page=bp-activity&amp;aid=' . $activity->id );
				$spam_nonce = esc_html( '_wpnonce=' . wp_create_nonce( 'spam-activity_' . $activity->id ) );
				$delete_url = $base_url . "&amp;action=delete&amp;$spam_nonce";
				$edit_url   = $base_url . '&amp;action=edit';
				$ham_url    = $base_url . "&amp;action=ham&amp;$spam_nonce";
				$spam_url   = $base_url . "&amp;action=spam&amp;$spam_nonce";

				if ( $activity->is_spam ) {
					$links[ esc_html__( 'Ham', 'stream' ) ] = $ham_url;
				} else {
					$links[ esc_html__( 'Edit', 'stream' ) ] = $edit_url;
					$links[ esc_html__( 'Spam', 'stream' ) ] = $spam_url;
				}
				$links[ esc_html__( 'Delete', 'stream' ) ] = $delete_url;
			}
		} elseif ( in_array( $record->context, array( 'profile_fields' ), true ) ) {
			$field_id = $record->get_meta( 'field_id', true );
			$group_id = $record->get_meta( 'group_id', true );

			if ( empty( $field_id ) ) { // is a group action.
				$links[ esc_html__( 'Edit', 'stream' ) ]   = add_query_arg(
					array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'edit_group',
						'group_id' => $group_id,
					),
					admin_url( 'users.php' )
				);
				$links[ esc_html__( 'Delete', 'stream' ) ] = add_query_arg(
					array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'delete_group',
						'group_id' => $group_id,
					),
					admin_url( 'users.php' )
				);
			} elseif ( class_exists( 'BP_XProfile_Field' ) ) {
				$field = new \BP_XProfile_Field( $field_id );
				if ( empty( $field->type ) ) {
					return $links;
				}
				$links[ esc_html__( 'Edit', 'stream' ) ]   = add_query_arg(
					array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'edit_field',
						'group_id' => $group_id,
						'field_id' => $field_id,
					),
					admin_url( 'users.php' )
				);
				$links[ esc_html__( 'Delete', 'stream' ) ] = add_query_arg(
					array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'delete_field',
						'field_id' => $field_id,
					),
					admin_url( 'users.php' )
				);
			}
		}

		return $links;
	}

	/**
	 * Register the connector
	 */
	public function register() {
		parent::register();

		$this->options = array_merge(
			$this->options,
			array(
				'hide-loggedout-adminbar'       => array(
					'label' => esc_html_x( 'Toolbar', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'_bp_force_buddybar'            => array(
					'label' => esc_html_x( 'Toolbar', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'bp-disable-account-deletion'   => array(
					'label' => esc_html_x( 'Account Deletion', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'bp-disable-profile-sync'       => array(
					'label' => esc_html_x( 'Profile Syncing', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'bp_restrict_group_creation'    => array(
					'label' => esc_html_x( 'Group Creation', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'bb-config-location'            => array(
					'label' => esc_html_x( 'bbPress Configuration', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'bp-disable-blogforum-comments' => array(
					'label' => _x( 'Blog &amp; Forum Comments', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'_bp_enable_heartbeat_refresh'  => array(
					'label' => esc_html_x( 'Activity auto-refresh', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'_bp_enable_akismet'            => array(
					'label' => esc_html_x( 'Akismet', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
				'bp-disable-avatar-uploads'     => array(
					'label' => esc_html_x( 'Avatar Uploads', 'buddypress', 'stream' ),
					'page'  => 'bp-settings',
				),
			)
		);
	}

	/**
	 * Track buddyPress-specific option changes.
	 *
	 * @param string $option    Option key.
	 * @param string $old_value Old value.
	 * @param string $new_value New value.
	 */
	public function callback_update_option( $option, $old_value, $new_value ) {
		$this->check( $option, $old_value, $new_value );
	}

	/**
	 * Track buddyPress-specific option creations.
	 *
	 * @param string $option Option key.
	 * @param string $val    Value.
	 */
	public function callback_add_option( $option, $val ) {
		$this->check( $option, null, $val );
	}

	/**
	 * Track buddyPress-specific option deletions.
	 *
	 * @param string $option Option key.
	 */
	public function callback_delete_option( $option ) {
		$this->check( $option, null, null );
	}

	/**
	 * Track buddyPress-specific site option changes
	 *
	 * @param string $option    Option key.
	 * @param string $old_value Old value.
	 * @param string $new_value New value.
	 */
	public function callback_update_site_option( $option, $old_value, $new_value ) {
		$this->check( $option, $old_value, $new_value );
	}

	/**
	 * Track buddyPress-specific site option creations.
	 *
	 * @param string $option Option key.
	 * @param string $val    Value.
	 */
	public function callback_add_site_option( $option, $val ) {
		$this->check( $option, null, $val );
	}

	/**
	 * Track buddyPress-specific site option deletions.
	 *
	 * @param string $option Option key.
	 */
	public function callback_delete_site_option( $option ) {
		$this->check( $option, null, null );
	}

	/**
	 * Logs buddyPress-specific (site) option action.
	 *
	 * @param string $option     Option key.
	 * @param string $old_value  Old value.
	 * @param string $new_value  New value.
	 */
	public function check( $option, $old_value, $new_value ) {
		if ( ! array_key_exists( $option, $this->options ) ) {
			return;
		}

		$replacement = str_replace( '-', '_', $option );

		if ( method_exists( $this, 'check_' . $replacement ) ) {
			call_user_func(
				array(
					$this,
					'check_' . $replacement,
				),
				$old_value,
				$new_value
			);
		} else {
			$data         = $this->options[ $option ];
			$option_title = $data['label'];
			$context      = isset( $data['context'] ) ? $data['context'] : 'settings';
			$page         = isset( $data['page'] ) ? $data['page'] : null;

			$this->log(
				/* translators: %s: setting name (e.g. "Group Creation") */
				__( '"%s" setting updated', 'stream' ),
				compact( 'option_title', 'option', 'old_value', 'new_value', 'page' ),
				null,
				$context,
				isset( $data['action'] ) ? $data['action'] : 'updated'
			);
		}
	}

	/**
	 * Log buddyPress' components' state.
	 *
	 * @param array $old_value  Old value.
	 * @param array $new_value  New value.
	 */
	public function check_bp_active_components( $old_value, $new_value ) {
		$options = array();

		if ( ! is_array( $old_value ) || ! is_array( $new_value ) ) {
			return;
		}

		foreach ( $this->get_changed_keys( $old_value, $new_value, 0 ) as $field_key => $field_value ) {
			$options[ $field_key ] = $field_value;
		}

		$components = \bp_core_admin_get_components();

		$actions = array(
			true  => esc_html__( 'activated', 'stream' ),
			false => esc_html__( 'deactivated', 'stream' ),
		);

		foreach ( $options as $option => $option_value ) {
			if ( ! isset( $components[ $option ], $actions[ $option_value ] ) ) {
				continue;
			}

			$this->log(
				sprintf(
					/* translators: %1$s: component title, %2$s: component action (e.g. "Members component deactivated") */
					__( '"%1$s" component %2$s', 'stream' ),
					$components[ $option ]['title'],
					$actions[ $option_value ]
				),
				array(
					'option'     => $option,
					'option_key' => 'bp-active-components',
					'old_value'  => $old_value,
					'value'      => $new_value,
				),
				null,
				'components',
				$option_value ? 'activated' : 'deactivated'
			);
		}
	}

	/**
	 * Log buddyPress' page assignment.
	 *
	 * @param array $old_value  Old value.
	 * @param array $new_value  New value.
	 */
	public function check_bp_pages( $old_value, $new_value ) {
		$options = array();

		if ( ! is_array( $old_value ) || ! is_array( $new_value ) ) {
			return;
		}

		foreach ( $this->get_changed_keys( $old_value, $new_value, 0 ) as $field_key => $field_value ) {
			$options[ $field_key ] = $field_value;
		}

		$pages = array_merge(
			$this->bp_get_directory_pages(),
			array(
				'register' => esc_html_x( 'Register', 'buddypress', 'stream' ),
				'activate' => esc_html_x( 'Activate', 'buddypress', 'stream' ),
			)
		);

		foreach ( $options as $option => $option_value ) {
			if ( ! isset( $pages[ $option ] ) ) {
				continue;
			}

			$page = ! empty( $new_value[ $option ] ) ? get_post( $new_value[ $option ] )->post_title : esc_html__( 'No page', 'stream' );

			$this->log(
				sprintf(
					/* translators: %1$s: a directory page, %2$s: a page title (e.g. "Register", "Registration" ) */
					__( '"%1$s" page set to "%2$s"', 'stream' ),
					$pages[ $option ],
					$page
				),
				array(
					'option'     => $option,
					'option_key' => 'bp-pages',
					'old_value'  => $old_value,
					'value'      => $new_value,
					'page_id'    => empty( $new_value[ $option ] ) ? 0 : $new_value[ $option ],
				),
				null,
				'components',
				'updated'
			);
		}
	}

	/**
	 * Logs activity deletions
	 *
	 * @action bp_before_activity_delete
	 *
	 * @param array $args  Target activity data.
	 */
	public function callback_bp_before_activity_delete( $args ) {
		if ( empty( $args['id'] ) ) { // Bail if we're deleting in bulk.
			$this->delete_activity_args = $args;

			return;
		}

		$activity = new \BP_Activity_Activity( $args['id'] );

		$this->deleted_activity = $activity;
	}

	/**
	 * Logs activity bulk deletions.
	 *
	 * @action bp_activity_deleted_activities
	 *
	 * @param array $activities_ids  Activity IDs of deleted activities.
	 */
	public function callback_bp_activity_deleted_activities( $activities_ids ) {
		if ( 1 === count( $activities_ids ) && isset( $this->deleted_activity ) ) { // Single activity deletion.
			$activity = $this->deleted_activity;
			$this->log(
				sprintf(
					/* translators: %s: an activity title (e.g. "Update") */
					__( '"%s" activity deleted', 'stream' ),
					wp_strip_all_tags( $activity->action )
				),
				array(
					'id'      => $activity->id,
					'item_id' => $activity->item_id,
					'type'    => $activity->type,
					'author'  => $activity->user_id,
				),
				$activity->id,
				$activity->component,
				'deleted'
			);
		} else {
			/**
			 * Bulk deletion
			 * Sometimes some objects removal are followed by deleting relevant
			 * activities, so we probably don't need to track those
			 */
			if ( $this->ignore_activity_bulk_deletion ) {
				$this->ignore_activity_bulk_deletion = false;

				return;
			}
			$this->log(
				sprintf(
					/* translators: %s: an activity title (e.g. "Update") */
					__( '"%s" activities were deleted', 'stream' ),
					count( $activities_ids )
				),
				array(
					'count' => count( $activities_ids ),
					'args'  => $this->delete_activity_args,
					'ids'   => $activities_ids,
				),
				null,
				'activity',
				'deleted'
			);
		}
	}

	/**
	 * Logs activates marked as spam
	 *
	 * @action bp_activity_mark_as_spam
	 *
	 * @param array $activity  Activity.
	 * @param mixed $by        Marker.
	 */
	public function callback_bp_activity_mark_as_spam( $activity, $by ) {
		unset( $by );

		$this->log(
			sprintf(
				/* translators: %s an activity title (e.g. "Update") */
				__( 'Marked activity "%s" as spam', 'stream' ),
				wp_strip_all_tags( $activity->action )
			),
			array(
				'id'      => $activity->id,
				'item_id' => $activity->item_id,
				'type'    => $activity->type,
				'author'  => $activity->user_id,
			),
			$activity->id,
			$activity->component,
			'spammed'
		);
	}

	/**
	 * Log activities marked as ham
	 *
	 * @action bp_activity_mark_as_ham
	 *
	 * @param array $activity  Activity.
	 * @param mixed $by        Marker.
	 */
	public function callback_bp_activity_mark_as_ham( $activity, $by ) {
		unset( $by );

		$this->log(
			sprintf(
				/* translators: %s: an activity title (e.g. "Update") */
				__( 'Unmarked activity "%s" as spam', 'stream' ),
				wp_strip_all_tags( $activity->action )
			),
			array(
				'id'      => $activity->id,
				'item_id' => $activity->item_id,
				'type'    => $activity->type,
				'author'  => $activity->user_id,
			),
			$activity->id,
			$activity->component,
			'unspammed'
		);
	}

	/**
	 * Log activity changes made in the WP Admin.
	 *
	 * @action bp_activity_admin_edit_after
	 *
	 * @param array $activity  Activity.
	 * @param mixed $error     Any errors.
	 */
	public function callback_bp_activity_admin_edit_after( $activity, $error ) {
		unset( $error );

		$this->log(
			sprintf(
				/* translators: %s: an activity title (e.g. "Update") */
				__( '"%s" activity updated', 'stream' ),
				wp_strip_all_tags( $activity->action )
			),
			array(
				'id'      => $activity->id,
				'item_id' => $activity->item_id,
				'type'    => $activity->type,
				'author'  => $activity->user_id,
			),
			$activity->id,
			'activity',
			'updated'
		);
	}

	/**
	 * Logs group actions
	 *
	 * @param int|object $group   Group object or group ID.
	 * @param string     $action  Action.
	 * @param array      $meta    Meta data.
	 * @param string     $message Message.
	 */
	public function group_action( $group, $action, $meta = array(), $message = null ) {
		if ( is_numeric( $group ) ) {
			$group = \groups_get_group(
				array(
					'group_id' => $group,
				)
			);
		}

		$replacements = array(
			$group->name,
		);

		if ( ! $message ) {
			if ( 'created' === $action ) {
				/* translators: %s: a group name (e.g. "Favourites") */
				$message = esc_html__( '"%s" group created', 'stream' );
			} elseif ( 'updated' === $action ) {
				/* translators: %s: a group name (e.g. "Favourites") */
				$message = esc_html__( '"%s" group updated', 'stream' );
			} elseif ( 'deleted' === $action ) {
				/* translators: %s: a group name (e.g. "Favourites") */
				$message = esc_html__( '"%s" group deleted', 'stream' );
			} elseif ( 'joined' === $action ) {
				/* translators: %s: a group name (e.g. "Favourites") */
				$message = esc_html__( 'Joined group "%s"', 'stream' );
			} elseif ( 'left' === $action ) {
				/* translators: %s: a group name (e.g. "Favourites") */
				$message = esc_html__( 'Left group "%s"', 'stream' );
			} elseif ( 'banned' === $action ) {
				/* translators: %1$s: a user display name, %2$s: a group name (e.g. "Jane Doe", "Favourites") */
				$message        = esc_html__( 'Banned "%2$s" from "%1$s"', 'stream' );
				$replacements[] = get_user_by( 'id', $meta['user_id'] )->display_name;
			} elseif ( 'unbanned' === $action ) {
				/* translators: %1$s: a user display name, %2$s: a group name (e.g. "Jane Doe", "Favourites") */
				$message        = esc_html__( 'Unbanned "%2$s" from "%1$s"', 'stream' );
				$replacements[] = get_user_by( 'id', $meta['user_id'] )->display_name;
			} elseif ( 'removed' === $action ) {
				/* translators: %1$s: a user display name, %2$s: a group name (e.g. "Jane Doe", "Favourites") */
				$message        = esc_html__( 'Removed "%2$s" from "%1$s"', 'stream' );
				$replacements[] = get_user_by( 'id', $meta['user_id'] )->display_name;
			} else {
				return;
			}
		}

		$this->log(
			vsprintf(
				$message,
				$replacements
			),
			array_merge(
				array(
					'id'   => $group->id,
					'name' => $group->name,
					'slug' => $group->slug,
				),
				$meta
			),
			$group->id,
			'groups',
			$action
		);
	}

	/**
	 * Log creation of new group.
	 *
	 * @action groups_create_group
	 *
	 * @param int    $group_id  Group ID.
	 * @param object $member    Group founder user object.
	 * @param object $group     Group object.
	 */
	public function callback_groups_create_group( $group_id, $member, $group ) {
		unset( $group_id );
		unset( $member );

		$this->group_action( $group, 'created' );
	}

	/**
	 * Log update to existing group.
	 *
	 * @action groups_update_group
	 *
	 * @param int    $group_id Group ID.
	 * @param object $group    Group object.
	 */
	public function callback_groups_update_group( $group_id, $group ) {
		unset( $group_id );

		$this->group_action( $group, 'updated' );
	}

	/**
	 * Log group deletion
	 *
	 * @action groups_before_delete_group
	 *
	 * @param int $group_id  Group ID.
	 */
	public function callback_groups_before_delete_group( $group_id ) {
		$this->ignore_activity_bulk_deletion = true;
		$this->group_action( $group_id, 'deleted' );
	}

	/**
	 * Log change to group details
	 *
	 * @action groups_details_updated
	 *
	 * @param int $group_id  Group ID.
	 */
	public function callback_groups_details_updated( $group_id ) {
		$this->is_update = true;
		$this->group_action( $group_id, 'updated' );
	}

	/**
	 * Log change to group settings
	 *
	 * @action groups_settings_updated
	 *
	 * @param int $group_id  Group ID.
	 */
	public function callback_groups_settings_updated( $group_id ) {
		if ( $this->is_update ) {
			return;
		}
		$this->group_action( $group_id, 'updated' );
	}

	/**
	 * Logs user leaving group
	 *
	 * @action groups_leave_group
	 *
	 * @param int $group_id  Group ID.
	 * @param int $user_id  User ID of member.
	 */
	public function callback_groups_leave_group( $group_id, $user_id ) {
		$this->group_action( $group_id, 'left', compact( 'user_id' ) );
	}

	/**
	 * Logs user joining group
	 *
	 * @action groups_join_group
	 *
	 * @param int $group_id  Group ID.
	 * @param int $user_id  User ID of member.
	 */
	public function callback_groups_join_group( $group_id, $user_id ) {
		$this->group_action( $group_id, 'joined', compact( 'user_id' ) );
	}

	/**
	 * Logs group member promotion.
	 *
	 * @action groups_promote_member
	 *
	 * @param int    $group_id  Group ID.
	 * @param int    $user_id   User ID of member.
	 * @param string $status    Member's new user role.
	 */
	public function callback_groups_promote_member( $group_id, $user_id, $status ) {
		$group   = \groups_get_group(
			array(
				'group_id' => $group_id,
			)
		);
		$user    = new \WP_User( $user_id );
		$roles   = array(
			'admin' => esc_html_x( 'Administrator', 'buddypress', 'stream' ),
			'mod'   => esc_html_x( 'Moderator', 'buddypress', 'stream' ),
		);
		$message = sprintf(
			/* translators: %1$s: a user's display name, %2$s: a user role, %3$s: a group name (e.g. "Jane Doe", "subscriber", "Favourites") */
			__( 'Promoted "%1$s" to "%2$s" in "%3$s"', 'stream' ),
			$user->display_name,
			$roles[ $status ],
			$group->name
		);
		$this->group_action( $group_id, 'promoted', compact( 'user_id', 'status' ), $message );
	}

	/**
	 * Log group member demotion
	 *
	 * @action groups_demote_member
	 *
	 * @param int $group_id  Group ID.
	 * @param int $user_id   User ID of member.
	 */
	public function callback_groups_demote_member( $group_id, $user_id ) {
		$group   = \groups_get_group(
			array(
				'group_id' => $group_id,
			)
		);
		$user    = new \WP_User( $user_id );
		$message = sprintf(
			/* translators: %1$s: a user's display name, %2$s: a user role, %3$s: a group name (e.g. "Jane Doe", "Member", "Favourites") */
			__( 'Demoted "%1$s" to "%2$s" in "%3$s"', 'stream' ),
			$user->display_name,
			_x( 'Member', 'buddypress', 'stream' ),
			$group->name
		);
		$this->group_action( $group_id, 'demoted', compact( 'user_id' ), $message );
	}

	/**
	 * Log member banning
	 *
	 * @action groups_ban_member
	 *
	 * @param int $group_id  Group ID.
	 * @param int $user_id   User ID of banned member.
	 */
	public function callback_groups_ban_member( $group_id, $user_id ) {
		$this->group_action( $group_id, 'banned', compact( 'user_id' ) );
	}

	/**
	 * Log member reinstatement
	 *
	 * @action groups_unban_member
	 *
	 * @param int $group_id  Group ID.
	 * @param int $user_id   User ID of reinstated member.
	 */
	public function callback_groups_unban_member( $group_id, $user_id ) {
		$this->group_action( $group_id, 'unbanned', compact( 'user_id' ) );
	}

	/**
	 * Log member removal.
	 *
	 * @action groups_remove_member
	 *
	 * @param int $group_id  Group ID.
	 * @param int $user_id   User ID of removed member.
	 */
	public function callback_groups_remove_member( $group_id, $user_id ) {
		$this->group_action( $group_id, 'removed', compact( 'user_id' ) );
	}

	/**
	 * Logs user profile field actions
	 *
	 * @param object $field    Field object.
	 * @param string $action   Action.
	 * @param array  $meta     Meta.
	 * @param string $message  Message.
	 */
	public function field_action( $field, $action, $meta = array(), $message = null ) {
		$replacements = array(
			$field->name,
		);

		if ( ! $message ) {
			if ( 'created' === $action ) {
				/* translators: %s: a user profile field (e.g. "Job Title") */
				$message = esc_html__( 'Created profile field "%s"', 'stream' );
			} elseif ( 'updated' === $action ) {
				/* translators: %s: a user profile field (e.g. "Job Title") */
				$message = esc_html__( 'Updated profile field "%s"', 'stream' );
			} elseif ( 'deleted' === $action ) {
				/* translators: %s: a user profile field (e.g. "Job Title") */
				$message = esc_html__( 'Deleted profile field "%s"', 'stream' );
			} else {
				return;
			}
		}

		$this->log(
			vsprintf(
				$message,
				$replacements
			),
			array_merge(
				array(
					'field_id'   => $field->id,
					'field_name' => $field->name,
					'group_id'   => $field->group_id,
				),
				$meta
			),
			$field->id,
			'profile_fields',
			$action
		);
	}

	/**
	 * Logs field writes
	 *
	 * @action xprofile_field_after_save
	 *
	 * @param object $field  Field object.
	 */
	public function callback_xprofile_field_after_save( $field ) {
		$action = isset( $field->id ) ? 'updated' : 'created';
		$this->field_action( $field, $action );
	}

	/**
	 * Logs field deletions
	 *
	 * @action xprofile_fields_deleted_field
	 *
	 * @param object $field  Field object.
	 */
	public function callback_xprofile_fields_deleted_field( $field ) {
		$this->field_action( $field, 'deleted' );
	}

	/**
	 * Logs user profile field group actions
	 *
	 * @param int    $group    Field group object.
	 * @param string $action   Action.
	 * @param array  $meta     Meta.
	 * @param string $message  Message.
	 */
	public function field_group_action( $group, $action, $meta = array(), $message = null ) {
		$replacements = array(
			$group->name,
		);

		if ( ! $message ) {
			if ( 'created' === $action ) {
				/* translators: %s: a user profile field group (e.g. "Appearance") */
				$message = esc_html__( 'Created profile field group "%s"', 'stream' );
			} elseif ( 'updated' === $action ) {
				/* translators: %s: a user profile field group (e.g. "Appearance") */
				$message = esc_html__( 'Updated profile field group "%s"', 'stream' );
			} elseif ( 'deleted' === $action ) {
				/* translators: %s: a user profile field group (e.g. "Appearance") */
				$message = esc_html__( 'Deleted profile field group "%s"', 'stream' );
			} else {
				return;
			}
		}

		$this->log(
			vsprintf(
				$message,
				$replacements
			),
			array_merge(
				array(
					'group_id'   => $group->id,
					'group_name' => $group->name,
				),
				$meta
			),
			$group->id,
			'profile_fields',
			$action
		);
	}

	/**
	 * Logs field group writes
	 *
	 * @action xprofile_group_after_save
	 *
	 * @param object $group  Field group.
	 */
	public function callback_xprofile_group_after_save( $group ) {
		global $wpdb;
		/**
		 * A bit hacky, due to inconsistency with BP action scheme,
		 * see callback_xprofile_field_after_save for correct behavior.
		 */
		$action = ( $group->id === $wpdb->insert_id ) ? 'created' : 'updated';
		$this->field_group_action( $group, $action );
	}

	/**
	 * Logs field group deletions
	 *
	 * @action xprofile_groups_deleted_group
	 *
	 * @param object $group  Field group object.
	 */
	public function callback_xprofile_groups_deleted_group( $group ) {
		$this->field_group_action( $group, 'deleted' );
	}

	/**
	 * Returns the directory pages
	 *
	 * @return array
	 */
	private function bp_get_directory_pages() {
		$bp              = \buddypress();
		$directory_pages = array();

		// Loop through loaded components and collect directories.
		if ( is_array( $bp->loaded_components ) ) {
			foreach ( $bp->loaded_components as $component_slug => $component_id ) {
				// Only components that need directories should be listed here.
				if ( isset( $bp->{$component_id} ) && ! empty( $bp->{$component_id}->has_directory ) ) {
					// component->name was introduced in BP 1.5, so we must provide a fallback.
					$directory_pages[ $component_id ] = ! empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );
				}
			}
		}

		return $directory_pages;
	}
}
