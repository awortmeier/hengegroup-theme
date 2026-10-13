// Editor for "Textabschnitt": heading/text as RichText in the canvas, heading tag, alignment and
// width in the toolbar. alignfull is hardcoded so the width choice is visible in the editor.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { ToolbarButton, ToolbarDropdownMenu, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { ACCENT_FORMATS, HeadingTagDropdown } from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/textabschnitt/block.json";

// "default" = .wrapper, "small" = .wrapper-small.
const CONTAINER_WIDTH_OPTIONS = [
    { title: __("Standard", "hengegroup-theme"), value: "default" },
    { title: __("Schmal", "hengegroup-theme"), value: "small" },
];

function Edit({ attributes, setAttributes }) {
    const { heading, headingTag, text, textAlign, containerWidth } = attributes;
    const alignClass = textAlign === "left" ? "text-left" : "text-center";
    const wrapperClass = containerWidth === "default" ? "wrapper" : "wrapper-small";
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-25 lg:py-35" });
    // Array + join: prettier-plugin-tailwindcss would eat the leading space in a template literal.
    const headingClassName = ["text-5xl font-semibold leading-tight", text !== "" ? "mb-7" : ""]
        .filter(Boolean)
        .join(" ");

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <HeadingTagDropdown
                        value={headingTag}
                        onChange={(value) => setAttributes({ headingTag: value })}
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
            <section {...blockProps}>
                <div className={wrapperClass}>
                    <div className={`col-span-12 ${alignClass}`}>
                        <RichText
                            tagName={headingTag}
                            className={headingClassName}
                            value={heading}
                            onChange={(value) => setAttributes({ heading: value })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-2xl leading-normal"
                            value={text}
                            onChange={(value) => setAttributes({ text: value })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
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
