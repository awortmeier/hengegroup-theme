// Editor for "Textabschnitt": heading/text as RichText in the canvas, heading tag, alignment, width,
// background and spacing in the toolbar. alignfull is hardcoded so the width choice is visible in the editor.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { ToolbarButton, ToolbarDropdownMenu, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    ACCENT_FORMATS,
    HeadingTagDropdown,
    SectionHeaderInner,
    sectionHeaderClassNames,
    sectionBackground,
    SectionBackgroundDropdown,
    sectionSpacingClassName,
    SectionSpacingDropdown,
    sectionSpacing,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/textabschnitt/block.json";

// "default" = .wrapper, "small" = .wrapper-small.
const CONTAINER_WIDTH_OPTIONS = [
    { title: __("Standard", "hengegroup-theme"), value: "default" },
    { title: __("Schmal", "hengegroup-theme"), value: "small" },
];

function Edit({ attributes, setAttributes }) {
    const { heading, headingTag, text, textAlign, containerWidth, spacingBottom } = attributes;
    // Legacy `spacingBottom: false` (= top only) applies while `spacing` is unset, as in render.php.
    const spacing =
        sectionSpacing(attributes.spacing) === "both" && spacingBottom === false
            ? "top"
            : sectionSpacing(attributes.spacing);
    const align = textAlign === "left" ? "start" : "center";
    const background = sectionBackground(attributes.background);
    const headerClasses = sectionHeaderClassNames({ size: "base", align, color: background.color });
    const wrapperClass = containerWidth === "default" ? "wrapper" : "wrapper-small";
    const blockProps = useBlockProps({
        className: `alignfull ${sectionSpacingClassName(spacing)} ${background.className}`,
    });

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
                    <SectionBackgroundDropdown
                        value={background.name}
                        onChange={(value) => setAttributes({ background: value })}
                    />
                    <SectionSpacingDropdown
                        value={spacing}
                        onChange={(value) => setAttributes({ spacing: value, spacingBottom: true })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className={wrapperClass}>
                    <SectionHeaderInner
                        size="base"
                        align={align}
                        color={background.color}
                        className="col-span-12"
                        title={
                            <RichText
                                tagName={headingTag}
                                className={headerClasses.title}
                                value={heading}
                                onChange={(value) => setAttributes({ heading: value })}
                                placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                                allowedFormats={ACCENT_FORMATS}
                                disableLineBreaks
                            />
                        }
                        description={
                            <RichText
                                tagName="p"
                                className={headerClasses.description}
                                value={text}
                                onChange={(value) => setAttributes({ text: value })}
                                placeholder={__("Text eingeben…", "hengegroup-theme")}
                                allowedFormats={ACCENT_FORMATS}
                                disableLineBreaks
                            />
                        }
                    />
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
