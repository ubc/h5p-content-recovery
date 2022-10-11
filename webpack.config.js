const path = require( 'path' );
const UglifyJsPlugin = require( 'uglifyjs-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const OptimizeCSSAssetsPlugin = require( 'optimize-css-assets-webpack-plugin' );

module.exports = function ( env, argv ) {
	const isDev = 'development' === argv.mode ? true : false;
	return {
		devtool: isDev ? 'source-map' : false,
		entry: {
			'content-edit-view': './src/content-edit-view.js',
			'listing-view': './src/listing-view.js',
		},
		output: {
			path: path.join( __dirname, 'dist' ),
			filename: '[name].js',
		},
		watchOptions: {
			ignored: [ 'node_modules', path.join( __dirname, 'dist' ) ],
		},
		module: {
			rules: [
				{
					test: /\.scss$/,
					use: [
						{
							loader: MiniCssExtractPlugin.loader,
						},
						{
							loader: 'css-loader',
							options: {
								sourceMap: isDev,
							},
						},
						{
							loader: 'postcss-loader', // Run post css actions
							options: {
								plugins() {
									// post css plugins, can be exported to postcss.config.js
									return [
										require( 'precss' ),
										require( 'autoprefixer' ),
									];
								},
								sourceMap: isDev,
							},
						},
						{
							loader: 'sass-loader',
							options: {
								sourceMap: isDev,
							},
						},
					],
				},
				{
					test: /\.css$/i,
					use: [ 'style-loader', 'css-loader' ],
				},
				{
					test: /\.(js|jsx)$/, // Identifies which file or files should be transformed.
					use: {
						loader: 'babel-loader',
					}, // Babel loader to transpile modern JavaScript.
					exclude: /(node_modules|bower_components)/, // JavaScript files to be ignored.
				},
				{
					test: /\.(png|jpg|gif|svg)$/i,
					use: [
						{
							loader: 'url-loader',
							options: {
								limit: 8192,
							},
						},
					],
				},
			],
		},
		plugins: [
			new MiniCssExtractPlugin( {
				// Options similar to the same options in webpackOptions.output
				// both options are optional
				filename: '../dist/[name].css',
			} ),
		],
		optimization: isDev
			? {
					minimizer: [
						new UglifyJsPlugin( {
							cache: true,
							parallel: true,
							sourceMap: isDev, // set to true if you want JS source maps
						} ),
						new OptimizeCSSAssetsPlugin( {} ),
					],
			  }
			: {},
	};
};
