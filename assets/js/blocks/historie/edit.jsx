// Editor for "Historie" (parent) and "Historie-Eintrag" (child), edited directly in the canvas.
// Markup mirrors the render.php files.
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
    CardInner,
    cardClassName,
    cardTitleClassName,
    cardVariant,
    cardVariations,
    CardToolbar,
    fromRichTextValue,
    HeadingTagDropdown,
    toRichTextValue,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/historie/block.json";
import entryMetadata from "../../../../template-parts/blocks/historie-eintrag/block.json";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag } = attributes;
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({ className: "alignfull section-spacing" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className:
                "relative flex flex-col gap-5 before:absolute before:inset-y-2 before:left-[calc(5.5rem-1px)] before:w-0.5 before:bg-linear-to-b before:brand-gradient md:before:left-[calc(8.25rem-1px)]",
        },
        {
            allowedBlocks: [entryMetadata.name],
            template: [[entryMetadata.name]],
            renderAppender: () => (
                <li className="grid grid-cols-[4.5rem_2rem_minmax(0,1fr)] md:grid-cols-[6.875rem_2.75rem_minmax(0,1fr)]">
                    <AddItemTile
                        rootClientId={clientId}
                        blockName={entryMetadata.name}
                        label={__("+ Eintrag hinzufügen", "hengegroup-theme")}
                        className="col-start-3 min-h-16"
                    />
                </li>
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
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper">
                    <div className="col-span-12 xl:col-span-10 xl:col-start-2">
                        <RichText
                            tagName={headingTag}
                            className="mb-12 text-4xl leading-tight font-semibold"
                            value={heading}
                            onChange={(html) => setAttributes({ heading: html })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
                            disableLineBreaks
                        />
                        <ol {...innerBlocksProps} />
                    </div>
                </div>
            </section>
        </Fragment>
    );
}

function EntryEdit({ attributes, setAttributes }) {
    const { year, title, text, accent } = attributes;
    const variant = cardVariant(entryMetadata, attributes.variant);
    const blockProps = useBlockProps({
        className:
            "grid grid-cols-[4.5rem_2rem_minmax(0,1fr)] items-start md:grid-cols-[6.875rem_2.75rem_minmax(0,1fr)]",
    });

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <CardToolbar
                        metadata={entryMetadata}
                        variant={variant}
                        accent={accent}
                        setAttributes={setAttributes}
                    />
                    <AccentDropdown
                        value={accent}
                        onChange={(value) => setAttributes({ accent: value })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <li {...blockProps}>
                <RichText
                    tagName="span"
                    className="font-accent block pt-5 text-right text-xl md:text-[1.625rem]"
                    value={toRichTextValue(year)}
                    onChange={(html) => setAttributes({ year: fromRichTextValue(html) })}
                    placeholder={__("Jahr", "hengegroup-theme")}
                    allowedFormats={[]}
                    disableLineBreaks
                />
                <span className="flex justify-center pt-7.5" aria-hidden="true">
                    <span
                        className={`border-grey-light relative z-1 size-3.5 rounded-full border-3 ${accentClasses(accent).bg}`}
                    />
                </span>
                <div className={cardClassName({ variant })}>
                    <CardInner
                        variant={variant}
                        title={
                            <RichText
                                tagName="h3"
                                className={cardTitleClassName(variant, "body-base")}
                                value={toRichTextValue(title)}
                                onChange={(html) =>
                                    setAttributes({ title: fromRichTextValue(html) })
                                }
                                placeholder={__("Titel eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                                disableLineBreaks
                            />
                        }
                        content={
                            <RichText
                                tagName="p"
                                className="text-base leading-normal text-pretty"
                                value={toRichTextValue(text)}
                                onChange={(html) =>
                                    setAttributes({ text: fromRichTextValue(html) })
                                }
                                placeholder={__("Text eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                            />
                        }
                    />
                </div>
            </li>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamic block; only the inner blocks are saved.
    save: () => <InnerBlocks.Content />,
});

registerBlockType(entryMetadata, {
    edit: EntryEdit,
    variations: cardVariations(entryMetadata),
    save: () => null,
});
