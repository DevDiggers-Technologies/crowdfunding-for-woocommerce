/** @format */
/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const IS_PRODUCTION = NODE_ENV === 'production';
const SOURCE_EXTENSIONS = [ '.json', '.js', '.jsx' ];
const JS_ENTRY_POINTS = {
	"admin": "./src/admin/index.js",
	"front": "./src/front/index.js"
};
const createCssLoaders = () => ( [
	MiniCssExtractPlugin.loader,
	{
		loader : 'css-loader',
		options: {
			url      : false,
			sourceMap: false,
		},
	},
	{
		loader : 'less-loader',
		options: {
			sourceMap        : false,
			javascriptEnabled: true,
		},
	},
] );

const createBabelRules = () => ( [
	{
		test   : /\.jsx?$/,
		loader : 'babel-loader',
		exclude: /node_modules/,
	},
] );

const createPlugins = () => ( [
	new MiniCssExtractPlugin( {
		filename: './assets/css/[name].css',
	} ),
] );

const webpackConfig = {
	mode : NODE_ENV,
	entry: JS_ENTRY_POINTS,
	output: {
		filename     : './assets/js/[name].js',
		path         : __dirname,
		libraryTarget: 'this',
		chunkFilename: './assets/js/chunks/[name].js',
	},
	module   : {
		rules: [
			...createBabelRules(),
			{
				test: /\.(less|css)$/,
				use : createCssLoaders(),
			},
		],
	},
	resolve: {
		extensions: SOURCE_EXTENSIONS,
		modules   : [
			path.join( __dirname, 'src' ),
			'node_modules',
		],
	},
	plugins: createPlugins(),
	optimization: {
		minimize : IS_PRODUCTION,
		minimizer: [ new TerserPlugin(), new CssMinimizerPlugin() ],
		splitChunks: {
			name: false,
		},
	},
};

if ( ! IS_PRODUCTION ) {
	webpackConfig.devtool = 'inline-source-map';
}

module.exports = webpackConfig;
