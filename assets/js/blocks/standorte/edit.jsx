// Editor for "Standorte" (parent) and its location cards, edited directly in the canvas. Markup
// mirrors contact-card.php (lower = hours); the accent reaches the cards via block context.
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
    AccentDropdown,
    AddItemTile,
    CARD_SUBHEADING_CLASSNAME,
    CardInner,
    cardClassName,
    cardTitleClassName,
    cardVariant,
    cardVariations,
    CardToolbar,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    toRichTextValue,
    TileLayoutDropdown,
    tileGridClass,
    useInnerBlockCount,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/standorte/block.json";
import locationMetadata from "../../../../template-parts/blocks/standort/block.json";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, accent, layout } = attributes;
    const gridClass = tileGridClass(
        layout === "full" ? "full" : "grid-4",
        useInnerBlockCount(clientId)
    );
    const blockProps = useBlockProps({ className: "alignfull section-spacing" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-6 ${gridClass} ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [locationMetadata.name],
            template: [[locationMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={locationMetadata.name}
                    label={__("+ Standort hinzufügen", "hengegroup-theme")}
                    className="h-full min-h-48"
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
                    <AccentDropdown
                        value={accent}
                        onChange={(value) => setAttributes({ accent: value })}
                    />
                    <TileLayoutDropdown
                        value={layout}
                        onChange={(value) => setAttributes({ layout: value })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <RichText
                        tagName={headingTag}
                        className="col-span-12 text-4xl leading-tight font-semibold"
                        value={heading}
                        onChange={(html) => setAttributes({ heading: html })}
                        placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                        allowedFormats={ACCENT_FORMATS}
                        disableLineBreaks
                    />
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function LocationEdit({ attributes, setAttributes, context }) {
    const accent = context["hengegroup-theme/standorte-accent"];
    const variant = cardVariant(locationMetadata, attributes.variant);
    const blockProps = useBlockProps({
        className: `h-full ${cardClassName({ variant, accent })}`,
    });
    // Without disableLineBreaks, Enter inserts <br>, which the plain text keeps as "\n".
    const plainText = (key, tagName, className, placeholder, multiline = false) => (
        <RichText
            tagName={tagName}
            className={className}
            value={toRichTextValue(attributes[key])}
            onChange={(html) => setAttributes({ [key]: fromRichTextValue(html) })}
            placeholder={placeholder}
            allowedFormats={[]}
            disableLineBreaks={!multiline}
        />
    );
    const hours = (
        <Fragment>
            {plainText(
                "hoursLabel",
                "p",
                CARD_SUBHEADING_CLASSNAME,
                __("Bezeichnung, z. B. Ladezeiten…", "hengegroup-theme")
            )}
            {plainText(
                "hours",
                "p",
                "text-base leading-normal",
                __("Zeiten eingeben (Enter = neue Zeile)…", "hengegroup-theme"),
                true
            )}
        </Fragment>
    );
    const address = plainText(
        "address",
        "p",
        "text-base leading-normal",
        __("Adresse eingeben (Enter = neue Zeile)…", "hengegroup-theme"),
        true
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <CardToolbar
                        metadata={locationMetadata}
                        variant={variant}
                        accent={accent}
                        setAttributes={setAttributes}
                    />
                </ToolbarGroup>
            </BlockControls>
            <li {...blockProps}>
                <CardInner
                    variant={variant}
                    accent={accent}
                    title={plainText(
                        "title",
                        "h3",
                        cardTitleClassName(variant, "body-base"),
                        __("Standort eingeben…", "hengegroup-theme")
                    )}
                    content={
                        <Fragment>
                            {address}
                            {variant !== "split" && hours}
                        </Fragment>
                    }
                    footer={variant === "split" ? hours : null}
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

registerBlockType(locationMetadata, {
    edit: LocationEdit,
    variations: cardVariations(locationMetadata),
    save: () => null,
});
