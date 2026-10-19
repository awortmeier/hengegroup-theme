// Editor for "Kennzahlen" (parent) and "Kennzahl" (child), edited directly in the canvas. Markup
// mirrors the render.php files.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import { ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    ACCENT_FORMATS,
    accentClasses,
    AccentDropdown,
    AddItemTile,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    toRichTextValue,
    SectionHeaderInner,
    sectionHeaderClassNames,
    sectionBackground,
    SectionBackgroundDropdown,
    sectionSpacingClassName,
    SectionSpacingDropdown,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/kennzahlen/block.json";
import itemMetadata from "../../../../template-parts/blocks/kennzahl/block.json";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag } = attributes;
    const background = sectionBackground(attributes.background);
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({
        className: `alignfull ${sectionSpacingClassName(attributes.spacing)} ${background.className}`,
    });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-6 ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [itemMetadata.name],
            template: [[itemMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={itemMetadata.name}
                    label={__("+ Kennzahl", "hengegroup-theme")}
                    tone={background.tone}
                    className="min-h-24"
                />
            ),
        }
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <HeadingTagDropdown
                        value={headingTag}
                        onChange={(value) => setAttributes({ headingTag: value })}
                    />
                    <SectionBackgroundDropdown
                        value={background.name}
                        onChange={(value) => setAttributes({ background: value })}
                    />
                    <SectionSpacingDropdown
                        value={attributes.spacing}
                        onChange={(value) => setAttributes({ spacing: value })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <SectionHeaderInner
                        className="col-span-12"
                        color={background.color}
                        title={
                            <RichText
                                tagName={headingTag}
                                className={
                                    sectionHeaderClassNames({ color: background.color }).title
                                }
                                value={heading}
                                onChange={(html) => setAttributes({ heading: html })}
                                placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                                allowedFormats={ACCENT_FORMATS}
                                disableLineBreaks
                            />
                        }
                    />
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function ItemEdit({ attributes, setAttributes }) {
    const { value, label, accent } = attributes;
    const blockProps = useBlockProps({
        className: `border-t-2 pt-5 ${accentClasses(accent).border}`,
    });

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <AccentDropdown
                        value={accent}
                        onChange={(next) => setAttributes({ accent: next })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <li {...blockProps}>
                <RichText
                    tagName="p"
                    className="text-5xl leading-none font-semibold tabular-nums"
                    value={toRichTextValue(value)}
                    onChange={(html) => setAttributes({ value: fromRichTextValue(html) })}
                    placeholder={__("Wert", "hengegroup-theme")}
                    allowedFormats={[]}
                    disableLineBreaks
                />
                <RichText
                    tagName="p"
                    className="mt-2.5 text-base leading-normal"
                    value={toRichTextValue(label)}
                    onChange={(html) => setAttributes({ label: fromRichTextValue(html) })}
                    placeholder={__("Beschriftung eingeben…", "hengegroup-theme")}
                    allowedFormats={[]}
                />
            </li>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamic block; only the inner blocks are saved.
    save: () => <InnerBlocks.Content />,
});

registerBlockType(itemMetadata, {
    edit: ItemEdit,
    save: () => null,
});
