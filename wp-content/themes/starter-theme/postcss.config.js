const postcssImport = require("postcss-import");
const tailwindcss = require("tailwindcss");
const autoprefixer = require("autoprefixer");
const postcssPrefixSelector = require("postcss-prefix-selector");

module.exports = {
    plugins: [
        require("postcss-import")(),
        require("tailwindcss")(),
        require("autoprefixer")(),
    ],
};