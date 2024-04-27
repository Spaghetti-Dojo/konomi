/**
 * WordPress dependencies
 */
const baseConfiguration = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
/**
 * External dependencies
 */
const path = require( 'path' );

module.exports = {
	...baseConfiguration,
	entry: {
	},
	plugins: [
		...baseConfiguration.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			outputFormat: 'php',
		} ),
	],
	resolve: {
		...baseConfiguration.resolve,
		alias: {
			...baseConfiguration.resolve.alias,
			'@types': path.resolve( __dirname, './@types/index.d.ts' ),
		},
	},
	output: {
		...baseConfiguration.output,
		filename: '[name].js',
		library: {
			name: 'wp-konomi',
			type: 'window',
		},
	},
};
