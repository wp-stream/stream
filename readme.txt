=== Stream ===
Contributors: xwp
Tags: wp stream, stream, activity, logs, track
Requires at least: 4.6
Tested up to: 6.6
Stable tag: 4.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

With Stream, you’re never left in the dark about changes to your WordPress site.

== Description ==

With real-time notifications and third-party integrations, Stream can proactively alert you when something goes wrong with your WordPress site.

Designed for debugging and compliance purposes, Stream is useful for keeping tabs on your WordPress users: From activating plugins to deleting posts, to login attempts and new user creation, you can see what’s changed, who changed it and when.

The plugin records WordPress user and system action to the Stream logs.  Every logged-in user action is displayed in an activity stream and organized for easy filtering by User, Role, Context, Action or IP address. Admins can highlight entries in the Stream log—such as suspicious user activity—to investigate what’s happening in real time. Stream also allows you to configure email alerts and webhooks for integrations like Slack and IFTTT to notify you and your team when something has gone wrong.

For advanced users, Stream also supports a network view of all activity records on your Multisite, the ability to set exclude rules to ignore certain kinds of user activity, and a WP‑CLI command for querying records.

With Stream’s powerful logging, you’ll have the valuable information you need to responsibly manage your WordPress sites.


= Built-In Tracking Integrations For Popular Plugins: =

 * Advanced Custom Fields
 * bbPress
 * BuddyPress
 * Easy Digital Downloads
 * Gravity Forms
 * Jetpack
 * User Switching
 * WooCommerce
 * Yoast SEO

= Built-In Tracking For Core Actions: =

 * Posts
 * Pages
 * Custom Post Types
 * Users
 * Themes
 * Plugins
 * Tags
 * Categories
 * Custom Taxonomies
 * Settings
 * Custom Backgrounds
 * Custom Headers
 * Menus
 * Media Library
 * Widgets
 * Comments
 * Theme Editor
 * WordPress Core Updates

= Other Noteworthy Features: =

 * Multisite view of all activity records on a network
 * Limit who can view user activity records by user role
 * Set exclude rules to ignore certain kinds of user activity
 * Live updates of user activity records in the Stream
 * Export your Activity Stream as a CSV or JSON file
 * WP-CLI command for querying records


== Configuration ==

Most of the plugin configuration is available under the "Stream" → "Settings" page in the WordPress dashboard.


= Request IP Address =

The plugin expects the `$_SERVER['REMOTE_ADDR']` variable to contain the verified IP address of the current request. On hosting environments with PHP processing behind reverse proxies or CDNs the actual client IP is passed to PHP through request HTTP headers such as `X-Forwarded-For` and `True-Client-IP` which can't be trusted without an additional layer of validation. Update your server configuration to set the `$_SERVER['REMOTE_ADDR']` variable to the verified client IP address.

As a workaround, you can use the `wp_stream_client_ip_address` filter to adapt the IP address:

`add_filter(
	'wp_stream_client_ip_address',
	function( $client_ip ) {
		// Trust the first IP in the X-Forwarded-For header.
		// ⚠️ Note: This is inherently insecure and can easily be spoofed!
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded_ips = explode( ',' $_SERVER['HTTP_X_FORWARDED_FOR'] );

			if ( filter_var( $forwarded_ips[0], FILTER_VALIDATE_IP ) ) {
				return $forwarded_ips[0];
			}
		}

		return $client_ip;
	}
);`

⚠️ **WARNING:** The above is an insecure workaround that you should only use when you fully understand what this implies. Relying on any variable with the `HTTP_*` prefix is prone to spoofing and cannot be trusted!


== Known Issues ==

 * We have temporarily disabled the data removal feature through plugin uninstallation, starting with version 3.9.3. We identified a few edge cases that did not behave as expected and we decided that a temporary removal is preferable at this time for such an impactful and irreversible operation. Our team is actively working on refining this feature to ensure it performs optimally and securely. We plan to reintroduce it in a future update with enhanced safeguards.


== Contribute ==

There are several ways you can get involved to help make Stream better:

1. **Report Bugs:** If you find a bug, error or other problem, please report it! You can do this by [creating a new topic](https://wordpress.org/support/plugin/stream) in the plugin forum. Once a developer can verify the bug by reproducing it, they will create an official bug report in GitHub where the bug will be worked on.

2. **Translate into Your Language:** Use the official plugin translation tool to [translate Stream into your language](https://translate.wordpress.org/projects/wp-plugins/stream/).

3. **Suggest New Features:** Have an awesome idea? Please share it! Simply [create a new topic](https://wordpress.org/support/plugin/stream) in the plugin forum to express your thoughts on why the feature should be included and get a discussion going around your idea.

4. **Issue Pull Requests:** If you're a developer, the easiest way to get involved is to help out on [issues already reported](https://github.com/x-team/wp-stream/issues) in GitHub. Be sure to check out the [contributing guide](https://github.com/x-team/wp-stream/blob/master/contributing.md) for developers.

Thank you for wanting to make Stream better for everyone!

[View contributors here.](https://github.com/xwp/stream/graphs/contributors)


== Screenshots ==

1. Every logged-in user action is displayed in an activity stream and organized for easy filtering and searching.
2. Enable live updates in Screen Options to watch your site activity appear in near real-time.
3. Create rules for excluding certain kinds of records from appearing in Stream.


== Upgrade Notice ==

= 4.0.0 =

Use only `$_SERVER['REMOTE_ADDR']` as the client IP address for event logs without additional support for `X-Forwarded-For` HTTP request header value which could be spoofed. See the changelog for additional details.


== Changelog ==

= 4.1.0 - January 20, 2025 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md##410---january-15-2025](https://github.com/xwp/stream/blob/develop/changelog.md##410---january-15-2025)

= 4.0.2 - August 22, 2024 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#402---august-22-2024](https://github.com/xwp/stream/blob/develop/changelog.md#402---august-22-2024)

= 4.0.1 - July 30, 2024 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#401---july-30-2024](https://github.com/xwp/stream/blob/develop/changelog.md#401---july-30-2024)

= 4.0.0 - January 9, 2024 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#400---january-9-2024](https://github.com/xwp/stream/blob/develop/changelog.md#400---january-9-2024)

= 3.10.0 - October 9, 2023 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#3100---october-9-2023](https://github.com/xwp/stream/blob/develop/changelog.md#3100---october-9-2023)

= 3.9.3 - April 25, 2023 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#393---april-25-2023](https://github.com/xwp/stream/blob/develop/changelog.md#393---april-25-2023)

= 3.9.2 - January 10, 2023 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#392---january-10-2023](https://github.com/xwp/stream/blob/develop/changelog.md#392---january-10-2023)

= 3.9.1 - August 23, 2022 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#391---august-23-2022](https://github.com/xwp/stream/blob/develop/changelog.md#391---august-23-2022)

= 3.9.0 - March 8, 2022 =

See: [https://github.com/xwp/stream/blob/develop/changelog.md#390---march-8-2022](https://github.com/xwp/stream/blob/develop/changelog.md#390---march-8-2022)

[See the full changelog here.](https://github.com/xwp/stream/blob/master/changelog.md)
