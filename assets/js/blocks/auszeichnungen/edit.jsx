// Editor for "Auszeichnungen" (parent) and "Auszeichnung" (child card), edited directly in the canvas.
// Markup mirrors the render.php files (Tailwind is loaded in the editor iframe); sidebar and
// popovers render outside the iframe.
import { createElement as el, Fragment, useEffect, useRef, useState } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    InspectorControls,
    LinkControl,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import {
    PanelBody,
    Popover,
    TextControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/auszeichnungen/block.json";
import cardMetadata from "../../../../template-parts/blocks/auszeichnung/block.json";
import {
    ACCENT_FORMATS,
    AddItemTile,
    APPENDER_GRID_CELL_CLASSNAME,
    CardInner,
    cardClassName,
    cardVariant,
    cardVariations,
    buttonPreviewClassName,
    cardButtonVariant,
    CardToolbar,
    cardTitleClassName,
    TileLayoutDropdown,
    tileGridClass,
    useInnerBlockCount,
} from "../shared.jsx";

const CHILD_BLOCK_NAME = cardMetadata.name;

// Texts stay plain strings; render.php escapes them.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

const SHAPE_OPTIONS = [
    { title: __("Rund (Siegel)", "hengegroup-theme"), value: "circle" },
    { title: __("Abgerundet (Logo)", "hengegroup-theme"), value: "rounded" },
];

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, layout } = attributes;
    const gridClass = tileGridClass(layout, useInnerBlockCount(clientId), "md:grid-cols-2");
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({
        className: "alignfull bg-neutral-200 section-spacing",
    });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-8 ${gridClass} ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [cardMetadata.name],
            template: [[cardMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={CHILD_BLOCK_NAME}
                    label={__("+ Auszeichnung hinzufügen", "hengegroup-theme")}
                    className="h-full min-h-40"
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
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <RichText
                        tagName={headingTag}
                        className="col-span-12 text-3xl leading-tight font-semibold"
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

function CardEdit({ attributes, setAttributes, isSelected }) {
    const { imageId, imageAlt, imageShape, title, text, buttonText, buttonUrl, accent } =
        attributes;
    const variant = cardVariant(cardMetadata, attributes.variant);
    const blockProps = useBlockProps({
        className: cardClassName({ variant, accent }),
    });
    const media = useSelect(
        (select) => (imageId ? select("core").getMedia(imageId) : null),
        [imageId]
    );
    const buttonRef = useRef();
    const [isEditingLink, setIsEditingLink] = useState(false);
    const shapeClassName = imageShape === "rounded" ? "rounded-xl" : "rounded-full";
    const imageUrl = media?.media_details?.sizes?.thumbnail?.source_url || media?.source_url;

    useEffect(() => {
        if (!isSelected) {
            setIsEditingLink(false);
        }
    }, [isSelected]);

    function selectImage(nextMedia) {
        setAttributes({ imageId: nextMedia.id, imageAlt: imageAlt || nextMedia.alt || "" });
    }

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <CardToolbar
                        metadata={cardMetadata}
                        variant={variant}
                        accent={accent}
                        setAttributes={setAttributes}
                    />
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={selectImage}
                            allowedTypes={["image"]}
                            value={imageId}
                            render={({ open }) => (
                                <ToolbarButton
                                    icon="format-image"
                                    label={
                                        imageId
                                            ? __("Bild ändern", "hengegroup-theme")
                                            : __("Bild auswählen", "hengegroup-theme")
                                    }
                                    onClick={open}
                                />
                            )}
                        />
                    </MediaUploadCheck>
                    {imageId > 0 && (
                        <ToolbarButton
                            icon="no-alt"
                            label={__("Bild entfernen", "hengegroup-theme")}
                            onClick={() => setAttributes({ imageId: 0 })}
                        />
                    )}
                    <ToolbarDropdownMenu
                        icon="image-crop"
                        label={__("Bildform", "hengegroup-theme")}
                        controls={SHAPE_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === imageShape,
                            onClick: () => setAttributes({ imageShape: option.value }),
                        }))}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="admin-links"
                        label={__(
                            "Link bearbeiten (ohne Button-Text: ganze Karte)",
                            "hengegroup-theme"
                        )}
                        onClick={() => setIsEditingLink(true)}
                        isPressed={isEditingLink}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Bild", "hengegroup-theme")} initialOpen>
                    <TextControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Alternativtext", "hengegroup-theme")}
                        help={__(
                            "Beschreibt das Bild für Screenreader, z. B. „Siegel Krisensicherste Unternehmen 2023“.",
                            "hengegroup-theme"
                        )}
                        value={imageAlt}
                        onChange={(value) => setAttributes({ imageAlt: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <li {...blockProps}>
                <CardInner
                    variant={variant}
                    accent={accent}
                    leading={
                        <MediaUploadCheck>
                            <MediaUpload
                                onSelect={selectImage}
                                allowedTypes={["image"]}
                                value={imageId}
                                render={({ open }) =>
                                    imageUrl ? (
                                        <button
                                            type="button"
                                            className="shrink-0 cursor-pointer border-0 bg-transparent p-0"
                                            onClick={open}
                                            aria-label={__("Bild ändern", "hengegroup-theme")}
                                        >
                                            <img
                                                src={imageUrl}
                                                alt=""
                                                className={`size-19 object-cover ${shapeClassName}`}
                                            />
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            className={`flex size-19 shrink-0 cursor-pointer items-center justify-center border-2 border-dashed border-neutral-300 bg-transparent text-sm text-neutral-500 ${shapeClassName}`}
                                            onClick={open}
                                        >
                                            {__("Bild", "hengegroup-theme")}
                                        </button>
                                    )
                                }
                            />
                        </MediaUploadCheck>
                    }
                    title={
                        <RichText
                            tagName="h3"
                            className={cardTitleClassName(variant, "body-base")}
                            value={toRichTextValue(title)}
                            onChange={(html) => setAttributes({ title: fromRichTextValue(html) })}
                            placeholder={__("Titel eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                    }
                    content={
                        <RichText
                            tagName="p"
                            className="text-base leading-normal"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                    }
                    footer={
                        <span ref={buttonRef} className="relative ml-auto">
                            <RichText
                                tagName="span"
                                className={buttonPreviewClassName(cardButtonVariant(variant))}
                                value={toRichTextValue(buttonText)}
                                onChange={(html) =>
                                    setAttributes({ buttonText: fromRichTextValue(html) })
                                }
                                placeholder={__("Button-Text…", "hengegroup-theme")}
                                allowedFormats={[]}
                                disableLineBreaks
                            />
                            {isEditingLink && isSelected && (
                                <Popover
                                    placement="bottom-end"
                                    anchor={buttonRef.current}
                                    onClose={() => setIsEditingLink(false)}
                                >
                                    <LinkControl
                                        value={{ url: buttonUrl }}
                                        onChange={(nextValue) =>
                                            setAttributes({
                                                buttonUrl: nextValue.url || "",
                                            })
                                        }
                                        onRemove={() => setAttributes({ buttonUrl: "" })}
                                        settings={[]}
                                    />
                                </Popover>
                            )}
                        </span>
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

registerBlockType(cardMetadata, {
    edit: CardEdit,
    variations: cardVariations(cardMetadata),
    save: () => null,
});
