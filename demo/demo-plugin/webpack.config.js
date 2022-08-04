const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    'axios-only': './src/js/axios-only.js',
    'axios-w-helper': './src/js/axios-w-helper.js',
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].js',
  },
};
