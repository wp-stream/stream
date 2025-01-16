module.exports = {
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended-with-formatting',
	],
	env: {
		browser: true,
		es6: true,
	},
	rules: {
		'@wordpress/no-global-event-listener': 'off',
		'jsdoc/check-indentation': 'error',
		'@wordpress/dependency-group': 'error',
		'import/order': [ 'error', { groups: [ 'builtin', [ 'external', 'unknown' ], 'internal', 'parent', 'sibling', 'index' ] } ],
		'jsdoc/require-param': 'off',
		'jsdoc/require-param-type': 'off',
		'jsdoc/check-param-names': 'off',
	},
};
