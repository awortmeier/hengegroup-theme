// Editor for "Personen" (parent) and "Person" (child card), edited directly in the canvas. Markup
// mirrors the render.php files (card.php via CardInner).
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
import metadata from "../../../../template-parts/blocks/personen/block.json";
import personMetadata from "../../../../template-parts/blocks/person/block.json";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, layout } = attributes;
    const gridClass = tileGridClass(
        layout,
        useInnerBlockCount(clientId),
        "mx-auto w-full max-w-225 md:grid-cols-2"
    );
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({ className: "alignfull section-spacing" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-8 ${gridClass} ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [personMetadata.name],
            template: [[personMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={personMetadata.name}
                    label={__("+ Person hinzufügen", "hengegroup-theme")}
                    className="h-full min-h-60"
                />
            ),
        }
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <TileLayoutDropdown
                        value={layout}
                        onChange={(value) => setAttributes({ layout: value })}
                        withDefault
                    />
                    <HeadingTagDropdown
                        value={headingTag}
                        onChange={(value) => setAttributes({ headingTag: value })}
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

function PersonEdit({ attributes, setAttributes }) {
    const { imageId, name, role, accent } = attributes;
    const variant = cardVariant(personMetadata, attributes.variant);
    const blockProps = useBlockProps({
        className: cardClassName({ variant, accent, hasMedia: true }),
    });
    const imageUrl = useMediaUrl(imageId);
    const selectImage = (media) => setAttributes({ imageId: media.id });

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
                <CardInner
                    variant={variant}
                    accent={accent}
                    media={
                        imageUrl ? (
                            <img src={imageUrl} alt="" className="aspect-4/3 w-full object-cover" />
                        ) : (
                            <MediaPicker value={imageId} onSelect={selectImage}>
                                {(open) => (
                                    <button
                                        type="button"
                                        className="flex aspect-4/3 w-full cursor-pointer items-center justify-center border-0 bg-neutral-200 text-base font-semibold text-neutral-600"
                                        onClick={open}
                                    >
                                        {__("Porträt auswählen", "hengegroup-theme")}
                                    </button>
                                )}
                            </MediaPicker>
                        )
                    }
                    title={
                        <RichText
                            tagName="h3"
                            className={cardTitleClassName(variant, "body-base")}
                            value={toRichTextValue(name)}
                            onChange={(html) => setAttributes({ name: fromRichTextValue(html) })}
                            placeholder={__("Name eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                    }
                    description={
                        <RichText
                            tagName="p"
                            className={cardDescriptionClassName(variant)}
                            value={toRichTextValue(role)}
                            onChange={(html) => setAttributes({ role: fromRichTextValue(html) })}
                            placeholder={__("Funktion eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                    }
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
