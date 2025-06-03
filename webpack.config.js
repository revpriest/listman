const path = require('path')
const config = require('@nextcloud/webpack-vue-config')
const VueLoaderPlugin = require('vue-loader/lib/plugin') 


module.exports = {
  // You need to list out every file you want to bundle in `entry`
  entry: {
    main: `${process.cwd()}/src/main.js`  // Primary entry point
  },
  output: {
    path: path.resolve(__dirname, 'js'),
    filename: '[name].js',
    publicPath: '/apps/listman/js/' 
  },
  target: "web",

  module: {
    rules: [
      // Handle external CSS (like splitpanes) without Sass processing
      {
        test: /node_modules\/splitplanes\/dist\/.*\.css$/,
        use: [
          'vue-style-loader',
          'css-loader'
        ]
      },
      {
        test: /\.scss$/,
        use: [
          'vue-style-loader',
          'css-loader',
          'sass-loader'
        ]
      },
      {
        test: /\.vue$/,
        loader: 'vue-loader'
      },
      {
        test: /\.css$/,
        use: ['vue-style-loader', 'css-loader']
      },
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: 'babel-loader'
      }
    ]
  },
  plugins: [
    new VueLoaderPlugin() // Required for .vue file processing
  ],
  resolve: {
    extensions: ['.js', '.vue', '.json'] // Auto-resolve these extensions
  },
};
