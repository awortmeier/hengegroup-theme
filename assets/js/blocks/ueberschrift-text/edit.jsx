// Editor for "Ueberschrift & Text": heading/text as RichText in the canvas; heading tag, alignment
// and width in the toolbar, accent words in the sidebar. alignfull is hardcoded so the width choice
// is visible in the editor.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import {
    Notice,
    PanelBody,
    TextControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/ueberschrift-text/block.json";

const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

// "default" = .wrapper, "small" = .wrapper-small.
const CONTAINER_WIDTH_OPTIONS = [
    { title: __("Standard", "hengegroup-theme"), value: "default" },
    { title: __("Schmal", "hengegroup-theme"), value: "small" },
];

function parseAccentWords(value) {
    return value
        .split(",")
        .map((word) => word.trim())
        .filter((word) => word !== "");
}

// Accent words only wrap words already in the heading; warn about ones that don't occur.
function findUnmatchedAccentWords(heading, accentWords) {
    return accentWords.filter((word) => !heading.includes(word));
}

// Texts stay plain strings (render.php escapes them); create()/toHTMLString() only handles entity
// escaping inside RichText. allowedFormats={[]} blocks formatting.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

function Edit({ attributes, setAttributes }) {
    const { heading, headingTag, accentWords, text, textAlign, containerWidth } = attributes;
    const unmatchedAccentWords = findUnmatchedAccentWords(heading, accentWords);
    const alignClass = textAlign === "left" ? "text-left" : "text-center";
    const wrapperClass = containerWidth === "default" ? "wrapper" : "wrapper-small";
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-24 lg:py-35" });
    // Array + join: prettier-plugin-tailwindcss would eat the leading space in a template literal.
    const headingClassName = ["text-5xl font-semibold leading-tight", text !== "" ? "mb-7" : ""]
        .filter(Boolean)
        .join(" ");

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="heading"
                        label={__("Überschrift-Element ändern", "hengegroup-theme")}
                        controls={HEADING_TAG_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === headingTag,
                            onClick: () => setAttributes({ headingTag: option.value }),
                        }))}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="editor-alignleft"
                        label={__("Linksbündig", "hengegroup-theme")}
                        isPressed={textAlign === "left"}
                        onClick={() => setAttributes({ textAlign: "left" })}
                    />
                    <ToolbarButton
                        icon="editor-aligncenter"
                        label={__("Zentriert", "hengegroup-theme")}
                        isPressed={textAlign === "center"}
                        onClick={() => setAttributes({ textAlign: "center" })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="editor-expand"
                        label={__("Breite ändern", "hengegroup-theme")}
                        controls={CONTAINER_WIDTH_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === containerWidth,
                            onClick: () => setAttributes({ containerWidth: option.value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Einstellungen", "hengegroup-theme")} initialOpen>
                    <TextControl
                        label={__("Akzent-Wörter", "hengegroup-theme")}
                        help={__(
                            "Kommagetrennt — hebt diese Wörter in der Akzent-Schrift hervor. Muss WÖRTLICH oben in der Überschrift vorkommen, wird nicht ergänzt.",
                            "hengegroup-theme"
                        )}
                        value={accentWords.join(", ")}
                        onChange={(value) =>
                            setAttributes({ accentWords: parseAccentWords(value) })
                        }
                    />
                    {unmatchedAccentWords.length > 0 && (
                        <Notice status="warning" isDismissible={false}>
                            {sprintf(
                                __(
                                    "Kommt nicht in der Überschrift vor und wird deshalb nicht angezeigt: %s",
                                    "hengegroup-theme"
                                ),
                                unmatchedAccentWords.join(", ")
                            )}
                        </Notice>
                    )}
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className={wrapperClass}>
                    <div className={`col-span-12 ${alignClass}`}>
                        <RichText
                            tagName={headingTag}
                            className={headingClassName}
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-2xl leading-normal"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                    </div>
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
