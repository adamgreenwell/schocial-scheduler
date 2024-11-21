const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        index: './src/index.js',
        settings: './src/settings.js'
    },
    output: {
        path: path.resolve(process.cwd(), 'build'),
        filename: '[name].js',
    },
    devServer: {
        devMiddleware: {
            writeToDisk: true,
        },
        allowedHosts: 'all',
        host: 'localhost',
        port: 8887,
        proxy: {
            '/wp-admin': 'http://localhost',
            '/wp-content': 'http://localhost',
        },
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
};