/**
 * External dependencies
 */
const path = require( 'path' );
const CopyPlugin = require( 'copy-webpack-plugin' );

/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		admin: './src/js/admin.js',
		'admin-exclude': './src/js/admin-exclude.js',
		'alert-type-highlight': './src/js/alert-type-highlight.js',
		alerts: './src/js/alerts.js',
		'alerts-list': './src/js/alerts-list.js',
		global: './src/js/global.js',
		'live-updates': './src/js/live-updates.js',
		settings: './src/js/settings.js',
		'wpseo-admin': './src/js/wpseo-admin.js',
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyPlugin( {
			patterns: [
				{
					from: 'node_modules/select2/dist',
					// Convert filenames to lowercase.
					to( { context, absoluteFilename } ) {
						const baseName = path.basename( absoluteFilename ).toLowerCase();
						const relativePath = path.relative( context, path.dirname( absoluteFilename ) );

						return path.join( 'select2', relativePath, baseName );
					},
				},
				{
					from: 'node_modules/timeago/jquery.timeago.js',
					to: 'timeago/js/jquery.timeago.js',
				},
				{
					from: 'node_modules/timeago/locales',
					// Convert filenames to lowercase.
					to( { context, absoluteFilename } ) {
						const baseName = path.basename( absoluteFilename ).toLowerCase();
						const relativePath = path.relative( context, path.dirname( absoluteFilename ) );

						return path.join( 'timeago', 'js', 'locales', relativePath, baseName );
					},
				},
			],
		} ),
	],
};
