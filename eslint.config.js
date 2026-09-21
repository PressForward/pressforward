const { includeIgnoreFile } = require( '@eslint/compat' );
const { defineConfig } = require( 'eslint/config' );
const { resolve } = require( 'node:path' );
const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = defineConfig(
    includeIgnoreFile( resolve( __dirname, '.gitignore' ) ),
    ...wordpress.configs.recommended,
    {
        rules: {
            camelcase: [ 'error', { ignoreDestructuring: true } ],
            'prettier/prettier': 'off',
        },
    }
);
