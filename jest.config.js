const defaultConfig = require('@wordpress/scripts/config/jest-unit.config');

module.exports = {
    ...defaultConfig,
    rootDir: 'src',
    setupFilesAfterEnv: ['<rootDir>/../tests/js/setup-tests.js'],
    testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
    coverageDirectory: '../coverage/js',
    collectCoverageFrom: [
        '**/*.{js,jsx}',
        '!**/node_modules/**',
        '!**/vendor/**'
    ],
};