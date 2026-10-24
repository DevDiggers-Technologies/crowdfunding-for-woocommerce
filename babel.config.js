module.exports = function( api ) {
	api.cache( true );

	return {
		presets: [ '@wordpress/babel-preset-default' ],
		plugins: [
			'@babel/plugin-transform-runtime',
			'@babel/plugin-transform-async-to-generator',
			'transform-class-properties',
		],
		sourceType: 'unambiguous',
		env: {
			production: {
				plugins: [
					[
						'@wordpress/babel-plugin-makepot',
						{
							output: 'i18n/devdiggers-crowdfunding-for-woocommerce.po',
						},
					],
				],
			},
		},
	};
};
