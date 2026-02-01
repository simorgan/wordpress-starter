// cssPrefixer.config.js
import postcss from "postcss";
import prefixer from "postcss-prefix-selector";

const PREFIX = ".editor-styles-wrapper .acf-block-preview";
const EXCLUDE = ["html", "body", ".wp-block.acf-block-preview"];

export default function prefixEditorCss() {
    return {
        name: "prefix-editor-css",

        // --- Build mode ---
        generateBundle(_, bundle) {
            Object.entries(bundle).forEach(([fileName, asset]) => {
                if (
                    asset.type === "asset" &&
                    fileName.endsWith(".css") &&
                    fileName.includes("editor")
                ) {
                    const css = asset.source.toString();
                    if (css.includes(PREFIX)) return; // prevent double-prefixing

                    const result = postcss([
                        prefixer({
                            prefix: PREFIX,
                            exclude: EXCLUDE,
                            transform(prefix, selector, prefixedSelector) {
                                if (selector.startsWith("html") || selector.startsWith("body")) {
                                    return prefix;
                                }
                                return prefixedSelector;
                            },
                        }),
                    ]).process(css, {from: undefined});

                    asset.source = result.css;
                }
            });
        },

        // --- Dev mode ---
        async transform(code, id) {
            if (!id.endsWith(".css") || !id.includes("editor")) return;
            if (code.includes(PREFIX)) return code;

            const result = await postcss([
                prefixer({
                    prefix: PREFIX,
                    exclude: EXCLUDE,
                    transform(prefix, selector, prefixedSelector) {
                        if (selector.startsWith("html") || selector.startsWith("body")) {
                            return prefix;
                        }
                        return prefixedSelector;
                    },
                }),
            ]).process(code, {from: id});

            return result.css;
        },
    };
}
