// Editor for "Ansprechpartner" (parent) and its person cards, edited directly in the canvas. Markup
// mirrors contact-card.php (lower = contact, optional portrait on the left); the accent reaches the
// cards via block context.
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
    CardInner,
    cardClassName,
    cardDescriptionClassName,
    cardTitleClassName,
    cardVariant,
    cardVariations,
    CardToolbar,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    MediaPicker,
    MediaToolbarButtons,
    toRichTextValue,
    useMediaUrl,
    TileLayoutDropdown,
    tileGridClass,
    useInnerBlockCount,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/ansprechpartner/block.json";
import personMetadata from "../../../../template-parts/blocks/ansprechpartner-person/block.json";

const CONTACT_FIELDS = [
    ["email", __("E-Mail eingeben…", "hengegroup-theme")],
    ["phone", __("Telefon eingeben…", "hengegroup-theme")],
    ["fax", __("Fax eingeben…", "hengegroup-theme")],
];

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, accent, layout } = attributes;
    const isFull = layout === "full";
    const gridClass = `${tileGridClass(isFull ? "full" : "grid-4", useInnerBlockCount(clientId))} ${
        isFull ? "" : "lg:has-[[data-slot=card-media]]:grid-cols-2"
    }`;
    const blockProps = useBlockProps({ className: "alignfull bg-neutral-200 section-spacing" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-6 ${gridClass} ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [personMetadata.name],
            template: [[personMetadata.name], [personMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={personMetadata.name}
                    label={__("+ Ansprechpartner hinzufügen", "hengegroup-theme")}
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

function PersonEdit({ attributes, setAttributes, context }) {
    const accent = context["hengegroup-theme/ansprechpartner-accent"];
    const variant = cardVariant(personMetadata, attributes.variant);
    const { imageId } = attributes;
    const imageUrl = useMediaUrl(imageId);
    const selectImage = (media) => setAttributes({ imageId: media.id });
    const blockProps = useBlockProps({
        className: `h-full ${cardClassName({ variant, accent, horizontal: true, hasMedia: Boolean(imageUrl) })}`,
    });
    const plainText = (key, tagName, className, placeholder) => (
        <RichText
            tagName={tagName}
            className={className}
            value={toRichTextValue(attributes[key])}
            onChange={(html) => setAttributes({ [key]: fromRichTextValue(html) })}
            placeholder={placeholder}
            allowedFormats={[]}
            disableLineBreaks
        />
    );
    const rows = (
        <div className="flex flex-col gap-1">
            {CONTACT_FIELDS.map(([key, placeholder]) => (
                <Fragment key={key}>
                    {plainText(key, "p", "text-base leading-normal", placeholder)}
                </Fragment>
            ))}
        </div>
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <CardToolbar
                        metadata={personMetadata}
                        variant={variant}
                        accent={accent}
                        setAttributes={setAttributes}
                    />
                    <MediaToolbarButtons
                        value={imageId}
                        onSelect={selectImage}
                        onRemove={() => setAttributes({ imageId: 0 })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <li {...blockProps}>
                {!imageUrl && (
                    <MediaPicker value={imageId} onSelect={selectImage}>
                        {(open) => (
                            <button
                                type="button"
                                className="flex w-full cursor-pointer items-center justify-center border-0 bg-neutral-200 py-3 text-sm font-semibold text-neutral-600"
                                onClick={open}
                            >
                                {__("+ Porträt (optional)", "hengegroup-theme")}
                            </button>
                        )}
                    </MediaPicker>
                )}
                <CardInner
                    variant={variant}
                    accent={accent}
                    horizontal
                    media={
                        imageUrl && (
                            <img
                                src={imageUrl}
                                alt=""
                                className="absolute inset-0 size-full object-cover object-top"
                            />
                        )
                    }
                    title={plainText(
                        "name",
                        "h3",
                        cardTitleClassName(variant, "body-base"),
                        __("Name eingeben…", "hengegroup-theme")
                    )}
                    description={plainText(
                        "role",
                        "p",
                        cardDescriptionClassName(variant),
                        __("Funktion eingeben…", "hengegroup-theme")
                    )}
                    content={variant === "split" ? null : rows}
                    footer={variant === "split" ? rows : null}
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

registerBlockType(personMetadata, {
    edit: PersonEdit,
    variations: cardVariations(personMetadata),
    save: () => null,
});
