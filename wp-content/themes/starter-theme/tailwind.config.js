/** @type {import('tailwindcss').Config} */
const spacingConfig = require('./theme-spacing-config.json');
const config = require('./theme-config.json');
const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './resources/js/**/*.js',
        './**/*.php',
        './**/*.twig'
    ],
    theme: {
        screens: Object.fromEntries(
            Object.entries(defaultTheme.screens).filter(([key, value]) => key !== '2xl')
        ),
        extend: {
            spacing: spacingConfig.spacing
        },
    },
    plugins: [],
}

