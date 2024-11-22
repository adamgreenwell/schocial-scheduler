module.exports = {
	env: {
		browser: true,
		es2020: true,
	},
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	globals: {
		wp: true,
		window: true,
		document: true,
	},
	rules: {
		'react/react-in-jsx-scope': 'off',
		'react/prop-types': 'off',
		'prettier/prettier': 'warn',
		'no-console': 'off',
	},
};
