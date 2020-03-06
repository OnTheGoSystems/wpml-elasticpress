const path                  = require('path');
const MiniCssExtractPlugin  = require('mini-css-extract-plugin');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const {CleanWebpackPlugin}  = require('clean-webpack-plugin');

/**
 * @typedef LibrariesHash
 * @property {object} [entryChunkName: string] A unique entry ID
 * @property {array} entryChunkName.entry - A list of entries to bundle with the library
 * @property {string} [entryChunkName.filename] - The target file name (if omitted, the entry entryChunkName will be used
 * @property {string} [entryChunkName.var] - The name of the global variable to which the library will be assigned
 *
 * @type LibrariesHash
 */
const libraries = {
	'testApp': {
		entry: ['./src/js/my-project-library/app.js'],
	},
};

const getEntries = () => {
	const entries = {};
	Object.keys(libraries).map(key => entries[key] = libraries[key].entry);

	return entries;
};

const getEntryFileName = (chunk) => {
	if (libraries.hasOwnProperty(chunk.id) && libraries[chunk.id].hasOwnProperty('filename') && libraries[chunk.id].filename) {
		return libraries[chunk.id].filename;
	}
	return path.join(chunk.name);
};

module.exports = env => {
	const isProduction = env === 'production';

	console.log('getEntries()', getEntries());
	// console.log('getVars()',getVars());

	return {
		entry:   getEntries,
		output:  {
			path:          path.join(__dirname, 'dist'),
			filename:      chunkData => path.join('js', getEntryFileName(chunkData.chunk) + '.js?ver=' + chunkData.chunk.hash),
			chunkFilename: '[id].[name].js?ver=[chunkhash]',
			library:       ['OTGSUI', '[name]'],
			libraryTarget: 'var',
		},
		module:  {
			rules: [
				{
					test: /\.s?css$/,
					use:  [MiniCssExtractPlugin.loader, 'css-loader'],
				},
			],
		},
		plugins: [
			new CleanWebpackPlugin(),
			new MiniCssExtractPlugin({
				filename: path.join('css', '[name].css?ver=[chunkhash]'),
			}),
			new WebpackAssetsManifest({
				output:      path.join(__dirname, 'dist', 'assets.json'),
				entrypoints: true,
			}),
		],
		devtool: isProduction ? '' : 'inline-source-map',
	};
};
