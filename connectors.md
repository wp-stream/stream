
## Connector: WP_Stream\Connector_ACF

### Actions

	- save_post
	- post_updated
	- added_post_meta
	- updated_post_meta
	- delete_post_meta
	- added_user_meta
	- updated_user_meta
	- delete_user_meta
	- added_option
	- updated_option
	- deleted_option
	- pre_post_update

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );

		/**
		 * Allow devs to disable logging values of rendered forms
		 *
		 * @return bool
		 */
		if ( apply_filters( 'wp_stream_acf_enable_value_logging', true ) ) {
			$this->actions[] = 'acf/update_value';
		}

		parent::register();
	}
```
</details>


## Connector: WP_Stream\Connector_BbPress

### Actions

	- bbp_toggle_topic_admin

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );
	}
```
</details>


## Connector: WP_Stream\Connector_Blogs

### Actions

	- wp_initialize_site
	- wp_delete_site
	- wpmu_activate_blog
	- wpmu_new_user
	- add_user_to_blog
	- remove_user_from_blog
	- make_spam_blog
	- make_ham_blog
	- mature_blog
	- unmature_blog
	- archive_blog
	- unarchive_blog
	- make_delete_blog
	- make_undelete_blog
	- update_blog_public

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_BuddyPress

### Actions

	- update_option
	- add_option
	- delete_option
	- update_site_option
	- add_site_option
	- delete_site_option
	- bp_before_activity_delete
	- bp_activity_deleted_activities
	- bp_activity_mark_as_spam
	- bp_activity_mark_as_ham
	- bp_activity_admin_edit_after
	- groups_create_group
	- groups_update_group
	- groups_before_delete_group
	- groups_details_updated
	- groups_settings_updated
	- groups_leave_group
	- groups_join_group
	- groups_promote_member
	- groups_demote_member
	- groups_ban_member
	- groups_unban_member
	- groups_remove_member
	- xprofile_field_after_save
	- xprofile_fields_deleted_field
	- xprofile_group_after_save
	- xprofile_groups_deleted_group

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
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
```
</details>


## Connector: WP_Stream\Connector_Comments

### Actions

	- comment_flood_trigger
	- wp_insert_comment
	- edit_comment
	- before_delete_post
	- deleted_post
	- delete_comment
	- trash_comment
	- untrash_comment
	- spam_comment
	- unspam_comment
	- transition_comment_status
	- comment_duplicate_trigger

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_EDD

### Actions

	- update_option
	- add_option
	- delete_option
	- update_site_option
	- add_site_option
	- delete_site_option
	- edd_pre_update_discount_status
	- edd_generate_pdf
	- edd_earnings_export
	- edd_payment_export
	- edd_email_export
	- edd_downloads_history_export
	- edd_import_settings
	- edd_export_settings
	- add_user_meta
	- update_user_meta
	- delete_user_meta

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );

		$this->options = array(
			'edd_settings' => null,
		);
	}
```
</details>


## Connector: WP_Stream\Connector_Editor

### Actions


### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();
		add_action( 'load-theme-editor.php', array( $this, 'get_edition_data' ) );
		add_action( 'load-plugin-editor.php', array( $this, 'get_edition_data' ) );
		add_filter( 'wp_redirect', array( $this, 'log_changes' ) );
	}
```
</details>


## Connector: WP_Stream\Connector_GravityForms

### Actions

	- gform_after_save_form
	- gform_pre_confirmation_save
	- gform_pre_notification_save
	- gform_pre_notification_deleted
	- gform_pre_confirmation_deleted
	- gform_before_delete_form
	- gform_post_form_trashed
	- gform_post_form_restored
	- gform_post_form_activated
	- gform_post_form_deactivated
	- gform_post_form_duplicated
	- gform_post_form_views_deleted
	- gform_post_export_entries
	- gform_forms_post_import
	- gform_delete_lead
	- gform_post_note_added
	- gform_pre_note_deleted
	- gform_update_status
	- gform_update_is_read
	- gform_update_is_starred
	- update_option
	- add_option
	- delete_option
	- update_site_option
	- add_site_option
	- delete_site_option

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		$this->options = array(
			'rg_gforms_disable_css'         => array(
				'label' => esc_html_x( 'Output CSS', 'gravityforms', 'stream' ),
			),
			'rg_gforms_enable_html5'        => array(
				'label' => esc_html_x( 'Output HTML5', 'gravityforms', 'stream' ),
			),
			'gform_enable_noconflict'       => array(
				'label' => esc_html_x( 'No-Conflict Mode', 'gravityforms', 'stream' ),
			),
			'rg_gforms_currency'            => array(
				'label' => esc_html_x( 'Currency', 'gravityforms', 'stream' ),
			),
			'rg_gforms_captcha_public_key'  => array(
				'label' => esc_html_x( 'reCAPTCHA Public Key', 'gravityforms', 'stream' ),
			),
			'rg_gforms_captcha_private_key' => array(
				'label' => esc_html_x( 'reCAPTCHA Private Key', 'gravityforms', 'stream' ),
			),
			'rg_gforms_key'                 => null,
		);
	}
```
</details>


## Connector: WP_Stream\Connector_Installer

### Actions

	- upgrader_process_complete
	- activate_plugin
	- deactivate_plugin
	- switch_theme
	- delete_site_transient_update_themes
	- pre_option_uninstall_plugins
	- pre_set_site_transient_update_plugins
	- _core_updated_successfully

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Jetpack

### Actions

	- jetpack_log_entry
	- sharing_get_services_state
	- update_option
	- add_option
	- delete_option
	- jetpack_module_configuration_load_monitor
	- wp_ajax_jetpack_post_by_email_enable
	- wp_ajax_jetpack_post_by_email_regenerate
	- wp_ajax_jetpack_post_by_email_disable

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );

		$this->options = array(
			'jetpack_options'                   => null,
			// Sharing module.
			'hide_gplus'                        => null,
			'gplus_authors'                     => null,
			'sharing-options'                   => array(
				'label'   => esc_html__( 'Sharing options', 'stream' ),
				'context' => 'sharedaddy',
			),
			'sharedaddy_disable_resources'      => null,
			'jetpack-twitter-cards-site-tag'    => array(
				'label'   => esc_html__( 'Twitter site tag', 'stream' ),
				'context' => 'sharedaddy',
			),
			// Stats module.
			'stats_options'                     => array(
				'label'   => esc_html__( 'WordPress.com Stats', 'stream' ),
				'context' => 'stats',
			),
			// Comments.
			'jetpack_comment_form_color_scheme' => array(
				'label'   => esc_html__( 'Color Scheme', 'stream' ),
				'context' => 'jetpack-comments',
			),
			// Likes.
			'disabled_likes'                    => array(
				'label'   => esc_html__( 'WP.com Site-wide Likes', 'stream' ),
				'context' => 'likes',
			),
			// Mobile.
			'wp_mobile_excerpt'                 => array(
				'label'   => esc_html__( 'Excerpts appearance', 'stream' ),
				'context' => 'minileven',
			),
			'wp_mobile_app_promos'              => array(
				'label'   => esc_html__( 'App promos', 'stream' ),
				'context' => 'minileven',
			),
		);

		$this->options_override = array(
			// Carousel Module.
			'carousel_background_color'        => array(
				'label'   => esc_html__( 'Background color', 'stream' ),
				'context' => 'carousel',
			),
			'carousel_display_exif'            => array(
				'label'   => esc_html__( 'Metadata', 'stream' ),
				'context' => 'carousel',
			),
			// Subscriptions.
			'stb_enabled'                      => array(
				'label'   => esc_html__( 'Follow blog comment form button', 'stream' ),
				'context' => 'subscriptions',
			),
			'stc_enabled'                      => array(
				'label'   => esc_html__( 'Follow comments form button', 'stream' ),
				'context' => 'subscriptions',
			),
			// Jetpack comments.
			'highlander_comment_form_prompt'   => array(
				'label'   => esc_html__( 'Greeting Text', 'stream' ),
				'context' => 'jetpack-comments',
			),
			// Infinite Scroll.
			'infinite_scroll_google_analytics' => array(
				'label'   => esc_html__( 'Infinite Scroll Google Analytics', 'stream' ),
				'context' => 'infinite-scroll',
			),
			// Protect.
			'jetpack_protect_blocked_attempts' => array(
				'label'   => esc_html__( 'Blocked Attempts', 'stream' ),
				'context' => 'protect',
			),
			// SSO.
			'jetpack_sso_require_two_step'     => array(
				'label'   => esc_html__( 'Require Two-Step Authentication', 'stream' ),
				'context' => 'sso',
			),
			'jetpack_sso_match_by_email'       => array(
				'label'   => esc_html__( 'Match by Email', 'stream' ),
				'context' => 'sso',
			),
			// Related posts.
			'jetpack_relatedposts'             => array(
				'show_headline'   => array(
					'label'   => esc_html__( 'Show Related Posts Headline', 'stream' ),
					'context' => 'related-posts',
				),
				'show_thumbnails' => array(
					'label'   => esc_html__( 'Show Related Posts Thumbnails', 'stream' ),
					'context' => 'related-posts',
				),
			),
			// Site verification.
			'verification_services_codes'      => array(
				'google'    => array(
					'label'   => esc_html__( 'Google Webmaster Tools Token', 'stream' ),
					'context' => 'verification-tools',
				),
				'bing'      => array(
					'label'   => esc_html__( 'Bing Webmaster Center Token', 'stream' ),
					'context' => 'verification-tools',
				),
				'pinterest' => array(
					'label'   => esc_html__( 'Pinterest Site Verification Token', 'stream' ),
					'context' => 'verification-tools',
				),
			),
			// Tiled galleries.
			'tiled_galleries'                  => array(
				'label'   => esc_html__( 'Tiled Galleries', 'stream' ),
				'context' => 'tiled-gallery',
			),
			// Monitor.
			'monitor_receive_notification'     => array(
				'label'   => esc_html__( 'Monitor notifications', 'stream' ),
				'context' => 'monitor',
			),
		);
	}
```
</details>


## Connector: WP_Stream\Connector_Media

### Actions

	- add_attachment
	- edit_attachment
	- delete_attachment
	- wp_save_image_editor_file
	- wp_save_image_file

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Menus

### Actions

	- wp_create_nav_menu
	- wp_update_nav_menu
	- delete_nav_menu

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_action( 'update_option_theme_mods_' . get_option( 'stylesheet' ), array( $this, 'callback_update_option_theme_mods' ), 10, 2 );
	}
```
</details>


## Connector: WP_Stream\Connector_Mercator

### Actions

	- mercator.mapping.updated
	- mercator.mapping.deleted
	- mercator.mapping.created
	- mercator.mapping.made_primary

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Posts

### Actions

	- transition_post_status
	- deleted_post

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Settings

### Actions

	- allowed_options
	- update_option
	- update_site_option
	- update_option_permalink_structure
	- update_option_category_base
	- update_option_tag_base

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		$this->labels = array(
			// General.
			'blogname'                      => esc_html__( 'Site Title', 'stream' ),
			'blogdescription'               => esc_html__( 'Tagline', 'stream' ),
			'gmt_offset'                    => esc_html__( 'Timezone', 'stream' ),
			'admin_email'                   => esc_html__( 'E-mail Address', 'stream' ),
			'new_admin_email'               => esc_html__( 'E-mail Address', 'stream' ),
			'siteurl'                       => esc_html__( 'WordPress Address (URL)', 'stream' ),
			'home'                          => esc_html__( 'Site Address (URL)', 'stream' ),
			'users_can_register'            => esc_html__( 'Membership', 'stream' ),
			'default_role'                  => esc_html__( 'New User Default Role', 'stream' ),
			'timezone_string'               => esc_html__( 'Timezone', 'stream' ),
			'date_format'                   => esc_html__( 'Date Format', 'stream' ),
			'time_format'                   => esc_html__( 'Time Format', 'stream' ),
			'start_of_week'                 => esc_html__( 'Week Starts On', 'stream' ),
			// Writing.
			'use_smilies'                   => esc_html__( 'Formatting', 'stream' ),
			'use_balanceTags'               => esc_html__( 'Formatting', 'stream' ),
			'default_category'              => esc_html__( 'Default Post Category', 'stream' ),
			'default_post_format'           => esc_html__( 'Default Post Format', 'stream' ),
			'mailserver_url'                => esc_html__( 'Mail Server', 'stream' ),
			'mailserver_login'              => esc_html__( 'Login Name', 'stream' ),
			'mailserver_pass'               => esc_html__( 'Password', 'stream' ),
			'default_email_category'        => esc_html__( 'Default Mail Category', 'stream' ),
			'default_link_category'         => esc_html__( 'Default Link Category', 'stream' ),
			'ping_sites'                    => esc_html__( 'Update Services', 'stream' ),
			// Reading.
			'show_on_front'                 => esc_html__( 'Front page displays', 'stream' ),
			'page_on_front'                 => esc_html__( 'Front page displays', 'stream' ),
			'page_for_posts'                => esc_html__( 'Front page displays', 'stream' ),
			'posts_per_page'                => esc_html__( 'Blog pages show at most', 'stream' ), // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page, because this is not a query.
			'posts_per_rss'                 => esc_html__( 'Syndication feeds show the most recent', 'stream' ),
			'rss_use_excerpt'               => esc_html__( 'For each article in a feed, show', 'stream' ),
			'blog_public'                   => esc_html__( 'Search Engine Visibility', 'stream' ),
			// Discussion.
			'default_pingback_flag'         => esc_html__( 'Default article settings', 'stream' ),
			'default_ping_status'           => esc_html__( 'Default article settings', 'stream' ),
			'default_comment_status'        => esc_html__( 'Default article settings', 'stream' ),
			'require_name_email'            => esc_html__( 'Other comment settings', 'stream' ),
			'comment_registration'          => esc_html__( 'Other comment settings', 'stream' ),
			'close_comments_for_old_posts'  => esc_html__( 'Other comment settings', 'stream' ),
			'close_comments_days_old'       => esc_html__( 'Other comment settings', 'stream' ),
			'thread_comments'               => esc_html__( 'Other comment settings', 'stream' ),
			'thread_comments_depth'         => esc_html__( 'Other comment settings', 'stream' ),
			'page_comments'                 => esc_html__( 'Other comment settings', 'stream' ),
			'comments_per_page'             => esc_html__( 'Other comment settings', 'stream' ),
			'default_comments_page'         => esc_html__( 'Other comment settings', 'stream' ),
			'comment_order'                 => esc_html__( 'Other comment settings', 'stream' ),
			'comments_notify'               => esc_html__( 'E-mail me whenever', 'stream' ),
			'moderation_notify'             => esc_html__( 'E-mail me whenever', 'stream' ),
			'comment_moderation'            => esc_html__( 'Before a comment appears', 'stream' ),
			'comment_whitelist'             => esc_html__( 'Before a comment appears', 'stream' ),
			'comment_max_links'             => esc_html__( 'Comment Moderation', 'stream' ),
			'moderation_keys'               => esc_html__( 'Comment Moderation', 'stream' ),
			'blacklist_keys'                => esc_html__( 'Comment Blacklist', 'stream' ),
			'show_avatars'                  => esc_html__( 'Show Avatars', 'stream' ),
			'avatar_rating'                 => esc_html__( 'Maximum Rating', 'stream' ),
			'avatar_default'                => esc_html__( 'Default Avatar', 'stream' ),
			// Media.
			'thumbnail_size_w'              => esc_html__( 'Thumbnail size', 'stream' ),
			'thumbnail_size_h'              => esc_html__( 'Thumbnail size', 'stream' ),
			'thumbnail_crop'                => esc_html__( 'Thumbnail size', 'stream' ),
			'medium_size_w'                 => esc_html__( 'Medium size', 'stream' ),
			'medium_size_h'                 => esc_html__( 'Medium size', 'stream' ),
			'large_size_w'                  => esc_html__( 'Large size', 'stream' ),
			'large_size_h'                  => esc_html__( 'Large size', 'stream' ),
			'uploads_use_yearmonth_folders' => esc_html__( 'Uploading Files', 'stream' ),
			// Permalinks.
			'permalink_structure'           => esc_html__( 'Permalink Settings', 'stream' ),
			'category_base'                 => esc_html__( 'Category base', 'stream' ),
			'tag_base'                      => esc_html__( 'Tag base', 'stream' ),
			// Network.
			'registrationnotification'      => esc_html__( 'Registration notification', 'stream' ),
			'registration'                  => esc_html__( 'Allow new registrations', 'stream' ),
			'add_new_users'                 => esc_html__( 'Add New Users', 'stream' ),
			'menu_items'                    => esc_html__( 'Enable administration menus', 'stream' ),
			'upload_space_check_disabled'   => esc_html__( 'Site upload space check', 'stream' ),
			'blog_upload_space'             => esc_html__( 'Site upload space', 'stream' ),
			'upload_filetypes'              => esc_html__( 'Upload file types', 'stream' ),
			'site_name'                     => esc_html__( 'Network Title', 'stream' ),
			'first_post'                    => esc_html__( 'First Post', 'stream' ),
			'first_page'                    => esc_html__( 'First Page', 'stream' ),
			'first_comment'                 => esc_html__( 'First Comment', 'stream' ),
			'first_comment_url'             => esc_html__( 'First Comment URL', 'stream' ),
			'first_comment_author'          => esc_html__( 'First Comment Author', 'stream' ),
			'welcome_email'                 => esc_html__( 'Welcome Email', 'stream' ),
			'welcome_user_email'            => esc_html__( 'Welcome User Email', 'stream' ),
			'fileupload_maxk'               => esc_html__( 'Max upload file size', 'stream' ),
			'global_terms_enabled'          => esc_html__( 'Terms Enabled', 'stream' ),
			'illegal_names'                 => esc_html__( 'Banned Names', 'stream' ),
			'limited_email_domains'         => esc_html__( 'Limited Email Registrations', 'stream' ),
			'banned_email_domains'          => esc_html__( 'Banned Email Domains', 'stream' ),
			'WPLANG'                        => esc_html__( 'Network Language', 'stream' ),
			'blog_count'                    => esc_html__( 'Blog Count', 'stream' ),
			'user_count'                    => esc_html__( 'User Count', 'stream' ),
			// Other.
			'wp_stream_db'                  => esc_html__( 'Stream Database Version', 'stream' ),
		);

		// These option labels are special and need to change based on multisite context.
		if ( is_network_admin() ) {
			$this->labels['admin_email']     = esc_html__( 'Network Admin Email', 'stream' );
			$this->labels['new_admin_email'] = esc_html__( 'Network Admin Email', 'stream' );
		}

		add_action( 'admin_head', array( $this, 'highlight_field' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_jquery_color' ) );
		add_action( sprintf( 'update_option_theme_mods_%s', get_option( 'stylesheet' ) ), array( $this, 'log_theme_modification' ), 10, 2 );
	}
```
</details>


## Connector: WP_Stream\Connector_Taxonomies

### Actions

	- created_term
	- delete_term
	- edit_term
	- edited_term

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Two_Factor

### Actions

	- update_user_meta
	- updated_user_meta
	- added_user_meta
	- two_factor_user_authenticated
	- wp_login_failed

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );
	}
```
</details>


## Connector: WP_Stream\Connector_User_Switching

### Actions

	- wp_stream_after_connectors_registration
	- switch_to_user
	- switch_back_user
	- switch_off_user

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );
	}
```
</details>


## Connector: WP_Stream\Connector_Users

### Actions

	- user_register
	- profile_update
	- password_reset
	- retrieve_password
	- set_logged_in_cookie
	- clear_auth_cookie
	- delete_user
	- deleted_user
	- set_user_role
	- set_current_user

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Widgets

### Actions

	- update_option_sidebars_widgets
	- updated_option

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		foreach ( $this->actions as $action ) {
			add_action( $action, array( $this, 'callback' ), 10, 99 );
		}

		add_filter( 'wp_stream_action_links_' . $this->name, array( $this, 'action_links' ), 10, 2 );

		$this->is_registered = true;
	}
```
</details>


## Connector: WP_Stream\Connector_Woocommerce

### Actions

	- wp_stream_record_array
	- updated_option
	- transition_post_status
	- deleted_post
	- woocommerce_order_status_changed
	- woocommerce_attribute_added
	- woocommerce_attribute_updated
	- woocommerce_attribute_deleted
	- woocommerce_tax_rate_added
	- woocommerce_tax_rate_updated
	- woocommerce_tax_rate_deleted

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		parent::register();

		add_filter( 'wp_stream_posts_exclude_post_types', array( $this, 'exclude_order_post_types' ) );
		add_action( 'wp_stream_comments_exclude_comment_types', array( $this, 'exclude_order_comment_types' ) );

		$this->get_woocommerce_settings_fields();
	}
```
</details>


## Connector: WP_Stream\Connector_WordPress_SEO

### Actions

	- wpseo_handle_import
	- wpseo_import
	- seo_page_wpseo_files
	- added_post_meta
	- updated_post_meta
	- deleted_post_meta

### Class register()

<details>
<summary>This is the register method for the Connector. Occasionally there are additional actions in here.</summary>

```php
	public function register() {
		if ( is_network_admin() && ! is_plugin_active_for_network( 'wordpress-seo/wordpress-seo-main.php' ) ) {
			return;
		}
		parent::register();

		foreach ( \WPSEO_Options::$options as $class ) {
			/**
			 * WPSEO Options object.
			 *
			 * @var WPSEO_Options $class
			 */
			$this->option_groups[ $class::get_instance()->group_name ] = array(
				'class' => $class,
				'name'  => $class::get_instance()->option_name,
			);
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );
	}
```
</details>

