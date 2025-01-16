# Stream for WordPress

[![Lint and Test](https://github.com/xwp/stream/actions/workflows/ci.yml/badge.svg)](https://github.com/xwp/stream/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/xwp/stream/badge.svg?branch=develop)](https://coveralls.io/github/xwp/stream?branch=develop)

**Track WordPress user and system actions for debugging, logging and compliance purposes.**

- [Product Website](https://xwp.co/work/stream/)
- [Plugin on WordPress.org](https://wordpress.org/plugins/stream/)

## Documentation

View the [plugin description on WordPress.org](https://wordpress.org/plugins/stream/) for the list of features and screenshots.

### Connectors

A list of the connectors is in [connectors.md](connectors.md).

### Configuration

To customize who can manage Stream settings, you can define the `WP_STREAM_SETTINGS_CAPABILITY` constant in your `wp-config.php` file. By default, capability will be set to `manage_options`.

```php
define('WP_STREAM_SETTINGS_CAPABILITY', 'wp_stream_manage_settings');
```

## Known Issues

- We have temporarily disabled the data removal feature through plugin uninstallation, starting with version 3.9.3. We identified a few edge cases that did not behave as expected and we decided that a temporary removal is preferable at this time for such an impactful and irreversible operation. Our team is actively working on refining this feature to ensure it performs optimally and securely. We plan to reintroduce it in a future update with enhanced safeguards.

## Changelog

[View the changelog here.](changelog.md)

## Contribute

All suggestions and contributions are welcome! View the [contributor documentation](contributing.md) for how to report issues and setup the local development environment.

## Credits

The plugin is owned and maintained by [XWP](https://xwp.co). View [all contributors](https://github.com/xwp/stream/graphs/contributors).
